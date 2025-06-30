package scraper

import (
	"strconv"
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

// parseRelativeDate parses relative date strings like "2 days ago", "1 week ago", etc.
func parseRelativeDate(dateStr string) *time.Time {
	dateStr = strings.ToLower(strings.TrimSpace(dateStr))
	now := time.Now()

	// Handle Danish time expressions
	if strings.Contains(dateStr, "siden") {
		// "3 dage siden" -> 3 days ago
		if strings.Contains(dateStr, "dag") {
			if strings.Contains(dateStr, "1 dag") || strings.Contains(dateStr, "en dag") {
				result := now.AddDate(0, 0, -1)
				return &result
			}
			// Extract number of days
			parts := strings.Fields(dateStr)
			for i, part := range parts {
				if num, err := strconv.Atoi(part); err == nil && i+1 < len(parts) && strings.Contains(parts[i+1], "dag") {
					result := now.AddDate(0, 0, -num)
					return &result
				}
			}
		}
		
		if strings.Contains(dateStr, "uge") {
			if strings.Contains(dateStr, "1 uge") || strings.Contains(dateStr, "en uge") {
				result := now.AddDate(0, 0, -7)
				return &result
			}
			// Extract number of weeks
			parts := strings.Fields(dateStr)
			for i, part := range parts {
				if num, err := strconv.Atoi(part); err == nil && i+1 < len(parts) && strings.Contains(parts[i+1], "uge") {
					result := now.AddDate(0, 0, -num*7)
					return &result
				}
			}
		}
		
		if strings.Contains(dateStr, "måned") {
			if strings.Contains(dateStr, "1 måned") || strings.Contains(dateStr, "en måned") {
				result := now.AddDate(0, -1, 0)
				return &result
			}
			// Extract number of months
			parts := strings.Fields(dateStr)
			for i, part := range parts {
				if num, err := strconv.Atoi(part); err == nil && i+1 < len(parts) && strings.Contains(parts[i+1], "måned") {
					result := now.AddDate(0, -num, 0)
					return &result
				}
			}
		}
	}

	// Handle English time expressions
	if strings.Contains(dateStr, "ago") {
		if strings.Contains(dateStr, "day") {
			if strings.Contains(dateStr, "1 day") || strings.Contains(dateStr, "a day") {
				result := now.AddDate(0, 0, -1)
				return &result
			}
			// Extract number of days
			parts := strings.Fields(dateStr)
			for i, part := range parts {
				if num, err := strconv.Atoi(part); err == nil && i+1 < len(parts) && strings.Contains(parts[i+1], "day") {
					result := now.AddDate(0, 0, -num)
					return &result
				}
			}
		}
		
		if strings.Contains(dateStr, "week") {
			if strings.Contains(dateStr, "1 week") || strings.Contains(dateStr, "a week") {
				result := now.AddDate(0, 0, -7)
				return &result
			}
			// Extract number of weeks
			parts := strings.Fields(dateStr)
			for i, part := range parts {
				if num, err := strconv.Atoi(part); err == nil && i+1 < len(parts) && strings.Contains(parts[i+1], "week") {
					result := now.AddDate(0, 0, -num*7)
					return &result
				}
			}
		}
		
		if strings.Contains(dateStr, "month") {
			if strings.Contains(dateStr, "1 month") || strings.Contains(dateStr, "a month") {
				result := now.AddDate(0, -1, 0)
				return &result
			}
			// Extract number of months
			parts := strings.Fields(dateStr)
			for i, part := range parts {
				if num, err := strconv.Atoi(part); err == nil && i+1 < len(parts) && strings.Contains(parts[i+1], "month") {
					result := now.AddDate(0, -num, 0)
					return &result
				}
			}
		}
	}

	return nil
}

// parseApplicantsCount extracts the number of applicants from strings like "57 ansøgere" or "123 applicants"
func parseApplicantsCount(applicantsStr string) *int {
	applicantsStr = strings.ToLower(strings.TrimSpace(applicantsStr))
	
	// Look for patterns like "X ansøgere" or "X applicants"
	if strings.Contains(applicantsStr, "ansøgere") || strings.Contains(applicantsStr, "applicants") {
		// Extract the number
		parts := strings.Fields(applicantsStr)
		for _, part := range parts {
			// Remove any non-numeric characters except numbers
			numStr := strings.Map(func(r rune) rune {
				if r >= '0' && r <= '9' {
					return r
				}
				return -1
			}, part)
			
			if numStr != "" {
				if num, err := strconv.Atoi(numStr); err == nil && num > 0 {
					return &num
				}
			}
		}
	}
	
	return nil
}

// getString safely extracts a string from map data
func getString(data map[string]interface{}, key string) string {
	if value, ok := data[key]; ok {
		if str, ok := value.(string); ok {
			return str
		}
	}
	return ""
}

// getSkillsPointer safely extracts skills array and converts to SkillsList pointer
func getSkillsPointer(data map[string]interface{}, key string) *models.SkillsList {
	if value, ok := data[key]; ok {
		if skillsInterface, ok := value.([]interface{}); ok && len(skillsInterface) > 0 {
			skills := make(models.SkillsList, len(skillsInterface))
			for i, skill := range skillsInterface {
				if skillStr, ok := skill.(string); ok {
					skills[i] = skillStr
				}
			}
			return &skills
		}
	}
	return nil
}
