package scraper

import (
	"context"
	"database/sql"
	"fmt"
	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/database"
	"linkedin-job-scraper/internal/models"
	"os"
	"strconv"
	"strings"
	"time"

	"github.com/chromedp/cdproto/runtime"
	"github.com/chromedp/chromedp"
)

type LinkedInScraper struct {
	config     *config.Config
	db         *database.DB
	companyRepo *database.CompanyRepository
	jobRepo     *database.JobPostingRepository
}

// NewLinkedInScraper creates a new LinkedIn scraper
func NewLinkedInScraper(cfg *config.Config, db *database.DB) *LinkedInScraper {
	return &LinkedInScraper{
		config:      cfg,
		db:          db,
		companyRepo: database.NewCompanyRepository(db),
		jobRepo:     database.NewJobPostingRepository(db),
	}
}

// ScrapeJobs scrapes LinkedIn jobs based on search parameters
func (s *LinkedInScraper) ScrapeJobs(keywords, location string, totalJobs int) error {
	fmt.Println("🚀 Starting LinkedIn job scraper...")
	
	// Setup Chrome options with better error handling
	opts := append(chromedp.DefaultExecAllocatorOptions[:],
		chromedp.ExecPath(s.config.Scraper.ChromeExecutablePath), // Use configurable Chrome path
		chromedp.Flag("headless", s.config.Scraper.HeadlessBrowser),
		chromedp.Flag("disable-gpu", true),
		chromedp.Flag("no-sandbox", true),
		chromedp.Flag("disable-dev-shm-usage", true),
		chromedp.Flag("disable-web-security", true),
		chromedp.Flag("disable-features", "VizDisplayCompositor"),
		chromedp.Flag("disable-extensions", true),
		chromedp.Flag("disable-plugins", true),
		chromedp.Flag("disable-images", true), // Speed up loading
		chromedp.UserDataDir(s.config.Scraper.UserDataDir),
	)

	allocCtx, cancel := chromedp.NewExecAllocator(context.Background(), opts...)
	defer cancel()

	// Add timeout context
	ctx, cancel := chromedp.NewContext(allocCtx, chromedp.WithLogf(func(s string, args ...interface{}) {
		// Suppress cookie parsing errors - they're not critical
		if !strings.Contains(s, "cookiePart") && !strings.Contains(s, "could not unmarshal event") {
			fmt.Printf("ChromeDP: "+s+"\n", args...)
		}
	}))
	defer cancel()

	// Enable console logging from JavaScript only if DEBUG_SCRAPER is enabled
	debugScraper := strings.ToLower(os.Getenv("DEBUG_SCRAPER")) == "true"
	chromedp.ListenTarget(ctx, func(ev interface{}) {
		// Skip logging if debugging is disabled
		if !debugScraper {
			return
		}
		
		switch ev := ev.(type) {
		case *runtime.EventConsoleAPICalled:
			args := make([]string, len(ev.Args))
			for i, arg := range ev.Args {
				if arg.Value != nil {
					args[i] = string(arg.Value)
				} else {
					args[i] = "null"
				}
			}
			// Log console messages only when debugging is enabled
			message := strings.Join(args, " ")
			fmt.Printf("JS: %s\n", message)
		}
	})

	// Login to LinkedIn
	if err := s.login(ctx); err != nil {
		return fmt.Errorf("login failed: %w", err)
	}
	fmt.Println("✅ Ready to scrape!")

	// Dynamic pagination based on job URLs FOUND on LinkedIn (not jobs saved to DB)
	totalJobUrlsFound := 0   // Total job URLs LinkedIn has shown us (for pagination)
	totalJobsSaved := 0      // Total jobs actually saved to database
	page := 1
	const maxPages = 1000 // Safety limit to prevent infinite loops
	
	fmt.Printf("🎯 Target: %d jobs | Keywords: %s | Location: %s\n", totalJobs, keywords, location)
	
	// Create unified progress tracker
	progress := NewOverallScrapingProgress(totalJobs)

	for page <= maxPages && totalJobsSaved < totalJobs {
		// Use LinkedIn's pagination: start from total job URLs we've seen
		start := totalJobUrlsFound
		pageURL := s.buildSearchURL(keywords, location, start)
		
		// Scrape page and get result info
		pageResult, err := s.scrapePageWithDetails(ctx, pageURL, 25, progress)
		if err != nil {
			progress.AddFailed(1)
			break
		}
		
		if pageResult.TotalJobsFound == 0 {
			break
		}
		
		// Update totals
		totalJobUrlsFound += pageResult.TotalJobsFound
		totalJobsSaved += pageResult.JobsSaved
		
		// Update page number in progress tracker
		progress.UpdatePage(page, 0, 0, 0) // Just update page number, individual jobs already tracked
		
		// If we've reached our target, stop
		if totalJobsSaved >= totalJobs {
			break
		}
		
		page++
	}

	progress.Finish()
	return nil
}

// PageResult holds information about a scraped page
type PageResult struct {
    TotalJobsFound int // Number of job URLs found on the page
    JobsSaved      int // Number of jobs actually saved to database  
    JobsSkipped    int // Number of jobs skipped (already exist)
}

// scrapePageWithDetails scrapes a page and returns detailed results
func (s *LinkedInScraper) scrapePageWithDetails(ctx context.Context, pageURL string, maxJobs int, progress *OverallScrapingProgress) (*PageResult, error) {
	// Navigate to the page
	err := chromedp.Run(ctx, chromedp.Navigate(pageURL))
	if err != nil {
		return nil, fmt.Errorf("failed to navigate to page: %w", err)
	}

	// Extract job URLs using existing function
	jobURLs, err := s.extractJobURLs(ctx)
	if err != nil {
		return nil, fmt.Errorf("failed to extract job URLs: %w", err)
	}
	
	// Filter new vs existing jobs
	newJobURLs, skippedCount := s.filterNewJobs(jobURLs)
	
	// Update progress for skipped jobs
	for i := 0; i < skippedCount; i++ {
		progress.UpdateJob(false, true) // Skipped job
	}
	
	result := &PageResult{
		TotalJobsFound: len(jobURLs),
		JobsSkipped:    skippedCount,
		JobsSaved:      0,
	}
	
	if len(newJobURLs) == 0 {
		return result, nil
	}
	
	// Process new jobs with progress tracking
	result.JobsSaved = s.processNewJobs(ctx, newJobURLs, progress)
	
	return result, nil
}

// filterNewJobs separates new jobs from existing ones
func (s *LinkedInScraper) filterNewJobs(jobURLs []string) ([]string, int) {
	newJobURLs := []string{}
	skippedCount := 0
	
	for _, jobURL := range jobURLs {
		if s.isJobNew(jobURL) {
			newJobURLs = append(newJobURLs, jobURL)
		} else {
			skippedCount++
		}
	}
	
	return newJobURLs, skippedCount
}

// isJobNew checks if a job is new (not in database)
func (s *LinkedInScraper) isJobNew(jobURL string) bool {
	jobID := s.extractJobIDFromURL(jobURL)
	if jobID == "" {
		fmt.Printf("⚠️  Could not extract job ID from URL: %s\n", jobURL)
		return false
	}
	
	jobIDInt, err := strconv.ParseInt(jobID, 10, 64)
	if err != nil {
		fmt.Printf("⚠️  Invalid job ID '%s': %v\n", jobID, err)
		return false
	}
	
	exists, err := s.jobRepo.ExistsLinkedInJobID(jobIDInt)
	if err != nil {
		fmt.Printf("⚠️  Error checking if job exists: %v\n", err)
		return false
	}
	
	return !exists
}

// processNewJobs scrapes and saves new jobs with individual progress updates
func (s *LinkedInScraper) processNewJobs(ctx context.Context, jobURLs []string, progress *OverallScrapingProgress) int {
	if len(jobURLs) == 0 {
		return 0
	}
	
	savedCount := 0
	
	for _, jobURL := range jobURLs {
		job, err := s.scrapeJobDetails(ctx, jobURL)
		if err != nil {
			progress.UpdateJob(false, false) // Failed job
			continue
		}
		
		if err := s.saveJob(job); err != nil {
			progress.UpdateJob(false, false) // Failed to save
			continue
		}
		
		savedCount++
		progress.UpdateJob(true, false) // Successfully saved
	}
	
	return savedCount
}

// RescrapeFromQueue scrapes jobs from the database queue instead of LinkedIn search
func (s *LinkedInScraper) RescrapeFromQueue(limit int) error {
	fmt.Println("🚀 Initializing Chrome browser...")
	
	// Setup Chrome options with better error handling
	opts := append(chromedp.DefaultExecAllocatorOptions[:],
		chromedp.ExecPath(s.config.Scraper.ChromeExecutablePath),
		chromedp.Flag("headless", s.config.Scraper.HeadlessBrowser),
		chromedp.Flag("disable-gpu", true),
		chromedp.Flag("no-sandbox", true),
		chromedp.Flag("disable-dev-shm-usage", true),
		chromedp.Flag("disable-web-security", true),
		chromedp.Flag("disable-features", "VizDisplayCompositor"),
		chromedp.Flag("disable-extensions", true),
		chromedp.Flag("disable-plugins", true),
		chromedp.Flag("disable-images", true), // Speed up loading
		chromedp.UserDataDir(s.config.Scraper.UserDataDir),
	)

	allocCtx, cancel := chromedp.NewExecAllocator(context.Background(), opts...)
	defer cancel()

	// Add timeout context
	ctx, cancel := chromedp.NewContext(allocCtx, chromedp.WithLogf(func(s string, args ...interface{}) {
		// Suppress cookie parsing errors - they're not critical
		if !strings.Contains(s, "cookiePart") && !strings.Contains(s, "could not unmarshal event") {
			fmt.Printf("ChromeDP: "+s+"\n", args...)
		}
	}))
	defer cancel()

	// Enable console logging from JavaScript
	chromedp.ListenTarget(ctx, func(ev interface{}) {
		switch ev := ev.(type) {
		case *runtime.EventConsoleAPICalled:
			args := make([]string, len(ev.Args))
			for i, arg := range ev.Args {
				if arg.Value != nil {
					args[i] = string(arg.Value)
				} else {
					args[i] = "null"
				}
			}
			// Log ALL console messages for debugging
			message := strings.Join(args, " ")
			fmt.Printf("JS: %s\n", message)
		}
	})

	// Login to LinkedIn
	fmt.Println("🔐 Attempting to login to LinkedIn...")
	if err := s.login(ctx); err != nil {
		return fmt.Errorf("login failed: %w", err)
	}
	fmt.Println("✅ Login successful!")

	// Get jobs from database queue instead of LinkedIn search
	jobsToRescrape, err := s.getJobsFromQueue(limit)
	if err != nil {
		return fmt.Errorf("failed to get jobs from queue: %w", err)
	}

	if len(jobsToRescrape) == 0 {
		fmt.Println("✅ No jobs found in queue to rescrape")
		return nil
	}

	// Process jobs from queue
	successCount := 0
	failCount := 0
	
	fmt.Printf("🔍 Rescaping %d jobs from queue...\n", len(jobsToRescrape))

	for _, job := range jobsToRescrape {
		// Use existing scrapeJobDetails method
		jobPosting, err := s.scrapeJobDetails(ctx, job.ApplyURL)
		if err != nil {
			failCount++
			continue
		}
		
		// Update existing job in database
		if err := s.updateExistingJob(job.JobID, jobPosting); err != nil {
			failCount++
			continue
		}
		
		successCount++
		
		// Show simple progress
		fmt.Printf("\rProgress: %d/%d completed", successCount+failCount, len(jobsToRescrape))
	}
	
	fmt.Printf("\n✅ Rescraping complete! Success: %d | Failed: %d\n", successCount, failCount)
	return nil
}

type QueueJob struct {
	JobID    int
	ApplyURL string
	Title    string
	Company  string
}

// getJobsFromQueue retrieves jobs from the database queue
func (s *LinkedInScraper) getJobsFromQueue(limit int) ([]QueueJob, error) {
	var query string
	var rows *sql.Rows
	var err error

	if limit == 0 {
		// Get all jobs with empty descriptions
		query = `
			SELECT j.job_id, j.apply_url, j.title, COALESCE(c.name, 'Unknown') as company_name
			FROM job_postings j
			LEFT JOIN companies c ON j.company_id = c.company_id
			WHERE (j.description IS NULL OR j.description = '')
			AND j.apply_url IS NOT NULL 
			AND j.apply_url != ''
			ORDER BY j.created_at DESC
		`
		rows, err = s.db.Query(query)
	} else {
		// Get limited number of jobs
		query = `
			SELECT j.job_id, j.apply_url, j.title, COALESCE(c.name, 'Unknown') as company_name
			FROM job_postings j
			LEFT JOIN companies c ON j.company_id = c.company_id
			WHERE (j.description IS NULL OR j.description = '')
			AND j.apply_url IS NOT NULL 
			AND j.apply_url != ''
			ORDER BY j.created_at DESC
			LIMIT ?
		`
		rows, err = s.db.Query(query, limit)
	}

	if err != nil {
		return nil, fmt.Errorf("failed to query jobs from queue: %w", err)
	}
	defer rows.Close()

	var jobs []QueueJob
	for rows.Next() {
		var job QueueJob
		err := rows.Scan(&job.JobID, &job.ApplyURL, &job.Title, &job.Company)
		if err != nil {
			fmt.Printf("⚠️ Error scanning job: %v\n", err)
			continue
		}
		jobs = append(jobs, job)
	}

	return jobs, nil
}

// updateExistingJob updates an existing job with new scraped information
func (s *LinkedInScraper) updateExistingJob(jobID int, jobPosting *models.JobPosting) error {
	// Build update query for non-empty fields
	updates := []string{}
	args := []interface{}{}

	if jobPosting.Description != "" {
		updates = append(updates, "description = ?")
		args = append(args, jobPosting.Description)
	}
	
	if jobPosting.Applicants != nil {
		updates = append(updates, "applicants = ?")
		args = append(args, *jobPosting.Applicants)
	}
	
	if jobPosting.WorkType != nil && *jobPosting.WorkType != "" {
		updates = append(updates, "work_type = ?")
		args = append(args, *jobPosting.WorkType)
	}
	
	if jobPosting.Skills != nil && len(*jobPosting.Skills) > 0 {
		updates = append(updates, "skills = ?")
		args = append(args, *jobPosting.Skills)
	}

	if len(updates) == 0 {
		fmt.Printf("⚠️ No new information to update for job %d\n", jobID)
		return nil
	}

	// Add updated_at timestamp
	updates = append(updates, "updated_at = NOW()")
	
	// Add job ID for WHERE clause
	args = append(args, jobID)

	query := fmt.Sprintf("UPDATE job_postings SET %s WHERE job_id = ?", strings.Join(updates, ", "))
	
	_, err := s.db.Exec(query, args...)
	if err != nil {
		return fmt.Errorf("failed to update job: %w", err)
	}

	fmt.Printf("✅ Updated job %d with new information\n", jobID)
	return nil
}

// CheckJobClosureStatus checks if jobs are still open by visiting their apply_url
func (s *LinkedInScraper) CheckJobClosureStatus(limit int) error {
	fmt.Println("🚀 Initializing Chrome browser for job status checking...")
	
	// Setup Chrome options (same as RescrapeFromQueue)
	opts := append(chromedp.DefaultExecAllocatorOptions[:],
		chromedp.ExecPath(s.config.Scraper.ChromeExecutablePath),
		chromedp.Flag("headless", s.config.Scraper.HeadlessBrowser),
		chromedp.Flag("disable-gpu", true),
		chromedp.Flag("no-sandbox", true),
		chromedp.Flag("disable-dev-shm-usage", true),
		chromedp.Flag("disable-web-security", true),
		chromedp.Flag("disable-features", "VizDisplayCompositor"),
		chromedp.Flag("disable-extensions", true),
		chromedp.Flag("disable-plugins", true),
		chromedp.Flag("disable-images", true), // Speed up loading
		chromedp.UserDataDir(s.config.Scraper.UserDataDir),
	)

	allocCtx, cancel := chromedp.NewExecAllocator(context.Background(), opts...)
	defer cancel()

	// Add timeout context
	ctx, cancel := chromedp.NewContext(allocCtx, chromedp.WithLogf(func(s string, args ...interface{}) {
		// Suppress cookie parsing errors - they're not critical
		if !strings.Contains(s, "cookiePart") && !strings.Contains(s, "could not unmarshal event") {
			fmt.Printf("ChromeDP: "+s+"\n", args...)
		}
	}))
	defer cancel()

	// Enable console logging from JavaScript
	chromedp.ListenTarget(ctx, func(ev interface{}) {
		switch ev := ev.(type) {
		case *runtime.EventConsoleAPICalled:
			args := make([]string, len(ev.Args))
			for i, arg := range ev.Args {
				if arg.Value != nil {
					args[i] = string(arg.Value)
				} else {
					args[i] = "null"
				}
			}
			// Filter out LinkedIn internal messages and only show relevant ones
			message := strings.Join(args, " ")
			if !strings.Contains(message, "visitor.publishDestinations()") &&
			   !strings.Contains(message, "destination publishing iframe") {
				fmt.Printf("JS: %s\n", message)
			}
		}
	})

	// Login to LinkedIn
	fmt.Println("🔐 Attempting to login to LinkedIn...")
	if err := s.login(ctx); err != nil {
		return fmt.Errorf("login failed: %w", err)
	}
	fmt.Println("✅ Login successful!")

	// Get open jobs from database
	openJobs, err := s.getOpenJobs(limit)
	if err != nil {
		return fmt.Errorf("failed to get open jobs: %w", err)
	}

	if len(openJobs) == 0 {
		fmt.Println("✅ No open jobs found to check")
		return nil
	}

	// Process jobs to check their status
	successCount := 0
	closedCount := 0
	failCount := 0
	var closedJobs []string
	
	fmt.Printf("🔍 Checking %d jobs for closure status...\n", len(openJobs))
	
	// Create progress tracker
	progress := NewProgressTracker(len(openJobs), "Job Status Check")

	for i, job := range openJobs {
		// Check if job is closed
		isClosed, err := s.checkJobIsClosed(ctx, job.ApplyURL)
		if err != nil {
			fmt.Printf("\n❌ Error checking job %d (%s): %v", job.JobID, job.Title, err)
			failCount++
			progress.SetCurrent(i + 1)
			continue
		}
		
		if isClosed {
			// Mark job as closed in database (this also updates updated_at)
			if err := s.markJobAsClosed(job.JobID); err != nil {
				fmt.Printf("\n❌ Error marking job %d as closed: %v", job.JobID, err)
				failCount++
				progress.SetCurrent(i + 1)
				continue
			}
			closedCount++
			closedJobs = append(closedJobs, fmt.Sprintf("Job %d: %s", job.JobID, job.Title))
		} else {
			// Job is still open, update the updated_at timestamp so it's checked later
			if err := s.updateJobCheckedAt(job.JobID); err != nil {
				fmt.Printf("\n❌ Error updating job %d timestamp: %v", job.JobID, err)
				failCount++
				progress.SetCurrent(i + 1)
				continue
			}
		}
		
		successCount++
		
		// Update progress bar
		progress.SetCurrent(i + 1)
	}
	
	// Finish progress bar
	progress.Finish()
	
	// Show detailed summary
	fmt.Printf("✅ Job status checking complete! Success: %d | Closed: %d | Failed: %d\n", successCount, closedCount, failCount)
	
	if len(closedJobs) > 0 {
		fmt.Println("\n🔒 Jobs marked as closed:")
		for _, closedJob := range closedJobs {
			fmt.Printf("  • %s\n", closedJob)
		}
	}
	return nil
}

// getOpenJobs retrieves jobs from database that don't have a closed date
func (s *LinkedInScraper) getOpenJobs(limit int) ([]QueueJob, error) {
	var query string
	var rows *sql.Rows
	var err error

	if limit == 0 {
		// Get all jobs without closed date, ordered by oldest updated_at first
		// Only include jobs that haven't been checked in the last 24 hours
		query = `
			SELECT j.job_id, j.apply_url, j.title, COALESCE(c.name, 'Unknown') as company_name
			FROM job_postings j
			LEFT JOIN companies c ON j.company_id = c.company_id
			WHERE j.job_post_closed_date IS NULL
			AND j.apply_url IS NOT NULL 
			AND j.apply_url != ''
			AND (j.updated_at IS NULL OR j.updated_at < DATE_SUB(NOW(), INTERVAL 1 DAY))
			ORDER BY j.updated_at ASC, j.created_at ASC
		`
		rows, err = s.db.Query(query)
	} else {
		// Get limited number of open jobs, ordered by oldest updated_at first
		// Only include jobs that haven't been checked in the last 24 hours
		query = `
			SELECT j.job_id, j.apply_url, j.title, COALESCE(c.name, 'Unknown') as company_name
			FROM job_postings j
			LEFT JOIN companies c ON j.company_id = c.company_id
			WHERE j.job_post_closed_date IS NULL
			AND j.apply_url IS NOT NULL 
			AND j.apply_url != ''
			AND (j.updated_at IS NULL OR j.updated_at < DATE_SUB(NOW(), INTERVAL 1 DAY))
			ORDER BY j.updated_at ASC, j.created_at ASC
			LIMIT ?
		`
		rows, err = s.db.Query(query, limit)
	}

	if err != nil {
		return nil, fmt.Errorf("failed to query open jobs: %w", err)
	}
	defer rows.Close()

	var jobs []QueueJob
	for rows.Next() {
		var job QueueJob
		err := rows.Scan(&job.JobID, &job.ApplyURL, &job.Title, &job.Company)
		if err != nil {
			fmt.Printf("⚠️ Error scanning job: %v\n", err)
			continue
		}
		jobs = append(jobs, job)
	}

	return jobs, nil
}

// checkJobIsClosed checks if a job is closed by looking for the Danish closure message
func (s *LinkedInScraper) checkJobIsClosed(ctx context.Context, applyURL string) (bool, error) {
	var isClosed bool
	
	err := chromedp.Run(ctx,
		chromedp.Navigate(applyURL),
		chromedp.WaitVisible(`body`, chromedp.ByQuery),
		chromedp.Sleep(2*time.Second), // Wait for page to fully load
		chromedp.Evaluate(`
			// Check for the Danish closure message
			const closureSpan = document.querySelector('span.artdeco-inline-feedback__message');
			if (closureSpan && closureSpan.textContent.includes('Modtager ikke længere ansøgninger')) {
				console.log('Job is closed - found closure message');
				true;
			} else {
				console.log('Job appears to be open - no closure message found');
				false;
			}
		`, &isClosed),
	)
	
	if err != nil {
		return false, fmt.Errorf("failed to check job closure status: %w", err)
	}
	
	return isClosed, nil
}

// markJobAsClosed updates the job_post_closed_date in the database
func (s *LinkedInScraper) markJobAsClosed(jobID int) error {
	query := `UPDATE job_postings SET job_post_closed_date = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE job_id = ?`
	_, err := s.db.Exec(query, jobID)
	if err != nil {
		return fmt.Errorf("failed to mark job as closed: %w", err)
	}
	return nil
}

// updateJobCheckedAt updates the updated_at timestamp for a job that was checked but remains open
func (s *LinkedInScraper) updateJobCheckedAt(jobID int) error {
	query := `UPDATE job_postings SET updated_at = CURRENT_TIMESTAMP WHERE job_id = ?`
	_, err := s.db.Exec(query, jobID)
	if err != nil {
		return fmt.Errorf("failed to update job checked timestamp: %w", err)
	}
	return nil
}
