package main

import (
	"fmt"
	"log"
	"os"

	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/database"
	"linkedin-job-scraper/internal/scraper"

	"github.com/joho/godotenv"
	"github.com/sirupsen/logrus"
	"github.com/spf13/cobra"
)

var rootCmd = &cobra.Command{
	Use:   "linkedin-scraper",
	Short: "LinkedIn Job Scraper CLI",
	Long:  "A CLI tool to scrape LinkedIn job postings and store them in a database",
}

var scrapeCmd = &cobra.Command{
	Use:   "scrape",
	Short: "Start scraping LinkedIn jobs",
	Run: func(cmd *cobra.Command, args []string) {
		keywords, _ := cmd.Flags().GetString("keywords")
		location, _ := cmd.Flags().GetString("location")
		maxPages, _ := cmd.Flags().GetInt("max-pages")
		jobsPerPage, _ := cmd.Flags().GetInt("jobs-per-page")

		runScraper(keywords, location, maxPages, jobsPerPage)
	},
}

var migrateCmd = &cobra.Command{
	Use:   "migrate",
	Short: "Run database migrations",
	Run: func(cmd *cobra.Command, args []string) {
		runMigrations()
	},
}

func init() {
	scrapeCmd.Flags().StringP("keywords", "k", "", "Job search keywords (required)")
	scrapeCmd.Flags().StringP("location", "l", "", "Job search location (required)")
	scrapeCmd.Flags().IntP("max-pages", "p", 5, "Maximum pages to scrape")
	scrapeCmd.Flags().IntP("jobs-per-page", "j", 10, "Number of jobs to scrape per page (1-25)")
	scrapeCmd.MarkFlagRequired("keywords")
	scrapeCmd.MarkFlagRequired("location")

	rootCmd.AddCommand(scrapeCmd)
	rootCmd.AddCommand(migrateCmd)
}

func main() {
	// Load environment variables
	if err := godotenv.Load(); err != nil {
		log.Printf("Warning: .env file not found")
	}

	if err := rootCmd.Execute(); err != nil {
		fmt.Println(err)
		os.Exit(1)
	}
}

func runScraper(keywords, location string, maxPages, jobsPerPage int) {
	// Initialize configuration
	cfg := config.Load()

	// Setup logging
	setupLogging(cfg.LogLevel)

	// Initialize database with auto-create
	db, err := database.NewConnectionWithAutoCreate(cfg.Database)
	if err != nil {
		logrus.Fatal("Failed to connect to database: ", err)
	}
	defer db.Close()

	// Initialize scraper
	jobScraper := scraper.NewLinkedInScraper(cfg, db)

	// Start scraping
	logrus.Infof("Starting to scrape jobs with keywords: %s, location: %s, max pages: %d, jobs per page: %d", keywords, location, maxPages, jobsPerPage)
	
	err = jobScraper.ScrapeJobs(keywords, location, maxPages, jobsPerPage)
	if err != nil {
		logrus.Fatal("Scraping failed: ", err)
	}

	logrus.Info("Scraping completed successfully")
}

func runMigrations() {
	cfg := config.Load()
	
	db, err := database.NewConnectionWithAutoCreate(cfg.Database)
	if err != nil {
		logrus.Fatal("Failed to connect to database: ", err)
	}
	defer db.Close()

	if err := database.RunMigrations(db); err != nil {
		logrus.Fatal("Migration failed: ", err)
	}

	logrus.Info("Migrations completed successfully")
}

func setupLogging(level string) {
	logLevel, err := logrus.ParseLevel(level)
	if err != nil {
		logrus.SetLevel(logrus.InfoLevel)
	} else {
		logrus.SetLevel(logLevel)
	}

	logrus.SetFormatter(&logrus.TextFormatter{
		FullTimestamp: true,
	})
}
