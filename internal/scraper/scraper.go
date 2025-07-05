package scraper

import (
	"context"
	"database/sql"
	"fmt"
	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/database"
	"linkedin-job-scraper/internal/models"
	"strconv"
	"strings"

	"github.com/chromedp/cdproto/runtime"
	"github.com/chromedp/chromedp"
	"github.com/sirupsen/logrus"
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
	logrus.Infof("🚀 Initializing Chrome browser...")
	
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
			logrus.Debugf("ChromeDP: "+s, args...)
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
			// Only log our debug messages to avoid spam
			message := strings.Join(args, " ")
			if strings.Contains(message, "=== ") || strings.Contains(message, "✅ ") || strings.Contains(message, "❌ ") || strings.Contains(message, "⚠️ ") {
				logrus.Infof("JS: %s", message)
			}
		}
	})

	// Login to LinkedIn
	logrus.Info("🔐 Attempting to login to LinkedIn...")
	if err := s.login(ctx); err != nil {
		return fmt.Errorf("login failed: %w", err)
	}
	logrus.Info("✅ Login successful!")

	// Dynamic pagination based on job URLs FOUND on LinkedIn (not jobs saved to DB)
	totalJobUrlsFound := 0   // Total job URLs LinkedIn has shown us (for pagination)
	totalJobsSaved := 0      // Total jobs actually saved to database
	page := 1
	const maxPages = 1000 // Safety limit to prevent infinite loops
	
	logrus.Infof("🔍 Starting scrape to collect up to %d jobs with dynamic pagination", totalJobs)

	for page <= maxPages && totalJobsSaved < totalJobs {
		// Use LinkedIn's pagination: start from total job URLs we've seen
		start := totalJobUrlsFound
		pageURL := s.buildSearchURL(keywords, location, start)
		
		logrus.Infof("📄 Scraping page %d (start=%d): %s", page, start, pageURL)
		
		// Scrape page and get result info
		pageResult, err := s.scrapePageWithDetails(ctx, pageURL, 25)
		if err != nil {
			logrus.Errorf("❌ Error scraping page %d: %v", page, err)
			break
		}
		
		if pageResult.TotalJobsFound == 0 {
			logrus.Warn("⚠️  No jobs found on page, stopping...")
			break
		}
		
		// Update total job URLs found (this is what LinkedIn uses for pagination)
		totalJobUrlsFound += pageResult.TotalJobsFound
		
		// Update saved jobs count
		totalJobsSaved += pageResult.JobsSaved
		
		logrus.Infof("✅ Processed %d job URLs from page %d (saved: %d, skipped: %d, total saved: %d/%d, next start: %d)", 
			pageResult.TotalJobsFound, page, pageResult.JobsSaved, pageResult.JobsSkipped, 
			totalJobsSaved, totalJobs, totalJobUrlsFound)
		
		// If we've reached our target, stop
		if totalJobsSaved >= totalJobs {
			logrus.Infof("🎯 Reached target of %d jobs, stopping", totalJobs)
			break
		}
		
		page++
	}

	logrus.Infof("🎉 Scraping completed! Total jobs saved: %d, total job URLs processed: %d", totalJobsSaved, totalJobUrlsFound)
	return nil
}

// PageResult holds information about a scraped page
type PageResult struct {
    TotalJobsFound int // Number of job URLs found on the page
    JobsSaved      int // Number of jobs actually saved to database  
    JobsSkipped    int // Number of jobs skipped (already exist)
}

// scrapePageWithDetails scrapes a page and returns detailed results
func (s *LinkedInScraper) scrapePageWithDetails(ctx context.Context, pageURL string, maxJobs int) (*PageResult, error) {
	// Navigate to the page
	err := chromedp.Run(ctx, chromedp.Navigate(pageURL))
	if err != nil {
		return nil, fmt.Errorf("failed to navigate to page: %w", err)
	}

	logrus.Info("🔍 Extracting job URLs from current page...")
	
	// Extract job URLs using existing function
	jobURLs, err := s.extractJobURLs(ctx)
	if err != nil {
		return nil, fmt.Errorf("failed to extract job URLs: %w", err)
	}
	
	logrus.Infof("✅ Found %d job URLs on page", len(jobURLs))
	
	// Filter new vs existing jobs
	newJobURLs, skippedCount := s.filterNewJobs(jobURLs)
	
	result := &PageResult{
		TotalJobsFound: len(jobURLs),
		JobsSkipped:    skippedCount,
		JobsSaved:      0,
	}
	
	if len(newJobURLs) == 0 {
		logrus.Infof("⏭️  All %d jobs already exist in database", skippedCount)
		return result, nil
	}
	
	logrus.Infof("⏭️  Skipped %d existing jobs, will scrape %d new jobs", skippedCount, len(newJobURLs))
	
	// Process new jobs
	result.JobsSaved = s.processNewJobs(ctx, newJobURLs)
	
	logrus.Infof("✅ Scraped %d job details from page", result.JobsSaved)
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
		logrus.Warnf("⚠️  Could not extract job ID from URL: %s", jobURL)
		return false
	}
	
	jobIDInt, err := strconv.ParseInt(jobID, 10, 64)
	if err != nil {
		logrus.Warnf("⚠️  Invalid job ID '%s': %v", jobID, err)
		return false
	}
	
	exists, err := s.jobRepo.ExistsLinkedInJobID(jobIDInt)
	if err != nil {
		logrus.Warnf("⚠️  Error checking if job exists: %v", err)
		return false
	}
	
	return !exists
}

// processNewJobs scrapes and saves new jobs
func (s *LinkedInScraper) processNewJobs(ctx context.Context, jobURLs []string) int {
	savedCount := 0
	
	for i, jobURL := range jobURLs {
		logrus.Infof("📋 Scraping job %d/%d: %s", i+1, len(jobURLs), jobURL)
		
		job, err := s.scrapeJobDetails(ctx, jobURL)
		if err != nil {
			logrus.Errorf("❌ Failed to scrape job %s: %v", jobURL, err)
			continue
		}
		
		if err := s.saveJob(job); err != nil {
			logrus.Errorf("❌ Failed to save job: %v", err)
			continue
		}
		
		savedCount++
	}
	
	return savedCount
}

// RescrapeFromQueue scrapes jobs from the database queue instead of LinkedIn search
func (s *LinkedInScraper) RescrapeFromQueue(limit int) error {
	logrus.Infof("🚀 Initializing Chrome browser...")
	
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
			logrus.Debugf("ChromeDP: "+s, args...)
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
			// Only log our debug messages to avoid spam
			message := strings.Join(args, " ")
			if strings.Contains(message, "=== ") || strings.Contains(message, "✅ ") || strings.Contains(message, "❌ ") || strings.Contains(message, "⚠️ ") {
				logrus.Infof("JS: %s", message)
			}
		}
	})

	// Login to LinkedIn
	logrus.Info("🔐 Attempting to login to LinkedIn...")
	if err := s.login(ctx); err != nil {
		return fmt.Errorf("login failed: %w", err)
	}
	logrus.Info("✅ Login successful!")

	// Get jobs from database queue instead of LinkedIn search
	jobsToRescrape, err := s.getJobsFromQueue(limit)
	if err != nil {
		return fmt.Errorf("failed to get jobs from queue: %w", err)
	}

	if len(jobsToRescrape) == 0 {
		logrus.Info("✅ No jobs found in queue to rescrape")
		return nil
	}

	logrus.Infof("🔍 Starting rescrape of %d jobs from queue", len(jobsToRescrape))

	// Process jobs from queue
	successCount := 0
	failCount := 0

	for i, job := range jobsToRescrape {
		logrus.Infof("📋 Scraping job %d/%d: %s", i+1, len(jobsToRescrape), job.ApplyURL)
		
		// Use existing scrapeJobDetails method
		jobPosting, err := s.scrapeJobDetails(ctx, job.ApplyURL)
		if err != nil {
			logrus.Errorf("❌ Failed to scrape job %d: %v", job.JobID, err)
			failCount++
			continue
		}
		
		// Update existing job in database
		if err := s.updateExistingJob(job.JobID, jobPosting); err != nil {
			logrus.Errorf("❌ Failed to update job %d: %v", job.JobID, err)
			failCount++
			continue
		}
		
		successCount++
		logrus.Infof("✅ Job extraction completed successfully for: %s", jobPosting.Title)
	}

	logrus.Infof("🎉 Rescraping completed! Successful: %d, Failed: %d", successCount, failCount)
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
			logrus.Warnf("⚠️ Error scanning job: %v", err)
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
		logrus.Warnf("⚠️ No new information to update for job %d", jobID)
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

	logrus.Infof("✅ Updated job %d with new information", jobID)
	return nil
}
