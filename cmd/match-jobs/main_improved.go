package main

import (
	"context"
	"encoding/json"
	"flag"
	"fmt"
	"log"
	"os"
	"sort"
	"strings"

	"github.com/joho/godotenv"
	"github.com/sashabaranov/go-openai"

	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/database"
	"linkedin-job-scraper/internal/models"
)

const notSpecified = "ikke angivet"

// ImprovedJobMatch represents the enhanced AI-generated match scores
type ImprovedJobMatch struct {
	JobID        int `json:"job_id"`
	OverallScore int `json:"overall_score"`
	Scores       struct {
		Location        int `json:"location"`
		TechMatch       int `json:"tech_match"`
		CompanySize     int `json:"company_size"`
		SeniorityMatch  int `json:"seniority_match"`
		RemoteFlexibility int `json:"remote_flexibility"`
	} `json:"scores"`
	Reasoning struct {
		Location        string `json:"location"`
		TechMatch       string `json:"tech_match"`
		CompanySize     string `json:"company_size"`
		SeniorityMatch  string `json:"seniority_match"`
		RemoteFlexibility string `json:"remote_flexibility"`
		Summary         string `json:"summary"`
	} `json:"reasoning"`
	Confidence int `json:"confidence"` // How confident the AI is about the match (0-100)
}

const improvedJobMatchPrompt = `Du er en erfaren job-match ekspert der laver PRÆCISE og VELBALANCEREDE evalueringer baseret på FAKTISKE data.

ANALYSE DETTE JOB:
Job ID: %d
Titel: %s
Virksomhed: %s
Lokation: %s
Ansøgere: %s
Arbejdstype: %s
Færdigheder: %s
Beskrivelse: %s

KANDIDAT PROFIL:
• Bor i Roskilde, Danmark (35 km fra København)
• 8+ års erfaring: PHP/Laravel, JavaScript, AWS, Docker, Git
• Også kompetent i: Python, Go, IT-sikkerhed
• Ønsker IKKE lederrolle, men senior udvikler rolle er perfekt
• Foretrækker virksomheder med 50+ ansatte
• Åben for hybridarbejde/remote

SCORING GUIDE (0-100, vær REALISTISK og NUANCERET):

**LOCATION MATCH (25%% vægt):**
• Roskilde: 100 (perfekt)
• København/Måløv/Hillerød: 85-95 (pendlerafstand)
• Anden Region Hovedstaden: 70-80 (acceptabel)
• Øvrige Danmark: 40-60 (mulig remote)
• Sverige/Norge: 30-50 (kun hvis remote)
• Tom lokation: 50 (ukendt)

**TECH MATCH (35%% vægt):**
• Analyser skills og beskrivelse for: PHP, Laravel, JavaScript, Python, Go, AWS, Docker, Git
• 5+ matches: 90-100 (excellent)
• 3-4 matches: 70-85 (god)
• 1-2 matches: 40-60 (okay)
• 0 matches men relevant: 20-40 (mulig)
• Ingen match: 0-20 (dårlig)

**COMPANY SIZE (20%% vægt):**
• Stor kendt virksomhed (Novo Nordisk, Danske Bank): 95-100
• Mellemstor etableret (50-500 ansatte): 80-90
• Mindre virksomhed (20-50 ansatte): 60-75
• Startup/konsulentfirma: 40-60
• Ukendt størrelse: 50

**SENIORITY MATCH (15%% vægt):**
• Senior Developer/Lead Developer: 90-100
• Principal/Staff Engineer: 85-95
• Developer (erfaren): 80-90
• Team Lead (teknisk): 70-80
• Manager/Director: 20-40 (afvis)
• Junior/Graduate: 30-50 (under niveau)

**REMOTE FLEXIBILITY (5%% vægt):**
• Remote/Hybrid nævnt: 90-100
• Fleksibel arbejdstid nævnt: 80-90
• Intet nævnt: 50
• Kun på kontor: 20-40

Returnér KUN dette JSON (ingen markdown/formatering):

{
  "job_id": %d,
  "overall_score": [VÆGTEDE_GENNEMSNIT],
  "scores": {
    "location": [SCORE],
    "tech_match": [SCORE],
    "company_size": [SCORE],
    "seniority_match": [SCORE],
    "remote_flexibility": [SCORE]
  },
  "reasoning": {
    "location": "Specifik lokation og distance til Roskilde",
    "tech_match": "Liste matchende teknologier og begrundelse",
    "company_size": "Virksomhedsstørrelse estimation med begrundelse",
    "seniority_match": "Specificer seniority niveau og ansvar",
    "remote_flexibility": "Fleksibilitet i arbejdsform",
    "summary": "Kort sammenfatning af jobbet fit"
  },
  "confidence": [0-100_HVOR_SIKKER_ER_DU]
}`

func main() {
	var (
		limit    = flag.Int("limit", 10, "Antal jobs at behandle (0 for alle)")
		dryRun   = flag.Bool("dry-run", false, "Vis kun hvad der ville blive gjort")
		minScore = flag.Int("min-score", 50, "Minimum overall score")
		verbose  = flag.Bool("verbose", false, "Vis detaljeret reasoning")
		rerun    = flag.Bool("rerun", false, "Kør igen på jobs der allerede er rated")
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
	jobs, err := getJobsForImprovedMatching(db, *limit, *rerun)
	if err != nil {
		log.Fatalf("Failed to get jobs: %v", err)
	}

	log.Printf("Found %d jobs to process", len(jobs))

	if *dryRun {
		log.Println("DRY RUN - Would process these jobs:")
		for _, job := range jobs {
			fmt.Printf("Job ID: %d, Title: %s, Company: %s, Location: %s\n", 
				job.JobID, job.Title, job.CompanyName, job.Location)
		}
		return
	}

	// Process each job
	var results []ImprovedJobMatch
	processed := 0
	errors := 0

	for _, job := range jobs {
		log.Printf("Processing job %d: %s at %s", job.JobID, job.Title, job.CompanyName)

		match, err := improvedMatchJobWithAI(client, job)
		if err != nil {
			log.Printf("Error processing job %d: %v", job.JobID, err)
			errors++
			continue
		}

		// Save the match to database
		if err := saveImprovedJobRating(db, match); err != nil {
			log.Printf("Error saving job rating for job %d: %v", job.JobID, err)
		}

		// Add to results if above minimum score
		if match.OverallScore >= *minScore {
			results = append(results, *match)
		}

		processed++
	}

	// Sort results by overall score (highest first)
	sort.Slice(results, func(i, j int) bool {
		return results[i].OverallScore > results[j].OverallScore
	})

	// Display results
	displayResults(results, *minScore, *verbose, db)

	log.Printf("\n✅ Done! Processed: %d, Errors: %d, Results shown: %d", 
		processed, errors, len(results))
}

func getJobsForImprovedMatching(db *database.DB, limit int, rerun bool) ([]models.JobPosting, error) {
	var query string
	
	if rerun {
		// Get all jobs with descriptions, including already rated ones
		query = `
			SELECT 
				j.job_id, j.title, j.location, j.description, j.apply_url,
				j.posted_date, j.applicants, j.work_type, j.skills,
				c.name as company_name
			FROM job_postings j
			LEFT JOIN companies c ON j.company_id = c.company_id
			WHERE j.description IS NOT NULL 
			AND j.description != ''
			AND LENGTH(j.description) > 100
			ORDER BY j.posted_date DESC
		`
	} else {
		// Get only unrated jobs (with new rating type)
		query = `
			SELECT 
				j.job_id, j.title, j.location, j.description, j.apply_url,
				j.posted_date, j.applicants, j.work_type, j.skills,
				c.name as company_name
			FROM job_postings j
			LEFT JOIN companies c ON j.company_id = c.company_id
			LEFT JOIN job_ratings r ON j.job_id = r.job_id AND r.rating_type = 'ai_match_v2'
			WHERE j.description IS NOT NULL 
			AND j.description != ''
			AND LENGTH(j.description) > 100
			AND r.job_id IS NULL
			ORDER BY j.posted_date DESC
		`
	}
	
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
			&job.Skills,
			&job.CompanyName,
		)
		if err != nil {
			return nil, fmt.Errorf("failed to scan job: %w", err)
		}
		jobs = append(jobs, job)
	}

	return jobs, nil
}

func improvedMatchJobWithAI(client *openai.Client, job models.JobPosting) (*ImprovedJobMatch, error) {
	// Format optional fields
	applicants := notSpecified
	if job.Applicants != nil {
		applicants = fmt.Sprintf("%d ansøgere", *job.Applicants)
	}

	workType := notSpecified
	if job.WorkType != nil {
		workType = *job.WorkType
	}

	skills := notSpecified
	if job.Skills != nil {
		// Convert skills slice to string
		skillsData, err := json.Marshal(*job.Skills)
		if err == nil {
			skills = string(skillsData)
		}
	}

	// Truncate description to avoid token limits
	description := job.Description
	if len(description) > 1500 {
		description = description[:1500] + "..."
	}

	prompt := fmt.Sprintf(improvedJobMatchPrompt,
		job.JobID, job.Title, job.CompanyName, job.Location,
		applicants, workType, skills, description, job.JobID)

	resp, err := client.CreateChatCompletion(
		context.Background(),
		openai.ChatCompletionRequest{
			Model: openai.GPT4oMini,
			Messages: []openai.ChatCompletionMessage{
				{
					Role:    openai.ChatMessageRoleUser,
					Content: prompt,
				},
			},
			MaxTokens:   1000,
			Temperature: 0.2, // Lower temperature for more consistent results
		},
	)

	if err != nil {
		return nil, fmt.Errorf("OpenAI API error: %w", err)
	}

	if len(resp.Choices) == 0 {
		return nil, fmt.Errorf("no response from OpenAI")
	}

	responseText := strings.TrimSpace(resp.Choices[0].Message.Content)
	
	// Clean up response (remove markdown formatting if present)
	responseText = strings.TrimPrefix(responseText, "```json")
	responseText = strings.TrimSuffix(responseText, "```")
	responseText = strings.TrimSpace(responseText)

	var match ImprovedJobMatch
	if err := json.Unmarshal([]byte(responseText), &match); err != nil {
		return nil, fmt.Errorf("failed to parse OpenAI response: %w\nResponse: %s", err, responseText)
	}

	return &match, nil
}

func saveImprovedJobRating(db *database.DB, match *ImprovedJobMatch) error {
	// Create detailed criteria JSON
	criteriaJSON, err := json.Marshal(map[string]interface{}{
		"location":          match.Reasoning.Location,
		"tech_match":        match.Reasoning.TechMatch,
		"company_size":      match.Reasoning.CompanySize,
		"seniority_match":   match.Reasoning.SeniorityMatch,
		"remote_flexibility": match.Reasoning.RemoteFlexibility,
		"summary":           match.Reasoning.Summary,
		"confidence":        match.Confidence,
	})
	if err != nil {
		return fmt.Errorf("failed to marshal criteria: %w", err)
	}

	query := `
		INSERT INTO job_ratings (
			job_id, overall_score, location_score, tech_score, 
			team_size_score, leadership_score, criteria, rating_type, rated_at
		) VALUES (?, ?, ?, ?, ?, ?, ?, 'ai_match_v2', NOW())
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
		match.JobID,
		match.OverallScore,
		match.Scores.Location,
		match.Scores.TechMatch,
		match.Scores.CompanySize,      // Using company_size for team_size_score
		match.Scores.SeniorityMatch,   // Using seniority for leadership_score
		string(criteriaJSON),
	)

	return err
}

func displayResults(results []ImprovedJobMatch, minScore int, verbose bool, db *database.DB) {
	fmt.Printf("\n🎯 IMPROVED JOB MATCH RESULTS (Min score: %d)\n", minScore)
	fmt.Printf(strings.Repeat("=", 70) + "\n\n")

	if len(results) == 0 {
		fmt.Printf("❌ No jobs found with score >= %d\n", minScore)
		return
	}

	for i, match := range results {
		// Get job details
		job, err := getJobDetailsByID(db, match.JobID)
		if err != nil {
			continue
		}

		// Display rank and basic info
		fmt.Printf("%d. 🏆 SCORE: %d/100 (Confidence: %d%%)\n", 
			i+1, match.OverallScore, match.Confidence)
		fmt.Printf("   📋 %s\n", job.Title)
		fmt.Printf("   🏢 %s\n", job.CompanyName)
		fmt.Printf("   📍 %s\n", job.Location)
		fmt.Printf("   🔗 %s\n", job.ApplyURL)
		
		if job.Applicants != nil {
			fmt.Printf("   👥 %d ansøgere\n", *job.Applicants)
		}

		// Display score breakdown
		fmt.Printf("\n   📊 SCORE BREAKDOWN:\n")
		fmt.Printf("   • Location:     %d/100\n", match.Scores.Location)
		fmt.Printf("   • Tech Match:   %d/100\n", match.Scores.TechMatch)
		fmt.Printf("   • Company Size: %d/100\n", match.Scores.CompanySize)
		fmt.Printf("   • Seniority:    %d/100\n", match.Scores.SeniorityMatch)
		fmt.Printf("   • Remote:       %d/100\n", match.Scores.RemoteFlexibility)

		if verbose {
			fmt.Printf("\n   🧠 REASONING:\n")
			fmt.Printf("   • Location: %s\n", match.Reasoning.Location)
			fmt.Printf("   • Tech: %s\n", match.Reasoning.TechMatch)
			fmt.Printf("   • Company: %s\n", match.Reasoning.CompanySize)
			fmt.Printf("   • Seniority: %s\n", match.Reasoning.SeniorityMatch)
			fmt.Printf("   • Remote: %s\n", match.Reasoning.RemoteFlexibility)
			fmt.Printf("   • Summary: %s\n", match.Reasoning.Summary)
		}

		fmt.Printf("\n" + strings.Repeat("-", 70) + "\n")
	}
}

func getJobDetailsByID(db *database.DB, jobID int) (*models.JobPosting, error) {
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
