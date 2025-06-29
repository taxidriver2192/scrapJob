package scraper

import (
	"context"
	"fmt"
	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/database"
	"linkedin-job-scraper/internal/models"
	"regexp"
	"strconv"
	"strings"
	"time"

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
func (s *LinkedInScraper) ScrapeJobs(keywords, location string, maxPages int) error {
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
		
		jobs, err := s.scrapePage(ctx, pageURL)
		if err != nil {
			logrus.Errorf("❌ Failed to scrape page %d: %v", page+1, err)
			continue
		}

		if len(jobs) == 0 {
			logrus.Warnf("⚠️  No jobs found on page %d, stopping", page+1)
			break
		}

		// Save jobs to database
		savedCount := 0
		for _, job := range jobs {
			if err := s.saveJob(job); err != nil {
				logrus.Errorf("❌ Failed to save job %d: %v", job.LinkedInJobID, err)
			} else {
				savedCount++
			}
		}

		totalJobs += savedCount
		logrus.Infof("✅ Page %d complete: Found %d jobs, saved %d jobs", page+1, len(jobs), savedCount)

		// Add delay between pages
		if page < maxPages-1 {
			logrus.Infof("⏱️  Waiting %d seconds before next page...", s.config.Scraper.DelayBetweenRequests)
			time.Sleep(time.Duration(s.config.Scraper.DelayBetweenRequests) * time.Second)
		}
	}

	logrus.Infof("Scraping completed. Total jobs saved: %d", totalJobs)
	return nil
}

// login performs LinkedIn login
func (s *LinkedInScraper) login(ctx context.Context) error {
	logrus.Info("🔐 Checking if already logged in to LinkedIn...")

	var isLoggedIn bool
	
	// Check if already logged in by navigating to LinkedIn and checking for login form
	err := chromedp.Run(ctx,
		chromedp.Navigate("https://www.linkedin.com/login"),
		chromedp.WaitVisible(`body`, chromedp.ByQuery),
		chromedp.Sleep(2*time.Second), // Give page time to load
		chromedp.Evaluate(`document.querySelector('input[name="session_key"]') === null`, &isLoggedIn),
	)
	
	if err != nil {
		return fmt.Errorf("failed to check login status: %w", err)
	}

	if isLoggedIn {
		logrus.Info("✅ Already logged in to LinkedIn")
		return nil
	}

	// Perform login
	logrus.Info("🔑 Performing login...")
	if s.config.LinkedIn.Email == "" || s.config.LinkedIn.Password == "" {
		return fmt.Errorf("LinkedIn credentials not provided in config")
	}

	err = chromedp.Run(ctx,
		chromedp.WaitVisible(`input[name="session_key"]`, chromedp.ByQuery),
		chromedp.SendKeys(`input[name="session_key"]`, s.config.LinkedIn.Email, chromedp.ByQuery),
		chromedp.SendKeys(`input[name="session_password"]`, s.config.LinkedIn.Password, chromedp.ByQuery),
		chromedp.Click(`button[type="submit"]`, chromedp.ByQuery),
		chromedp.Sleep(3*time.Second), // Wait for login to process
	)

	if err != nil {
		return fmt.Errorf("login process failed: %w", err)
	}

	// Verify login was successful
	var loginSuccess bool
	err = chromedp.Run(ctx,
		chromedp.Evaluate(`!document.querySelector('input[name="session_key"]')`, &loginSuccess),
	)

	if err != nil || !loginSuccess {
		return fmt.Errorf("login verification failed - check credentials")
	}

	logrus.Info("✅ Successfully logged in to LinkedIn")
	return nil
}

// buildSearchURL constructs the LinkedIn job search URL
func (s *LinkedInScraper) buildSearchURL(keywords, location string, start int) string {
	baseURL := "https://www.linkedin.com/jobs/search/"
	params := fmt.Sprintf("?keywords=%s&location=%s&start=%d",
		strings.ReplaceAll(keywords, " ", "%20"),
		strings.ReplaceAll(location, " ", "%20"),
		start,
	)
	return baseURL + params
}

// scrapePage scrapes a single page of job results
func (s *LinkedInScraper) scrapePage(ctx context.Context, url string) ([]*models.ScrapedJob, error) {
	logrus.Infof("🌐 Navigating to: %s", url)
	
	err := chromedp.Run(ctx,
		chromedp.Navigate(url),
		chromedp.Sleep(3*time.Second), // Wait for page to load
	)

	if err != nil {
		return nil, fmt.Errorf("failed to navigate to page: %w", err)
	}

	// Check if we're on the jobs page
	var pageTitle string
	err = chromedp.Run(ctx,
		chromedp.Title(&pageTitle),
	)
	
	if err != nil {
		return nil, fmt.Errorf("failed to get page title: %w", err)
	}
	
	logrus.Infof("📄 Page loaded: %s", pageTitle)

	// Check if login is required
	var hasLoginForm bool
	err = chromedp.Run(ctx,
		chromedp.Evaluate(`document.querySelector('input[name="session_key"]') !== null`, &hasLoginForm),
	)
	
	if err == nil && hasLoginForm {
		return nil, fmt.Errorf("redirected to login page - authentication may have expired")
	}

	// Wait for job results to load
	logrus.Info("⏳ Waiting for job results to load...")
	
	var jobResultsFound bool
	timeoutCtx, timeoutCancel := context.WithTimeout(ctx, 15*time.Second)
	defer timeoutCancel()
	
	err = chromedp.Run(timeoutCtx,
		chromedp.Sleep(3*time.Second),
		chromedp.Evaluate(`
			(function() {
				console.log('=== DEBUGGING PAGE STRUCTURE ===');
				console.log('URL:', window.location.href);
				console.log('Title:', document.title);
				
				// Check for various possible containers
				const selectors = [
					'.jobs-search__results-list',
					'.jobs-search-results-list', 
					'.scaffold-layout__list-container',
					'[data-testid="job-search-results-list"]',
					'main[role="main"]',
					'.scaffold-layout__list',
					'.jobs-search-results__list',
					'ul[role="list"]'
				];
				
				let foundContainer = false;
				for (const selector of selectors) {
					const element = document.querySelector(selector);
					if (element) {
						console.log('✅ Found container with selector:', selector);
						console.log('Container innerHTML length:', element.innerHTML.length);
						foundContainer = true;
					} else {
						console.log('❌ No container found with selector:', selector);
					}
				}
				
				// Check for job links regardless of container
				const jobLinkSelectors = [
					'a[href*="/jobs/view/"]',
					'a[data-tracking-control-name*="job"]',
					'[data-occludable-job-id]'
				];
				
				let totalJobLinks = 0;
				for (const selector of jobLinkSelectors) {
					const links = document.querySelectorAll(selector);
					console.log('Found', links.length, 'elements with selector:', selector);
					if (selector === 'a[href*="/jobs/view/"]') {
						totalJobLinks = links.length;
						// Log first few URLs for debugging
						for (let i = 0; i < Math.min(3, links.length); i++) {
							console.log('Job URL', i+1, ':', links[i].href);
						}
					}
				}
				
				// Check for any other possible job containers
				const possibleContainers = document.querySelectorAll('[class*="job"], [class*="result"], [data-job], [data-occludable]');
				console.log('Found', possibleContainers.length, 'elements with job-related classes or attributes');
				
				// Log main element classes for debugging
				const main = document.querySelector('main');
				if (main) {
					console.log('Main element classes:', main.className);
					console.log('Main element children count:', main.children.length);
				}
				
				// Check if we're on the right page
				const isJobSearchPage = window.location.href.includes('/jobs/search');
				console.log('Is job search page:', isJobSearchPage);
				
				// Check if logged in
				const hasUserMenu = document.querySelector('[data-tracking-control-name*="nav.feed"]') !== null;
				console.log('Appears to be logged in:', hasUserMenu);
				
				console.log('=== END DEBUGGING ===');
				
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
			return []*models.ScrapedJob{}, nil
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
				
				const urls = [];
				let foundLinks = [];
				
				for (const selector of linkSelectors) {
					const links = document.querySelectorAll(selector);
					console.log('Selector', selector, 'found', links.length, 'links');
					
					if (links.length > 0) {
						foundLinks = foundLinks.concat(Array.from(links));
					}
				}
				
				// Remove duplicates and extract clean URLs
				const processedUrls = new Set();
				
				for (const link of foundLinks) {
					if (link.href && link.href.includes('/jobs/view/')) {
						// Extract clean job URL without query parameters
						const match = link.href.match(/(https:\/\/[^\/]+\/jobs\/view\/\d+)/);
						if (match) {
							processedUrls.add(match[1]);
							console.log('Added job URL:', match[1]);
						}
					}
				}
				
				const finalUrls = Array.from(processedUrls);
				console.log('=== FINAL RESULT: Found', finalUrls.length, 'unique job URLs ===');
				
				return finalUrls;
			})();
		`, &jobURLs),
	)

	if err != nil {
		return nil, fmt.Errorf("failed to extract job URLs: %w", err)
	}

	if len(jobURLs) == 0 {
		logrus.Warn("⚠️  No job URLs found on this page")
		return []*models.ScrapedJob{}, nil
	}

	logrus.Infof("✅ Found %d job URLs on page", len(jobURLs))

	// Scrape details from each job page
	var jobs []*models.ScrapedJob
	for i, jobURL := range jobURLs {
		if i >= 10 { // Limit to first 10 jobs per page to avoid rate limiting
			break
		}
		
		logrus.Infof("📋 Scraping job %d/%d: %s", i+1, len(jobURLs), jobURL)
		
		job, err := s.scrapeJobDetails(ctx, jobURL)
		if err != nil {
			logrus.Errorf("❌ Failed to scrape job details from %s: %v", jobURL, err)
			continue
		}
		
		if job != nil {
			jobs = append(jobs, job)
		}
		
		// Small delay between job detail requests
		time.Sleep(1 * time.Second)
	}
	
	logrus.Infof("📊 Successfully scraped %d jobs from page", len(jobs))
	return jobs, nil
}

// scrapeJobDetails scrapes detailed information from a single job page
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

	// Try to click the job insights button to open skills modal
	var skillsModalOpened bool
	err = chromedp.Run(ctx,
		chromedp.Evaluate(`
			(function() {
				// Look for the job insights button
				const insightButtons = document.querySelectorAll('.job-details-jobs-unified-top-card__job-insight-text-button, button[aria-label*="kvalifikation"], button[aria-label*="qualification"]');
				for (const button of insightButtons) {
					console.log('Found job insight button:', button.innerText || button.getAttribute('aria-label'));
					button.click();
					return true;
				}
				
				// Also try more generic selectors for insights
				const genericButtons = document.querySelectorAll('button[class*="job-insight"], button[class*="qualification"]');
				for (const button of genericButtons) {
					if (button.innerText && (button.innerText.includes('kompetenc') || button.innerText.includes('skill') || button.innerText.includes('kvalifik'))) {
						console.log('Clicking generic insight button:', button.innerText);
						button.click();
						return true;
					}
				}
				
				console.log('No job insight button found');
				return false;
			})();
		`, &skillsModalOpened),
		chromedp.Sleep(2*time.Second), // Wait for modal to open
	)

	// Scroll down to ensure all content is loaded
	err = chromedp.Run(ctx,
		chromedp.Evaluate(`window.scrollTo(0, document.body.scrollHeight);`, nil),
		chromedp.Sleep(1*time.Second),
		chromedp.Evaluate(`window.scrollTo(0, 0);`, nil), // Scroll back to top
		chromedp.Sleep(1*time.Second),
	)

	// Extract job ID from URL
	jobID := ""
	if matches := strings.Split(jobURL, "/"); len(matches) > 0 {
		for _, part := range matches {
			if len(part) > 8 && strings.ContainsAny(part, "0123456789") {
				// Remove any query parameters
				if idx := strings.Index(part, "?"); idx != -1 {
					part = part[:idx]
				}
				jobID = part
				break
			}
		}
	}

	if jobID == "" {
		return nil, fmt.Errorf("could not extract job ID from URL: %s", jobURL)
	}

	// Extract job details using the selectors you provided
	var jobData map[string]interface{}
	err = chromedp.Run(ctx,
		chromedp.Evaluate(`
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
					
					// Try to find location info in broader elements
					const broadSelectors = [
						'.job-details-jobs-unified-top-card__primary-description-container',
						'.topcard__flavor-row',
						'.job-details-jobs-unified-top-card__company-name',
						'[data-tracking-control-name="public_jobs_topcard_company_location"]'
					];
					
					for (const sel of broadSelectors) {
						const elem = document.querySelector(sel);
						if (elem && elem.innerText) {
							const text = elem.innerText.trim();
							if (text.includes('·') && (text.includes('siden') || text.includes('ago') || text.includes('ansøgere') || text.includes('applicants'))) {
								console.log('✅ Found full location data in broader element:', text);
								return text;
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
						'.job-details-jobs-unified-top-card__job-description .jobs-description-content__text',
						'section[data-max-lines] .jobs-description-content__text',
						'.jobs-description .jobs-description-content__text',
						'[data-tracking-control-name="public_jobs_description"] .jobs-description-content__text',
						'.jobs-unified-top-card__content .jobs-description__content',
						'div[data-job-id] .jobs-description-content__text',
						'.show-more-less-html__markup',
						'.jobs-box__html-content'
					];
					
					console.log('=== SEARCHING FOR DESCRIPTION ===');
					
					for (const sel of selectors) {
						const elem = document.querySelector(sel);
						if (elem && elem.innerText && elem.innerText.trim().length > 50) {
							console.log('✅ Found description with selector:', sel, 'length:', elem.innerText.length);
							console.log('First 100 chars:', elem.innerText.trim().substring(0, 100));
							return elem.innerText.trim();
						} else if (elem) {
							console.log('⚠️ Found description element with selector:', sel, 'but text too short:', elem.innerText ? elem.innerText.length : 'no text');
						} else {
							console.log('❌ No element found with selector:', sel);
						}
					}
					
					// Try to find any element containing substantial text about the job
					console.log('=== TRYING FALLBACK DESCRIPTION SEARCH ===');
					const fallbackSelectors = [
						'[class*="description"]',
						'[class*="job-details"]',
						'[class*="job-content"]',
						'div[data-max-lines]',
						'section:has(.jobs-description)',
						'main div:has(p)'
					];
					
					for (const sel of fallbackSelectors) {
						try {
							const elements = document.querySelectorAll(sel);
							for (const elem of elements) {
								if (elem && elem.innerText && elem.innerText.trim().length > 100) {
									console.log('✅ Found fallback description with selector:', sel, 'length:', elem.innerText.length);
									return elem.innerText.trim();
								}
							}
						} catch (e) {
							console.log('Error with fallback selector:', sel, e.message);
						}
					}
					
					console.log('❌ No description found with any selector');
					console.log('Page structure analysis:');
					
					// Debug page structure
					const allDivs = document.querySelectorAll('div');
					let longestText = '';
					let longestLength = 0;
					
					for (const div of allDivs) {
						if (div.innerText && div.innerText.length > longestLength && div.innerText.length > 200) {
							longestLength = div.innerText.length;
							longestText = div.innerText.substring(0, 200);
						}
					}
					
					if (longestText) {
						console.log('Longest text found (first 200 chars):', longestText);
						console.log('Length:', longestLength);
					}
					
					return '';
				};
				
				// Apply URL
				const getApplyUrl = function() {
					const selectors = [
						'a.apply-button',
						'a[data-tracking-control-name="public_jobs_apply_action"]',
						'.job-details-jobs-unified-top-card__container--two-pane a[href*="apply"]',
						'a[href*="/jobs/view/"][href*="apply"]'
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
				
				// Posted Date
				const getPostedDate = function() {
					const selectors = [
						'span.posted-date',
						'div.posted-time > time',
						'.job-details-jobs-unified-top-card__primary-description time',
						'time[datetime]'
					];
					for (const sel of selectors) {
						const elem = document.querySelector(sel);
						if (elem) {
							// Try datetime attribute first
							const datetime = elem.getAttribute('datetime');
							if (datetime) {
								console.log('Found posted date with datetime:', datetime);
								return datetime;
							}
							// Then try text content
							if (elem.innerText) {
								console.log('Found posted date with text:', elem.innerText.trim());
								return elem.innerText.trim();
							}
						}
					}
					console.log('No posted date found');
					return '';
				};
				
				// Extract work type and skills from modal if available
				const getWorkTypeAndSkills = function() {
					const result = { workType: '', skills: [] };
					
					// Check if skills modal is open
					const modal = document.querySelector('.job-details-skill-match-modal, [role="dialog"]');
					if (!modal) {
						console.log('No skills modal found');
						return result;
					}
					
					console.log('=== EXTRACTING FROM SKILLS MODAL ===');
					
					// Extract work type from requirements
					const requirementsList = modal.querySelectorAll('.job-details-skill-match-modal__screening-questions-qualification-list-item');
					for (const item of requirementsList) {
						const text = item.innerText.toLowerCase();
						console.log('Checking requirement:', text);
						
						if (text.includes('fjernarbejde') || text.includes('remote')) {
							result.workType = 'Remote';
						} else if (text.includes('hybridarbejde') || text.includes('hybrid')) {
							result.workType = 'Hybrid';
						} else if (text.includes('arbejder på arbejdspladsen') || text.includes('on-site') || text.includes('arbejdspladsen')) {
							result.workType = 'On-site';
						}
					}
					
					// Extract skills from both matched and unmatched lists
					const skillElements = modal.querySelectorAll('.job-details-skill-match-status-list__matched-skill, .job-details-skill-match-status-list__unmatched-skill');
					for (const skillEl of skillElements) {
						// Look for the skill name in aria-label or text content
						const ariaLabel = skillEl.getAttribute('aria-label');
						if (ariaLabel) {
							// Extract skill name from aria-label like "Din profil viser, at du har C# som en kompetence"
							const skillMatch = ariaLabel.match(/(?:har|viser ikke)\s+([^.]+?)\s+som en kompetence/i);
							if (skillMatch) {
								const skillName = skillMatch[1].trim();
								if (skillName && !result.skills.includes(skillName)) {
									result.skills.push(skillName);
									console.log('Found skill:', skillName);
								}
							}
						}
						
						// Also try direct text content as fallback
						const skillTextEl = skillEl.querySelector('div[aria-label] div, .job-details-skill-match-status-list__skill-name');
						if (skillTextEl && skillTextEl.innerText) {
							const skillName = skillTextEl.innerText.trim();
							if (skillName && !result.skills.includes(skillName) && skillName.length > 0 && skillName.length < 50) {
								result.skills.push(skillName);
								console.log('Found skill from text:', skillName);
							}
						}
					}
					
					console.log('Work type extracted:', result.workType);
					console.log('Skills extracted:', result.skills);
					console.log('=== END MODAL EXTRACTION ===');
					
					return result;
				};
				
				// Get work type and skills
				const workTypeAndSkills = getWorkTypeAndSkills();
				
				const result = {
					title: getTitleText(),
					company: getCompanyText(),
					location: getLocationData(), // Changed from getLocationText() to getLocationData()
					description: getDescriptionText(),
					applyUrl: getApplyUrl(),
					postedDate: getPostedDate(),
					workType: workTypeAndSkills.workType,
					skills: workTypeAndSkills.skills
				};
				
				console.log('Extracted job data:', result);
				return result;
			})();
		`, &jobData),
	)

	if err != nil {
		return nil, fmt.Errorf("failed to extract job data: %w", err)
	}

	// Parse location info to extract posted date and applicants
	locationStr := getString(jobData, "location")
	parsedLocation, parsedPostedDate, parsedApplicants := parseLocationInfo(locationStr)
	
	// Use parsed location if available, otherwise use original
	finalLocation := parsedLocation
	if finalLocation == "" {
		finalLocation = locationStr
	}

	// Parse posted date - prefer parsed date from location, then explicit date field
	postedDate := time.Now() // Default to current date
	if parsedPostedDate != nil {
		postedDate = *parsedPostedDate
	} else if dateStr := getString(jobData, "postedDate"); dateStr != "" {
		// Try different date formats
		dateFormats := []string{
			"2006-01-02",
			time.RFC3339,
			"2006-01-02T15:04:05Z",
			"January 2, 2006",
		}
		
		for _, format := range dateFormats {
			if parsedDate, err := time.Parse(format, dateStr); err == nil {
				postedDate = parsedDate
				break
			}
		}
	}

	job := &models.ScrapedJob{
		LinkedInJobID: jobID,
		Title:         getString(jobData, "title"),
		CompanyName:   getString(jobData, "company"),
		Location:      finalLocation, // Use cleaned location
		Description:   getString(jobData, "description"),
		ApplyURL:      getString(jobData, "applyUrl"),
		PostedDate:    postedDate,
		Applicants:    parsedApplicants, // Add applicants count
		WorkType:      getStringPointer(jobData, "workType"), // Add work type
		Skills:        getSkillsPointer(jobData, "skills"),   // Add skills
	}

	// Validate that we got essential data
	if job.Title == "" && job.CompanyName == "" {
		return nil, fmt.Errorf("failed to extract essential job data (title and company both empty)")
	}

	return job, nil
}

// parseLocationInfo parses LinkedIn location string to extract posted date and applicants
// Examples: "København · 6 dage siden · 71 ansøgere"
//           "Den Europæiske Union · 2 uger siden · Mere end 100 ansøgere"
//           "Københavns Kommune, Region Hovedstaden, Danmark · 1 uge siden · 25 ansøgere"
func parseLocationInfo(locationStr string) (location string, postedDate *time.Time, applicants *int) {
	if locationStr == "" {
		return "", nil, nil
	}

	// Split by · separator
	parts := strings.Split(locationStr, "·")
	
	// First part is always location
	location = strings.TrimSpace(parts[0])
	
	// Look for posted date and applicants in remaining parts
	for _, part := range parts[1:] {
		part = strings.TrimSpace(part)
		
		// Parse Danish relative dates
		if strings.Contains(part, "siden") || strings.Contains(part, "ago") {
			if parsedDate := parseRelativeDate(part); parsedDate != nil {
				postedDate = parsedDate
			}
		}
		
		// Parse applicants count
		if strings.Contains(part, "ansøgere") || strings.Contains(part, "applicants") {
			if count := parseApplicantsCount(part); count != nil {
				applicants = count
			}
		}
	}
	
	return location, postedDate, applicants
}

// parseRelativeDate converts Danish relative dates to actual dates
// Examples: "6 dage siden", "2 uger siden", "1 måned siden"
func parseRelativeDate(dateStr string) *time.Time {
	dateStr = strings.ToLower(strings.TrimSpace(dateStr))
	
	// Regular expressions for different date formats
	patterns := []struct {
		regex *regexp.Regexp
		unit  string
	}{
		{regexp.MustCompile(`(\d+)\s*dag[e]?\s*siden`), "days"},
		{regexp.MustCompile(`(\d+)\s*uge[r]?\s*siden`), "weeks"}, 
		{regexp.MustCompile(`(\d+)\s*måned[er]?\s*siden`), "months"},
		{regexp.MustCompile(`(\d+)\s*år\s*siden`), "years"},
		{regexp.MustCompile(`(\d+)\s*day[s]?\s*ago`), "days"},
		{regexp.MustCompile(`(\d+)\s*week[s]?\s*ago`), "weeks"},
		{regexp.MustCompile(`(\d+)\s*month[s]?\s*ago`), "months"},
		{regexp.MustCompile(`(\d+)\s*year[s]?\s*ago`), "years"},
	}
	
	for _, pattern := range patterns {
		if matches := pattern.regex.FindStringSubmatch(dateStr); len(matches) > 1 {
			if count, err := strconv.Atoi(matches[1]); err == nil {
				now := time.Now()
				var result time.Time
				
				switch pattern.unit {
				case "days":
					result = now.AddDate(0, 0, -count)
				case "weeks":
					result = now.AddDate(0, 0, -count*7)
				case "months":
					result = now.AddDate(0, -count, 0)
				case "years":
					result = now.AddDate(-count, 0, 0)
				}
				
				return &result
			}
		}
	}
	
	return nil
}

// parseApplicantsCount extracts number of applicants from text
// Examples: "71 ansøgere", "Mere end 100 ansøgere", "Over 200 applicants"
func parseApplicantsCount(applicantsStr string) *int {
	applicantsStr = strings.ToLower(strings.TrimSpace(applicantsStr))
	
	// Handle "more than X" cases
	morePatterns := []*regexp.Regexp{
		regexp.MustCompile(`mere\s*end\s*(\d+)`),
		regexp.MustCompile(`over\s*(\d+)`),
		regexp.MustCompile(`more\s*than\s*(\d+)`),
	}
	
	for _, pattern := range morePatterns {
		if matches := pattern.FindStringSubmatch(applicantsStr); len(matches) > 1 {
			if count, err := strconv.Atoi(matches[1]); err == nil {
				// For "more than X", we use X as minimum estimate
				return &count
			}
		}
	}
	
	// Handle exact numbers
	exactPattern := regexp.MustCompile(`(\d+)\s*(?:ansøgere|applicants)`)
	if matches := exactPattern.FindStringSubmatch(applicantsStr); len(matches) > 1 {
		if count, err := strconv.Atoi(matches[1]); err == nil {
			return &count
		}
	}
	
	return nil
}

// Helper functions to safely get values from map
func getString(m map[string]interface{}, key string) string {
	if val, ok := m[key].(string); ok {
		return val
	}
	return ""
}

func getStringPointer(m map[string]interface{}, key string) *string {
	if val, ok := m[key].(string); ok && val != "" {
		return &val
	}
	return nil
}

func getSkillsPointer(m map[string]interface{}, key string) *models.SkillsList {
	if val, ok := m[key].([]interface{}); ok && len(val) > 0 {
		skills := make(models.SkillsList, len(val))
		for i, skill := range val {
			if skillStr, ok := skill.(string); ok {
				skills[i] = skillStr
			}
		}
		return &skills
	}
	return nil
}

// saveJob saves a scraped job to the database
func (s *LinkedInScraper) saveJob(scrapedJob *models.ScrapedJob) error {
	// Convert LinkedIn job ID from string to int64
	jobID, err := strconv.ParseInt(scrapedJob.LinkedInJobID, 10, 64)
	if err != nil {
		return fmt.Errorf("invalid LinkedIn job ID '%s': %w", scrapedJob.LinkedInJobID, err)
	}

	// Check if job already exists
	exists, err := s.jobRepo.ExistsLinkedInJobID(jobID)
	if err != nil {
		return fmt.Errorf("failed to check if job exists: %w", err)
	}

	if exists {
		logrus.Debugf("⏭️  Job %s already exists, skipping", scrapedJob.LinkedInJobID)
		return nil
	}

	// Get or create company
	company, err := s.companyRepo.CreateOrGet(scrapedJob.CompanyName)
	if err != nil {
		return fmt.Errorf("failed to get/create company: %w", err)
	}

	// Create job posting
	jobPosting := &models.JobPosting{
		LinkedInJobID: jobID,
		Title:         scrapedJob.Title,
		CompanyID:     company.CompanyID,
		Location:      scrapedJob.Location,
		Description:   scrapedJob.Description,
		ApplyURL:      scrapedJob.ApplyURL,
		PostedDate:    scrapedJob.PostedDate,
		Applicants:    scrapedJob.Applicants, // Add applicants count
		WorkType:      scrapedJob.WorkType,   // Add work type
		Skills:        scrapedJob.Skills,     // Add skills
	}

	_, err = s.jobRepo.Create(jobPosting)
	if err != nil {
		return fmt.Errorf("failed to create job posting: %w", err)
	}

	logrus.Debugf("💾 Successfully saved job: %s at %s", scrapedJob.Title, scrapedJob.CompanyName)
	return nil
}


