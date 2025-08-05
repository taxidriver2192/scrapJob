package scraper

import (
	"fmt"

	"linkedin-job-scraper/internal/models"

	"github.com/sirupsen/logrus"
)

// saveJob saves a job posting via the data service (API + cache)
func (s *LinkedInScraper) saveJob(jobPosting *models.JobPosting) error {
	// Ensure company exists and get its ID
	companyID, err := s.ensureCompanyExists(jobPosting.CompanyName, jobPosting.CompanyImageURL)
	if err != nil {
		return fmt.Errorf("failed to ensure company exists: %w", err)
	}
	
	// Set the company ID on the job
	jobPosting.CompanyID = companyID

	// Create job posting via DataService (includes company creation)
	_, err = s.dataService.CreateJob(jobPosting)
	if err != nil {
		return fmt.Errorf("failed to create job posting: %w", err)
	}

	logrus.Debugf("💾 Successfully saved job: %s at %s", jobPosting.Title, jobPosting.CompanyName)
	return nil
}

// ensureCompanyExists checks if a company exists and creates it if it doesn't
func (s *LinkedInScraper) ensureCompanyExists(companyName, companyImageURL string) (int64, error) {
	if companyName == "" {
		return 0, fmt.Errorf("company name cannot be empty")
	}
	
	// Add debug logging to show where we're getting the company name from
	logrus.Debugf("🏢 Processing company: name='%s', imageURL='%s'", companyName, companyImageURL)
	
	// Check if company exists
	exists, companyID, err := s.dataService.CompanyExists(companyName)
	if err != nil {
		return 0, fmt.Errorf("failed to check if company exists: %w", err)
	}
	
	if exists {
		logrus.Debugf("✅ Company already exists: %s (ID: %d)", companyName, companyID)
		return int64(companyID), nil
	}
	
	// Create company if it doesn't exist
	logrus.Debugf("🆕 Creating new company: %s with image: %s", companyName, companyImageURL)
	companyID, err = s.dataService.CreateCompany(companyName, companyImageURL)
	if err != nil {
		return 0, fmt.Errorf("failed to create company: %w", err)
	}
	
	return int64(companyID), nil
}
