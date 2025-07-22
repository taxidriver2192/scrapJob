package main

import (
	"flag"
	"fmt"
	"log"

	"github.com/joho/godotenv"

	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/database"
	"linkedin-job-scraper/internal/scraper"
)

func main() {
	var (
		limit = flag.Int("limit", 100, "Number of jobs to check (0 = all)")
	)
	flag.Parse()

	// Load environment variables
	if err := godotenv.Load(); err != nil {
		log.Printf("Warning: .env file not found, using environment variables")
	}

	// Load configuration
	cfg := config.Load()

	// Validate LinkedIn credentials
	if cfg.LinkedIn.Email == "" || cfg.LinkedIn.Password == "" {
		log.Fatal("LinkedIn credentials not found. Please set LINKEDIN_EMAIL and LINKEDIN_PASSWORD in .env file")
	}

	// Initialize database
	db, err := database.NewConnection(cfg.Database)
	if err != nil {
		log.Fatalf("Failed to connect to database: %v", err)
	}
	defer db.Close()

	// Initialize scraper
	linkedinScraper := scraper.NewLinkedInScraper(cfg, db)

	// Start checking job closure status
	fmt.Printf("🔄 Starting job status check for open jobs (limit: %d)\n", *limit)
	if *limit == 0 {
		fmt.Println("ℹ️  No limit set - will check ALL open jobs")
	}

	err = linkedinScraper.CheckJobClosureStatus(*limit)
	if err != nil {
		log.Fatalf("Job status checking failed: %v", err)
	}

	fmt.Println("🎉 Job status checking completed successfully!")
}
