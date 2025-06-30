package scraper

import (
	"context"
	"fmt"
	"strings"
	"time"

	"github.com/chromedp/chromedp"
	"github.com/sirupsen/logrus"
	"linkedin-job-scraper/internal/models"
)

// extractJobURLs extracts job URLs from the current search results page
func (s *LinkedInScraper) extractJobURLs(ctx context.Context) ([]string, error) {
	// Wait for job results to load
	var jobResultsFound bool
	err := chromedp.Run(ctx,
		chromedp.Evaluate(s.buildPageAnalysisScript(), &jobResultsFound),
	)

	if err != nil || !jobResultsFound {
		logrus.Warn("⚠️  Job results container not found - analyzing page...")
		
		// Get more detailed page analysis
		var pageAnalysis map[string]interface{}
		chromedp.Run(ctx, chromedp.Evaluate(s.buildDetailedAnalysisScript(), &pageAnalysis))
		
		logrus.Infof("📊 Page analysis: %+v", pageAnalysis)
		
		// Check if we have job links even without proper container
		if jobLinks, ok := pageAnalysis["jobLinksCount"].(float64); ok && jobLinks > 0 {
			logrus.Infof("✅ Found %.0f job links, proceeding anyway...", jobLinks)
		} else {
			logrus.Warn("❌ No job links found - page may have changed or we need login")
			return []string{}, nil
		}
	} else {
		logrus.Info("✅ Job results container found!")
	}

	// Get job URLs from search results
	logrus.Info("🔍 Extracting job URLs...")
	var jobURLs []string
	err = chromedp.Run(ctx,
		chromedp.Evaluate(s.buildExtractJobURLsScript(), &jobURLs),
	)

	if err != nil {
		return nil, fmt.Errorf("failed to extract job URLs: %w", err)
	}

	return jobURLs, nil
}

// scrapeJobDetails extracts detailed information from a single job page
func (s *LinkedInScraper) scrapeJobDetails(ctx context.Context, jobURL string) (*models.ScrapedJob, error) {
	// Navigate to job detail page
	err := chromedp.Run(ctx,
		chromedp.Navigate(jobURL),
		chromedp.Sleep(3*time.Second), // Increased wait time
	)

	if err != nil {
		return nil, fmt.Errorf("failed to navigate to job page: %w", err)
	}

	// Wait for page to fully load and try to expand description if needed
	err = chromedp.Run(ctx,
		chromedp.Sleep(2*time.Second),
		// Try to click "Show more" button if it exists
		chromedp.Evaluate(s.buildExpandDescriptionScript(), nil),
		chromedp.Sleep(1*time.Second), // Wait for content to expand
	)

	// Try to click insights button and extract skills
	if err := s.clickInsightsButton(ctx); err != nil {
		logrus.Debugf("Failed to click insights button: %v", err)
	}

	// Scroll to ensure all content is loaded
	err = chromedp.Run(ctx,
		chromedp.Evaluate(`window.scrollTo(0, document.body.scrollHeight);`, nil),
		chromedp.Sleep(1*time.Second),
		chromedp.Evaluate(`window.scrollTo(0, 0);`, nil), // Scroll back to top
		chromedp.Sleep(1*time.Second),
	)

	// Extract job ID from URL
	jobID := s.extractJobIDFromURL(jobURL)
	if jobID == "" {
		return nil, fmt.Errorf("could not extract job ID from URL: %s", jobURL)
	}

	// Extract job details using JavaScript
	var jobData map[string]interface{}
	err = chromedp.Run(ctx,
		chromedp.Evaluate(s.buildJobExtractionScript(), &jobData),
	)

	if err != nil {
		return nil, fmt.Errorf("failed to extract job data: %w", err)
	}

	// Convert extracted data to ScrapedJob
	return s.convertToScrapedJob(jobData, jobID, jobURL)
}

// extractJobIDFromURL extracts the LinkedIn job ID from a job URL
func (s *LinkedInScraper) extractJobIDFromURL(jobURL string) string {
	if matches := strings.Split(jobURL, "/"); len(matches) > 0 {
		for _, part := range matches {
			if len(part) > 8 && strings.ContainsAny(part, "0123456789") {
				// Remove any query parameters
				if idx := strings.Index(part, "?"); idx != -1 {
					part = part[:idx]
				}
				return part
			}
		}
	}
	return ""
}
