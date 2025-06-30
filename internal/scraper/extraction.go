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
	logrus.Infof("🌐 Navigating to job page: %s", jobURL)
	
	// Navigate to job detail page with timeout
	navCtx, navCancel := context.WithTimeout(ctx, 15*time.Second)
	defer navCancel()
	
	err := chromedp.Run(navCtx,
		chromedp.Navigate(jobURL),
	)

	if err != nil {
		return nil, fmt.Errorf("failed to navigate to job page: %w", err)
	}
	
	logrus.Info("⏳ Waiting for job page to load...")
	
	// Use smart wait with timeout
	waitErr := smartWaitForCondition(ctx, `
		document.querySelector('.topcard__title') !== null ||
		document.querySelector('.job-details-headline__title') !== null ||
		document.querySelector('.job-details-jobs-unified-top-card__job-title') !== null ||
		document.querySelector('.jobs-unified-top-card__job-title') !== null ||
		document.querySelector('h1[data-test-id="job-title"]') !== null ||
		document.querySelector('h1') !== null ||
		document.querySelector('.error-page') !== null
	`, 15*time.Second)
	
	// If intelligent wait fails, use fallback sleep
	if waitErr != nil {
		logrus.Warnf("Smart wait failed for job page (%v), using fallback sleep", waitErr)
		err = chromedp.Run(ctx,
			chromedp.Sleep(3*time.Second),
		)
		if err != nil {
			return nil, fmt.Errorf("fallback wait failed: %w", err)
		}
	}
	
	logrus.Info("✅ Job page loaded, checking page status...")
	
	// Check page title to see if we're on the right page
	var pageTitle string
	err = chromedp.Run(ctx,
		chromedp.Title(&pageTitle),
	)
	if err == nil {
		logrus.Infof("📄 Job page title: %s", pageTitle)
	}

	// Wait for page to fully load and try to expand description if needed
	logrus.Info("🔍 Expanding job description if needed...")
	
	// Try to wait for description area with timeout - don't fail if not found
	descWaitCtx, descCancel := context.WithTimeout(ctx, 5*time.Second)
	defer descCancel()
	
	if err = chromedp.Run(descWaitCtx,
		// Wait for description area to be present - use more generic selectors
		chromedp.WaitReady(`.description, .show-more-less-html, .jobs-description, [data-test-job-description]`, chromedp.ByQuery),
	); err != nil {
		logrus.Infof("Description area not found with standard selectors, trying expansion anyway: %v", err)
	}
	
	// Try to click "Show more" button if it exists - don't fail if it doesn't work
	if err = chromedp.Run(ctx,
		chromedp.Evaluate(s.buildExpandDescriptionScript(), nil),
		// Wait a brief moment for expansion to complete
		chromedp.Sleep(500*time.Millisecond),
	); err != nil {
		logrus.Infof("Warning: could not expand description: %v", err)
	}

	// Try to click insights button and extract skills
	logrus.Info("🔍 Trying to click insights button...")
	
	insightsCtx, insightsCancel := context.WithTimeout(ctx, 5*time.Second)
	defer insightsCancel()
	
	if err := s.clickInsightsButton(insightsCtx); err != nil {
		logrus.Infof("Failed to click insights button (timeout or not found): %v", err)
	}

	// Scroll to ensure all content is loaded
	logrus.Info("📜 Scrolling page to load all content...")
	if err = chromedp.Run(ctx,
		chromedp.Evaluate(`window.scrollTo(0, document.body.scrollHeight);`, nil),
		// Wait for any lazy-loaded content
		chromedp.Sleep(500*time.Millisecond),
		chromedp.Evaluate(`window.scrollTo(0, 0);`, nil), // Scroll back to top
		// Brief pause to ensure scroll is complete
		chromedp.Sleep(300*time.Millisecond),
	); err != nil {
		logrus.Warnf("Warning: scrolling failed: %v", err)
	}

	// Extract job ID from URL
	logrus.Info("🆔 Extracting job ID from URL...")
	jobID := s.extractJobIDFromURL(jobURL)
	if jobID == "" {
		return nil, fmt.Errorf("could not extract job ID from URL: %s", jobURL)
	}

	// Extract job details using JavaScript with timeout
	logrus.Infof("📊 Extracting job data with JavaScript for job ID: %s", jobID)
	var jobData map[string]interface{}
	
	// Create a context with timeout for the JavaScript execution
	evalCtx, cancel := context.WithTimeout(ctx, 30*time.Second)
	defer cancel()
	
	err = chromedp.Run(evalCtx,
		chromedp.Evaluate(s.buildJobExtractionScript(), &jobData),
	)

	if err != nil {
		logrus.Errorf("❌ JavaScript extraction failed: %v", err)
		// Try to get page source for debugging if extraction fails
		var pageSource string
		if pageErr := chromedp.Run(ctx, chromedp.OuterHTML("html", &pageSource)); pageErr == nil {
			if len(pageSource) > 1000 {
				logrus.Debugf("Page source preview: %s...", pageSource[:1000])
			}
		}
		return nil, fmt.Errorf("failed to extract job data: %w", err)
	}
	
	logrus.Infof("✅ JavaScript extraction completed, data keys: %v", getMapKeys(jobData))

	// Convert extracted data to ScrapedJob
	logrus.Info("🔄 Converting extracted data to ScrapedJob...")
	result, err := s.convertToScrapedJob(jobData, jobID, jobURL)
	if err != nil {
		return nil, fmt.Errorf("failed to convert job data: %w", err)
	}
	
	logrus.Infof("✅ Job extraction completed successfully for: %s", result.Title)
	return result, nil
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
