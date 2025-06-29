package utils

import (
	"strconv"
	"strings"
	"time"
)

// ParseRelativeDate converts relative date strings like "2 weeks ago" to actual dates
func ParseRelativeDate(relative string) time.Time {
	now := time.Now()
	
	if strings.Contains(relative, "day") {
		return parseDays(relative, now)
	} else if strings.Contains(relative, "week") {
		return parseWeeks(relative, now)
	} else if strings.Contains(relative, "month") {
		return parseMonths(relative, now)
	}
	
	// Default to today if can't parse
	return now
}

func parseDays(relative string, now time.Time) time.Time {
	parts := strings.Fields(relative)
	if len(parts) > 0 {
		if days, err := strconv.Atoi(parts[0]); err == nil {
			return now.AddDate(0, 0, -days)
		}
	}
	return now
}

func parseWeeks(relative string, now time.Time) time.Time {
	parts := strings.Fields(relative)
	if len(parts) > 0 {
		if weeks, err := strconv.Atoi(parts[0]); err == nil {
			return now.AddDate(0, 0, -weeks*7)
		}
	}
	return now
}

func parseMonths(relative string, now time.Time) time.Time {
	parts := strings.Fields(relative)
	if len(parts) > 0 {
		if months, err := strconv.Atoi(parts[0]); err == nil {
			return now.AddDate(0, -months, 0)
		}
	}
	return now
}

// ExtractJobIDFromURL extracts job ID from LinkedIn URL
func ExtractJobIDFromURL(url string) (int64, error) {
	// LinkedIn job URLs typically contain /jobs/view/JOB_ID
	parts := strings.Split(url, "/")
	for i, part := range parts {
		if part == "view" && i+1 < len(parts) {
			return strconv.ParseInt(parts[i+1], 10, 64)
		}
	}
	return 0, nil
}

// SanitizeString removes extra whitespace and cleans up text
func SanitizeString(s string) string {
	return strings.TrimSpace(strings.ReplaceAll(s, "\n", " "))
}
