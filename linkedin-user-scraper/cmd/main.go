package main

import (
	"fmt"
	"log"
	"os"
	"strings"

	"linkedin-user-scraper/internal/config"
	"linkedin-user-scraper/internal/scraper"
	"linkedin-user-scraper/internal/services"

	"github.com/joho/godotenv"
	"github.com/sirupsen/logrus"
	"github.com/spf13/cobra"
)

var rootCmd = &cobra.Command{
	Use:   "linkedin-user-scraper",
	Short: "LinkedIn User Profile Scraper CLI",
	Long:  "A CLI tool to scrape LinkedIn user profiles and store them in a database",
}

var scrapeUserCmd = &cobra.Command{
	Use:   "scrape-user",
	Short: "Scrape a single LinkedIn user profile",
	Run: func(cmd *cobra.Command, args []string) {
		username, _ := cmd.Flags().GetString("username")
		debug, _ := cmd.Flags().GetBool("debug")

		if debug {
			logrus.SetLevel(logrus.DebugLevel)
			logrus.Info("🐛 Debug mode enabled")
		}

		// Extract username from URL if full URL was provided
		if strings.Contains(username, "linkedin.com/in/") {
			parts := strings.Split(username, "/in/")
			if len(parts) > 1 {
				username = strings.TrimSuffix(parts[1], "/")
			}
		}

		runUserScraper(username)
	},
}

var addToQueueCmd = &cobra.Command{
	Use:   "add-to-queue",
	Short: "Add LinkedIn usernames/URLs to processing queue",
	Run: func(cmd *cobra.Command, args []string) {
		usernames, _ := cmd.Flags().GetStringSlice("usernames")
		file, _ := cmd.Flags().GetString("file")
		debug, _ := cmd.Flags().GetBool("debug")

		if debug {
			logrus.SetLevel(logrus.DebugLevel)
			logrus.Info("🐛 Debug mode enabled")
		}

		runAddToQueue(usernames, file)
	},
}

var processQueueCmd = &cobra.Command{
	Use:   "process-queue",
	Short: "Process LinkedIn user profiles from Redis queue",
	Run: func(cmd *cobra.Command, args []string) {
		limit, _ := cmd.Flags().GetInt("limit")
		debug, _ := cmd.Flags().GetBool("debug")

		if debug {
			logrus.SetLevel(logrus.DebugLevel)
			logrus.Info("🐛 Debug mode enabled")
		}

		runProcessQueue(limit)
	},
}

var clearQueueCmd = &cobra.Command{
	Use:   "clear-queue",
	Short: "Clear the user processing queue",
	Run: func(cmd *cobra.Command, args []string) {
		runClearQueue()
	},
}

func init() {
	// Scrape user command flags
	scrapeUserCmd.Flags().StringP("username", "u", "", "LinkedIn username or full profile URL (required)")
	scrapeUserCmd.Flags().BoolP("debug", "d", false, "Enable debug mode")
	scrapeUserCmd.MarkFlagRequired("username")

	// Add to queue command flags
	addToQueueCmd.Flags().StringSliceP("usernames", "u", []string{}, "LinkedIn usernames or URLs (comma-separated)")
	addToQueueCmd.Flags().StringP("file", "f", "", "File containing LinkedIn usernames/URLs (one per line)")
	addToQueueCmd.Flags().BoolP("debug", "d", false, "Enable debug mode")

	// Process queue command flags
	processQueueCmd.Flags().IntP("limit", "l", 50, "Maximum number of users to process from queue")
	processQueueCmd.Flags().BoolP("debug", "d", false, "Enable debug mode")

	rootCmd.AddCommand(scrapeUserCmd)
	rootCmd.AddCommand(addToQueueCmd)
	rootCmd.AddCommand(processQueueCmd)
	rootCmd.AddCommand(clearQueueCmd)
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

func runUserScraper(username string) {
	// Initialize configuration
	cfg := config.Load()

	// Setup logging
	setupLogging(cfg.LogLevel)

	// Initialize data service
	dataService := services.NewDataService(cfg)
	defer dataService.Close()

	// Initialize scraper
	userScraper := scraper.NewLinkedInUserScraper(cfg, dataService)

	// Start scraping
	logrus.Infof("🚀 Starting to scrape user: %s", username)

	err := userScraper.ScrapeUser(username)
	if err != nil {
		logrus.Fatal("User scraping failed: ", err)
	}

	logrus.Info("✅ User scraping completed successfully")
}

func runAddToQueue(usernames []string, file string) {
	// Initialize configuration
	cfg := config.Load()

	// Setup logging
	setupLogging(cfg.LogLevel)

	// Initialize data service
	dataService := services.NewDataService(cfg)
	defer dataService.Close()

	var usersToAdd []string

	// Add usernames from command line
	usersToAdd = append(usersToAdd, usernames...)

	// Add usernames from file
	if file != "" {
		fileUsers, err := readUsernamesFromFile(file)
		if err != nil {
			logrus.Fatal("Failed to read usernames from file: ", err)
		}
		usersToAdd = append(usersToAdd, fileUsers...)
	}

	if len(usersToAdd) == 0 {
		logrus.Fatal("No usernames provided. Use --usernames or --file")
	}

	// Add users to queue
	logrus.Infof("📝 Adding %d users to queue...", len(usersToAdd))
	
	for i, username := range usersToAdd {
		// Clean username
		username = strings.TrimSpace(username)
		if username == "" {
			continue
		}

		// Extract username from URL if needed
		if strings.Contains(username, "linkedin.com/in/") {
			parts := strings.Split(username, "/in/")
			if len(parts) > 1 {
				username = strings.TrimSuffix(parts[1], "/")
			}
		}

		err := dataService.AddUserToQueue(username)
		if err != nil {
			logrus.Errorf("❌ Failed to add user %s to queue: %v", username, err)
			continue
		}

		logrus.Infof("✅ Added user %d/%d to queue: %s", i+1, len(usersToAdd), username)
	}

	logrus.Info("✅ Finished adding users to queue")
}

func runProcessQueue(limit int) {
	// Initialize configuration
	cfg := config.Load()

	// Setup logging
	setupLogging(cfg.LogLevel)

	// Initialize data service
	dataService := services.NewDataService(cfg)
	defer dataService.Close()

	// Initialize scraper
	userScraper := scraper.NewLinkedInUserScraper(cfg, dataService)

	// Check queue size
	queueSize, err := dataService.GetQueueSize()
	if err != nil {
		logrus.Warnf("⚠️  Could not get queue size: %v", err)
	} else {
		logrus.Infof("📊 Queue contains %d users", queueSize)
	}

	// Start processing
	logrus.Infof("⚙️  Starting user processing from queue (limit: %d)", limit)

	err = userScraper.ProcessUsersFromQueue(limit)
	if err != nil {
		logrus.Fatal("User processing failed: ", err)
	}

	logrus.Info("✅ User processing completed successfully")
}

func runClearQueue() {
	// Initialize configuration
	cfg := config.Load()

	// Setup logging
	setupLogging(cfg.LogLevel)

	// Initialize data service
	dataService := services.NewDataService(cfg)
	defer dataService.Close()

	// Clear queue
	logrus.Info("🧹 Clearing user processing queue...")

	err := dataService.ClearUserQueue()
	if err != nil {
		logrus.Fatal("Failed to clear queue: ", err)
	}

	logrus.Info("✅ Queue cleared successfully")
}

func readUsernamesFromFile(filename string) ([]string, error) {
	content, err := os.ReadFile(filename)
	if err != nil {
		return nil, err
	}

	lines := strings.Split(string(content), "\n")
	var usernames []string

	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line != "" && !strings.HasPrefix(line, "#") {
			usernames = append(usernames, line)
		}
	}

	return usernames, nil
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
