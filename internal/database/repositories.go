package database

import (
	"database/sql"
	"fmt"
	"linkedin-job-scraper/internal/models"
	"time"

	"github.com/sirupsen/logrus"
)

// CompanyRepository handles company-related database operations
type CompanyRepository struct {
	db *DB
}

// NewCompanyRepository creates a new company repository
func NewCompanyRepository(db *DB) *CompanyRepository {
	return &CompanyRepository{db: db}
}

// CreateOrGet creates a new company or returns existing one
func (r *CompanyRepository) CreateOrGet(name string) (*models.Company, error) {
	// First try to get existing company
	company, err := r.GetByName(name)
	if err == nil {
		return company, nil
	}

	// If not found, create new one
	if err == sql.ErrNoRows {
		return r.Create(name)
	}

	return nil, err
}

// Create creates a new company
func (r *CompanyRepository) Create(name string) (*models.Company, error) {
	query := `INSERT INTO companies (name) VALUES (?)`
	result, err := r.db.Exec(query, name)
	if err != nil {
		return nil, fmt.Errorf("failed to create company: %w", err)
	}

	id, err := result.LastInsertId()
	if err != nil {
		return nil, fmt.Errorf("failed to get last insert id: %w", err)
	}

	return &models.Company{
		CompanyID: int(id),
		Name:      name,
	}, nil
}

// GetByName finds a company by name
func (r *CompanyRepository) GetByName(name string) (*models.Company, error) {
	query := `SELECT company_id, name FROM companies WHERE name = ?`
	var company models.Company
	err := r.db.QueryRow(query, name).Scan(&company.CompanyID, &company.Name)
	if err != nil {
		return nil, err
	}
	return &company, nil
}

// GetByID finds a company by ID
func (r *CompanyRepository) GetByID(id int) (*models.Company, error) {
	query := `SELECT company_id, name FROM companies WHERE company_id = ?`
	var company models.Company
	err := r.db.QueryRow(query, id).Scan(&company.CompanyID, &company.Name)
	if err != nil {
		return nil, err
	}
	return &company, nil
}

// JobPostingRepository handles job posting-related database operations
type JobPostingRepository struct {
	db *DB
}

// NewJobPostingRepository creates a new job posting repository
func NewJobPostingRepository(db *DB) *JobPostingRepository {
	return &JobPostingRepository{db: db}
}

// Create creates a new job posting
func (r *JobPostingRepository) Create(job *models.JobPosting) (*models.JobPosting, error) {
	query := `
		INSERT INTO job_postings (linkedin_job_id, title, company_id, location, description, apply_url, posted_date, applicants, work_type, skills)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	`
	result, err := r.db.Exec(query,
		job.LinkedInJobID,
		job.Title,
		job.CompanyID,
		job.Location,
		job.Description,
		job.ApplyURL,
		job.PostedDate,
		job.Applicants,
		job.WorkType,
		job.Skills,
	)
	if err != nil {
		return nil, fmt.Errorf("failed to create job posting: %w", err)
	}

	id, err := result.LastInsertId()
	if err != nil {
		return nil, fmt.Errorf("failed to get last insert id: %w", err)
	}

	job.JobID = int(id)
	return job, nil
}

// ExistsLinkedInJobID checks if a LinkedIn job ID already exists
func (r *JobPostingRepository) ExistsLinkedInJobID(linkedinJobID int64) (bool, error) {
	query := `SELECT COUNT(*) FROM job_postings WHERE linkedin_job_id = ?`
	var count int
	err := r.db.QueryRow(query, linkedinJobID).Scan(&count)
	if err != nil {
		return false, err
	}
	return count > 0, nil
}

// GetByLinkedInJobID finds a job posting by LinkedIn job ID
func (r *JobPostingRepository) GetByLinkedInJobID(linkedinJobID int64) (*models.JobPosting, error) {
	query := `
		SELECT jp.job_id, jp.linkedin_job_id, jp.title, jp.company_id, jp.location, 
		       jp.description, jp.apply_url, jp.posted_date, jp.applicants, jp.work_type, jp.skills, c.name as company_name
		FROM job_postings jp
		JOIN companies c ON jp.company_id = c.company_id
		WHERE jp.linkedin_job_id = ?
	`
	var job models.JobPosting
	err := r.db.QueryRow(query, linkedinJobID).Scan(
		&job.JobID,
		&job.LinkedInJobID,
		&job.Title,
		&job.CompanyID,
		&job.Location,
		&job.Description,
		&job.ApplyURL,
		&job.PostedDate,
		&job.Applicants,
		&job.WorkType,
		&job.Skills,
		&job.CompanyName,
	)
	if err != nil {
		return nil, err
	}
	return &job, nil
}

// GetRecent returns recent job postings
func (r *JobPostingRepository) GetRecent(limit int) ([]*models.JobPosting, error) {
	query := `
		SELECT jp.job_id, jp.linkedin_job_id, jp.title, jp.company_id, jp.location, 
		       jp.description, jp.apply_url, jp.posted_date, jp.applicants, jp.work_type, jp.skills, c.name as company_name
		FROM job_postings jp
		JOIN companies c ON jp.company_id = c.company_id
		ORDER BY jp.created_at DESC
		LIMIT ?
	`
	rows, err := r.db.Query(query, limit)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var jobs []*models.JobPosting
	for rows.Next() {
		var job models.JobPosting
		err := rows.Scan(
			&job.JobID,
			&job.LinkedInJobID,
			&job.Title,
			&job.CompanyID,
			&job.Location,
			&job.Description,
			&job.ApplyURL,
			&job.PostedDate,
			&job.Applicants,
			&job.WorkType,
			&job.Skills,
			&job.CompanyName,
		)
		if err != nil {
			logrus.Error("Error scanning job posting: ", err)
			continue
		}
		jobs = append(jobs, &job)
	}

	return jobs, nil
}

// JobQueueRepository handles job queue operations
type JobQueueRepository struct {
	db *DB
}

// NewJobQueueRepository creates a new job queue repository
func NewJobQueueRepository(db *DB) *JobQueueRepository {
	return &JobQueueRepository{db: db}
}

// Add adds a job to the queue
func (r *JobQueueRepository) Add(jobID int) error {
	query := `INSERT INTO job_queue (job_id, queued_at, status_code) VALUES (?, ?, ?)`
	_, err := r.db.Exec(query, jobID, time.Now(), models.StatusPending)
	return err
}

// UpdateStatus updates the status of a job in the queue
func (r *JobQueueRepository) UpdateStatus(jobID int, status int) error {
	query := `UPDATE job_queue SET status_code = ? WHERE job_id = ?`
	_, err := r.db.Exec(query, status, jobID)
	return err
}

// GetPending returns pending jobs from the queue
func (r *JobQueueRepository) GetPending(limit int) ([]*models.JobQueue, error) {
	query := `
		SELECT queue_id, job_id, queued_at, status_code
		FROM job_queue
		WHERE status_code = ?
		ORDER BY queued_at ASC
		LIMIT ?
	`
	rows, err := r.db.Query(query, models.StatusPending, limit)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var jobs []*models.JobQueue
	for rows.Next() {
		var job models.JobQueue
		err := rows.Scan(&job.QueueID, &job.JobID, &job.QueuedAt, &job.StatusCode)
		if err != nil {
			continue
		}
		jobs = append(jobs, &job)
	}

	return jobs, nil
}
