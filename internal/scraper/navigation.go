package scraper

import (
	"context"
	"fmt"
	"strings"
	"time"

	"linkedin-job-scraper/internal/models"

	"github.com/chromedp/chromedp"
)

// buildSearchURL constructs the LinkedIn job search URL with additional filters
func (s *LinkedInScraper) buildSearchURL(keywords, location string, start int) string {
	baseURL := "https://www.linkedin.com/jobs/search/"
	params := fmt.Sprintf("?keywords=%s&location=%s&start=%d&distance=25&f_WT=1%%2C3&sortBy=DD",
		strings.ReplaceAll(keywords, " ", "%20"),
		strings.ReplaceAll(location, " ", "%20"),
		start,
	)
	return baseURL + params
}

// scrapePage scrapes a single page of job results
func (s *LinkedInScraper) scrapePage(ctx context.Context, url string, maxJobsFromPage int) ([]*models.JobPosting, error) {
	fmt.Printf("🌐 Navigating to: %s\n", url)
	
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
		fmt.Printf("Intelligent wait failed (%v), using fallback sleep\n", waitErr)
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
	
	fmt.Printf("📄 Page loaded: %s\n", pageTitle)

	// Check if login is required
	var hasLoginForm bool
	err = chromedp.Run(ctx,
		chromedp.Evaluate(s.buildHasLoginFormScript(), &hasLoginForm),
	)
	
	if err == nil && hasLoginForm {
		return nil, fmt.Errorf("redirected to login page - authentication may have expired")
	}

	// Wait for job results to load and extract URLs
	// Note: We no longer scroll as we use pagination via start parameter
	fmt.Println("🔍 Extracting job URLs from current page...")
	jobURLs, err := s.extractJobURLs(ctx)
	if err != nil {
		return nil, fmt.Errorf("failed to extract job URLs: %w", err)
	}

	if len(jobURLs) == 0 {
		fmt.Println("⚠️  No job URLs found on this page")
		return []*models.JobPosting{}, nil
	}

	fmt.Printf("✅ Found %d job URLs on page\n", len(jobURLs))

	// Filter out jobs that already exist in database to avoid unnecessary scraping
	var filteredJobURLs []string
	var skippedCount int
	
	for _, jobURL := range jobURLs {
		// Extract LinkedIn job ID from URL
		jobID, err := extractLinkedInJobIDFromURL(jobURL)
		if err != nil {
			fmt.Printf("⚠️  Could not extract job ID from URL %s: %v\n", jobURL, err)
			// Include URL anyway - let the detailed scraper handle it
			filteredJobURLs = append(filteredJobURLs, jobURL)
			continue
		}
		
		// Check if job already exists in database
		exists, err := s.jobRepo.ExistsLinkedInJobID(jobID)
		if err != nil {
			fmt.Printf("⚠️  Failed to check if job %d exists: %v\n", jobID, err)
			// Include URL anyway to be safe
			filteredJobURLs = append(filteredJobURLs, jobURL)
			continue
		}
		
		if exists {
			fmt.Printf("⏭️  Job %d already exists, skipping URL scraping\n", jobID)
			skippedCount++
			continue
		}
		
		filteredJobURLs = append(filteredJobURLs, jobURL)
	}
	
	if skippedCount > 0 {
		fmt.Printf("⏭️  Skipped %d existing jobs, will scrape %d new jobs\n", skippedCount, len(filteredJobURLs))
	}

	// Limit to specified max jobs from this page if needed
	if len(filteredJobURLs) > maxJobsFromPage {
		filteredJobURLs = filteredJobURLs[:maxJobsFromPage]
		fmt.Printf("📊 Limited to %d jobs from this page\n", maxJobsFromPage)
	}

	// Scrape details from each job page
	var jobs []*models.JobPosting
	for i, jobURL := range filteredJobURLs {
		fmt.Printf("📋 Scraping job %d/%d: %s\n", i+1, len(filteredJobURLs), jobURL)
		
		job, err := s.scrapeJobDetails(ctx, jobURL)
		if err != nil {
			fmt.Printf("❌ Failed to scrape job details from %s: %v\n", jobURL, err)
			continue
		}
		
		if job != nil {
			jobs = append(jobs, job)
		}
		
		// Small delay between job detail requests to be respectful to LinkedIn
		// Only sleep if not the last job to optimize speed
		if i < len(filteredJobURLs)-1 {
			time.Sleep(500 * time.Millisecond) // Reduced from 1 second
		}
	}

	fmt.Printf("✅ Scraped %d job details from page\n", len(jobs))
	return jobs, nil
}
