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

const notSpecified = "not specified"

// OptimizedJobMatch represents the enhanced AI-generated match scores that work with limited data
type OptimizedJobMatch struct {
	JobID        int `json:"job_id"`
	OverallScore int `json:"overall_score"`
	Scores       struct {
		Location        int `json:"location"`
		TechMatch       int `json:"tech_match"`
		CompanyFit      int `json:"company_fit"`
		SeniorityMatch  int `json:"seniority_match"`
		WorkTypeMatch   int `json:"work_type_match"`
	} `json:"scores"`
	Reasoning struct {
		Location        string `json:"location"`
		TechMatch       string `json:"tech_match"`
		CompanyFit      string `json:"company_fit"`
		SeniorityMatch  string `json:"seniority_match"`
		WorkTypeMatch   string `json:"work_type_match"`
		Summary         string `json:"summary"`
	} `json:"reasoning"`
	Confidence int `json:"confidence"` // How confident the AI is about the match (0-100)
}

const optimizedJobMatchPrompt = `You are an expert job matching AI that provides REALISTIC and ACCURATE evaluations based on AVAILABLE data only.

ANALYZE THIS JOB:
Job ID: %d
Title: %s
Company: %s
Location: %s
Applicants: %s
Work Type: %s
Skills: %s
Description Available: %s

CANDIDATE PROFILE:
• Lives in Roskilde, Denmark (35km from Copenhagen)
• 8+ years experience: PHP/Laravel backend, JavaScript, AWS, Docker, Git
• Also skilled in: Python, Go, IT security (CTF, penetration testing)
• Does NOT want management roles, but senior individual contributor is perfect
• Prefers companies with 50+ employees
• Open to hybrid/remote work
• Looking for challenging technical roles without people management

SCORING GUIDELINES (0-100, be REALISTIC and use available data):

**LOCATION MATCH (25%% weight):**
• Roskilde: 100 (perfect)
• Copenhagen/København/Måløv/Hillerød: 85-95 (commutable)
• Other Region Hovedstaden: 70-85 (acceptable)
• Rest of Denmark: 40-60 (possible if remote)
• Sweden/Norway: 30-50 (only if remote work mentioned)
• Empty/unclear location: 40 (unknown, assume average)

**TECH MATCH (35%% weight - most important):**
Analyze job title + skills array + any description for: PHP, Laravel, JavaScript, Python, Go, AWS, Docker, Git, backend, full-stack, web development
• 5+ technology matches: 90-100 (excellent match)
• 3-4 technology matches: 75-85 (good match)
• 1-2 technology matches: 50-70 (possible match)
• Related technologies (Node.js, React, MySQL): 40-60 (transferable)
• No clear tech match but development role: 20-40 (unlikely)
• Non-technical role: 0-20 (poor match)

**COMPANY FIT (20%% weight):**
Base on company name and applicant count:
• Large known companies (Novo Nordisk, Danske Bank, TDC, Maersk): 90-100
• Medium established companies (50-200 applicants suggests good size): 80-90
• Smaller companies (10-50 applicants): 60-75
• Very small (1-10 applicants) or unknown: 40-60
• Retail/non-tech companies (unless tech role): 30-50

**SENIORITY MATCH (15%% weight):**
Analyze job title for seniority level:
• Senior Developer/Lead Developer/Principal: 90-100 (perfect)
• Developer/Software Engineer (experienced level): 80-90 (good)
• Full-stack Developer/Backend Developer: 85-95 (very good)
• Tech Lead (technical, not management): 75-85 (acceptable)
• Manager/Director/Head of: 10-30 (reject - management role)
• Junior/Graduate/Trainee: 20-40 (below experience level)
• Consultant/Contractor: 70-80 (depends on project)

**WORK TYPE MATCH (5%% weight):**
• Remote/Hybrid mentioned: 90-100 (perfect)
• Flexible work mentioned: 80-90 (good)
• On-site but Copenhagen area: 60-70 (commutable)
• Not specified: 50 (assume average)
• Strict on-site far location: 20-30 (poor)

IMPORTANT: Work with available data only. If description is missing, rely on title, company, skills, and other available fields. Don't penalize for missing data - focus on what IS available.

Return ONLY this JSON (no markdown formatting, all scores must be integers):

{
  "job_id": %d,
  "overall_score": [INTEGER_WEIGHTED_AVERAGE],
  "scores": {
    "location": [INTEGER_SCORE],
    "tech_match": [INTEGER_SCORE],
    "company_fit": [INTEGER_SCORE],
    "seniority_match": [INTEGER_SCORE],
    "work_type_match": [INTEGER_SCORE]
  },
  "reasoning": {
    "location": "Location assessment with distance to Roskilde",
    "tech_match": "List matching technologies found in title/skills",
    "company_fit": "Company size estimation and fit reasoning",
    "seniority_match": "Seniority level analysis from job title",
    "work_type_match": "Work arrangement flexibility assessment",
    "summary": "Brief overall job fit assessment"
  },
  "confidence": [0-100_HOW_CONFIDENT_ARE_YOU]
}`

func main() {
	var (
		limit    = flag.Int("limit", 10, "Number of jobs to process (0 for all)")
		dryRun   = flag.Bool("dry-run", false, "Show what would be processed")
		minScore = flag.Int("min-score", 50, "Minimum overall score to display")
		verbose  = flag.Bool("verbose", false, "Show detailed reasoning")
		rerun    = flag.Bool("rerun", false, "Re-process already rated jobs")
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
	jobs, err := getJobsForOptimizedMatching(db, *limit, *rerun)
	if err != nil {
		log.Fatalf("Failed to get jobs: %v", err)
	}

	log.Printf("Found %d jobs to process", len(jobs))

	if *dryRun {
		log.Println("DRY RUN - Would process these jobs:")
		for _, job := range jobs {
			hasDesc := "No"
			if job.Description != "" && len(job.Description) > 50 {
				hasDesc = "Yes"
			}
			fmt.Printf("Job ID: %d, Title: %s, Company: %s, Location: %s, Description: %s\n", 
				job.JobID, job.Title, job.CompanyName, job.Location, hasDesc)
		}
		return
	}

	// Process each job
	var results []OptimizedJobMatch
	processed := 0
	errors := 0

	for _, job := range jobs {
		log.Printf("Processing job %d: %s at %s", job.JobID, job.Title, job.CompanyName)

		match, err := optimizedMatchJobWithAI(client, job)
		if err != nil {
			log.Printf("Error processing job %d: %v", job.JobID, err)
			errors++
			continue
		}

		// Save the match to database
		if err := saveOptimizedJobRating(db, match); err != nil {
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
	displayOptimizedResults(results, *minScore, *verbose, db)

	log.Printf("\n✅ Done! Processed: %d, Errors: %d, Results shown: %d", 
		processed, errors, len(results))
}

func getJobsForOptimizedMatching(db *database.DB, limit int, rerun bool) ([]models.JobPosting, error) {
	var query string
	
	if rerun {
		// Get all jobs, including those without descriptions
		query = `
			SELECT 
				j.job_id, j.title, j.location, j.description, j.apply_url,
				j.posted_date, j.applicants, j.work_type, j.skills,
				c.name as company_name
			FROM job_postings j
			LEFT JOIN companies c ON j.company_id = c.company_id
			WHERE j.title IS NOT NULL 
			AND j.title != ''
			ORDER BY j.posted_date DESC
		`
	} else {
		// Get only unrated jobs (including those without descriptions)
		query = `
			SELECT 
				j.job_id, j.title, j.location, j.description, j.apply_url,
				j.posted_date, j.applicants, j.work_type, j.skills,
				c.name as company_name
			FROM job_postings j
			LEFT JOIN companies c ON j.company_id = c.company_id
			LEFT JOIN job_ratings r ON j.job_id = r.job_id AND r.rating_type = 'ai_match_v3'
			WHERE j.title IS NOT NULL 
			AND j.title != ''
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

func optimizedMatchJobWithAI(client *openai.Client, job models.JobPosting) (*OptimizedJobMatch, error) {
	// Format optional fields
	applicants := notSpecified
	if job.Applicants != nil {
		applicants = fmt.Sprintf("%d applicants", *job.Applicants)
	}

	workType := notSpecified
	if job.WorkType != nil {
		workType = *job.WorkType
	}

	skills := notSpecified
	if job.Skills != nil {
		// Convert skills slice to readable string
		skillsData, err := json.Marshal(*job.Skills)
		if err == nil {
			skills = string(skillsData)
		}
	}

	// Check if description is available and meaningful
	descriptionAvailable := "No"
	if job.Description != "" && len(job.Description) > 50 {
		descriptionAvailable = "Yes"
	}

	prompt := fmt.Sprintf(optimizedJobMatchPrompt,
		job.JobID, job.Title, job.CompanyName, job.Location,
		applicants, workType, skills, descriptionAvailable,
		job.JobID)

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
			Temperature: 0.1, // Very low temperature for consistent results
		},
	)

	if err != nil {
		return nil, fmt.Errorf("OpenAI API error: %w", err)
	}

	if len(resp.Choices) == 0 {
		return nil, fmt.Errorf("no response from OpenAI")
	}

	responseText := strings.TrimSpace(resp.Choices[0].Message.Content)
	
	// Clean up response (remove any markdown formatting)
	responseText = strings.TrimPrefix(responseText, "```json")
	responseText = strings.TrimSuffix(responseText, "```")
	responseText = strings.TrimSpace(responseText)

	var match OptimizedJobMatch
	if err := json.Unmarshal([]byte(responseText), &match); err != nil {
		return nil, fmt.Errorf("failed to parse OpenAI response: %w\nResponse: %s", err, responseText)
	}

	return &match, nil
}

func saveOptimizedJobRating(db *database.DB, match *OptimizedJobMatch) error {
	// Create detailed criteria JSON
	criteriaJSON, err := json.Marshal(map[string]interface{}{
		"location":       match.Reasoning.Location,
		"tech_match":     match.Reasoning.TechMatch,
		"company_fit":    match.Reasoning.CompanyFit,
		"seniority_match": match.Reasoning.SeniorityMatch,
		"work_type_match": match.Reasoning.WorkTypeMatch,
		"summary":        match.Reasoning.Summary,
		"confidence":     match.Confidence,
	})
	if err != nil {
		return fmt.Errorf("failed to marshal criteria: %w", err)
	}

	query := `
		INSERT INTO job_ratings (
			job_id, overall_score, location_score, tech_score, 
			team_size_score, leadership_score, criteria, rating_type, rated_at
		) VALUES (?, ?, ?, ?, ?, ?, ?, 'ai_match_v3', NOW())
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
		match.Scores.CompanyFit,      // Using company_fit for team_size_score
		match.Scores.SeniorityMatch,  // Using seniority for leadership_score
		string(criteriaJSON),
	)

	return err
}

func displayOptimizedResults(results []OptimizedJobMatch, minScore int, verbose bool, db *database.DB) {
	fmt.Printf("\n🎯 OPTIMIZED JOB MATCH RESULTS (Min score: %d)\n", minScore)
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
			fmt.Printf("   👥 %d applicants\n", *job.Applicants)
		}

		// Display score breakdown
		fmt.Printf("\n   📊 SCORE BREAKDOWN:\n")
		fmt.Printf("   • Location:     %d/100\n", match.Scores.Location)
		fmt.Printf("   • Tech Match:   %d/100\n", match.Scores.TechMatch)
		fmt.Printf("   • Company Fit:  %d/100\n", match.Scores.CompanyFit)
		fmt.Printf("   • Seniority:    %d/100\n", match.Scores.SeniorityMatch)
		fmt.Printf("   • Work Type:    %d/100\n", match.Scores.WorkTypeMatch)

		if verbose {
			fmt.Printf("\n   🧠 REASONING:\n")
			fmt.Printf("   • Location: %s\n", match.Reasoning.Location)
			fmt.Printf("   • Tech: %s\n", match.Reasoning.TechMatch)
			fmt.Printf("   • Company: %s\n", match.Reasoning.CompanyFit)
			fmt.Printf("   • Seniority: %s\n", match.Reasoning.SeniorityMatch)
			fmt.Printf("   • Work Type: %s\n", match.Reasoning.WorkTypeMatch)
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
