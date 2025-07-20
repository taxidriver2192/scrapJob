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
		return nil, fmt.Errorf("page analysis failed: %w", err)
	}
	
	if !jobResultsFound {
		// Get more detailed page analysis to understand what's happening
		var pageAnalysis map[string]interface{}
		chromedp.Run(ctx, chromedp.Evaluate(s.buildDetailedAnalysisScript(), &pageAnalysis))
		
		// Check if we have job links even without proper container
		if jobLinks, ok := pageAnalysis["jobLinksCount"].(float64); ok && jobLinks <= 0 {
			return []string{}, nil
		}
	}

	// Get job URLs from search results
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
	// Navigate to job detail page with timeout
	navCtx, navCancel := context.WithTimeout(ctx, 15*time.Second)
	defer navCancel()
	
	err := chromedp.Run(navCtx,
		chromedp.Navigate(jobURL),
	)
	if err != nil {
		return nil, fmt.Errorf("failed to navigate to job page: %w", err)
	}
	
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
		err = chromedp.Run(ctx, chromedp.Sleep(3*time.Second))
		if err != nil {
			return nil, fmt.Errorf("fallback wait failed: %w", err)
		}
	}
	
	// Wait for page to fully load and try to expand description if needed
	descWaitCtx, descCancel := context.WithTimeout(ctx, 5*time.Second)
	defer descCancel()
	
	chromedp.Run(descWaitCtx,
		chromedp.WaitReady(`.description, .show-more-less-html, .jobs-description, [data-test-job-description]`, chromedp.ByQuery),
	)
	
	// Try to click "Show more" button if it exists
	chromedp.Run(ctx,
		chromedp.Evaluate(s.buildExpandDescriptionScript(), nil),
		chromedp.Sleep(500*time.Millisecond),
	)

	// Try to click insights button and extract skills
	insightsCtx, insightsCancel := context.WithTimeout(ctx, 5*time.Second)
	defer insightsCancel()
	s.clickInsightsButton(insightsCtx)

	// Scroll to ensure all content is loaded
	chromedp.Run(ctx,
		chromedp.Evaluate(`window.scrollTo(0, document.body.scrollHeight);`, nil),
		chromedp.Sleep(500*time.Millisecond),
		chromedp.Evaluate(`window.scrollTo(0, 0);`, nil),
		chromedp.Sleep(300*time.Millisecond),
	)

	// Extract job ID from URL
	jobID := s.extractJobIDFromURL(jobURL)
	if jobID == "" {
		return nil, fmt.Errorf("could not extract job ID from URL: %s", jobURL)
	}

	// Extract job details using JavaScript with timeout
	var jobData map[string]interface{}
	evalCtx, cancel := context.WithTimeout(ctx, 30*time.Second)
	defer cancel()
	
	err = chromedp.Run(evalCtx,
		chromedp.Evaluate(s.buildJobExtractionScript(), &jobData),
	)
	if err != nil {
		return nil, fmt.Errorf("JavaScript extraction failed: %w", err)
	}

	// Convert extracted data to JobPosting
	result, err := s.convertToJobPosting(jobData, jobID, jobURL)
	if err != nil {
		return nil, fmt.Errorf("failed to convert job data: %w", err)
	}
	
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

