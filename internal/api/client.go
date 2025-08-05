package api

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/models"
	"net/http"
	"net/url"
	"time"

	"github.com/sirupsen/logrus"
)

type Client struct {
	baseURL    string
	apiKey     string
	httpClient *http.Client
}

// NewClient creates a new API client
func NewClient(cfg *config.APIConfig) *Client {
	return &Client{
		baseURL: cfg.BaseURL,
		apiKey:  cfg.APIKey,
		httpClient: &http.Client{
			Timeout: 30 * time.Second,
		},
	}
}

// CompanyExistsResponse represents the response from the company exists endpoint
type CompanyExistsResponse struct {
	Exists  bool             `json:"exists"`
	Company *models.Company  `json:"company"`
}

// CompanyCreateResponse represents the response from creating a company
type CompanyCreateResponse struct {
	Success bool            `json:"success"`
	Message string          `json:"message"`
	Company *models.Company `json:"company"`
}

// JobExistsResponse represents the response from the job exists endpoint
type JobExistsResponse struct {
	Exists bool `json:"exists"`
}

// JobCreateResponse represents the response from creating a job
type JobCreateResponse struct {
	Success    bool                `json:"success"`
	Message    string              `json:"message"`
	JobPosting *models.JobPosting  `json:"job_posting"`
}

// JobIDsResponse represents the response structure for job IDs endpoint
type JobIDsResponse struct {
	Success       bool  `json:"success"`
	Count         int   `json:"count"`
	LinkedInJobIDs []int `json:"linkedin_job_ids"`
}

// CompanyNamesResponse represents the response structure for company names endpoint
type CompanyNamesResponse struct {
	Success      bool     `json:"success"`
	Count        int      `json:"count"`
	CompanyNames []string `json:"company_names"`
}

// CheckCompanyExists checks if a company exists via API
func (c *Client) CheckCompanyExists(name string) (*models.Company, error) {
	data := map[string]string{"name": name}
	jsonData, err := json.Marshal(data)
	if err != nil {
		return nil, fmt.Errorf("failed to marshal request: %w", err)
	}

	req, err := http.NewRequest("POST", c.baseURL+"/companies/exists", bytes.NewBuffer(jsonData))
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-API-Key", c.apiKey)

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("API request failed: %w", err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("failed to read response: %w", err)
	}

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("API error %d: %s", resp.StatusCode, string(body))
	}

	var response CompanyExistsResponse
	if err := json.Unmarshal(body, &response); err != nil {
		return nil, fmt.Errorf("failed to parse response: %w", err)
	}

	if response.Exists {
		return response.Company, nil
	}
	return nil, nil
}

// CreateCompany creates a new company via API
func (c *Client) CreateCompany(name string) (*models.Company, error) {
	data := map[string]string{"name": name}
	jsonData, err := json.Marshal(data)
	if err != nil {
		return nil, fmt.Errorf("failed to marshal request: %w", err)
	}

	req, err := http.NewRequest("POST", c.baseURL+"/companies", bytes.NewBuffer(jsonData))
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-API-Key", c.apiKey)

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("API request failed: %w", err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("failed to read response: %w", err)
	}

	if resp.StatusCode == http.StatusConflict {
		// Company already exists, try to get it
		logrus.Debugf("Company already exists, fetching existing company: %s", name)
		return c.CheckCompanyExists(name)
	}

	if resp.StatusCode != http.StatusCreated {
		return nil, fmt.Errorf("API error %d: %s", resp.StatusCode, string(body))
	}

	var response CompanyCreateResponse
	if err := json.Unmarshal(body, &response); err != nil {
		return nil, fmt.Errorf("failed to parse response: %w", err)
	}

	if !response.Success {
		return nil, fmt.Errorf("API response indicates failure: %s", response.Message)
	}

	return response.Company, nil
}

// CheckJobExists checks if a job exists via API
func (c *Client) CheckJobExists(linkedinJobID int) (bool, error) {
	params := url.Values{}
	params.Add("linkedin_job_id", fmt.Sprintf("%d", linkedinJobID))

	req, err := http.NewRequest("GET", c.baseURL+"/jobs/exists?"+params.Encode(), nil)
	if err != nil {
		return false, fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("X-API-Key", c.apiKey)

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return false, fmt.Errorf("API request failed: %w", err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return false, fmt.Errorf("failed to read response: %w", err)
	}

	if resp.StatusCode != http.StatusOK {
		return false, fmt.Errorf("API error %d: %s", resp.StatusCode, string(body))
	}

	var response JobExistsResponse
	if err := json.Unmarshal(body, &response); err != nil {
		return false, fmt.Errorf("failed to parse response: %w", err)
	}

	return response.Exists, nil
}

// CreateJob creates a new job posting via API
func (c *Client) CreateJob(job *models.JobPosting) (*models.JobPosting, error) {
	// Convert the job to API format
	apiJob := map[string]interface{}{
		"linkedin_job_id": job.LinkedInJobID,
		"title":           job.Title,
		"company_id":      job.CompanyID,
		"location":        job.Location,
		"description":     job.Description,
		"apply_url":       job.ApplyURL,
		"posted_date":     job.PostedDate.Format("2006-01-02"),
	}

	// Add optional fields
	if job.Applicants != nil {
		apiJob["applicants"] = *job.Applicants
	}
	if job.WorkType != nil {
		apiJob["work_type"] = *job.WorkType
	}
	if job.Skills != nil {
		apiJob["skills"] = *job.Skills
	}

	jsonData, err := json.Marshal(apiJob)
	if err != nil {
		return nil, fmt.Errorf("failed to marshal request: %w", err)
	}

	req, err := http.NewRequest("POST", c.baseURL+"/jobs", bytes.NewBuffer(jsonData))
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-API-Key", c.apiKey)

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("API request failed: %w", err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("failed to read response: %w", err)
	}

	if resp.StatusCode == http.StatusConflict {
		return nil, fmt.Errorf("job already exists (LinkedIn ID: %d)", job.LinkedInJobID)
	}

	if resp.StatusCode != http.StatusCreated {
		return nil, fmt.Errorf("API error %d: %s", resp.StatusCode, string(body))
	}

	var response JobCreateResponse
	if err := json.Unmarshal(body, &response); err != nil {
		return nil, fmt.Errorf("failed to parse response: %w", err)
	}

	if !response.Success {
		return nil, fmt.Errorf("API response indicates failure: %s", response.Message)
	}

	return response.JobPosting, nil
}

// GetAllJobIDs retrieves all LinkedIn job IDs from the API
func (c *Client) GetAllJobIDs() ([]int, error) {
	url := fmt.Sprintf("%s/jobs/ids", c.baseURL)

	req, err := http.NewRequest("GET", url, nil)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("X-API-Key", c.apiKey)
	req.Header.Set("Content-Type", "application/json")

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("failed to make request: %w", err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("failed to read response body: %w", err)
	}

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("API returned status %d: %s", resp.StatusCode, string(body))
	}

	var response JobIDsResponse
	if err := json.Unmarshal(body, &response); err != nil {
		return nil, fmt.Errorf("failed to parse response: %w", err)
	}

	if !response.Success {
		return nil, fmt.Errorf("API response indicates failure")
	}

	return response.LinkedInJobIDs, nil
}

// GetAllCompanyNames retrieves all company names from the API
func (c *Client) GetAllCompanyNames() ([]string, error) {
	url := fmt.Sprintf("%s/companies/names", c.baseURL)

	req, err := http.NewRequest("GET", url, nil)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("X-API-Key", c.apiKey)
	req.Header.Set("Content-Type", "application/json")

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("failed to make request: %w", err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("failed to read response body: %w", err)
	}

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("API returned status %d: %s", resp.StatusCode, string(body))
	}

	var response CompanyNamesResponse
	if err := json.Unmarshal(body, &response); err != nil {
		return nil, fmt.Errorf("failed to parse response: %w", err)
	}

	if !response.Success {
		return nil, fmt.Errorf("API response indicates failure")
	}

	return response.CompanyNames, nil
}
