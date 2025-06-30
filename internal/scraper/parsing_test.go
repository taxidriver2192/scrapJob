package scraper

import (
	"testing"
	"time"
)

func TestParseApplicantsCount(t *testing.T) {
	tests := []struct {
		name     string
		input    string
		expected *int
	}{
		{
			name:     "Danish single applicant",
			input:    "1 ansøger",
			expected: intPtr(1),
		},
		{
			name:     "Danish multiple applicants",
			input:    "25 ansøgere",
			expected: intPtr(25),
		},
		{
			name:     "English single applicant",
			input:    "1 applicant",
			expected: intPtr(1),
		},
		{
			name:     "English multiple applicants",
			input:    "15 applicants",
			expected: intPtr(15),
		},
		{
			name:     "No applicants info",
			input:    "some other text",
			expected: nil,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			result := parseApplicantsCount(tt.input)
			if tt.expected == nil {
				if result != nil {
					t.Errorf("parseApplicantsCount(%q) = %v, expected nil", tt.input, *result)
				}
			} else {
				if result == nil {
					t.Errorf("parseApplicantsCount(%q) returned nil, expected %d", tt.input, *tt.expected)
				} else if *result != *tt.expected {
					t.Errorf("parseApplicantsCount(%q) = %d, expected %d", tt.input, *result, *tt.expected)
				}
			}
		})
	}
}

func TestParseLocationInfo(t *testing.T) {
	tests := []struct {
		name                string
		input               string
		expectedLocation    string
		expectsDate         bool
		expectsApplicants   bool
	}{
		{
			name:              "Full location string",
			input:             "Copenhagen, Denmark · 2 dage siden · 15 ansøgere",
			expectedLocation:  "Copenhagen, Denmark",
			expectsDate:       true,
			expectsApplicants: true,
		},
		{
			name:              "Location with date only",
			input:             "Stockholm, Sweden · 1 week ago",
			expectedLocation:  "Stockholm, Sweden",
			expectsDate:       true,
			expectsApplicants: false,
		},
		{
			name:              "Location only",
			input:             "Oslo, Norway",
			expectedLocation:  "Oslo, Norway",
			expectsDate:       false,
			expectsApplicants: false,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			location, postedDate, applicants := parseLocationInfo(tt.input)
			
			if location != tt.expectedLocation {
				t.Errorf("parseLocationInfo(%q) location = %q, expected %q", tt.input, location, tt.expectedLocation)
			}
			
			if tt.expectsDate {
				// Just check that the date is not the default current time (within reasonable bounds)
				now := time.Now()
				if postedDate.After(now) || postedDate.Before(now.AddDate(0, 0, -30)) {
					t.Errorf("parseLocationInfo(%q) postedDate seems incorrect: %v", tt.input, postedDate)
				}
			}
			
			if tt.expectsApplicants && applicants == nil {
				t.Errorf("parseLocationInfo(%q) expected applicants count but got nil", tt.input)
			} else if !tt.expectsApplicants && applicants != nil {
				t.Errorf("parseLocationInfo(%q) expected no applicants count but got %d", tt.input, *applicants)
			}
		})
	}
}

// Helper function to create int pointer
func intPtr(i int) *int {
	return &i
}
