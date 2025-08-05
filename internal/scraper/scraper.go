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

// RescrapeFromQueue scrapes jobs from the database queue instead of LinkedIn search
func (s *LinkedInScraper) RescrapeFromQueue(limit int) error {
	return fmt.Errorf("RescrapeFromQueue is disabled - database functionality replaced with API")
}

// CheckJobClosureStatus is disabled - use API instead
func (s *LinkedInScraper) CheckJobClosureStatus(limit int) error {
	return fmt.Errorf("CheckJobClosureStatus is disabled - database functionality replaced with API")
}
