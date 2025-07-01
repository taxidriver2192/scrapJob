package scraper

import (
	"context"
	"fmt"
	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/database"
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
