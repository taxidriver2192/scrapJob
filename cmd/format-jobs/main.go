package main

import (
	"encoding/json"
	"flag"
	"fmt"
	"log"
	"os"
	"strings"

	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/scraper"
)

func main() {
	// Command line flags
	var (
		jobID     = flag.Int("job-id", 0, "Specific job ID to format")
		limit     = flag.Int("limit", 5, "Number of recent jobs to format")
		apiKey    = flag.String("api-key", "", "OpenAI API key (or set OPENAI_API_KEY env var)")
	)
	flag.Parse()

	// Load configuration
	cfg, err := config.LoadConfig()
	if err != nil {
		log.Fatalf("Failed to load config: %v", err)
	}

	// Initialize database connection
	db, err := scraper.InitializeDatabase(cfg.Database)
	if err != nil {
		log.Fatalf("Failed to connect to database: %v", err)
	}
	defer db.Close()

	// Get API key from flag or environment
	openaiKey := *apiKey
	if openaiKey == "" {
		openaiKey = os.Getenv("OPENAI_API_KEY")
	}
	if openaiKey == "" {
		log.Fatal("OpenAI API key required. Use --api-key flag or set OPENAI_API_KEY environment variable")
	}

	// Get jobs to format
	var jobs []JobWithCompany
	if *jobID > 0 {
		// Get specific job
		job, err := getJobWithCompany(db, *jobID)
		if err != nil {
			log.Fatalf("Failed to get job %d: %v", *jobID, err)
		}
		jobs = append(jobs, job)
	} else {
		// Get recent jobs
		jobs, err = getRecentJobsWithCompanies(db, *limit)
		if err != nil {
			log.Fatalf("Failed to get recent jobs: %v", err)
		}
	}

	if len(jobs) == 0 {
		fmt.Println("No jobs found to format")
		return
	}

	// Format each job using ChatGPT
	for i, job := range jobs {
		fmt.Printf("🔄 Formatting job %d/%d: %s\n", i+1, len(jobs), job.Title)
		
		formatted, err := formatJobWithChatGPT(job, openaiKey)
		if err != nil {
			fmt.Printf("❌ Failed to format job %s: %v\n", job.Title, err)
			continue
		}

		fmt.Printf("\n%s\n", strings.Repeat("=", 80))
		fmt.Printf("📋 Formatted Job: %s\n", job.Title)
		fmt.Printf("%s\n", strings.Repeat("=", 80))
		fmt.Println(formatted)
		fmt.Printf("%s\n\n", strings.Repeat("=", 80))
	}

	fmt.Printf("✅ Formatted %d jobs successfully\n", len(jobs))
}

// JobWithCompany represents a job with its company information
type JobWithCompany struct {
	JobID         int    `json:"job_id"`
	LinkedInJobID string `json:"linkedin_job_id"`
	Title         string `json:"title"`
	CompanyID     int    `json:"company_id"`
	Location      string `json:"location"`
	Description   string `json:"description"`
	ApplyURL      string `json:"apply_url"`
	PostedDate    string `json:"posted_date"`
	Applicants    string `json:"applicants"`
	WorkType      string `json:"work_type"`
	Skills        string `json:"skills"`
	CreatedAt     string `json:"created_at"`
	UpdatedAt     string `json:"updated_at"`
	CompanyName   string `json:"company_name"`
}

func getJobWithCompany(db interface{}, jobID int) (JobWithCompany, error) {
	// This is a placeholder - you'll need to implement actual database query
	// based on your database structure
	return JobWithCompany{}, fmt.Errorf("not implemented - need database query logic")
}

func getRecentJobsWithCompanies(db interface{}, limit int) ([]JobWithCompany, error) {
	// This is a placeholder - you'll need to implement actual database query
	// based on your database structure
	return nil, fmt.Errorf("not implemented - need database query logic")
}

func formatJobWithChatGPT(job JobWithCompany, apiKey string) (string, error) {
	// Create the JSON input for the prompt
	jobJSON, err := json.MarshalIndent(map[string]interface{}{
		"job_id":           job.JobID,
		"linkedin_job_id":  job.LinkedInJobID,
		"title":            job.Title,
		"company_id":       job.CompanyID,
		"location":         job.Location,
		"description":      job.Description,
		"apply_url":        job.ApplyURL,
		"posted_date":      job.PostedDate,
		"applicants":       job.Applicants,
		"work_type":        job.WorkType,
		"skills":           job.Skills,
		"created_at":       job.CreatedAt,
		"updated_at":       job.UpdatedAt,
	}, "", "  ")
	if err != nil {
		return "", fmt.Errorf("failed to marshal job JSON: %w", err)
	}

	companyJSON, err := json.MarshalIndent(map[string]interface{}{
		"company_id":  job.CompanyID,
		"name":        job.CompanyName,
		"created_at":  job.CreatedAt,
		"updated_at":  job.UpdatedAt,
	}, "", "  ")
	if err != nil {
		return "", fmt.Errorf("failed to marshal company JSON: %w", err)
	}

	// Create the complete prompt
	prompt := fmt.Sprintf(`Du er en skarp rekrutterings-assistent.  
Input: JSON med et enkelt job og firma-objekt, fx:

%s

%s

Opgave:  
1. Ekstraher fra 'title', 'location' og 'description' en kort "Job Overview" (1–2 sætninger).  
2. List nøgle-ansvarsområder under "You will do" som bullet-points.  
3. List hårde krav under "You should have" som bullet-points (OOP, Laravel osv.).  
4. Tilføj "Company size:" på baggrund af kendt data (hvis ukendt, skriv "Information not available").  
5. Tilføj "Team size:" (samme tommelfingerregel).  

Output skal være i dette format:

---
**Job Overview**  
Kort, konkret beskrivelse af jobbet.

**You will do**  
- Punkt 1  
- Punkt 2  
- …

**You should have**  
- Punkt 1  
- Punkt 2  
- …

**Location**  
<value fra location>

**Company size:**  
<"Information not available" eller antal medarbejdere>

**Team size:**  
<"Information not available" eller antal i team>

**Apply here:**  
<apply_url>

---`, string(jobJSON), string(companyJSON))

	// Send to ChatGPT
	response, err := callOpenAI(prompt, apiKey)
	if err != nil {
		return "", fmt.Errorf("failed to call OpenAI: %w", err)
	}

	return response, nil
}

func callOpenAI(prompt, apiKey string) (string, error) {
	// This is a placeholder for OpenAI API call
	// You'll need to implement the actual HTTP request to OpenAI
	return "OpenAI integration not yet implemented", nil
}
