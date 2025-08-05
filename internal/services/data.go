package services

import (
	"fmt"
	"linkedin-job-scraper/internal/api"
	"linkedin-job-scraper/internal/cache"
	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/models"
	"strconv"

	"github.com/sirupsen/logrus"
)

// DataService handles data operations with caching and API integration
type DataService struct {
	apiClient *api.Client
	cache     *cache.RedisCache
}

// NewDataService creates a new data service with API and cache
func NewDataService(cfg *config.Config) *DataService {
	apiClient := api.NewClient(&cfg.API)
	redisCache := cache.NewRedisCache(&cfg.Redis)

	return &DataService{
		apiClient: apiClient,
		cache:     redisCache,
	}
}

// JobExists checks if a job exists, using cache first, then API
func (s *DataService) JobExists(linkedinJobID int) (bool, error) {
	// Check cache first
	if exists, found := s.cache.JobExistsInCache(linkedinJobID); found {
		logrus.Debugf("🎯 Cache hit for job existence: %d = %v", linkedinJobID, exists)
		return exists, nil
	}

	// Cache miss, check via API
	logrus.Debugf("💻 Cache miss, checking job via API: %d", linkedinJobID)
	exists, err := s.apiClient.CheckJobExists(linkedinJobID)
	if err != nil {
		return false, fmt.Errorf("failed to check job existence via API: %w", err)
	}

	// Cache the result
	s.cache.SetJobExists(linkedinJobID, exists)

	return exists, nil
}

// CreateOrGetCompany gets an existing company or creates a new one
func (s *DataService) CreateOrGetCompany(name string) (*models.Company, error) {
	// Check cache first
	if company, found := s.cache.GetCompanyByName(name); found {
		logrus.Debugf("🎯 Cache hit for company: %s (ID: %d)", name, company.CompanyID)
		return company, nil
	}

	// Cache miss, check via API
	logrus.Debugf("💻 Cache miss, checking company via API: %s", name)
	company, err := s.apiClient.CheckCompanyExists(name)
	if err != nil {
		return nil, fmt.Errorf("failed to check company existence via API: %w", err)
	}

	// If company exists, cache it and return
	if company != nil {
		s.cache.SetCompany(company)
		return company, nil
	}

	// Company doesn't exist, create it
	logrus.Debugf("🆕 Creating new company via API: %s", name)
	company, err = s.apiClient.CreateCompany(name)
	if err != nil {
		return nil, fmt.Errorf("failed to create company via API: %w", err)
	}

	// Cache the new company
	s.cache.SetCompany(company)

	return company, nil
}

// CreateJob creates a new job posting
func (s *DataService) CreateJob(job *models.JobPosting) (*models.JobPosting, error) {
	// Double-check that job doesn't exist (should be filtered out earlier, but safety check)
	exists, err := s.JobExists(job.LinkedInJobID)
	if err != nil {
		return nil, fmt.Errorf("failed to check job existence before creation: %w", err)
	}

	if exists {
		logrus.Debugf("⏭️  Job %d already exists, skipping creation", job.LinkedInJobID)
		return nil, fmt.Errorf("job already exists (LinkedIn ID: %d)", job.LinkedInJobID)
	}

	// Create job via API
	logrus.Debugf("🆕 Creating new job via API: %d - %s", job.LinkedInJobID, job.Title)
	createdJob, err := s.apiClient.CreateJob(job)
	if err != nil {
		return nil, fmt.Errorf("failed to create job via API: %w", err)
	}

	// Update cache to reflect that this job now exists
	s.cache.SetJobExists(job.LinkedInJobID, true)

	return createdJob, nil
}

// PreloadJobIDsToCache fetches all LinkedIn job IDs from API and populates Redis cache
func (s *DataService) PreloadJobIDsToCache() error {
	logrus.Info("🔄 Preloading existing job IDs to Redis cache...")

	// Get all LinkedIn job IDs from API
	jobIDs, err := s.apiClient.GetAllJobIDs()
	if err != nil {
		return fmt.Errorf("failed to fetch job IDs from API: %w", err)
	}

	if len(jobIDs) == 0 {
		logrus.Info("📝 No existing jobs found in database")
		return nil
	}

	// Add all job IDs to cache as existing
	for _, jobID := range jobIDs {
		s.cache.SetJobExists(jobID, true)
	}

	logrus.Infof("✅ Successfully preloaded %d job IDs to Redis cache", len(jobIDs))
	return nil
}

// PreloadCompanyNamesToCache fetches all company names from API and populates Redis cache
func (s *DataService) PreloadCompanyNamesToCache() error {
	logrus.Info("🔄 Preloading existing company names to Redis cache...")

	// Get all company names from API
	companyNames, err := s.apiClient.GetAllCompanyNames()
	if err != nil {
		return fmt.Errorf("failed to fetch company names from API: %w", err)
	}

	if len(companyNames) == 0 {
		logrus.Info("📝 No existing companies found in database")
		return nil
	}

	// Add all company names to cache as existing
	for _, companyName := range companyNames {
		s.cache.SetCompanyExists(companyName, true)
	}

	logrus.Infof("✅ Successfully preloaded %d company names to Redis cache", len(companyNames))
	return nil
}

// ExtractJobIDFromURL extracts the LinkedIn job ID from a job URL (helper method)
func (s *DataService) ExtractJobIDFromURL(jobURL string) (int, error) {
	// This is the same logic from the scraper - we can move it here for reuse
	for _, part := range splitURL(jobURL) {
		if len(part) > 8 && containsDigits(part) {
			// Remove query parameters
			if idx := findIndex(part, "?"); idx != -1 {
				part = part[:idx]
			}

			if jobID, err := strconv.Atoi(part); err == nil {
				return jobID, nil
			}
		}
	}
	return 0, fmt.Errorf("could not extract job ID from URL: %s", jobURL)
}

// IsJobNew checks if a job is new (not in cache or API)
func (s *DataService) IsJobNew(jobURL string) bool {
	jobID, err := s.ExtractJobIDFromURL(jobURL)
	if err != nil {
		logrus.Warnf("⚠️  Could not extract job ID from URL: %s", jobURL)
		return false
	}

	exists, err := s.JobExists(jobID)
	if err != nil {
		logrus.Warnf("⚠️  Error checking if job exists: %v", err)
		return false
	}

	return !exists
}

// Close closes the cache connection
func (s *DataService) Close() error {
	return s.cache.Close()
}

// Helper functions
func splitURL(url string) []string {
	result := []string{}
	current := ""
	for _, char := range url {
		if char == '/' {
			if current != "" {
				result = append(result, current)
				current = ""
			}
		} else {
			current += string(char)
		}
	}
	if current != "" {
		result = append(result, current)
	}
	return result
}

func containsDigits(s string) bool {
	for _, char := range s {
		if char >= '0' && char <= '9' {
			return true
		}
	}
	return false
}

func findIndex(s, substr string) int {
	for i := 0; i <= len(s)-len(substr); i++ {
		if s[i:i+len(substr)] == substr {
			return i
		}
	}
	return -1
}
