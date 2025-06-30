package models

import (
	"time"
)

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
