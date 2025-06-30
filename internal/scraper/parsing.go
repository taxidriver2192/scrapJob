package scraper

import (
	"strings"
	"time"

	"linkedin-job-scraper/internal/models"
)

// convertToScrapedJob converts JavaScript extracted data to ScrapedJob struct
func (s *LinkedInScraper) convertToScrapedJob(jobData map[string]interface{}, jobID, jobURL string) (*models.ScrapedJob, error) {
	// Parse location data to extract applicants and posted date
	locationStr := getString(jobData, "location")
	location, postedDate, applicants := parseLocationInfo(locationStr)

	// Create the scraped job
	job := &models.ScrapedJob{
		LinkedInJobID: jobID,
		Title:         getString(jobData, "title"),
		CompanyName:   getString(jobData, "company"),
		Location:      location,
		Description:   getString(jobData, "description"),
		ApplyURL:      getString(jobData, "applyUrl"),
		PostedDate:    postedDate,
		Applicants:    applicants,
		WorkType:      getStringPointer(jobData, "workType"),
		Skills:        getSkillsPointer(jobData, "skills"),
	}

	return job, nil
}

// parseLocationInfo extracts location, posted date, and applicant count from LinkedIn location string
func parseLocationInfo(locationStr string) (location string, postedDate time.Time, applicants *int) {
	// Default to current time if no posted date found
	postedDate = time.Now()
	
	if locationStr == "" {
		return "", postedDate, nil
	}

	// Split by bullet points (·) to separate different pieces of information
	parts := strings.Split(locationStr, "·")
	
	// First part is usually the location
	if len(parts) > 0 {
		location = strings.TrimSpace(parts[0])
	}

	// Look for posted date and applicants in the remaining parts
	for _, part := range parts[1:] {
		part = strings.TrimSpace(part)
		
		// Check for posted date patterns
		if parsedDate := parseRelativeDate(part); parsedDate != nil {
			postedDate = *parsedDate
		}
		
		// Check for applicants count
		if parsedApplicants := parseApplicantsCount(part); parsedApplicants != nil {
			applicants = parsedApplicants
		}
	}

	return location, postedDate, applicants
}
