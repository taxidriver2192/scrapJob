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
		limit  = flag.Int("limit", 50, "Number of jobs to rescrape (0 = all)")
		dryRun = flag.Bool("dry-run", false, "Show what would be rescraped without actually doing it")
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

	if *dryRun {
		fmt.Printf("🔍 DRY RUN - Would rescrape %d jobs with empty descriptions\n", *limit)
		if *limit == 0 {
			fmt.Println("(No limit - would rescrape ALL jobs with empty descriptions)")
		}
		return
	}

	// Start rescraping from queue
	fmt.Printf("🔄 Starting rescrape of jobs with empty descriptions (limit: %d)\n", *limit)
	if *limit == 0 {
		fmt.Println("ℹ️  No limit set - will rescrape ALL jobs with empty descriptions")
	}

	err = linkedinScraper.RescrapeFromQueue(*limit)
	if err != nil {
		log.Fatalf("Rescraping failed: %v", err)
	}

	fmt.Println("🎉 Rescraping completed successfully!")
}
