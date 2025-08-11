package services

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"linkedin-user-scraper/internal/config"
	"linkedin-user-scraper/internal/models"
	"net/http"
	"strings"
	"time"

	"github.com/redis/go-redis/v9"
	"github.com/sirupsen/logrus"
)

// Simplified structures for clean JSON output
type SimplifiedUser struct {
	ID              int                      `json:"id"`
	LinkedInURL     string                   `json:"linkedin_url"`
	Headline        string                   `json:"headline,omitempty"`
	Summary         string                   `json:"summary,omitempty"`
	LocationCity    string                   `json:"location_city,omitempty"`
	Avatar          string                   `json:"avatar,omitempty"`
	Positions       []SimplifiedPosition     `json:"positions"`
	Educations      []SimplifiedEducation    `json:"educations"`
	SkillFrequencies map[string]int          `json:"skill_frequencies"`
}

type SimplifiedPosition struct {
	Title       string    `json:"title"`
	CompanyName string    `json:"company_name"`
	Summary     string    `json:"summary,omitempty"`
	Location    string    `json:"location,omitempty"`
	StartDate   *time.Time `json:"start_date,omitempty"`
	EndDate     *time.Time `json:"end_date,omitempty"`
	Skills      []string  `json:"skills,omitempty"`
}

type SimplifiedEducation struct {
	SchoolName  string   `json:"school_name"`
	Degree      string   `json:"degree,omitempty"`
	StartYear   *int     `json:"start_year,omitempty"`
	EndYear     *int     `json:"end_year,omitempty"`
	Skills      []string `json:"skills,omitempty"`
}

type DataService struct {
	config      *config.Config
	redisClient *redis.Client
	httpClient  *http.Client
}

// NewDataService creates a new data service instance
func NewDataService(cfg *config.Config) *DataService {
	// Initialize Redis client
	rdb := redis.NewClient(&redis.Options{
		Addr:     fmt.Sprintf("%s:%s", cfg.Redis.Host, cfg.Redis.Port),
		Password: cfg.Redis.Password,
		DB:       cfg.Redis.DB,
	})

	// Test Redis connection
	ctx := context.Background()
	_, err := rdb.Ping(ctx).Result()
	if err != nil {
		logrus.Warnf("Redis connection failed: %v", err)
	} else {
		logrus.Info("✅ Redis connected successfully")
	}

	return &DataService{
		config:      cfg,
		redisClient: rdb,
		httpClient: &http.Client{
			Timeout: 30 * time.Second,
		},
	}
}

// Close closes the data service connections
func (ds *DataService) Close() {
	if ds.redisClient != nil {
		ds.redisClient.Close()
	}
}

// convertToSimplifiedUser converts a full User model to simplified structure
func (ds *DataService) convertToSimplifiedUser(user *models.User) *SimplifiedUser {
	simplified := &SimplifiedUser{
		ID:          user.ID,
		LinkedInURL: user.LinkedInURL,
		Headline:    user.Headline,
		Summary:     user.Summary,
		LocationCity: user.LocationCity,
		Avatar:      user.Avatar,
		Positions:   make([]SimplifiedPosition, 0),
		Educations:  make([]SimplifiedEducation, 0),
		SkillFrequencies: make(map[string]int),
	}

	// Convert positions
	for _, pos := range user.Positions {
		simplifiedPos := SimplifiedPosition{
			Title:       pos.Title,
			CompanyName: pos.CompanyName,
			Summary:     pos.Summary,
			Location:    pos.Location,
			StartDate:   pos.StartDate,
			EndDate:     pos.EndDate,
			Skills:      make([]string, 0),
		}
		
		// Extract skill names
		for _, skill := range pos.Skills {
			simplifiedPos.Skills = append(simplifiedPos.Skills, skill.Name)
		}
		
		simplified.Positions = append(simplified.Positions, simplifiedPos)
	}

	// Convert educations
	for _, edu := range user.Educations {
		simplifiedEdu := SimplifiedEducation{
			SchoolName: edu.SchoolName,
			Degree:     edu.Degree,
			StartYear:  edu.StartYear,
			EndYear:    edu.EndYear,
			Skills:     make([]string, 0),
		}
		
		// Extract skill names
		for _, skill := range edu.Skills {
			simplifiedEdu.Skills = append(simplifiedEdu.Skills, skill.Name)
		}
		
		simplified.Educations = append(simplified.Educations, simplifiedEdu)
	}

	// Convert skill frequencies
	for _, freq := range user.SkillFrequencies {
		simplified.SkillFrequencies[freq.Skill.Name] = freq.Frequency
	}

	return simplified
}

// SaveUser saves a user to the API or dumps to console based on configuration
func (ds *DataService) SaveUser(user *models.User) error {
	logrus.Debugf("💾 Saving user: %s", user.LinkedInURL)

	// Check if we should dump to console instead of API
	if ds.config.Debug.DumpDataToConsole {
		logrus.Info("🖥️  DUMP_DATA_TO_CONSOLE=true - Outputting user data to console:")
		logrus.Info(strings.Repeat("=", 80))
		
		// Convert to simplified structure
		simplified := ds.convertToSimplifiedUser(user)
		
		// Convert simplified user to JSON
		jsonData, err := json.Marshal(simplified)
		if err != nil {
			return fmt.Errorf("failed to marshal simplified user data: %w", err)
		}
		
		// Pretty print JSON
		var prettyJSON bytes.Buffer
		err = json.Indent(&prettyJSON, jsonData, "", "  ")
		if err != nil {
			logrus.Error("Failed to format JSON:", err)
			fmt.Println(string(jsonData))
		} else {
			fmt.Println(prettyJSON.String())
		}
		
		logrus.Info(strings.Repeat("=", 80))
		logrus.Infof("✅ User data dumped to console: %s", user.LinkedInURL)
	}

	// Send to Laravel API - convert to simplified structure
	simplified := ds.convertToSimplifiedUser(user)
	
	// Convert simplified user to JSON
	jsonData, err := json.Marshal(simplified)
	if err != nil {
		return fmt.Errorf("failed to marshal simplified user data: %w", err)
	}

	// Extract LinkedIn username from URL for the API endpoint
	linkedinUsername, err := ds.extractLinkedInUsername(user.LinkedInURL)
	if err != nil {
		return fmt.Errorf("failed to extract LinkedIn username: %w", err)
	}

	// Use PUT request to the Laravel API endpoint
	url := fmt.Sprintf("%s/linkedin-profile/users/%s", ds.config.API.BaseURL, linkedinUsername)
	req, err := http.NewRequest("PUT", url, bytes.NewBuffer(jsonData))
	if err != nil {
		return fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-API-Key", ds.config.API.APIKey)

	logrus.Infof("📡 Sending data to Laravel API: %s", url)
	
	resp, err := ds.httpClient.Do(req)
	if err != nil {
		return fmt.Errorf("failed to make API request: %w", err)
	}
	defer resp.Body.Close()

	// Read response body for debugging
	body, _ := io.ReadAll(resp.Body)
	
	if resp.StatusCode != http.StatusOK && resp.StatusCode != http.StatusCreated {
		return fmt.Errorf("API request failed with status %d: %s", resp.StatusCode, string(body))
	}

	logrus.Infof("✅ User saved successfully to Laravel API: %s (Status: %d)", user.LinkedInURL, resp.StatusCode)
	logrus.Debugf("📡 API Response: %s", string(body))
	return nil
}

// extractLinkedInUsername extracts the username from a LinkedIn URL
func (ds *DataService) extractLinkedInUsername(linkedinURL string) (string, error) {
	// LinkedIn URL format: https://www.linkedin.com/in/username/
	parts := strings.Split(linkedinURL, "/")
	for i, part := range parts {
		if part == "in" && i+1 < len(parts) {
			username := parts[i+1]
			// Remove trailing slash if present
			username = strings.TrimSuffix(username, "/")
			if username != "" {
				return username, nil
			}
		}
	}
	return "", fmt.Errorf("could not extract username from LinkedIn URL: %s", linkedinURL)
}

// CheckUserExists checks if a user already exists by LinkedIn URL
func (ds *DataService) CheckUserExists(linkedinURL string) (bool, error) {
	// If dumping to console, always return false (don't check for existence)
	if ds.config.Debug.DumpDataToConsole {
		logrus.Debugf("🖥️  DUMP_DATA_TO_CONSOLE=true - Skipping user existence check: %s", linkedinURL)
		return false, nil
	}

	// First check Redis cache
	cacheKey := fmt.Sprintf("user_exists:%s", linkedinURL)
	exists, err := ds.redisClient.Exists(context.Background(), cacheKey).Result()
	if err == nil && exists > 0 {
		logrus.Debugf("🎯 User exists (from cache): %s", linkedinURL)
		return true, nil
	}

	// If not in cache, check via Laravel API
	linkedinUsername, err := ds.extractLinkedInUsername(linkedinURL)
	if err != nil {
		logrus.Warnf("⚠️  Could not extract username from LinkedIn URL, assuming user doesn't exist: %s", linkedinURL)
		return false, nil
	}

	url := fmt.Sprintf("%s/linkedin-profile/users/%s", ds.config.API.BaseURL, linkedinUsername)
	req, err := http.NewRequest("GET", url, nil)
	if err != nil {
		return false, fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("X-API-Key", ds.config.API.APIKey)

	resp, err := ds.httpClient.Do(req)
	if err != nil {
		logrus.Warnf("⚠️  Failed to check user existence via API, assuming user doesn't exist: %s", err)
		return false, nil
	}
	defer resp.Body.Close()

	userExists := resp.StatusCode == http.StatusOK

	// Cache the result
	if userExists {
		ds.redisClient.Set(context.Background(), cacheKey, "1", time.Duration(ds.config.Redis.CacheTTL)*time.Second)
		logrus.Debugf("🎯 User exists (from API, cached): %s", linkedinURL)
	}

	return userExists, nil
}

// GetQueueSize returns the size of the user processing queue
func (ds *DataService) GetQueueSize() (int, error) {
	size, err := ds.redisClient.LLen(context.Background(), "user_queue").Result()
	if err != nil {
		return 0, err
	}
	return int(size), nil
}

// AddUserToQueue adds a LinkedIn URL to the processing queue
func (ds *DataService) AddUserToQueue(linkedinURL string) error {
	return ds.redisClient.RPush(context.Background(), "user_queue", linkedinURL).Err()
}

// GetUserFromQueue gets a LinkedIn URL from the processing queue
func (ds *DataService) GetUserFromQueue() (string, error) {
	result, err := ds.redisClient.LPop(context.Background(), "user_queue").Result()
	if err == redis.Nil {
		return "", nil // Queue is empty
	}
	return result, err
}

// ClearUserQueue clears the user processing queue
func (ds *DataService) ClearUserQueue() error {
	return ds.redisClient.Del(context.Background(), "user_queue").Err()
}
