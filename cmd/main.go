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
		totalJobs, _ := cmd.Flags().GetInt("total-jobs")
		debug, _ := cmd.Flags().GetBool("debug")

		if debug {
			logrus.SetLevel(logrus.DebugLevel)
			logrus.Info("🐛 Debug mode enabled - will show detailed job data")
		}

		runScraper(keywords, location, totalJobs)
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
	scrapeCmd.Flags().IntP("total-jobs", "t", 50, "Total number of jobs to scrape (LinkedIn shows 25 jobs per page)")
	scrapeCmd.Flags().BoolP("debug", "d", false, "Enable debug mode with detailed job data output")
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

func runScraper(keywords, location string, totalJobs int) {
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
	logrus.Infof("Starting to scrape %d jobs with keywords: %s, location: %s", totalJobs, keywords, location)
	
	err = jobScraper.ScrapeJobs(keywords, location, totalJobs)
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
