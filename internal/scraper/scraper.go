package scraper

import (
	"context"
	"fmt"
	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/services"
	"os"
	"strconv"
	"strings"

	"github.com/chromedp/cdproto/runtime"
	"github.com/chromedp/chromedp"
)

type LinkedInScraper struct {
	config      *config.Config
	dataService *services.DataService
}

// NewLinkedInScraper creates a new LinkedIn scraper
func NewLinkedInScraper(cfg *config.Config, dataService *services.DataService) *LinkedInScraper {
	return &LinkedInScraper{
		config:      cfg,
		dataService: dataService,
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

	// Preload existing job IDs to Redis cache for faster lookup
	fmt.Println("🔄 Preloading existing job IDs to cache...")
	if err := s.dataService.PreloadJobIDsToCache(); err != nil {
		fmt.Printf("⚠️  Failed to preload job IDs to cache: %v\n", err)
		fmt.Println("📝 Continuing without preload - will check individual jobs via API")
	}

	// Preload existing company names to Redis cache for faster lookup
	fmt.Println("🔄 Preloading existing company names to cache...")
	if err := s.dataService.PreloadCompanyNamesToCache(); err != nil {
		fmt.Printf("⚠️  Failed to preload company names to cache: %v\n", err)
		fmt.Println("📝 Continuing without company preload - will check individual companies via API")
	}

	// Dynamic pagination based on job URLs FOUND on LinkedIn (not jobs saved to DB)
	totalJobUrlsFound := 0   // Total job URLs LinkedIn has shown us (for pagination)
	totalJobsSaved := 0      // Total jobs actually saved to database
	page := 1
	const maxPages = 1000 // Safety limit to prevent infinite loops

	fmt.Printf("🎯 Target: %d jobs | Keywords: %s | Location: %s\n", totalJobs, keywords, location)

	for page <= maxPages && totalJobsSaved < totalJobs {
		// Use LinkedIn's pagination: start from total job URLs we've seen
		start := totalJobUrlsFound
		pageURL := s.buildSearchURL(keywords, location, start)

		fmt.Printf("\n🔍 Scraping page %d (starting from result %d)...\n", page, start)

		// Scrape page and get result info
		pageResult, err := s.scrapePageWithDetails(ctx, pageURL, 25)
		if err != nil {
			fmt.Printf("❌ Failed to scrape page %d: %v\n", page, err)
			break
		}

		if pageResult.TotalJobsFound == 0 {
			fmt.Printf("🔍 No more jobs found on page %d, stopping scraping\n", page)
			break
		}

		// Update totals
		totalJobUrlsFound += pageResult.TotalJobsFound
		totalJobsSaved += pageResult.JobsSaved

		// Log page results
		fmt.Printf("📄 Page %d: Found %d jobs, Saved %d new jobs, Skipped %d existing jobs\n",
			page, pageResult.TotalJobsFound, pageResult.JobsSaved, pageResult.JobsSkipped)
		fmt.Printf("📊 Progress: %d/%d jobs saved (%.1f%%)\n",
			totalJobsSaved, totalJobs, float64(totalJobsSaved)/float64(totalJobs)*100)

		// If we've reached our target, stop
		if totalJobsSaved >= totalJobs {
			fmt.Printf("🎯 Target reached! Saved %d jobs\n", totalJobsSaved)
			break
		}

		page++
	}

	fmt.Printf("\n🎉 Scraping completed! Final results: %d jobs saved out of %d target\n", totalJobsSaved, totalJobs)
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

	// Extract job URLs using existing function
	jobURLs, err := s.extractJobURLs(ctx)
	if err != nil {
		return nil, fmt.Errorf("failed to extract job URLs: %w", err)
	}

	// Filter new vs existing jobs
	newJobURLs, skippedCount := s.filterNewJobs(jobURLs)

	if skippedCount > 0 {
		fmt.Printf("⏭️  Skipping %d existing jobs on this page\n", skippedCount)
	}

	result := &PageResult{
		TotalJobsFound: len(jobURLs),
		JobsSkipped:    skippedCount,
		JobsSaved:      0,
	}

	if len(newJobURLs) == 0 {
		fmt.Printf("ℹ️  No new jobs to process on this page\n")
		return result, nil
	}

	fmt.Printf("🆕 Processing %d new jobs...\n", len(newJobURLs))

	// Process new jobs with logging
	result.JobsSaved = s.processNewJobs(ctx, newJobURLs)

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

// isJobNew checks if a job is new (not in cache/API)
func (s *LinkedInScraper) isJobNew(jobURL string) bool {
	jobID := s.extractJobIDFromURL(jobURL)
	if jobID == "" {
		fmt.Printf("⚠️  Could not extract job ID from URL: %s\n", jobURL)
		return false
	}

	jobIDInt, err := strconv.Atoi(jobID)
	if err != nil {
		fmt.Printf("⚠️  Invalid job ID '%s': %v\n", jobID, err)
		return false
	}

	exists, err := s.dataService.JobExists(jobIDInt)
	if err != nil {
		fmt.Printf("⚠️  Error checking if job exists: %v\n", err)
		return false
	}

	return !exists
}

// processNewJobs scrapes and saves new jobs with logging
func (s *LinkedInScraper) processNewJobs(ctx context.Context, jobURLs []string) int {
	if len(jobURLs) == 0 {
		return 0
	}

	savedCount := 0

	for _, jobURL := range jobURLs {
		job, err := s.scrapeJobDetails(ctx, jobURL)
		if err != nil {
			fmt.Printf("❌ Failed to scrape job details: %v\n", err)
			continue
		}

		if err := s.saveJob(job); err != nil {
			fmt.Printf("❌ Failed to save job: %v\n", err)
			continue
		}

		savedCount++
		fmt.Printf("✅ Saved job: %s (ID: %d)\n", job.Title, job.JobID)
	}

	return savedCount
}

// DiscoverJobIDs discovers new job IDs and stores them in Redis queue (no detailed scraping)
func (s *LinkedInScraper) DiscoverJobIDs(keywords, location string, totalJobs int) error {
	fmt.Println("🔍 Starting LinkedIn job ID discovery...")

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
		chromedp.Flag("disable-images", true),
		chromedp.UserDataDir(s.config.Scraper.UserDataDir),
	)

	allocCtx, cancel := chromedp.NewExecAllocator(context.Background(), opts...)
	defer cancel()

	ctx, cancel := chromedp.NewContext(allocCtx, chromedp.WithLogf(func(s string, args ...interface{}) {
		if !strings.Contains(s, "cookiePart") && !strings.Contains(s, "could not unmarshal event") {
			fmt.Printf("ChromeDP: "+s+"\n", args...)
		}
	}))
	defer cancel()

	// Login to LinkedIn
	if err := s.login(ctx); err != nil {
		return fmt.Errorf("login failed: %w", err)
	}
	fmt.Println("✅ Ready to discover job IDs!")

	// Preload existing job IDs to cache for filtering
	fmt.Println("🔄 Preloading existing job IDs to cache...")
	if err := s.dataService.PreloadJobIDsToCache(); err != nil {
		fmt.Printf("⚠️  Failed to preload job IDs to cache: %v\n", err)
		fmt.Println("📝 Continuing without preload - will check individual jobs via API")
	}

	totalJobIDsFound := 0
	totalNewJobIDs := 0
	page := 1
	const maxPages = 1000

	fmt.Printf("🎯 Target: %d job IDs | Keywords: %s | Location: %s\n", totalJobs, keywords, location)

	for page <= maxPages && totalNewJobIDs < totalJobs {
		start := totalJobIDsFound
		pageURL := s.buildSearchURL(keywords, location, start)

		fmt.Printf("\n🔍 Discovering job IDs on page %d (starting from result %d)...\n", page, start)
		fmt.Printf("🌐 URL: %s\n", pageURL)

		// Extract job URLs from the page
		err := chromedp.Run(ctx, chromedp.Navigate(pageURL))
		if err != nil {
			fmt.Printf("❌ Failed to navigate to page %d: %v\n", page, err)
			break
		}

		jobURLs, err := s.extractJobURLs(ctx)
		if err != nil {
			fmt.Printf("❌ Failed to extract job URLs from page %d: %v\n", page, err)
			break
		}

		if len(jobURLs) == 0 {
			fmt.Printf("🔍 No more jobs found on page %d, stopping discovery\n", page)
			break
		}

		// Filter and queue new job IDs
		newJobIDs := 0
		skippedJobs := 0

		for _, jobURL := range jobURLs {
			jobID := s.extractJobIDFromURL(jobURL)
			if jobID == "" {
				continue
			}

			jobIDInt, err := strconv.Atoi(jobID)
			if err != nil {
				continue
			}

			// Check if job already exists in database - use discovery method that only caches positive results
			existsInDB, err := s.dataService.JobExistsForDiscovery(jobIDInt)
			if err != nil {
				fmt.Printf("⚠️  Error checking if job exists in database: %v\n", err)
				continue
			}

			// Check if job is already in the processing queue
			existsInQueue, err := s.dataService.IsJobInQueue(jobID)
			if err != nil {
				fmt.Printf("⚠️  Error checking if job exists in queue: %v\n", err)
				continue
			}

			if !existsInDB && !existsInQueue {
				// Add job ID to Redis queue for later processing
				if err := s.dataService.QueueJobForProcessing(jobID, jobURL); err != nil {
					continue
				}
				newJobIDs++
				totalNewJobIDs++
			} else {
				skippedJobs++
				if existsInDB {
					fmt.Printf("⏭️  Job ID %s already exists in database, skipping\n", jobID)
				} else if existsInQueue {
					fmt.Printf("⏭️  Job ID %s already in processing queue, skipping\n", jobID)
				}
			}
		}

		totalJobIDsFound += len(jobURLs)

		fmt.Printf("📄 Page %d: Found %d job URLs, Queued %d new job IDs, Skipped %d existing jobs\n",
			page, len(jobURLs), newJobIDs, skippedJobs)
		fmt.Printf("📊 Progress: %d/%d new job IDs queued (%.1f%%)\n",
			totalNewJobIDs, totalJobs, float64(totalNewJobIDs)/float64(totalJobs)*100)

		if totalNewJobIDs >= totalJobs {
			fmt.Printf("🎯 Target reached! Queued %d new job IDs\n", totalNewJobIDs)
			break
		}

		page++
	}

	fmt.Printf("\n🎉 Job ID discovery completed! Final results: %d new job IDs queued out of %d target\n", totalNewJobIDs, totalJobs)
	return nil
}

// ProcessJobsFromQueue processes job IDs from Redis queue and scrapes detailed data
func (s *LinkedInScraper) ProcessJobsFromQueue(limit int) error {
	fmt.Printf("⚙️  Starting to process jobs from Redis queue (limit: %d)...\n", limit)

	// Setup Chrome options
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
		chromedp.Flag("disable-images", true),
		chromedp.UserDataDir(s.config.Scraper.UserDataDir),
	)

	allocCtx, cancel := chromedp.NewExecAllocator(context.Background(), opts...)
	defer cancel()

	ctx, cancel := chromedp.NewContext(allocCtx, chromedp.WithLogf(func(s string, args ...interface{}) {
		if !strings.Contains(s, "cookiePart") && !strings.Contains(s, "could not unmarshal event") {
			fmt.Printf("ChromeDP: "+s+"\n", args...)
		}
	}))
	defer cancel()

	// Login to LinkedIn
	if err := s.login(ctx); err != nil {
		return fmt.Errorf("login failed: %w", err)
	}
	fmt.Println("✅ Ready to process jobs from queue!")

	// Preload company names for faster processing
	fmt.Println("🔄 Preloading existing company names to cache...")
	if err := s.dataService.PreloadCompanyNamesToCache(); err != nil {
		fmt.Printf("⚠️  Failed to preload company names to cache: %v\n", err)
		fmt.Println("📝 Continuing without company preload")
	}

	processedCount := 0
	failedCount := 0

	for processedCount < limit {
		// Get next job from queue
		jobID, jobURL, err := s.dataService.GetNextJobFromQueue()
		if err != nil {
			fmt.Printf("❌ Failed to get next job from queue: %v\n", err)
			break
		}

		if jobID == "" {
			fmt.Println("📭 No more jobs in queue to process")
			break
		}

		fmt.Printf("\n⚙️  Processing job ID %s...\n", jobID)

		// Scrape job details
		job, err := s.scrapeJobDetails(ctx, jobURL)
		if err != nil {
			fmt.Printf("❌ Failed to scrape job details for ID %s: %v\n", jobID, err)
			failedCount++
			// Remove failed job from queue
			s.dataService.RemoveJobFromQueue(jobID)
			continue
		}

		// Save job to database via API
		if err := s.saveJob(job); err != nil {
			fmt.Printf("❌ Failed to save job ID %s: %v\n", jobID, err)
			failedCount++
			// Remove failed job from queue
			s.dataService.RemoveJobFromQueue(jobID)
			continue
		}

		// Remove successfully processed job from queue
		if err := s.dataService.RemoveJobFromQueue(jobID); err != nil {
			fmt.Printf("⚠️  Failed to remove job ID %s from queue: %v\n", jobID, err)
		}

		processedCount++
		fmt.Printf("✅ Successfully processed job: %s (ID: %d) - %d/%d completed\n", 
			job.Title, job.JobID, processedCount, limit)
	}

	fmt.Printf("\n🎉 Job processing completed! Processed: %d, Failed: %d\n", processedCount, failedCount)
	return nil
}

// RescrapeFromQueue scrapes jobs from the database queue instead of LinkedIn search
func (s *LinkedInScraper) RescrapeFromQueue(limit int) error {
	return fmt.Errorf("RescrapeFromQueue is disabled - database functionality replaced with API")
}

// CheckJobClosureStatus is disabled - use API instead
func (s *LinkedInScraper) CheckJobClosureStatus(limit int) error {
	return fmt.Errorf("CheckJobClosureStatus is disabled - database functionality replaced with API")
}
