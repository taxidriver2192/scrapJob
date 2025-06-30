package scraper

import (
	"fmt"
	"strconv"

	"github.com/sirupsen/logrus"
	"linkedin-job-scraper/internal/models"
)

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
