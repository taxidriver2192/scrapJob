package models

import (
	"database/sql/driver"
	"encoding/json"
	"fmt"
	"time"
)

// Company represents the companies table
type Company struct {
	CompanyID int    `json:"company_id" db:"company_id"`
	Name      string `json:"name" db:"name"`
}

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
	
	// Joined fields
	CompanyName string `json:"company_name,omitempty" db:"company_name"`
}

// JobQueue represents the job_queue table
type JobQueue struct {
	QueueID   int       `json:"queue_id" db:"queue_id"`
	JobID     int       `json:"job_id" db:"job_id"`
	QueuedAt  time.Time `json:"queued_at" db:"queued_at"`
	StatusCode int      `json:"status_code" db:"status_code"`
}

// JobQueueStatus constants
const (
	StatusPending    = 1
	StatusInProgress = 2
	StatusDone       = 3
	StatusError      = 4
)

// JobRating represents the job_ratings table
type JobRating struct {
	RatingID int             `json:"rating_id" db:"rating_id"`
	JobID    int             `json:"job_id" db:"job_id"`
	Score    int             `json:"score" db:"score"`
	Criteria RatingCriteria  `json:"criteria" db:"criteria"`
	RatedAt  time.Time       `json:"rated_at" db:"rated_at"`
}

// SkillsList represents a list of skills stored as JSON
type SkillsList []string

// Value implements the driver.Valuer interface for database storage
func (sl SkillsList) Value() (driver.Value, error) {
	if sl == nil {
		return nil, nil
	}
	return json.Marshal(sl)
}

// Scan implements the sql.Scanner interface for database retrieval
func (sl *SkillsList) Scan(value interface{}) error {
	if value == nil {
		*sl = nil
		return nil
	}
	
	bytes, ok := value.([]byte)
	if !ok {
		return fmt.Errorf("cannot scan %T into SkillsList", value)
	}
	
	return json.Unmarshal(bytes, sl)
}

// RatingCriteria represents the JSON criteria field
type RatingCriteria map[string]interface{}

// Value implements the driver.Valuer interface for database storage
func (rc RatingCriteria) Value() (driver.Value, error) {
	if rc == nil {
		return nil, nil
	}
	return json.Marshal(rc)
}

// Scan implements the sql.Scanner interface for database retrieval
func (rc *RatingCriteria) Scan(value interface{}) error {
	if value == nil {
		*rc = nil
		return nil
	}
	
	bytes, ok := value.([]byte)
	if !ok {
		return fmt.Errorf("cannot scan %T into RatingCriteria", value)
	}
	
	return json.Unmarshal(bytes, rc)
}

// ScrapedJob represents a job found during scraping (before database insertion)
type ScrapedJob struct {
	LinkedInJobID string      // LinkedIn job ID as string (converted to int64 when saving)
	Title         string
	CompanyName   string
	Location      string
	Description   string
	ApplyURL      string
	PostedDate    time.Time
	Applicants    *int        // Number of applicants (NULL if not found)
	WorkType      *string     // Remote, Hybrid, On-site work type
	Skills        *SkillsList // List of required skills
}

// SearchParams represents search parameters for job scraping
type SearchParams struct {
	Keywords string
	Location string
	GeoID    string
	Start    int
	MaxPages int
}
