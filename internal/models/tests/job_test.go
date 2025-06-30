package models

import (
	"testing"
	"time"
)

func TestJobPostingValidation(t *testing.T) {
	tests := []struct {
		name    string
		job     JobPosting
		isValid bool
	}{
		{
			name: "Valid job posting",
			job: JobPosting{
				LinkedInJobID: 123456789,
				Title:         "Software Engineer",
				CompanyName:   "Test Company",
				Location:      "Copenhagen, Denmark",
				Description:   "A great job opportunity",
				PostedDate:    time.Now(),
			},
			isValid: true,
		},
		{
			name: "Job with missing title",
			job: JobPosting{
				LinkedInJobID: 123456789,
				CompanyName:   "Test Company",
				Location:      "Copenhagen, Denmark",
				Description:   "A great job opportunity",
				PostedDate:    time.Now(),
			},
			isValid: false,
		},
		{
			name: "Job with missing LinkedIn ID",
			job: JobPosting{
				Title:       "Software Engineer",
				CompanyName: "Test Company",
				Location:    "Copenhagen, Denmark",
				Description: "A great job opportunity",
				PostedDate:  time.Now(),
			},
			isValid: false,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			isValid := tt.job.Title != "" && tt.job.LinkedInJobID != 0
			if isValid != tt.isValid {
				t.Errorf("Job validation failed for %s: expected %v, got %v", tt.name, tt.isValid, isValid)
			}
		})
	}
}

func TestSkillsHandling(t *testing.T) {
	skills := []string{"Go", "JavaScript", "React", "Node.js"}
	
	job := JobPosting{
		LinkedInJobID: 123456789,
		Title:         "Software Engineer",
		CompanyName:   "Test Company",
		Skills:        &skills,
	}
	
	if job.Skills == nil {
		t.Error("Skills should not be nil")
	}
	
	if len(*job.Skills) != 4 {
		t.Errorf("Expected 4 skills, got %d", len(*job.Skills))
	}
	
	expectedSkills := map[string]bool{
		"Go":         true,
		"JavaScript": true,
		"React":      true,
		"Node.js":    true,
	}
	
	for _, skill := range *job.Skills {
		if !expectedSkills[skill] {
			t.Errorf("Unexpected skill: %s", skill)
		}
	}
}

func TestWorkTypeHandling(t *testing.T) {
	workTypes := []string{"Remote", "Hybrid", "On-site", "Full-time", "Part-time"}
	
	for _, workType := range workTypes {
		job := JobPosting{
			LinkedInJobID: 123456789,
			Title:         "Software Engineer",
			WorkType:      &workType,
		}
		
		if job.WorkType == nil {
			t.Errorf("WorkType should not be nil for %s", workType)
		}
		
		if *job.WorkType != workType {
			t.Errorf("Expected work type %s, got %s", workType, *job.WorkType)
		}
	}
}
