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

// buildJobExtractionScript returns the complete JavaScript for extracting job data
func (s *LinkedInScraper) buildJobExtractionScript() string {
	return `
		(function() {
			console.log('Scraping job details from:', window.location.href);
			
			// Job Title
			const getTitleText = function() {
				const selectors = [
					'h1.topcard__title',
					'div[data-job-id] .job-details-headline__title',
					'h1.t-24.t-bold.inline',
					'.job-details-jobs-unified-top-card__job-title h1'
				];
				for (const sel of selectors) {
					const elem = document.querySelector(sel);
					if (elem && elem.innerText) {
						console.log('Found title with selector:', sel, 'text:', elem.innerText.trim());
						return elem.innerText.trim();
					}
				}
				console.log('No title found');
				return '';
			};
			
			// Company Name
			const getCompanyText = function() {
				const selectors = [
					'a.topcard__org-name-link',
					'span.topcard__flavor-row > a',
					'.job-details-jobs-unified-top-card__company-name a',
					'.job-details-jobs-unified-top-card__company-name'
				];
				for (const sel of selectors) {
					const elem = document.querySelector(sel);
					if (elem && elem.innerText) {
						console.log('Found company with selector:', sel, 'text:', elem.innerText.trim());
						return elem.innerText.trim();
					}
				}
				console.log('No company found');
				return '';
			};
			
			// Location with applicants and posted date parsing
			const getLocationData = function() {
				const selectors = [
					'span.topcard__flavor-row--bullet',
					'span[aria-label^="Location"]',
					'.job-details-jobs-unified-top-card__primary-description-container .t-black--light',
					'.topcard__flavor-row .topcard__flavor--bullet',
					'.job-details-jobs-unified-top-card__primary-description-container span',
					'.topcard__flavor-row span'
				];
				
				for (const sel of selectors) {
					const elem = document.querySelector(sel);
					if (elem && elem.innerText) {
						const text = elem.innerText.trim();
						console.log('Found location element with selector:', sel, 'text:', text);
						
						// Check if this contains location + time + applicants info
						if (text.includes('·') && (text.includes('siden') || text.includes('ago') || text.includes('ansøgere') || text.includes('applicants'))) {
							console.log('✅ Found full location data:', text);
							return text;
						}
						
						// Filter out non-location text but keep substantial location info
						if (!text.includes('employees') && !text.includes('followers') && text.length > 2) {
							console.log('Found basic location with selector:', sel, 'text:', text);
							// Keep searching for better location data, but save this as fallback
							if (!window.locationFallback) {
								window.locationFallback = text;
							}
						}
					}
				}
				
				console.log('No comprehensive location data found, using fallback:', window.locationFallback || '');
				return window.locationFallback || '';
			};
			
			// Description
			const getDescriptionText = function() {
				const selectors = [
					'div.description__text',
					'div#job-details > span.formatted-content',
					'.job-details-jobs-unified-top-card__job-description',
					'.jobs-description-content__text',
					'.jobs-description__content',
					'.show-more-less-html__markup',
					'.jobs-box__html-content'
				];
				
				for (const sel of selectors) {
					const elem = document.querySelector(sel);
					if (elem && elem.innerText && elem.innerText.trim().length > 50) {
						console.log('✅ Found description with selector:', sel, 'length:', elem.innerText.length);
						return elem.innerText.trim();
					}
				}
				
				console.log('❌ No description found');
				return '';
			};
			
			// Apply URL
			const getApplyUrl = function() {
				const selectors = [
					'a.apply-button',
					'a[data-tracking-control-name="public_jobs_apply_action"]',
					'.job-details-jobs-unified-top-card__container--two-pane a[href*="apply"]'
				];
				for (const sel of selectors) {
					const elem = document.querySelector(sel);
					if (elem && elem.href) {
						console.log('Found apply URL with selector:', sel, 'url:', elem.href);
						return elem.href;
					}
				}
				// Fallback to current URL if no specific apply URL found
				console.log('No apply URL found, using current URL');
				return window.location.href;
			};
			
			// Get work type and skills
			` + s.buildSkillsExtractionScript() + `
			const workTypeAndSkills = getWorkTypeAndSkills();
			
			const result = {
				title: getTitleText(),
				company: getCompanyText(),
				location: getLocationData(),
				description: getDescriptionText(),
				applyUrl: getApplyUrl(),
				workType: workTypeAndSkills.workType,
				skills: workTypeAndSkills.skills
			};
			
			console.log('=== EXTRACTION COMPLETE ===');
			console.log('Final result:', result);
			
			return result;
		})();
	`
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
