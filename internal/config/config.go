package config

import (
	"os"
	"strconv"
)

type Config struct {
	Database DatabaseConfig
	LinkedIn LinkedInConfig
	Scraper  ScraperConfig
	LogLevel string
}

type DatabaseConfig struct {
	Host     string
	Port     string
	User     string
	Password string
	DBName   string
}

type LinkedInConfig struct {
	Email    string
	Password string
}

type ScraperConfig struct {
	MaxPages              int
	DelayBetweenRequests  int
	ConcurrentWorkers     int
	HeadlessBrowser       bool
	UserDataDir           string
	ChromeExecutablePath  string
}

func Load() *Config {
	return &Config{
		Database: DatabaseConfig{
			Host:     getEnv("DB_HOST", "localhost"),
			Port:     getEnv("DB_PORT", "3306"),
			User:     getEnv("DB_USER", "root"),
			Password: getEnv("DB_PASSWORD", ""),
			DBName:   getEnv("DB_NAME", "linkedin_jobs"),
		},
		LinkedIn: LinkedInConfig{
			Email:    getEnv("LINKEDIN_EMAIL", ""),
			Password: getEnv("LINKEDIN_PASSWORD", ""),
		},
		Scraper: ScraperConfig{
			MaxPages:              getEnvAsInt("MAX_PAGES", 10),
			DelayBetweenRequests:  getEnvAsInt("DELAY_BETWEEN_REQUESTS", 2),
			ConcurrentWorkers:     getEnvAsInt("CONCURRENT_WORKERS", 3),
			HeadlessBrowser:       getEnvAsBool("HEADLESS_BROWSER", true), // Already defaults to true (headless)
			UserDataDir:           getEnv("USER_DATA_DIR", "./chrome-profile"),
			ChromeExecutablePath:  getEnv("CHROME_EXECUTABLE_PATH", "/usr/bin/chromium"),
		},
		LogLevel: getEnv("LOG_LEVEL", "info"),
	}
}

func getEnv(key, defaultVal string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return defaultVal
}

func getEnvAsInt(key string, defaultVal int) int {
	strVal := getEnv(key, "")
	if value, err := strconv.Atoi(strVal); err == nil {
		return value
	}
	return defaultVal
}

func getEnvAsBool(key string, defaultVal bool) bool {
	strVal := getEnv(key, "")
	if value, err := strconv.ParseBool(strVal); err == nil {
		return value
	}
	return defaultVal
}
