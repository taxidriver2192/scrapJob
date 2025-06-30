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
		
		// Small delay between job detail requests
		time.Sleep(1 * time.Second)
	}

	logrus.Infof("✅ Scraped %d job details from page", len(jobs))
	return jobs, nil
}
