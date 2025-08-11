package config

import (
	"os"
	"os/exec"
	"strconv"
)

type Config struct {
	LinkedIn LinkedInConfig
	Scraper  ScraperConfig
	Redis    RedisConfig
	API      APIConfig
	Debug    DebugConfig
	LogLevel string
}

type LinkedInConfig struct {
	Email    string
	Password string
}

type ScraperConfig struct {
	DelayBetweenRequests  int
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
}

type APIConfig struct {
	BaseURL string
	APIKey  string
}

type DebugConfig struct {
	DumpDataToConsole bool
}

func Load() *Config {
	return &Config{
		LinkedIn: LinkedInConfig{
			Email:    getEnv("LINKEDIN_EMAIL", ""),
			Password: getEnv("LINKEDIN_PASSWORD", ""),
		},
		Scraper: ScraperConfig{
			DelayBetweenRequests:  getEnvAsInt("DELAY_BETWEEN_REQUESTS", 2),
			HeadlessBrowser:       getEnvAsBool("HEADLESS", true),
			UserDataDir:          getEnv("CHROME_USER_DATA_DIR", "./chrome-profile"),
			ChromeExecutablePath: getEnv("CHROME_EXECUTABLE_PATH", findChromeExecutable()),
		},
		Redis: RedisConfig{
			Host:     getEnv("REDIS_HOST", "localhost"),
			Port:     getEnv("REDIS_PORT", "6379"),
			Password: getEnv("REDIS_PASSWORD", ""),
			DB:       getEnvAsInt("REDIS_DB", 0),
			CacheTTL: getEnvAsInt("REDIS_CACHE_TTL", 3600),
		},
		API: APIConfig{
			BaseURL: getEnv("API_BASE_URL", "http://localhost:8000/api"),
			APIKey:  getEnv("API_KEY", ""),
		},
		Debug: DebugConfig{
			DumpDataToConsole: getEnvAsBool("DUMP_DATA_TO_CONSOLE", false),
		},
		LogLevel: getEnv("LOG_LEVEL", "info"),
	}
}

func getEnv(key, defaultValue string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return defaultValue
}

func getEnvAsInt(key string, defaultValue int) int {
	valueStr := getEnv(key, "")
	if value, err := strconv.Atoi(valueStr); err == nil {
		return value
	}
	return defaultValue
}

func getEnvAsBool(key string, defaultValue bool) bool {
	valueStr := getEnv(key, "")
	if value, err := strconv.ParseBool(valueStr); err == nil {
		return value
	}
	return defaultValue
}

// findChromeExecutable tries to find a Chrome/Chromium executable
func findChromeExecutable() string {
	// Common Chrome executable paths in order of preference
	paths := []string{
		"/usr/bin/google-chrome-stable",
		"/usr/bin/google-chrome",
		"/usr/bin/chromium",
		"/usr/bin/chromium-browser",
		"/snap/bin/chromium",
		"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome", // macOS
	}

	for _, path := range paths {
		if _, err := exec.LookPath(path); err == nil {
			return path
		}
		// Also check if file exists directly
		if _, err := os.Stat(path); err == nil {
			return path
		}
	}

	// Fallback to hoping it's in PATH
	if path, err := exec.LookPath("google-chrome"); err == nil {
		return path
	}
	if path, err := exec.LookPath("chromium"); err == nil {
		return path
	}

	// Final fallback
	return "/usr/bin/google-chrome-stable"
}
