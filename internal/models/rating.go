package models

import (
	"database/sql/driver"
	"encoding/json"
	"fmt"
	"time"
)

// JobRating represents the job_ratings table
type JobRating struct {
	RatingID int             `json:"rating_id" db:"rating_id"`
	JobID    int             `json:"job_id" db:"job_id"`
	Score    int             `json:"score" db:"score"`
	Criteria RatingCriteria  `json:"criteria" db:"criteria"`
	RatedAt  time.Time       `json:"rated_at" db:"rated_at"`
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
