package scraper

import (
	"context"
	"fmt"
	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/database"
	"strings"

	"github.com/chromedp/chromedp"
	"github.com/chromedp/cdproto/runtime"
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
func (s *LinkedInScraper) ScrapeJobs(keywords, location string, maxPages, jobsPerPage int) error {
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

	// Build search URL
	searchURL := s.buildSearchURL(keywords, location, 0)
	logrus.Infof("🔍 Starting scrape with search URL: %s", searchURL)

	totalJobs := 0
	for page := 0; page < maxPages; page++ {
		pageURL := s.buildSearchURL(keywords, location, page*25)
		
		logrus.Infof("📄 Scraping page %d/%d: %s", page+1, maxPages, pageURL)
		
		jobs, err := s.scrapePage(ctx, pageURL, jobsPerPage)
		if err != nil {
			logrus.Errorf("❌ Error scraping page %d: %v", page+1, err)
			continue
		}
		
		if len(jobs) == 0 {
			logrus.Warn("⚠️  No jobs found on page, stopping...")
			break
		}
		
		// Process and save jobs
		for _, job := range jobs {
			if err := s.saveJob(job); err != nil {
				logrus.Errorf("❌ Failed to save job: %v", err)
				continue
			}
			totalJobs++
		}
		
		logrus.Infof("✅ Processed %d jobs from page %d", len(jobs), page+1)
	}

	logrus.Infof("🎉 Scraping completed! Total jobs processed: %d", totalJobs)
	return nil
}
