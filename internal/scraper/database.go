package scraper

import (
	"fmt"

	"linkedin-job-scraper/internal/models"

	"github.com/sirupsen/logrus"
)

// saveJob saves a job posting to the database
func (s *LinkedInScraper) saveJob(jobPosting *models.JobPosting) error {
	// Check if job already exists (double-check for safety)
	// Note: This should rarely happen now due to early filtering in scrapePage
	exists, err := s.jobRepo.ExistsLinkedInJobID(jobPosting.LinkedInJobID)
	if err != nil {
		return fmt.Errorf("failed to check if job exists: %w", err)
	}

	if exists {
		logrus.Debugf("⏭️  Job %d already exists in database, skipping save", jobPosting.LinkedInJobID)
		return nil
	}

	// Get or create company using CompanyName (temporary field)
	company, err := s.companyRepo.CreateOrGet(jobPosting.CompanyName)
	if err != nil {
		return fmt.Errorf("failed to get/create company: %w", err)
	}

	// Set the CompanyID from the retrieved/created company
	jobPosting.CompanyID = company.CompanyID

	// Create job posting
	_, err = s.jobRepo.Create(jobPosting)
	if err != nil {
		return fmt.Errorf("failed to create job posting: %w", err)
	}

	logrus.Debugf("💾 Successfully saved job: %s at %s", jobPosting.Title, jobPosting.CompanyName)
	return nil
}
