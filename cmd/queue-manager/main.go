package main

import (
	"flag"
	"fmt"
	"log"
	"time"

	"github.com/joho/godotenv"

	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/database"
)

func main() {
	var (
		action = flag.String("action", "status", "Action: status, enqueue, process, reset")
		limit  = flag.Int("limit", 50, "Number of jobs to enqueue/process")
	)
	flag.Parse()

	// Load environment variables
	if err := godotenv.Load(); err != nil {
		log.Printf("Warning: .env file not found, using environment variables")
	}

	// Load configuration
	cfg := config.Load()

	// Initialize database
	db, err := database.NewConnection(cfg.Database)
	if err != nil {
		log.Fatalf("Failed to connect to database: %v", err)
	}
	defer db.Close()

	switch *action {
	case "status":
		showQueueStatus(db)
	case "enqueue":
		enqueueJobs(db, *limit)
	case "reset":
		resetQueue(db)
	case "list":
		listQueuedJobs(db, *limit)
	default:
		log.Fatalf("Unknown action: %s. Use: status, enqueue, reset, list", *action)
	}
}

func showQueueStatus(db *database.DB) {
	fmt.Println("📊 JOB QUEUE STATUS")
	fmt.Println("=" + string(make([]rune, 50)))

	// Count jobs by status
	statuses := []struct {
		code int
		name string
	}{
		{1, "Pending"},
		{2, "In Progress"},
		{3, "Done"},
		{4, "Error"},
	}

	totalQueued := 0
	for _, status := range statuses {
		count, err := getJobCountByStatus(db, status.code)
		if err != nil {
			log.Printf("Error getting count for status %d: %v", status.code, err)
			continue
		}
		fmt.Printf("%-12s: %d jobs\n", status.name, count)
		totalQueued += count
	}

	fmt.Printf("%-12s: %d jobs\n", "Total Queued", totalQueued)

	// Count jobs not in queue
	totalJobs, err := getTotalJobCount(db)
	if err != nil {
		log.Printf("Error getting total job count: %v", err)
	} else {
		notQueued := totalJobs - totalQueued
		fmt.Printf("%-12s: %d jobs\n", "Not Queued", notQueued)
		fmt.Printf("%-12s: %d jobs\n", "Total Jobs", totalJobs)
	}

	// Count jobs with ratings
	ratedJobs, err := getRatedJobCount(db)
	if err != nil {
		log.Printf("Error getting rated job count: %v", err)
	} else {
		fmt.Printf("%-12s: %d jobs\n", "Rated Jobs", ratedJobs)
	}
}

func enqueueJobs(db *database.DB, limit int) {
	fmt.Printf("📝 Enqueuing jobs for AI matching (limit: %d)...\n", limit)

	// Get jobs that are not already queued and don't have ratings
	query := `
		SELECT j.job_id
		FROM job_postings j
		LEFT JOIN job_queue q ON j.job_id = q.job_id
		LEFT JOIN job_ratings r ON j.job_id = r.job_id AND r.rating_type = 'ai_match'
		WHERE q.job_id IS NULL 
		AND r.job_id IS NULL
		AND j.description IS NOT NULL 
		AND j.description != ''
		ORDER BY j.posted_date DESC
		LIMIT ?
	`

	rows, err := db.Query(query, limit)
	if err != nil {
		log.Fatalf("Failed to get jobs for enqueuing: %v", err)
	}
	defer rows.Close()

	var jobIDs []int
	for rows.Next() {
		var jobID int
		if err := rows.Scan(&jobID); err != nil {
			log.Printf("Error scanning job ID: %v", err)
			continue
		}
		jobIDs = append(jobIDs, jobID)
	}

	if len(jobIDs) == 0 {
		fmt.Println("✅ No jobs need to be enqueued")
		return
	}

	// Enqueue jobs
	enqueued := 0
	for _, jobID := range jobIDs {
		if err := enqueueJob(db, jobID); err != nil {
			log.Printf("Error enqueuing job %d: %v", jobID, err)
			continue
		}
		enqueued++
	}

	fmt.Printf("✅ Enqueued %d jobs for AI matching\n", enqueued)
}

func resetQueue(db *database.DB) {
	fmt.Println("🗑️  Resetting job queue...")

	// Reset all jobs to pending
	query := `UPDATE job_queue SET status_code = 1, updated_at = NOW()`
	if _, err := db.Exec(query); err != nil {
		log.Fatalf("Failed to reset queue: %v", err)
	}

	fmt.Println("✅ Queue reset - all jobs set to pending")
}

func listQueuedJobs(db *database.DB, limit int) {
	fmt.Printf("📋 QUEUED JOBS (limit: %d)\n", limit)
	fmt.Println("=" + string(make([]rune, 80)))

	query := `
		SELECT 
			q.queue_id, q.job_id, q.status_code, q.queued_at,
			j.title, c.name as company_name
		FROM job_queue q
		JOIN job_postings j ON q.job_id = j.job_id
		LEFT JOIN companies c ON j.company_id = c.company_id
		ORDER BY q.queued_at DESC
		LIMIT ?
	`

	rows, err := db.Query(query, limit)
	if err != nil {
		log.Fatalf("Failed to list queued jobs: %v", err)
	}
	defer rows.Close()

	statusNames := map[int]string{
		1: "Pending",
		2: "In Progress",
		3: "Done",
		4: "Error",
	}

	count := 0
	for rows.Next() {
		var queueID, jobID, statusCode int
		var queuedAt time.Time
		var title, companyName string

		err := rows.Scan(&queueID, &jobID, &statusCode, &queuedAt, &title, &companyName)
		if err != nil {
			log.Printf("Error scanning row: %v", err)
			continue
		}

		count++
		statusName := statusNames[statusCode]
		fmt.Printf("%d. Job %d: %s at %s\n", count, jobID, title, companyName)
		fmt.Printf("   Status: %s | Queued: %s\n", statusName, queuedAt.Format("2006-01-02 15:04:05"))
		fmt.Println()
	}

	if count == 0 {
		fmt.Println("No jobs in queue")
	}
}

func getJobCountByStatus(db *database.DB, statusCode int) (int, error) {
	query := `SELECT COUNT(*) FROM job_queue WHERE status_code = ?`
	row := db.QueryRow(query, statusCode)
	
	var count int
	err := row.Scan(&count)
	return count, err
}

func getTotalJobCount(db *database.DB) (int, error) {
	query := `SELECT COUNT(*) FROM job_postings WHERE description IS NOT NULL AND description != ''`
	row := db.QueryRow(query)
	
	var count int
	err := row.Scan(&count)
	return count, err
}

func getRatedJobCount(db *database.DB) (int, error) {
	query := `SELECT COUNT(DISTINCT job_id) FROM job_ratings WHERE rating_type = 'ai_match'`
	row := db.QueryRow(query)
	
	var count int
	err := row.Scan(&count)
	return count, err
}

func enqueueJob(db *database.DB, jobID int) error {
	query := `
		INSERT INTO job_queue (job_id, queued_at, status_code) 
		VALUES (?, NOW(), 1)
		ON DUPLICATE KEY UPDATE status_code = 1, updated_at = NOW()
	`
	_, err := db.Exec(query, jobID)
	return err
}
