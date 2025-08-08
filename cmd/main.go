package main

import (
	"fmt"
	"log"
	"os"

	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/scraper"
	"linkedin-job-scraper/internal/services"

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
	Short: "Start scraping LinkedIn jobs (legacy - use discover + process instead)",
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

var discoverCmd = &cobra.Command{
	Use:   "discover",
	Short: "Discover new job IDs and store them in Redis queue",
	Run: func(cmd *cobra.Command, args []string) {
		keywords, _ := cmd.Flags().GetString("keywords")
		location, _ := cmd.Flags().GetString("location")
		totalJobs, _ := cmd.Flags().GetInt("total-jobs")
		startFrom, _ := cmd.Flags().GetInt("start-from")
		debug, _ := cmd.Flags().GetBool("debug")

		if debug {
			logrus.SetLevel(logrus.DebugLevel)
			logrus.Info("🐛 Debug mode enabled - will show detailed discovery process")
		}

		runDiscovery(keywords, location, totalJobs, startFrom)
	},
}

var processCmd = &cobra.Command{
	Use:   "process",
	Short: "Process job IDs from Redis queue and scrape detailed data",
	Run: func(cmd *cobra.Command, args []string) {
		limit, _ := cmd.Flags().GetInt("limit")
		debug, _ := cmd.Flags().GetBool("debug")

		if debug {
			logrus.SetLevel(logrus.DebugLevel)
			logrus.Info("🐛 Debug mode enabled - will show detailed processing data")
		}

		runProcessing(limit)
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
	// Scrape command flags (legacy)
	scrapeCmd.Flags().StringP("keywords", "k", "", "Job search keywords (required)")
	scrapeCmd.Flags().StringP("location", "l", "", "Job search location (required)")
	scrapeCmd.Flags().IntP("total-jobs", "t", 50, "Total number of jobs to scrape (LinkedIn shows 25 jobs per page)")
	scrapeCmd.Flags().BoolP("debug", "d", false, "Enable debug mode with detailed job data output")
	scrapeCmd.MarkFlagRequired("keywords")
	scrapeCmd.MarkFlagRequired("location")

	// Discover command flags
	discoverCmd.Flags().StringP("keywords", "k", "", "Job search keywords (required)")
	discoverCmd.Flags().StringP("location", "l", "", "Job search location (required)")
	discoverCmd.Flags().IntP("total-jobs", "t", 100, "Total number of job IDs to discover")
	discoverCmd.Flags().IntP("start-from", "s", 0, "Start from specific result number (default: 0)")
	discoverCmd.Flags().BoolP("debug", "d", false, "Enable debug mode")
	discoverCmd.MarkFlagRequired("keywords")
	discoverCmd.MarkFlagRequired("location")

	// Process command flags
	var limit int
	processCmd.Flags().IntVarP(&limit, "limit", "l", 50, "Maximum number of jobs to process from queue")
	processCmd.Flags().BoolP("debug", "d", false, "Enable debug mode")

	var clearCacheCmd = &cobra.Command{
		Use:   "clear-cache",
		Short: "Clear polluted job existence cache and processing queue",
		RunE: func(cmd *cobra.Command, args []string) error {
			cfg := config.Load()
			dataService := services.NewDataService(cfg)

			fmt.Println("🧹 Clearing polluted job existence cache and processing queue...")
			if err := dataService.ClearJobExistsCache(); err != nil {
				return fmt.Errorf("failed to clear cache: %w", err)
			}
			fmt.Println("✅ Cache and queue cleared successfully!")
			return nil
		},
	}

	rootCmd.AddCommand(scrapeCmd)
	rootCmd.AddCommand(discoverCmd)
	rootCmd.AddCommand(processCmd)
	rootCmd.AddCommand(clearCacheCmd)
	rootCmd.AddCommand(migrateCmd)
	rootCmd.AddCommand(clearCacheCmd)
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

	// Initialize data service (Redis + API)
	dataService := services.NewDataService(cfg)
	defer dataService.Close()

	// Initialize scraper
	jobScraper := scraper.NewLinkedInScraper(cfg, dataService)

	// Start scraping
	logrus.Infof("Starting to scrape %d jobs with keywords: %s, location: %s", totalJobs, keywords, location)

	err := jobScraper.ScrapeJobs(keywords, location, totalJobs)
	if err != nil {
		logrus.Fatal("Scraping failed: ", err)
	}

	logrus.Info("Scraping completed successfully")
}

func runDiscovery(keywords, location string, totalJobs, startFrom int) {
	// Initialize configuration
	cfg := config.Load()

	// Setup logging
	setupLogging(cfg.LogLevel)

	// Initialize data service (Redis + API)
	dataService := services.NewDataService(cfg)
	defer dataService.Close()

	// Initialize scraper
	jobScraper := scraper.NewLinkedInScraper(cfg, dataService)

	// Auto-calculate start position from Redis queue size if not specified
	if startFrom == 0 {
		queueSize, err := dataService.GetQueueSize()
		if err != nil {
			logrus.Warnf("Could not get queue size from Redis, starting from 0: %v", err)
			startFrom = 0
		} else {
			startFrom = queueSize
			logrus.Infof("📊 Found %d jobs in Redis queue, starting discovery from result %d", queueSize, startFrom)
		}
	}

	// Start job ID discovery
	if startFrom > 0 {
		logrus.Infof("🔍 Starting job ID discovery: %d jobs with keywords: %s, location: %s, starting from result: %d", totalJobs, keywords, location, startFrom)
	} else {
		logrus.Infof("🔍 Starting job ID discovery: %d jobs with keywords: %s, location: %s", totalJobs, keywords, location)
	}

	err := jobScraper.DiscoverJobIDs(keywords, location, totalJobs, startFrom)
	if err != nil {
		logrus.Fatal("Job ID discovery failed: ", err)
	}

	logrus.Info("✅ Job ID discovery completed successfully")
}

func runProcessing(limit int) {
	// Initialize configuration
	cfg := config.Load()

	// Setup logging
	setupLogging(cfg.LogLevel)

	// Initialize data service (Redis + API)
	dataService := services.NewDataService(cfg)
	defer dataService.Close()

	// Initialize scraper
	jobScraper := scraper.NewLinkedInScraper(cfg, dataService)

	// Start processing jobs from Redis queue
	logrus.Infof("⚙️  Starting job processing from Redis queue (limit: %d)", limit)

	err := jobScraper.ProcessJobsFromQueue(limit)
	if err != nil {
		logrus.Fatal("Job processing failed: ", err)
	}

	logrus.Info("✅ Job processing completed successfully")
}

func runMigrations() {
	logrus.Info("Database migrations are now handled by the Laravel API backend")
	logrus.Info("Please run migrations on the Laravel application instead")
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
