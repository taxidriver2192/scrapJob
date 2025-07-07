package main

import (
	"context"
	"encoding/json"
	"flag"
	"fmt"
	"log"
	"os"
	"strings"

	"github.com/joho/godotenv"
	"github.com/sashabaranov/go-openai"

	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/database"
	"linkedin-job-scraper/internal/models"
)

// JobMatchScores represents the AI-generated match scores for a job
type JobMatchScores struct {
	JobID   int `json:"job_id"`
	Scores  struct {
		Location      int `json:"location"`
		TechMatch     int `json:"tech_match"`
		TeamSize      int `json:"team_size"`
		LeadershipFit int `json:"leadership_fit"`
	} `json:"scores"`
	OverallScore int // Calculated locally, not from OpenAI
	Explanation  struct {
		Location      string `json:"location"`
		TechMatch     string `json:"tech_match"`
		TeamSize      string `json:"team_size"`
		LeadershipFit string `json:"leadership_fit"`
	} `json:"explanation"`
}

const jobMatchPrompt = `Du er en job-match-assistent der laver PRÆCISE og REALISTISKE evalueringer.

ANALYSÉR DETTE JOB:
%s

KANDIDAT PROFIL:
• Bor i Roskilde, Danmark
• 8+ års erfaring: PHP/Laravel backend, JavaScript, AWS, Docker
• Også kompetent i: Python, Go, IT-sikkerhed (CTF, pen-test)
• Ønsker IKKE lederrolle, men selvstyring er OK
• Foretrækker virksomheder med >50 ansatte

SCORING REGLER (vær STRENG og REALISTISK):

**LOCATION SCORE (0-100):**
• Roskilde eller tæt på Roskilde: 70-100
• København: 60
• Sjælland (andre byer): 50
• Fyn: 30
• Danmark (andre regioner): 20
• Udenfor Danmark: 0

**TECH MATCH SCORE (0-100):**
• Analyser job beskrivelsen NØJE for teknologier
• Perfect match (4-5 teknologier): 80-100
• God match (2-3 teknologier): 50-80
• Lille match (1 teknologi): 20-50
• Ingen match: 0-20
• IGNORER teknologier ikke nævnt i jobbet

**TEAM SIZE SCORE (0-100):**
• Fortune 500/store internationale virksomheder: 90-100
• Etablerede virksomheder 100-500 ansatte: 80-90
• Mellemstore virksomheder 50-100 ansatte: 70-80
• Mindre virksomheder 20-50 ansatte: 50-70
• Små virksomheder 5-20 ansatte: 30-50
• Startup/konsulent firma <5 ansatte: 10-30
• Freelance/enkeltmandsvirksomhed: 0-10
• Hvis størrelse IKKE nævnes: 40
• KEND til kendte virksomheder: Novo Nordisk=100, Danske Bank=90, Skat=80, etc.

**LEADERSHIP FIT (0-100):**
• Junior/Graduate udvikler: 95-100
• Senior udvikler (individual contributor): 85-95
• Principal/Staff engineer (teknisk ekspert): 80-90
• Tech Lead (teknisk ansvar, minimal management): 60-80
• Engineering Manager (folk ansvar): 20-40
• Senior Manager/Director: 10-30
• VP/C-level: 0-10
• Scrum Master: 40-60
• Product Owner: 50-70
• DevOps Engineer: 90-100
• Consultant/Contractor: 75-85
• KEND til nøgleord: "ansvar for team"=20, "management"=10, "lede"=30, "selvstændig"=90

Returnér KUN dette JSON format (ingen anden tekst):

{
  "job_id": %d,
  "scores": {
    "location": [REALISTISK_SCORE],
    "tech_match": [REALISTISK_SCORE], 
    "team_size": [SPECIFIK_SCORE_BASERET_PÅ_VIRKSOMHED],
    "leadership_fit": [SPECIFIK_SCORE_BASERET_PÅ_ROLLE]
  },
  "explanation": {
    "location": "Kort begrundelse med specifik placering",
    "tech_match": "Liste specifikke teknologier fundet/ikke fundet",
    "team_size": "Virksomhedsnavn og estimeret størrelse med begrundelse",
    "leadership_fit": "Specifik rolle og ansvarsniveau med nøgleord"
  }
}`

func main() {
	var (
		limit      = flag.Int("limit", 10, "Antal jobs at behandle (0 for alle)")
		dryRun     = flag.Bool("dry-run", false, "Vis kun hvad der ville blive gjort")
		minScore   = flag.Int("min-score", 60, "Minimum overall score for at vise resultatet")
		showAll    = flag.Bool("show-all", false, "Vis alle scores, ikke kun høje scores")
	)
	flag.Parse()

	// Load environment variables
	if err := godotenv.Load(); err != nil {
		log.Printf("Warning: .env file not found, using environment variables")
	}

	// Load configuration
	cfg := config.Load()

	// Initialize database
	db, err := database.NewConnection(cfg.Database)
	if err != nil {
		log.Fatalf("Failed to connect to database: %v", err)
	}
	defer db.Close()

	// Initialize OpenAI client
	openaiKey := os.Getenv("OPENAI_API_KEY")
	if openaiKey == "" {
		log.Fatalf("OPENAI_API_KEY environment variable not set")
	}

	client := openai.NewClient(openaiKey)

	// Get jobs for matching
	jobs, err := getJobsForMatching(db, *limit)
	if err != nil {
		log.Fatalf("Failed to get jobs: %v", err)
	}

	log.Printf("Found %d jobs to match", len(jobs))

	if *dryRun {
		log.Println("DRY RUN - Would process these jobs:")
		for _, job := range jobs {
			fmt.Printf("Job ID: %d, Title: %s, Company: %s, Location: %s\n", 
				job.JobID, job.Title, job.CompanyName, job.Location)
		}
		return
	}

	// Process each job
	var results []JobMatchScores
	processed := 0
	errors := 0

	for _, job := range jobs {
		log.Printf("Matching job %d: %s at %s", job.JobID, job.Title, job.CompanyName)

		score, err := matchJobWithOpenAI(client, job)
		if err != nil {
			log.Printf("Error matching job %d: %v", job.JobID, err)
			errors++
			continue
		}

		// Save the scores to the database
		if err := saveJobRating(db, score); err != nil {
			log.Printf("Error saving job rating for job %d: %v", job.JobID, err)
		}

		// Only show results above minimum score (unless show-all is set)
		if *showAll || int(score.OverallScore) >= *minScore {
			results = append(results, *score)
		}

		processed++
	}

	// Sort results by overall score (highest first)
	for i := 0; i < len(results)-1; i++ {
		for j := i + 1; j < len(results); j++ {
			if results[j].OverallScore > results[i].OverallScore {
				results[i], results[j] = results[j], results[i]
			}
		}
	}

	// Display results
	fmt.Printf("\n🎯 JOB MATCH RESULTS (Minimum score: %d)\n", *minScore)
	fmt.Printf("=" + strings.Repeat("=", 60) + "\n\n")

	if len(results) == 0 {
		fmt.Printf("❌ No jobs found with score >= %d\n", *minScore)
		fmt.Printf("Try running with --show-all to see all results\n")
	} else {
		for i, result := range results {
			// Get job details for display
			job, err := getJobByID(db, result.JobID)
			if err != nil {
				continue
			}

			fmt.Printf("%d. 🏆 OVERALL SCORE: %d/100\n", i+1, result.OverallScore)
			fmt.Printf("   📋 Job: %s at %s\n", job.Title, job.CompanyName)
			fmt.Printf("   📍 Location: %s\n", job.Location)
			fmt.Printf("   🔗 Apply: %s\n", job.ApplyURL)
			fmt.Printf("\n   📊 DETAILED SCORES:\n")
			fmt.Printf("   • Location:      %d/100 - %s\n", result.Scores.Location, result.Explanation.Location)
			fmt.Printf("   • Tech Match:    %d/100 - %s\n", result.Scores.TechMatch, result.Explanation.TechMatch)
			fmt.Printf("   • Team Size:     %d/100 - %s\n", result.Scores.TeamSize, result.Explanation.TeamSize)
			fmt.Printf("   • Leadership Fit: %d/100 - %s\n", result.Scores.LeadershipFit, result.Explanation.LeadershipFit)
			fmt.Printf("\n" + strings.Repeat("-", 80) + "\n")
		}
	}

	log.Printf("\nDone! Processed: %d, Errors: %d, Results shown: %d", processed, errors, len(results))
}

func getJobsForMatching(db *database.DB, limit int) ([]models.JobPosting, error) {
	query := `
		SELECT 
			j.job_id, j.title, j.location, j.description, j.apply_url,
			j.posted_date, j.applicants, j.work_type, j.skills,
			c.name as company_name
		FROM job_postings j
		LEFT JOIN companies c ON j.company_id = c.company_id
		WHERE j.title IS NOT NULL
		AND j.title != ''
		AND j.description IS NOT NULL
		AND j.description != ''
		ORDER BY j.posted_date DESC
	`
	
	if limit > 0 {
		query += fmt.Sprintf(" LIMIT %d", limit)
	}

	rows, err := db.Query(query)
	if err != nil {
		return nil, fmt.Errorf("failed to query jobs: %w", err)
	}
	defer rows.Close()

	var jobs []models.JobPosting
	for rows.Next() {
		var job models.JobPosting
		err := rows.Scan(
			&job.JobID,
			&job.Title,
			&job.Location,
			&job.Description,
			&job.ApplyURL,
			&job.PostedDate,
			&job.Applicants,
			&job.WorkType,
			&job.CompanyName,
		)
		if err != nil {
			return nil, fmt.Errorf("failed to scan job: %w", err)
		}
		jobs = append(jobs, job)
	}

	return jobs, nil
}

func getJobByID(db *database.DB, jobID int) (*models.JobPosting, error) {
	query := `
		SELECT 
			j.job_id, j.title, j.location, j.description, j.apply_url,
			j.posted_date, j.applicants, j.work_type,
			c.name as company_name
		FROM job_postings j
		LEFT JOIN companies c ON j.company_id = c.company_id
		WHERE j.job_id = ?
	`

	row := db.QueryRow(query, jobID)
	
	var job models.JobPosting
	err := row.Scan(
		&job.JobID,
		&job.Title,
		&job.Location,
		&job.Description,
		&job.ApplyURL,
		&job.PostedDate,
		&job.Applicants,
		&job.WorkType,
		&job.CompanyName,
	)
	if err != nil {
		return nil, fmt.Errorf("failed to scan job: %w", err)
	}

	return &job, nil
}

func matchJobWithOpenAI(client *openai.Client, job models.JobPosting) (*JobMatchScores, error) {
	// Create job JSON for the prompt
	jobJSON, err := json.MarshalIndent(map[string]interface{}{
		"job_id":         fmt.Sprintf("%d", job.JobID),
		"linkedin_job_id": job.LinkedInJobID,
		"title":          job.Title,
		"company_name":   job.CompanyName,
		"location":       job.Location,
		"description":    truncateString(job.Description, 2000), // Limit description length
		"apply_url":      job.ApplyURL,
		"posted_date":    job.PostedDate.Format("2006-01-02"),
		"applicants":     job.Applicants,
		"work_type":      job.WorkType,
	}, "", "  ")
	if err != nil {
		return nil, fmt.Errorf("failed to marshal job JSON: %w", err)
	}

	prompt := fmt.Sprintf(jobMatchPrompt, string(jobJSON), job.JobID)

	resp, err := client.CreateChatCompletion(
		context.Background(),
		openai.ChatCompletionRequest{
			Model: openai.GPT4oMini, // Use better model for more accurate analysis
			Messages: []openai.ChatCompletionMessage{
				{
					Role:    openai.ChatMessageRoleUser,
					Content: prompt,
				},
			},
			MaxTokens:   1500,
			Temperature: 0.3, // Slightly higher temperature for more variation
		},
	)

	if err != nil {
		return nil, fmt.Errorf("OpenAI API error: %w", err)
	}

	if len(resp.Choices) == 0 {
		return nil, fmt.Errorf("no response from OpenAI")
	}

	responseText := strings.TrimSpace(resp.Choices[0].Message.Content)
	
	// Parse JSON response
	var scores JobMatchScores
	if err := json.Unmarshal([]byte(responseText), &scores); err != nil {
		return nil, fmt.Errorf("failed to parse OpenAI response: %w\nResponse: %s", err, responseText)
	}

	// Calculate overall score as average of the four categories
	scores.OverallScore = (scores.Scores.Location + scores.Scores.TechMatch + scores.Scores.TeamSize + scores.Scores.LeadershipFit) / 4

	return &scores, nil
}

func truncateString(s string, maxLen int) string {
	if len(s) <= maxLen {
		return s
	}
	return s[:maxLen] + "..."
}

// saveJobRating saves the AI match scores to the database
func saveJobRating(db *database.DB, scores *JobMatchScores) error {
	// Create criteria JSON
	criteriaJSON, err := json.Marshal(map[string]interface{}{
		"location":      scores.Explanation.Location,
		"tech_match":    scores.Explanation.TechMatch,
		"team_size":     scores.Explanation.TeamSize,
		"leadership_fit": scores.Explanation.LeadershipFit,
	})
	if err != nil {
		return fmt.Errorf("failed to marshal criteria: %w", err)
	}

	query := `
		INSERT INTO job_ratings (
			job_id, overall_score, location_score, tech_score, 
			team_size_score, leadership_score, criteria, rating_type, rated_at
		) VALUES (?, ?, ?, ?, ?, ?, ?, 'ai_match', NOW())
		ON DUPLICATE KEY UPDATE
			overall_score = VALUES(overall_score),
			location_score = VALUES(location_score),
			tech_score = VALUES(tech_score),
			team_size_score = VALUES(team_size_score),
			leadership_score = VALUES(leadership_score),
			criteria = VALUES(criteria),
			rated_at = NOW(),
			updated_at = NOW()
	`

	_, err = db.Exec(query, 
		scores.JobID,
		int(scores.OverallScore), // Convert to int for database
		scores.Scores.Location,
		scores.Scores.TechMatch,
		scores.Scores.TeamSize,
		scores.Scores.LeadershipFit,
		string(criteriaJSON),
	)

	if err != nil {
		return fmt.Errorf("failed to save job rating: %w", err)
	}

	return nil
}
