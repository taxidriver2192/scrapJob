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
		chromedp.Evaluate(`
			(function() {
				console.log('=== DEBUGGING JOB RESULTS PAGE ===');
				
				// Check various indicators that this is a job search page
				const url = window.location.href;
				const title = document.title;
				const isJobSearchPage = url.includes('/jobs/search');
				
				console.log('Current URL:', url);
				console.log('Page title:', title);
				console.log('Is job search page:', isJobSearchPage);
				
				// Check if logged in
				const hasUserMenu = document.querySelector('[data-tracking-control-name*="nav.feed"]') !== null;
				console.log('Appears to be logged in:', hasUserMenu);
				
				console.log('=== END DEBUGGING ===');
				
				// Look for job results container
				const containerSelectors = [
					'.jobs-search-results-list',
					'.jobs-search__results-list',
					'[data-total-results]',
					'.search-results-container',
					'ul.jobs-search__results-list'
				];
				
				let foundContainer = false;
				for (const selector of containerSelectors) {
					const container = document.querySelector(selector);
					if (container) {
						console.log('Found job results container with selector:', selector);
						foundContainer = true;
						break;
					}
				}
				
				// Count total job links as backup
				const totalJobLinks = document.querySelectorAll('a[href*="/jobs/view/"]').length;
				console.log('Total job links found:', totalJobLinks);
				
				return foundContainer || totalJobLinks > 0;
			})();
		`, &jobResultsFound),
	)

	if err != nil || !jobResultsFound {
		logrus.Warn("⚠️  Job results container not found - analyzing page...")
		
		// Get more detailed page analysis
		var pageAnalysis map[string]interface{}
		chromedp.Run(ctx, chromedp.Evaluate(`
			(function() {
				return {
					url: window.location.href,
					title: document.title,
					bodyClasses: document.body ? document.body.className : 'no body',
					mainFound: document.querySelector('main') !== null,
					jobLinksCount: document.querySelectorAll('a[href*="/jobs/view/"]').length,
					hasLoginForm: document.querySelector('input[name="session_key"]') !== null,
					pageText: document.body ? document.body.innerText.substring(0, 500) : 'no body text'
				};
			})();
		`, &pageAnalysis))
		
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
		chromedp.Evaluate(`
			(function() {
				console.log('=== EXTRACTING JOB URLs ===');
				
				// Try multiple selectors for job links
				const linkSelectors = [
					'a[href*="/jobs/view/"]',
					'[data-occludable-job-id] a',
					'.job-search-card a[href*="/jobs/view/"]',
					'.result-card a[href*="/jobs/view/"]',
					'[data-tracking-control-name*="job-result-card"] a'
				];
				
				let allLinks = [];
				
				for (const selector of linkSelectors) {
					const links = document.querySelectorAll(selector);
					console.log('Selector', selector, 'found', links.length, 'links');
					
					for (const link of links) {
						if (link.href && link.href.includes('/jobs/view/')) {
							// Clean the URL - remove any tracking parameters and fragments
							let cleanURL = link.href.split('?')[0].split('#')[0];
							if (!allLinks.includes(cleanURL)) {
								allLinks.push(cleanURL);
								console.log('Found job URL:', cleanURL);
							}
						}
					}
				}
				
				console.log('Total unique job URLs extracted:', allLinks.length);
				return allLinks;
			})();
		`, &jobURLs),
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
		chromedp.Evaluate(`
			(function() {
				const showMoreButtons = document.querySelectorAll('button[aria-expanded="false"]');
				for (const button of showMoreButtons) {
					if (button.innerText && (button.innerText.includes('Show more') || button.innerText.includes('Se mere'))) {
						console.log('Clicking show more button:', button.innerText);
						button.click();
						return true;
					}
				}
				
				// Also try alternative selectors for show more
				const moreButtons = document.querySelectorAll('.jobs-description-content__toggle, .show-more-less-html__button, [data-tracking-control-name*="show_more"]');
				for (const button of moreButtons) {
					console.log('Clicking description toggle button');
					button.click();
					return true;
				}
				
				return false;
			})();
		`, nil),
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
