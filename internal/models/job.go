package models

import (
	"time"
)

// JobPosting represents the job_postings table
type JobPosting struct {
	JobID         int            `json:"job_id" db:"job_id"`
	LinkedInJobID int64          `json:"linkedin_job_id" db:"linkedin_job_id"`
	Title         string         `json:"title" db:"title"`
	CompanyID     int            `json:"company_id" db:"company_id"`
	Location      string         `json:"location" db:"location"`
	Description   string         `json:"description" db:"description"`
	ApplyURL      string         `json:"apply_url" db:"apply_url"`
	PostedDate    time.Time      `json:"posted_date" db:"posted_date"`
	Applicants    *int           `json:"applicants,omitempty" db:"applicants"` // Pointer to handle NULL values
	WorkType      *string        `json:"work_type,omitempty" db:"work_type"`   // Remote, Hybrid, On-site
	Skills        *SkillsList    `json:"skills,omitempty" db:"skills"`         // JSON list of skills
	OpenaiAdresse *string        `json:"openai_adresse,omitempty" db:"openai_adresse"` // AI-extracted standardized address
	
	// Joined fields (only used for display, not saved to DB)
	CompanyName string `json:"company_name,omitempty" db:"company_name"`
}
