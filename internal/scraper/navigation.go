package scraper

import (
	"context"
	"fmt"
	"strings"
	"time"

	"linkedin-job-scraper/internal/models"

	"github.com/chromedp/chromedp"
	"github.com/sirupsen/logrus"
)

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
func (s *LinkedInScraper) scrapePage(ctx context.Context, url string, jobsPerPage int) ([]*models.ScrapedJob, error) {
	logrus.Infof("🌐 Navigating to: %s", url)
	
	err := chromedp.Run(ctx,
		chromedp.Navigate(url),
	)
	
	if err != nil {
		return nil, fmt.Errorf("failed to navigate to page: %w", err)
	}
	
	// Try intelligent wait first, fallback to sleep if it fails
	waitErr := chromedp.Run(ctx,
		// More comprehensive selectors for job results pages
		chromedp.WaitReady(`
			.jobs-search__results-list, 
			.job-search-results-list,
			.jobs-search-results,
			.scaffold-layout__list,
			.no-results, 
			.error-page,
			main
		`, chromedp.ByQuery),
	)
	
	// If intelligent wait fails, use short fallback sleep
	if waitErr != nil {
		logrus.Debugf("Intelligent wait failed (%v), using fallback sleep", waitErr)
		err = chromedp.Run(ctx,
			chromedp.Sleep(3*time.Second), // Slightly longer for job search pages
		)
		if err != nil {
			return nil, fmt.Errorf("fallback wait failed: %w", err)
		}
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
		chromedp.Evaluate(s.buildHasLoginFormScript(), &hasLoginForm),
	)
	
	if err == nil && hasLoginForm {
		return nil, fmt.Errorf("redirected to login page - authentication may have expired")
	}

	// Scroll down to load more jobs dynamically
	logrus.Info("📜 Scrolling to load more jobs...")
	err = s.scrollToLoadMoreJobs(ctx)
	if err != nil {
		logrus.Warnf("⚠️  Failed to scroll and load more jobs: %v", err)
		// Continue anyway, we might still have some jobs
	}

	// Wait for job results to load and extract URLs
	jobURLs, err := s.extractJobURLs(ctx)
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
		if i >= jobsPerPage { // Limit to specified jobs per page to avoid rate limiting
			break
		}
		
		logrus.Infof("📋 Scraping job %d/%d: %s", i+1, minInt(len(jobURLs), jobsPerPage), jobURL)
		
		job, err := s.scrapeJobDetails(ctx, jobURL)
		if err != nil {
			logrus.Errorf("❌ Failed to scrape job details from %s: %v", jobURL, err)
			continue
		}
		
		if job != nil {
			jobs = append(jobs, job)
		}
		
		// Small delay between job detail requests to be respectful to LinkedIn
		// Only sleep if not the last job to optimize speed
		if i < len(jobURLs)-1 {
			time.Sleep(500 * time.Millisecond) // Reduced from 1 second
		}
	}

	logrus.Infof("✅ Scraped %d job details from page", len(jobs))
	return jobs, nil
}

// scrollToLoadMoreJobs scrolls down on job search page to load more jobs dynamically
func (s *LinkedInScraper) scrollToLoadMoreJobs(ctx context.Context) error {
	// First, try to find the proper job list using the stable container and specific selectors
	var scrollTarget string
	var scrollTargetFound bool
	
	err := chromedp.Run(ctx,
		chromedp.Evaluate(`
			// Method 1: Find UL containing job items inside scaffold-layout__list
			const container = document.querySelector('.scaffold-layout__list');
			if (container) {
				// Try to find UL with job items using data-occludable-job-id
				let jobListItem = container.querySelector('ul li[data-occludable-job-id]');
				if (jobListItem) {
					return 'job-list-ul'; // Found the job list UL
				}
				
				// Fallback: Find first UL in container
				let jobListUL = container.querySelector('ul');
				if (jobListUL) {
					return 'container-ul'; // Found a UL in container
				}
				
				return 'container'; // Use the container itself
			}
			
			return 'window'; // Use window scroll as last fallback
		`, &scrollTarget),
	)
	if err != nil {
		logrus.Warn("⚠️ Failed to determine scroll target, using window fallback")
		scrollTarget = "window"
	}

	logrus.Infof("📍 Using scroll target: %s", scrollTarget)

	// Get initial job count
	var initialJobCount int
	err = chromedp.Run(ctx,
		chromedp.Evaluate(`document.querySelectorAll('a[href*="/jobs/view/"]').length`, &initialJobCount),
	)
	if err != nil {
		return fmt.Errorf("failed to get initial job count: %w", err)
	}

	logrus.Infof("📊 Initial job count: %d", initialJobCount)

	// Scroll down multiple times to load more jobs
	maxScrolls := 10
	stableCount := 0

	for i := 0; i < maxScrolls; i++ {
		// Use different scroll strategies based on what we found
		switch scrollTarget {
		case "job-list-ul":
			err = chromedp.Run(ctx,
				chromedp.Evaluate(`
					const container = document.querySelector('.scaffold-layout__list');
					const jobListItem = container ? container.querySelector('ul li[data-occludable-job-id]') : null;
					if (jobListItem) {
						const ul = jobListItem.closest('ul');
						if (ul) {
							ul.scrollTop = ul.scrollHeight;
							return true;
						}
					}
					return false;
				`, &scrollTargetFound),
			)
		case "container-ul":
			err = chromedp.Run(ctx,
				chromedp.Evaluate(`
					const container = document.querySelector('.scaffold-layout__list');
					const ul = container ? container.querySelector('ul') : null;
					if (ul) {
						ul.scrollTop = ul.scrollHeight;
						return true;
					}
					return false;
				`, &scrollTargetFound),
			)
		case "container":
			err = chromedp.Run(ctx,
				chromedp.Evaluate(`
					const container = document.querySelector('.scaffold-layout__list');
					if (container) {
						container.scrollTop = container.scrollHeight;
						return true;
					}
					return false;
				`, &scrollTargetFound),
			)
		default:
			// Use window scroll
			err = chromedp.Run(ctx,
				chromedp.Evaluate(`window.scrollTo(0, document.body.scrollHeight); true;`, &scrollTargetFound),
			)
		}

		if err != nil {
			logrus.Warnf("⚠️ Scroll attempt %d failed: %v", i+1, err)
			continue
		}

		// Wait for new jobs to load
		chromedp.Run(ctx, chromedp.Sleep(2*time.Second))

		// Check new job count
		var newJobCount int
		err = chromedp.Run(ctx,
			chromedp.Evaluate(`document.querySelectorAll('a[href*="/jobs/view/"]').length`, &newJobCount),
		)
		if err != nil {
			continue
		}

		logrus.Infof("📊 After scroll %d: %d jobs found", i+1, newJobCount)

		// If no new jobs were loaded, increment stable counter
		if newJobCount == initialJobCount {
			stableCount++
			if stableCount >= 2 {
				logrus.Info("📄 No more jobs loading, stopping scroll")
				break
			}
		} else {
			stableCount = 0
			initialJobCount = newJobCount
		}

		// Check for "Show more results" button
		var hasShowMoreButton bool
		err = chromedp.Run(ctx,
			chromedp.Evaluate(`
				const showMoreBtn = document.querySelector('button[aria-label*="Show more"], button[data-tracking-control-name*="show_more"]');
				if (showMoreBtn && showMoreBtn.style.display !== 'none' && !showMoreBtn.disabled) {
					showMoreBtn.click();
					true;
				} else {
					false;
				}
			`, &hasShowMoreButton),
		)
		if err == nil && hasShowMoreButton {
			logrus.Info("🔲 Clicked 'Show more results' button")
			chromedp.Run(ctx, chromedp.Sleep(3*time.Second))
		}
	}

	// Scroll back to top using the same target
	switch scrollTarget {
	case "job-list-ul":
		chromedp.Run(ctx,
			chromedp.Evaluate(`
				const container = document.querySelector('.scaffold-layout__list');
				const jobListItem = container ? container.querySelector('ul li[data-occludable-job-id]') : null;
				if (jobListItem) {
					const ul = jobListItem.closest('ul');
					if (ul) ul.scrollTop = 0;
				}
			`, nil),
		)
	case "container-ul":
		chromedp.Run(ctx,
			chromedp.Evaluate(`
				const container = document.querySelector('.scaffold-layout__list');
				const ul = container ? container.querySelector('ul') : null;
				if (ul) ul.scrollTop = 0;
			`, nil),
		)
	case "container":
		chromedp.Run(ctx,
			chromedp.Evaluate(`
				const container = document.querySelector('.scaffold-layout__list');
				if (container) container.scrollTop = 0;
			`, nil),
		)
	default:
		chromedp.Run(ctx, chromedp.Evaluate(`window.scrollTo(0, 0);`, nil))
	}

	chromedp.Run(ctx, chromedp.Sleep(1*time.Second))

	// Get final job count
	var finalJobCount int
	err = chromedp.Run(ctx,
		chromedp.Evaluate(`document.querySelectorAll('a[href*="/jobs/view/"]').length`, &finalJobCount),
	)
	if err == nil {
		logrus.Infof("📊 Final job count after scrolling: %d", finalJobCount)
	}

	return nil
}
