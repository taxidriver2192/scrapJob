package config

import (
	"os"
	"strconv"
)

type Config struct {
	LinkedIn LinkedInConfig
	Scraper  ScraperConfig
	Redis    RedisConfig
	API      APIConfig
	LogLevel string
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

type RedisConfig struct {
	Host        string
	Port        string
	Password    string
	DB          int
	CacheTTL    int
	JobExistsTTL int
}

type APIConfig struct {
	BaseURL string
	APIKey  string
}

func Load() *Config {
	return &Config{
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
		Redis: RedisConfig{
			Host:         getEnv("REDIS_HOST", "127.0.0.1"),
			Port:         getEnv("REDIS_PORT", "6379"),
			Password:     getEnv("REDIS_PASSWORD", ""),
			DB:           getEnvAsInt("REDIS_DB", 0),
			CacheTTL:     getEnvAsInt("REDIS_CACHE_TTL", 300),
			JobExistsTTL: getEnvAsInt("REDIS_JOB_EXISTS_TTL", 120),
		},
		API: APIConfig{
			BaseURL: getEnv("API_BASE_URL", "http://localhost:8082/api"),
			APIKey:  getEnv("API_KEY", ""),
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
