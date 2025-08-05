package services

import (
	"encoding/json"
	"fmt"
	"io"
	"linkedin-job-scraper/internal/models"
	"net/http"
	"net/url"
	"os"
	"strings"
)

// CompanyExistsResponse represents the response from the company exists API
type CompanyExistsResponse struct {
	Exists  bool              `json:"exists"`
	Company *models.Company   `json:"company"`
}

// CreateCompanyRequest represents the request to create a company
type CreateCompanyRequest struct {
	Name     string `json:"name"`
	ImageURL string `json:"image_url"`
}

// CreateCompanyResponse represents the response from creating a company
type CreateCompanyResponse struct {
	Success bool            `json:"success"`
	Message string          `json:"message"`
	Company models.Company  `json:"company"`
}

// CompanyExists checks if a company exists by name
func (ds *DataService) CompanyExists(companyName string) (bool, int, error) {
	// Get API configuration from environment
	baseURL := os.Getenv("API_BASE_URL")
	apiKey := os.Getenv("API_KEY")
	
	if baseURL == "" {
		return false, 0, fmt.Errorf("API_BASE_URL not configured")
	}
	if apiKey == "" {
		return false, 0, fmt.Errorf("API_KEY not configured")
	}
	
	// URL encode the company name
	encodedName := url.QueryEscape(companyName)
	requestURL := fmt.Sprintf("%s/companies/exists?name=%s", baseURL, encodedName)
	
	req, err := http.NewRequest("GET", requestURL, nil)
	if err != nil {
		return false, 0, fmt.Errorf("failed to create request: %w", err)
	}
	
	req.Header.Set("X-API-Key", apiKey)
	req.Header.Set("Content-Type", "application/json")
	
	client := &http.Client{}
	resp, err := client.Do(req)
	if err != nil {
		return false, 0, fmt.Errorf("failed to make request: %w", err)
	}
	defer resp.Body.Close()
	
	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return false, 0, fmt.Errorf("failed to read response: %w", err)
	}
	
	if resp.StatusCode != http.StatusOK {
		return false, 0, fmt.Errorf("API returned status %d: %s", resp.StatusCode, string(body))
	}
	
	var response CompanyExistsResponse
	if err := json.Unmarshal(body, &response); err != nil {
		return false, 0, fmt.Errorf("failed to parse response: %w", err)
	}
	
	if response.Exists && response.Company != nil {
		return true, response.Company.CompanyID, nil
	}
	
	return false, 0, nil
}

// CreateCompany creates a new company
func (ds *DataService) CreateCompany(companyName, imageURL string) (int, error) {
	// Get API configuration from environment
	baseURL := os.Getenv("API_BASE_URL")
	apiKey := os.Getenv("API_KEY")
	
	if baseURL == "" {
		return 0, fmt.Errorf("API_BASE_URL not configured")
	}
	if apiKey == "" {
		return 0, fmt.Errorf("API_KEY not configured")
	}
	
	requestURL := fmt.Sprintf("%s/companies", baseURL)
	
	reqBody := CreateCompanyRequest{
		Name:     companyName,
		ImageURL: imageURL,
	}
	
	jsonData, err := json.Marshal(reqBody)
	if err != nil {
		return 0, fmt.Errorf("failed to marshal request: %w", err)
	}
	
	req, err := http.NewRequest("POST", requestURL, strings.NewReader(string(jsonData)))
	if err != nil {
		return 0, fmt.Errorf("failed to create request: %w", err)
	}
	
	req.Header.Set("X-API-Key", apiKey)
	req.Header.Set("Content-Type", "application/json")
	
	client := &http.Client{}
	resp, err := client.Do(req)
	if err != nil {
		return 0, fmt.Errorf("failed to make request: %w", err)
	}
	defer resp.Body.Close()
	
	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return 0, fmt.Errorf("failed to read response: %w", err)
	}
	
	if resp.StatusCode != http.StatusCreated {
		return 0, fmt.Errorf("API returned status %d: %s", resp.StatusCode, string(body))
	}
	
	var response CreateCompanyResponse
	if err := json.Unmarshal(body, &response); err != nil {
		return 0, fmt.Errorf("failed to parse response: %w", err)
	}
	
	if !response.Success {
		return 0, fmt.Errorf("failed to create company: %s", response.Message)
	}
	
	return response.Company.CompanyID, nil
}
