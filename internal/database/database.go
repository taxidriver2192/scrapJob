package database

import (
	"database/sql"
	"fmt"
	"linkedin-job-scraper/internal/config"

	_ "github.com/go-sql-driver/mysql"
)

type DB struct {
	*sql.DB
}

// NewConnection creates a new database connection
func NewConnection(cfg config.DatabaseConfig) (*DB, error) {
	dsn := fmt.Sprintf("%s:%s@tcp(%s:%s)/%s?charset=utf8mb4&parseTime=True&loc=Local",
		cfg.User, cfg.Password, cfg.Host, cfg.Port, cfg.DBName)

	db, err := sql.Open("mysql", dsn)
	if err != nil {
		return nil, fmt.Errorf("failed to open database: %w", err)
	}

	if err := db.Ping(); err != nil {
		return nil, fmt.Errorf("failed to ping database: %w", err)
	}

	return &DB{db}, nil
}

// NewConnectionWithAutoCreate creates a new database connection and creates database if it doesn't exist
func NewConnectionWithAutoCreate(cfg config.DatabaseConfig) (*DB, error) {
	// First try to connect without specifying database to create it if needed
	dsnWithoutDB := fmt.Sprintf("%s:%s@tcp(%s:%s)/?charset=utf8mb4&parseTime=True&loc=Local",
		cfg.User, cfg.Password, cfg.Host, cfg.Port)

	tempDB, err := sql.Open("mysql", dsnWithoutDB)
	if err != nil {
		return nil, fmt.Errorf("failed to open database connection: %w", err)
	}
	defer tempDB.Close()

	if err := tempDB.Ping(); err != nil {
		return nil, fmt.Errorf("failed to ping database: %w", err)
	}

	// Create database if it doesn't exist
	createDBQuery := fmt.Sprintf("CREATE DATABASE IF NOT EXISTS %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", cfg.DBName)
	if _, err := tempDB.Exec(createDBQuery); err != nil {
		return nil, fmt.Errorf("failed to create database: %w", err)
	}

	// Now connect to the specific database
	return NewConnection(cfg)
}

// RunMigrations runs database migrations
func RunMigrations(db *DB) error {
	migrations := []string{
		createCompaniesTable,
		createJobPostingsTable,
		createJobQueueTable,
		createJobRatingsTable,
		createIndexes,
	}

	for _, migration := range migrations {
		if _, err := db.Exec(migration); err != nil {
			return fmt.Errorf("migration failed: %w", err)
		}
	}

	return nil
}

const createCompaniesTable = `
CREATE TABLE IF NOT EXISTS companies (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
`

const createJobPostingsTable = `
CREATE TABLE IF NOT EXISTS job_postings (
    job_id INT AUTO_INCREMENT PRIMARY KEY,
    linkedin_job_id BIGINT NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    company_id INT NOT NULL,
    location VARCHAR(255),
    description TEXT,
    apply_url VARCHAR(2048),
    posted_date DATE,
    applicants INT DEFAULT NULL COMMENT 'Number of applicants',
    work_type VARCHAR(50) DEFAULT NULL COMMENT 'Remote, Hybrid, or On-site work type',
    skills JSON DEFAULT NULL COMMENT 'List of required skills',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    INDEX idx_linkedin_job_id (linkedin_job_id),
    INDEX idx_company_id (company_id),
    INDEX idx_posted_date (posted_date),
    INDEX idx_location (location),
    INDEX idx_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
`

const createJobQueueTable = `
CREATE TABLE IF NOT EXISTS job_queue (
    queue_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL UNIQUE,
    queued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status_code TINYINT NOT NULL DEFAULT 1 COMMENT '1=pending,2=in_progress,3=done,4=error',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES job_postings(job_id) ON DELETE CASCADE,
    INDEX idx_status_code (status_code),
    INDEX idx_queued_at (queued_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
`

const createJobRatingsTable = `
CREATE TABLE IF NOT EXISTS job_ratings (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    score INT NOT NULL,
    criteria JSON,
    rated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES job_postings(job_id) ON DELETE CASCADE,
    INDEX idx_job_id (job_id),
    INDEX idx_score (score),
    INDEX idx_rated_at (rated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
`

const createIndexes = `
-- Additional indexes for performance will be created within table definitions
SELECT 'Indexes created within table definitions' as message
`
