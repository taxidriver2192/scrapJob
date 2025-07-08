package scraper

import (
	"context"
	"encoding/json"
	"fmt"
	"strings"
	"time"

	"linkedin-job-scraper/internal/models"

	"github.com/chromedp/chromedp"
)

// extractJobURLs extracts job URLs from the current search results page
func (s *LinkedInScraper) extractJobURLs(ctx context.Context) ([]string, error) {
	// Wait for job results to load and check page status
	var jobResultsFound bool
	err := chromedp.Run(ctx,
		chromedp.Evaluate(s.buildPageAnalysisScript(), &jobResultsFound),
	)

	if err != nil {
		fmt.Printf("⚠️  Page analysis failed: %v", err)
		return nil, fmt.Errorf("page analysis failed: %w", err)
	}
	
	if !jobResultsFound {
		// Get more detailed page analysis to understand what's happening
		var pageAnalysis map[string]interface{}
		chromedp.Run(ctx, chromedp.Evaluate(s.buildDetailedAnalysisScript(), &pageAnalysis))
		
		fmt.Printf("📊 Page analysis: %+v", pageAnalysis)
		
		// Check if we have job links even without proper container
		if jobLinks, ok := pageAnalysis["jobLinksCount"].(float64); ok && jobLinks > 0 {
			fmt.Printf("✅ Found %.0f job links, proceeding with extraction...", jobLinks)
		} else {
			fmt.Println("❌ No job links found - page may have changed or we need login")
			return []string{}, nil
		}
	} else {
		fmt.Println("✅ Job results container found!")
	}

	// Get job URLs from search results
	fmt.Println("🔍 Extracting job URLs...")
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
func (s *LinkedInScraper) scrapeJobDetails(ctx context.Context, jobURL string) (*models.JobPosting, error) {
	fmt.Printf("🌐 Navigating to job page: %s\n", jobURL)
	
	// Navigate to job detail page with timeout
	navCtx, navCancel := context.WithTimeout(ctx, 15*time.Second)
	defer navCancel()
	
	err := chromedp.Run(navCtx,
		chromedp.Navigate(jobURL),
	)

	if err != nil {
		return nil, fmt.Errorf("failed to navigate to job page: %w", err)
	}
	
	fmt.Println("⏳ Waiting for job page to load...")
	
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
		fmt.Printf("Smart wait failed for job page (%v), using fallback sleep", waitErr)
		err = chromedp.Run(ctx,
			chromedp.Sleep(3*time.Second),
		)
		if err != nil {
			return nil, fmt.Errorf("fallback wait failed: %w", err)
		}
	}
	
	fmt.Println("✅ Job page loaded, checking page status...")
	
	// Check page title to see if we're on the right page
	var pageTitle string
	err = chromedp.Run(ctx,
		chromedp.Title(&pageTitle),
	)
	if err == nil {
		fmt.Printf("📄 Job page title: %s\n", pageTitle)
	}

	// Wait for page to fully load and try to expand description if needed
	fmt.Println("🔍 Expanding job description if needed...")
	
	// Try to wait for description area with timeout - don't fail if not found
	descWaitCtx, descCancel := context.WithTimeout(ctx, 5*time.Second)
	defer descCancel()
	
	if err = chromedp.Run(descWaitCtx,
		// Wait for description area to be present - use more generic selectors
		chromedp.WaitReady(`.description, .show-more-less-html, .jobs-description, [data-test-job-description]`, chromedp.ByQuery),
	); err != nil {
		fmt.Printf("Description area not found with standard selectors, trying expansion anyway: %v", err)
	}
	
	// Try to click "Show more" button if it exists - don't fail if it doesn't work
	if err = chromedp.Run(ctx,
		chromedp.Evaluate(s.buildExpandDescriptionScript(), nil),
		// Wait a brief moment for expansion to complete
		chromedp.Sleep(500*time.Millisecond),
	); err != nil {
		fmt.Printf("Warning: could not expand description: %v", err)
	}

	// Try to click insights button and extract skills
	fmt.Printf("🔍 Trying to click insights button...")
	
	insightsCtx, insightsCancel := context.WithTimeout(ctx, 5*time.Second)
	defer insightsCancel()
	
	if err := s.clickInsightsButton(insightsCtx); err != nil {
		fmt.Printf("Failed to click insights button (timeout or not found): %v", err)
	}

	// Scroll to ensure all content is loaded
	fmt.Printf("📜 Scrolling page to load all content...")
	if err = chromedp.Run(ctx,
		chromedp.Evaluate(`window.scrollTo(0, document.body.scrollHeight);`, nil),
		// Wait for any lazy-loaded content
		chromedp.Sleep(500*time.Millisecond),
		chromedp.Evaluate(`window.scrollTo(0, 0);`, nil), // Scroll back to top
		// Brief pause to ensure scroll is complete
		chromedp.Sleep(300*time.Millisecond),
	); err != nil {
		fmt.Printf("Warning: scrolling failed: %v", err)
	}

	// Extract job ID from URL
	fmt.Printf("🆔 Extracting job ID from URL...")
	fmt.Printf("Job URL: %s\n", jobURL)
	jobID := s.extractJobIDFromURL(jobURL)
	if jobID == "" {
		return nil, fmt.Errorf("could not extract job ID from URL: %s", jobURL)
	}

	// Extract job details using JavaScript with timeout
	fmt.Printf("📊 Extracting job data with JavaScript for job ID: %s", jobID)
	var jobData map[string]interface{}
	
	// Create a context with timeout for the JavaScript execution
	evalCtx, cancel := context.WithTimeout(ctx, 30*time.Second)
	defer cancel()
	
	err = chromedp.Run(evalCtx,
		chromedp.Evaluate(s.buildJobExtractionScript(), &jobData),
	)

	if err != nil {
		fmt.Printf("❌ JavaScript extraction failed: %v", err)
		// Try to get page source for debugging if extraction fails
		var pageSource string
		if pageErr := chromedp.Run(ctx, chromedp.OuterHTML("html", &pageSource)); pageErr == nil {
			if len(pageSource) > 1000 {
				fmt.Printf("Page source preview: %s...", pageSource[:1000])
			}
		}
		return nil, fmt.Errorf("JavaScript extraction failed: %w", err)
	}
	
	fmt.Printf("✅ JavaScript extraction completed, data keys: %v", getMapKeys(jobData))

	
		// Always show basic data overview
	fmt.Printf("📊 Job data overview: title=%q, company=%q, location=%q, description_length=%d, skills_count=%d",
		getStringValue(jobData, "title"),
		getStringValue(jobData, "company"),
		getStringValue(jobData, "location"),
		len(getStringValue(jobData, "description")),
		len(getSliceValue(jobData, "skills")))


	// Convert extracted data to JobPosting
	fmt.Printf("🔄 Converting extracted data to JobPosting...")
	result, err := s.convertToJobPosting(jobData, jobID, jobURL)
	if err != nil {
		return nil, fmt.Errorf("failed to convert job data: %w", err)
	}
	
	fmt.Printf("✅ Job extraction completed successfully for: %s", result.Title)
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

// formatJobDataForDebug formats job data for debug output with truncated description
func (s *LinkedInScraper) formatJobDataForDebug(jobData map[string]interface{}) string {
	// Create a copy of the data to avoid modifying the original
	debugData := make(map[string]interface{})
	for k, v := range jobData {
		debugData[k] = v
	}
	
	// Truncate description to first 300 characters if it exists
	if desc, ok := debugData["description"].(string); ok && len(desc) > 300 {
		debugData["description"] = desc[:300] + "..."
	}
	
	// Format as JSON
	jsonData, err := json.MarshalIndent(debugData, "", "  ")
	if err != nil {
		return fmt.Sprintf("Error formatting job data: %v", err)
	}
	
	return string(jsonData)
}

