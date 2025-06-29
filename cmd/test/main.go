package main

import (
	"fmt"
	"log"
	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/database"
	"linkedin-job-scraper/internal/models"
	"strings"

	"github.com/joho/godotenv"
)

func main() {
	// Load environment variables
	if err := godotenv.Load(); err != nil {
		log.Printf("Warning: .env file not found")
	}

	// Initialize configuration
	cfg := config.Load()

	// Initialize database with auto-create
	db, err := database.NewConnectionWithAutoCreate(cfg.Database)
	if err != nil {
		log.Fatal("Failed to connect to database: ", err)
	}
	defer db.Close()

	// Run migrations
	if err := database.RunMigrations(db); err != nil {
		log.Fatal("Migration failed: ", err)
	}

	// Initialize repositories
	jobRepo := database.NewJobPostingRepository(db)

	// Get recent jobs
	jobs, err := jobRepo.GetRecent(10)
	if err != nil {
		log.Fatal("Failed to get recent jobs: ", err)
	}

	// Display results
	fmt.Printf("Recent Jobs (%d found):\n", len(jobs))
	fmt.Println(strings.Repeat("=", 80))

	for _, job := range jobs {
		displayJob(job)
		fmt.Println(strings.Repeat("-", 80))
	}
}

func displayJob(job *models.JobPosting) {
	fmt.Printf("Title: %s\n", job.Title)
	fmt.Printf("Company: %s\n", job.CompanyName)
	fmt.Printf("Location: %s\n", job.Location)
	fmt.Printf("Posted: %s\n", job.PostedDate.Format("2006-01-02"))
	if job.Applicants != nil {
		fmt.Printf("Applicants: %d\n", *job.Applicants)
	}
	if job.WorkType != nil {
		fmt.Printf("Work Type: %s\n", *job.WorkType)
	}
	if job.Skills != nil && len(*job.Skills) > 0 {
		fmt.Printf("Skills: %s\n", strings.Join(*job.Skills, ", "))
	}
	fmt.Printf("LinkedIn ID: %d\n", job.LinkedInJobID)
	if job.ApplyURL != "" {
		fmt.Printf("Apply URL: %s\n", job.ApplyURL)
	}
	if len(job.Description) > 200 {
		fmt.Printf("Description: %s...\n", job.Description[:200])
	} else {
		fmt.Printf("Description: %s\n", job.Description)
	}
}
