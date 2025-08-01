package scraper

import (
	"context"
	"fmt"
	"linkedin-job-scraper/internal/models"
	"regexp"
	"strconv"
	"strings"
	"time"

	"github.com/chromedp/chromedp"
)

func extractLinkedInJobIDFromURL(jobURL string) (int64, error) {
	// LinkedIn job URLs typically look like: 
	// https://www.linkedin.com/jobs/view/1234567890/
	// or https://www.linkedin.com/jobs/view/1234567890?...
	
	// Use regex to extract the job ID
	re := regexp.MustCompile(`/jobs/view/(\d+)`)
	matches := re.FindStringSubmatch(jobURL)
	
	if len(matches) < 2 {
		return 0, fmt.Errorf("could not extract job ID from URL: %s", jobURL)
	}
	
	jobID, err := strconv.ParseInt(matches[1], 10, 64)
	if err != nil {
		return 0, fmt.Errorf("invalid job ID '%s' in URL: %w", matches[1], err)
	}
	
	return jobID, nil
}

// Math utilities
func minInt(a, b int) int {
	if a < b {
		return a
	}
	return b
}

// Data extraction utilities
func getString(data map[string]interface{}, key string) string {
	if value, ok := data[key]; ok {
		if str, ok := value.(string); ok {
			return str
		}
	}
	return ""
}

func getStringPointer(data map[string]interface{}, key string) *string {
	if value, ok := data[key]; ok {
		if str, ok := value.(string); ok && str != "" {
			return &str
		}
	}
	return nil
}

func getIntPointer(data map[string]interface{}, key string) *int {
	if value, ok := data[key]; ok {
		if intVal, ok := value.(int); ok {
			return &intVal
		}
		// Handle float64 from JSON
		if floatVal, ok := value.(float64); ok {
			intVal := int(floatVal)
			return &intVal
		}
	}
	return nil
}

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

// Helper function to safely get string value from map
func getStringValue(m map[string]interface{}, key string) string {
	if val, ok := m[key]; ok {
		if str, ok := val.(string); ok {
			return str
		}
	}
	return ""
}

// Helper function to safely get slice value from map
func getSliceValue(m map[string]interface{}, key string) []interface{} {
	if val, ok := m[key]; ok {
		if slice, ok := val.([]interface{}); ok {
			return slice
		}
	}
	return []interface{}{}
}

// Date parsing utilities
func parseRelativeDate(dateStr string) *time.Time {
	dateStr = strings.ToLower(strings.TrimSpace(dateStr))
	now := time.Now()

	// Handle Danish time expressions
	if strings.Contains(dateStr, "siden") {
		if date := parseDanishRelativeDate(dateStr, now); date != nil {
			return date
		}
	}

	// Handle English time expressions
	if strings.Contains(dateStr, "ago") {
		if date := parseEnglishRelativeDate(dateStr, now); date != nil {
			return date
		}
	}

	return nil
}

func parseDanishRelativeDate(dateStr string, now time.Time) *time.Time {
	if strings.Contains(dateStr, "dag") {
		if strings.Contains(dateStr, "1 dag") || strings.Contains(dateStr, "en dag") {
			result := now.AddDate(0, 0, -1)
			return &result
		}
		if num := extractNumber(dateStr, "dag"); num > 0 {
			result := now.AddDate(0, 0, -num)
			return &result
		}
	}
	
	if strings.Contains(dateStr, "uge") {
		if strings.Contains(dateStr, "1 uge") || strings.Contains(dateStr, "en uge") {
			result := now.AddDate(0, 0, -7)
			return &result
		}
		if num := extractNumber(dateStr, "uge"); num > 0 {
			result := now.AddDate(0, 0, -num*7)
			return &result
		}
	}
	
	if strings.Contains(dateStr, "måned") {
		if strings.Contains(dateStr, "1 måned") || strings.Contains(dateStr, "en måned") {
			result := now.AddDate(0, -1, 0)
			return &result
		}
		if num := extractNumber(dateStr, "måned"); num > 0 {
			result := now.AddDate(0, -num, 0)
			return &result
		}
	}
	
	return nil
}

func parseEnglishRelativeDate(dateStr string, now time.Time) *time.Time {
	if strings.Contains(dateStr, "day") {
		if strings.Contains(dateStr, "1 day") || strings.Contains(dateStr, "a day") {
			result := now.AddDate(0, 0, -1)
			return &result
		}
		if num := extractNumber(dateStr, "day"); num > 0 {
			result := now.AddDate(0, 0, -num)
			return &result
		}
	}
	
	if strings.Contains(dateStr, "week") {
		if strings.Contains(dateStr, "1 week") || strings.Contains(dateStr, "a week") {
			result := now.AddDate(0, 0, -7)
			return &result
		}
		if num := extractNumber(dateStr, "week"); num > 0 {
			result := now.AddDate(0, 0, -num*7)
			return &result
		}
	}
	
	if strings.Contains(dateStr, "month") {
		if strings.Contains(dateStr, "1 month") || strings.Contains(dateStr, "a month") {
			result := now.AddDate(0, -1, 0)
			return &result
		}
		if num := extractNumber(dateStr, "month"); num > 0 {
			result := now.AddDate(0, -num, 0)
			return &result
		}
	}
	
	return nil
}

func extractNumber(text, unit string) int {
	parts := strings.Fields(text)
	for i, part := range parts {
		if num, err := strconv.Atoi(part); err == nil && i+1 < len(parts) && strings.Contains(parts[i+1], unit) {
			return num
		}
	}
	return 0
}

// Applicants parsing utilities
func parseApplicantsCount(applicantsStr string) *int {
	applicantsStr = strings.ToLower(strings.TrimSpace(applicantsStr))
	
	// Handle "more than X" cases in Danish
	if strings.Contains(applicantsStr, "mere end") {
		if num := extractNumberFromPattern(applicantsStr, `mere\s*end\s*(\d+)`); num > 0 {
			return &num
		}
	}
	
	// Handle "more than X" cases in English
	if strings.Contains(applicantsStr, "more than") || strings.Contains(applicantsStr, "over") {
		if num := extractNumberFromPattern(applicantsStr, `(?:more\s*than|over)\s*(\d+)`); num > 0 {
			return &num
		}
	}
	
	// Handle exact numbers
	if strings.Contains(applicantsStr, "ansøger") || strings.Contains(applicantsStr, "applicant") {
		if num := extractNumberFromPattern(applicantsStr, `(\d+)\s*(?:ansøgere?|applicants?)`); num > 0 {
			return &num
		}
	}
	
	return nil
}

func extractNumberFromPattern(text, pattern string) int {
	// This is a simplified version - in a real implementation you'd use regexp
	// For now, let's extract numbers manually
	parts := strings.Fields(text)
	for _, part := range parts {
		if num, err := strconv.Atoi(part); err == nil && num > 0 {
			return num
		}
	}
	return 0
}

// Helper function to get map keys for debugging
func getMapKeys(m map[string]interface{}) []string {
	keys := make([]string, 0, len(m))
	for k := range m {
		keys = append(keys, k)
	}
	return keys
}

// Smart wait utilities for optimized scraping

// smartWaitForElement waits for an element to appear with a timeout and condition checking
func smartWaitForElement(ctx context.Context, selector string, maxWait time.Duration) error {
	timeout := time.Now().Add(maxWait)
	
	for time.Now().Before(timeout) {
		var elementExists bool
		err := chromedp.Run(ctx,
			chromedp.Evaluate(fmt.Sprintf(`document.querySelector('%s') !== null`, selector), &elementExists),
		)
		
		if err == nil && elementExists {
			return nil
		}
		
		// Check every 100ms
		time.Sleep(100 * time.Millisecond)
	}
	
	return fmt.Errorf("element %s not found within %v", selector, maxWait)
}

// smartWaitForCondition waits for a JavaScript condition to be true
func smartWaitForCondition(ctx context.Context, jsCondition string, maxWait time.Duration) error {
	timeout := time.Now().Add(maxWait)
	
	for time.Now().Before(timeout) {
		var conditionMet bool
		err := chromedp.Run(ctx,
			chromedp.Evaluate(jsCondition, &conditionMet),
		)
		
		if err == nil && conditionMet {
			return nil
		}
		
		// Check every 100ms for faster response
		time.Sleep(100 * time.Millisecond)
	}
	
	return fmt.Errorf("condition '%s' not met within %v", jsCondition, maxWait)
}
