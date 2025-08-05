package scraper

import (
	"fmt"
	"strconv"
	"strings"
	"time"

	"linkedin-job-scraper/internal/models"
)

// convertToJobPosting converts JavaScript extracted data to JobPosting struct
func (s *LinkedInScraper) convertToJobPosting(jobData map[string]interface{}, jobIDStr, jobURL string) (*models.JobPosting, error) {
	// Convert LinkedIn job ID from string to int
	jobID, err := strconv.Atoi(jobIDStr)
	if err != nil {
		return nil, fmt.Errorf("invalid LinkedIn job ID '%s': %w", jobIDStr, err)
	}

	// Parse location data to extract applicants and posted date
	locationStr := getString(jobData, "location")
	location, postedDate, applicants := parseLocationInfo(locationStr)

	// Create the job posting (CompanyID will be set when saving to DB)
	job := &models.JobPosting{
		LinkedInJobID:   jobID,
		Title:           getString(jobData, "title"),
		CompanyName:     getString(jobData, "company"), // Temporary field for company name
		CompanyImageURL: getString(jobData, "companyImageUrl"), // Temporary field for company image URL
		Location:        location,
		Description:     getString(jobData, "description"),
		ApplyURL:        getString(jobData, "applyUrl"),
		PostedDate:      postedDate,
		Applicants:      applicants,
		WorkType:        getStringPointer(jobData, "workType"),
		Skills:          getSkillsPointer(jobData, "skills"),
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
