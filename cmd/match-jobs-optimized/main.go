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

// Constants for AI rating system
const (
	notSpecified = "not specified"
	ratingType   = "ai_match_optimized"
	modelName    = openai.GPT4oMini
	maxTokens    = 1200
	temperature  = 0.1
)

// Scoring thresholds for consistent evaluation
const (
	excellentScore = 90
	veryGoodScore  = 75
	goodScore      = 60
	acceptableScore = 40
	poorScore      = 20
)

// CandidateProfile represents the configurable candidate information
type CandidateProfile struct {
	Name            string   `json:"name"`
	Location        string   `json:"location"`
	YearsExperience int      `json:"years_experience"`
	PrimarySkills   []string `json:"primary_skills"`
	SecondarySkills []string `json:"secondary_skills"`
	PreferredRoles  []string `json:"preferred_roles"`
	AvoidRoles      []string `json:"avoid_roles"`
	CompanySize     struct {
		Minimum   int    `json:"minimum"`
		Preferred string `json:"preferred"`
	} `json:"company_size"`
	WorkPreferences struct {
		Remote     bool   `json:"remote"`
		Hybrid     bool   `json:"hybrid"`
		OnSite     bool   `json:"on_site"`
		MaxCommute string `json:"max_commute"`
	} `json:"work_preferences"`
	SalaryRange struct {
		Minimum int `json:"minimum"`
		Maximum int `json:"maximum"`
	} `json:"salary_range"`
	Languages []string `json:"languages"`
}

// ScoringWeights represents the configurable scoring weights
type ScoringWeights struct {
	Location      int `json:"location"`       // Default: 25
	TechMatch     int `json:"tech_match"`     // Default: 35
	CompanyFit    int `json:"company_fit"`    // Default: 20
	SeniorityFit  int `json:"seniority_fit"`  // Default: 15
	WorkTypeFit   int `json:"work_type_fit"`  // Default: 5
}

// JobMatchConfig represents the complete matching configuration
type JobMatchConfig struct {
	Candidate CandidateProfile `json:"candidate"`
	Weights   ScoringWeights   `json:"weights"`
}

// OptimizedJobMatch represents the AI-generated match scores
type OptimizedJobMatch struct {
	JobID        int `json:"job_id"`
	OverallScore int `json:"overall_score"`
	Scores       struct {
		Location     int `json:"location"`
		TechMatch    int `json:"tech_match"`
		CompanyFit   int `json:"company_fit"`
		SeniorityFit int `json:"seniority_fit"`
		WorkTypeFit  int `json:"work_type_fit"`
	} `json:"scores"`
	Reasoning struct {
		Location     string `json:"location"`
		TechMatch    string `json:"tech_match"`
		CompanyFit   string `json:"company_fit"`
		SeniorityFit string `json:"seniority_fit"`
		WorkTypeFit  string `json:"work_type_fit"`
		Summary      string `json:"summary"`
	} `json:"reasoning"`
	Confidence int `json:"confidence"`
}

func getDefaultConfig() JobMatchConfig {
	return JobMatchConfig{
		Candidate: CandidateProfile{
			Name:            "Senior Developer",
			Location:        "Roskilde, Denmark",
			YearsExperience: 8,
			PrimarySkills:   []string{"PHP", "Laravel", "JavaScript", "AWS", "Docker", "Git"},
			SecondarySkills: []string{"Python", "Go", "IT Security", "Penetration Testing", "MySQL", "Linux"},
			PreferredRoles:  []string{"Senior Developer", "Lead Developer", "Full-Stack Developer", "Backend Developer", "Principal Engineer"},
			AvoidRoles:      []string{"Manager", "Director", "Head of", "VP", "Team Lead", "Scrum Master"},
			CompanySize: struct {
				Minimum   int    `json:"minimum"`
				Preferred string `json:"preferred"`
			}{
				Minimum:   50,
				Preferred: "50-500 employees",
			},
			WorkPreferences: struct {
				Remote     bool   `json:"remote"`
				Hybrid     bool   `json:"hybrid"`
				OnSite     bool   `json:"on_site"`
				MaxCommute string `json:"max_commute"`
			}{
				Remote:     true,
				Hybrid:     true,
				OnSite:     true,
				MaxCommute: "45 minutes from Roskilde",
			},
			SalaryRange: struct {
				Minimum int `json:"minimum"`
				Maximum int `json:"maximum"`
			}{
				Minimum: 600000,
				Maximum: 800000,
			},
			Languages: []string{"Danish", "English"},
		},
		Weights: ScoringWeights{
			Location:     25,
			TechMatch:    35,
			CompanyFit:   20,
			SeniorityFit: 15,
			WorkTypeFit:  5,
		},
	}
}

func generateOptimizedPrompt(config JobMatchConfig, job models.JobPosting) string {
	// Format job data using helper functions
	applicants := formatApplicants(job.Applicants)
	workType := formatWorkType(job.WorkType)
	skills := formatSkills(job.Skills)
	hasDescription := formatHasDescription(job.Description)
	
	// Format candidate data
	candidateData := formatCandidateData(config.Candidate)
	
	// Build prompt sections
	jobSection := buildJobSection(job, applicants, workType, skills, hasDescription)
	candidateSection := buildCandidateSection(config.Candidate, candidateData)
	weightsSection := buildWeightsSection(config.Weights)
	guidelinesSection := buildGuidelinesSection(config)
	
	prompt := fmt.Sprintf(`You are an expert job matching AI that provides REALISTIC and ACCURATE evaluations.

%s

%s

%s

%s

CALCULATE WEIGHTED SCORE:
Overall = (Location×%d + TechMatch×%d + CompanyFit×%d + SeniorityFit×%d + WorkTypeFit×%d) / 100

Return ONLY this JSON (all scores as integers):

{
  "job_id": %d,
  "overall_score": [WEIGHTED_SCORE_INTEGER],
  "scores": {
    "location": [INTEGER_0_100],
    "tech_match": [INTEGER_0_100],
    "company_fit": [INTEGER_0_100],
    "seniority_fit": [INTEGER_0_100],
    "work_type_fit": [INTEGER_0_100]
  },
  "reasoning": {
    "location": "Distance and commute assessment",
    "tech_match": "Primary and secondary skill matches found",
    "company_fit": "Company size and culture assessment",
    "seniority_fit": "Role level and responsibility match",
    "work_type_fit": "Work arrangement flexibility",
    "summary": "Overall job fit assessment"
  },
  "confidence": [INTEGER_0_100]
}`, 
		jobSection, candidateSection, weightsSection, guidelinesSection,
		config.Weights.Location, config.Weights.TechMatch, config.Weights.CompanyFit, config.Weights.SeniorityFit, config.Weights.WorkTypeFit,
		job.JobID)

	return prompt
}

// Helper functions for formatting
func formatApplicants(applicants *int) string {
	if applicants != nil {
		return fmt.Sprintf("%d applicants", *applicants)
	}
	return notSpecified
}

func formatWorkType(workType *string) string {
	if workType != nil {
		return *workType
	}
	return notSpecified
}

func formatSkills(skills *models.SkillsList) string {
	if skills != nil {
		return strings.Join(*skills, ", ")
	}
	return notSpecified
}

func formatHasDescription(description string) string {
	if description != "" && len(description) > 50 {
		return "Yes"
	}
	return "No"
}

type candidateData struct {
	primarySkills   string
	secondarySkills string
	preferredRoles  string
	avoidRoles      string
	languages       string
}

func formatCandidateData(candidate CandidateProfile) candidateData {
	return candidateData{
		primarySkills:   strings.Join(candidate.PrimarySkills, ", "),
		secondarySkills: strings.Join(candidate.SecondarySkills, ", "),
		preferredRoles:  strings.Join(candidate.PreferredRoles, ", "),
		avoidRoles:      strings.Join(candidate.AvoidRoles, ", "),
		languages:       strings.Join(candidate.Languages, ", "),
	}
}

func buildJobSection(job models.JobPosting, applicants, workType, skills, hasDescription string) string {
	return fmt.Sprintf(`ANALYZE THIS JOB:
Job ID: %d
Title: %s
Company: %s
Location: %s
Applicants: %s
Work Type: %s
Skills Listed: %s
Has Description: %s`, job.JobID, job.Title, job.CompanyName, job.Location, applicants, workType, skills, hasDescription)
}

func buildCandidateSection(candidate CandidateProfile, data candidateData) string {
	return fmt.Sprintf(`CANDIDATE PROFILE:
• Name: %s
• Location: %s (%s commute tolerance)
• Experience: %d+ years
• Primary Skills: %s
• Secondary Skills: %s
• Preferred Roles: %s
• Avoid Roles: %s
• Company Size Preference: %s (minimum %d employees)
• Work Preferences: Remote=%t, Hybrid=%t, OnSite=%t
• Languages: %s`, 
		candidate.Name, candidate.Location, candidate.WorkPreferences.MaxCommute, candidate.YearsExperience,
		data.primarySkills, data.secondarySkills, data.preferredRoles, data.avoidRoles,
		candidate.CompanySize.Preferred, candidate.CompanySize.Minimum,
		candidate.WorkPreferences.Remote, candidate.WorkPreferences.Hybrid, candidate.WorkPreferences.OnSite,
		data.languages)
}

func buildWeightsSection(weights ScoringWeights) string {
	return fmt.Sprintf(`SCORING WEIGHTS:
• Location: %d%% weight
• Tech Match: %d%% weight  
• Company Fit: %d%% weight
• Seniority Fit: %d%% weight
• Work Type Fit: %d%% weight`, weights.Location, weights.TechMatch, weights.CompanyFit, weights.SeniorityFit, weights.WorkTypeFit)
}

func buildGuidelinesSection(config JobMatchConfig) string {
	return fmt.Sprintf(`SCORING GUIDELINES (0-100):

**LOCATION MATCH (%d%% weight):**
• %s or within 20km: 95-100 (excellent)
• Copenhagen metropolitan area: 80-90 (very good)
• Rest of Denmark with remote option: 60-75 (acceptable)
• International with full remote: 50-65 (possible)
• International on-site only: 0-30 (poor)

**TECH MATCH (%d%% weight):**
Analyze job title + skills for PRIMARY: %s
• 5+ primary skill matches: 90-100 (excellent)
• 3-4 primary skill matches: 75-85 (very good)
• 1-2 primary skill matches: 60-70 (good)
• Secondary skills only: 40-55 (acceptable)
• Related/transferable skills: 25-40 (possible)
• No relevant skills: 0-20 (poor)

**COMPANY FIT (%d%% weight):**
• Large established companies (100+ employees): 85-100
• Medium companies (50-100 employees): 75-85
• Small companies (20-50 employees): 60-75
• Startups (<20 employees): 30-50
• Unknown size with many applicants: 70-80
• Unknown size with few applicants: 40-60

**SENIORITY FIT (%d%% weight):**
Match against PREFERRED: %s
AVOID: %s
• Perfect role match: 90-100
• Good role match: 75-85
• Acceptable role: 60-75
• Slight mismatch: 40-60
• Management roles (avoid list): 0-30
• Too junior for experience: 20-40

**WORK TYPE FIT (%d%% weight):**
• Remote/Hybrid explicitly mentioned: 90-100
• Flexible work mentioned: 80-90
• Location-based but within commute: 70-80
• Not specified: 50-60
• Strict on-site far location: 20-40`, 
		config.Weights.Location, config.Candidate.Location,
		config.Weights.TechMatch, strings.Join(config.Candidate.PrimarySkills, ", "),
		config.Weights.CompanyFit,
		config.Weights.SeniorityFit, strings.Join(config.Candidate.PreferredRoles, ", "), strings.Join(config.Candidate.AvoidRoles, ", "),
		config.Weights.WorkTypeFit)
}

func main() {
	// Parse command line arguments
	flags := parseFlags()
	
	// Handle configuration operations
	if flags.saveConfig {
		handleSaveConfig(flags.configFile)
		return
	}
	
	// Load configuration
	matchConfig := loadMatchConfig(flags.configFile)
	
	// Initialize services
	db, client := initializeServices()
	defer db.Close()
	
	// Process jobs
	processJobs(db, client, matchConfig, flags)
}

type appFlags struct {
	limit      int
	dryRun     bool
	minScore   int
	verbose    bool
	configFile string
	saveConfig bool
}

func parseFlags() appFlags {
	var flags appFlags
	flag.IntVar(&flags.limit, "limit", 10, "Number of jobs to process (0 for all)")
	flag.BoolVar(&flags.dryRun, "dry-run", false, "Show what would be processed")
	flag.IntVar(&flags.minScore, "min-score", 50, "Minimum overall score")
	flag.BoolVar(&flags.verbose, "verbose", false, "Show detailed reasoning")
	flag.StringVar(&flags.configFile, "config", "", "Path to JSON config file (optional)")
	flag.BoolVar(&flags.saveConfig, "save-config", false, "Save default config to file")
	flag.Parse()
	return flags
}

func handleSaveConfig(configFile string) {
	config := getDefaultConfig()
	configJSON, _ := json.MarshalIndent(config, "", "  ")
	configPath := "job_match_config.json"
	if configFile != "" {
		configPath = configFile
	}
	if err := os.WriteFile(configPath, configJSON, 0644); err != nil {
		log.Fatalf("Failed to save config: %v", err)
	}
	fmt.Printf("✅ Default config saved to: %s\n", configPath)
	fmt.Println("You can now edit this file to customize your preferences!")
}

func loadMatchConfig(configFile string) JobMatchConfig {
	if configFile != "" {
		configData, err := os.ReadFile(configFile)
		if err != nil {
			log.Fatalf("Failed to read config file: %v", err)
		}
		var matchConfig JobMatchConfig
		if err := json.Unmarshal(configData, &matchConfig); err != nil {
			log.Fatalf("Failed to parse config file: %v", err)
		}
		fmt.Printf("📋 Using config from: %s\n", configFile)
		return matchConfig
	}
	fmt.Println("📋 Using default configuration")
	return getDefaultConfig()
}

func initializeServices() (*database.DB, *openai.Client) {
	// Load environment variables
	if err := godotenv.Load(); err != nil {
		log.Printf("Warning: .env file not found, using environment variables")
	}
	
	// Load database configuration
	cfg := config.Load()
	
	// Initialize database
	db, err := database.NewConnection(cfg.Database)
	if err != nil {
		log.Fatalf("Failed to connect to database: %v", err)
	}
	
	// Initialize OpenAI client
	openaiKey := os.Getenv("OPENAI_API_KEY")
	if openaiKey == "" {
		log.Fatalf("OPENAI_API_KEY environment variable not set")
	}
	
	return db, openai.NewClient(openaiKey)
}

func processJobs(db *database.DB, client *openai.Client, matchConfig JobMatchConfig, flags appFlags) {
	// Get jobs for matching
	jobs, err := getJobsForMatching(db, flags.limit)
	if err != nil {
		log.Fatalf("Failed to get jobs: %v", err)
	}
	
	log.Printf("Found %d jobs to process", len(jobs))
	
	if flags.dryRun {
		showDryRunResults(jobs)
		return
	}
	
	// Process jobs and get results
	results := processJobMatches(client, db, jobs, matchConfig, flags.minScore)
	
	// Display results
	displayResults(results, flags.minScore, flags.verbose, db, matchConfig)
}

func showDryRunResults(jobs []models.JobPosting) {
	log.Println("DRY RUN - Would process these jobs:")
	for _, job := range jobs {
		hasDesc := "No"
		if job.Description != "" && len(job.Description) > 50 {
			hasDesc = "Yes"
		}
		fmt.Printf("Job ID: %d, Title: %s, Company: %s, Location: %s, Description: %s\n", 
			job.JobID, job.Title, job.CompanyName, job.Location, hasDesc)
	}
}

func processJobMatches(client *openai.Client, db *database.DB, jobs []models.JobPosting, matchConfig JobMatchConfig, minScore int) []OptimizedJobMatch {
	var results []OptimizedJobMatch
	processed := 0
	errors := 0
	
	for _, job := range jobs {
		log.Printf("Processing job %d: %s at %s", job.JobID, job.Title, job.CompanyName)
		
		match, err := matchJobWithOptimizedAI(client, job, matchConfig)
		if err != nil {
			log.Printf("Error processing job %d: %v", job.JobID, err)
			errors++
			continue
		}
		
		// Save the match to database
		if err := saveJobRating(db, match); err != nil {
			log.Printf("Error saving job rating for job %d: %v", job.JobID, err)
		}
		
		// Add to results if above minimum score
		if match.OverallScore >= minScore {
			results = append(results, *match)
		}
		
		processed++
	}
	
	// Sort results by overall score (highest first)
	sort.Slice(results, func(i, j int) bool {
		return results[i].OverallScore > results[j].OverallScore
	})
	
	log.Printf("\n✅ Done! Processed: %d, Errors: %d, Results shown: %d", 
		processed, errors, len(results))
	
	return results
}

func getJobsForMatching(db *database.DB, limit int) ([]models.JobPosting, error) {
	query := `
		SELECT 
			j.job_id, j.title, j.location, j.description, j.apply_url,
			j.posted_date, j.applicants, j.work_type, j.skills,
			c.name as company_name
		FROM job_postings j
		LEFT JOIN companies c ON j.company_id = c.company_id
		LEFT JOIN job_ratings r ON j.job_id = r.job_id AND r.rating_type = ?
		WHERE j.title IS NOT NULL 
		AND j.title != ''
		AND r.job_id IS NULL
		ORDER BY j.posted_date DESC
	`
	
	if limit > 0 {
		query += fmt.Sprintf(" LIMIT %d", limit)
	}

	rows, err := db.Query(query, ratingType)
	if err != nil {
		return nil, fmt.Errorf("failed to query jobs: %w", err)
	}
	defer rows.Close()

	var jobs []models.JobPosting
	for rows.Next() {
		var job models.JobPosting
		err := rows.Scan(
			&job.JobID, &job.Title, &job.Location, &job.Description, &job.ApplyURL,
			&job.PostedDate, &job.Applicants, &job.WorkType, &job.Skills, &job.CompanyName,
		)
		if err != nil {
			return nil, fmt.Errorf("failed to scan job: %w", err)
		}
		jobs = append(jobs, job)
	}

	return jobs, nil
}

func matchJobWithOptimizedAI(client *openai.Client, job models.JobPosting, config JobMatchConfig) (*OptimizedJobMatch, error) {
	prompt := generateOptimizedPrompt(config, job)

	resp, err := client.CreateChatCompletion(
		context.Background(),
		openai.ChatCompletionRequest{
			Model: modelName,
			Messages: []openai.ChatCompletionMessage{
				{
					Role:    openai.ChatMessageRoleUser,
					Content: prompt,
				},
			},
			MaxTokens:   maxTokens,
			Temperature: temperature,
		},
	)

	if err != nil {
		return nil, fmt.Errorf("OpenAI API error: %w", err)
	}

	if len(resp.Choices) == 0 {
		return nil, fmt.Errorf("no response from OpenAI")
	}

	responseText := strings.TrimSpace(resp.Choices[0].Message.Content)
	responseText = strings.TrimPrefix(responseText, "```json")
	responseText = strings.TrimSuffix(responseText, "```")
	responseText = strings.TrimSpace(responseText)

	var match OptimizedJobMatch
	if err := json.Unmarshal([]byte(responseText), &match); err != nil {
		return nil, fmt.Errorf("failed to parse OpenAI response: %w\nResponse: %s", err, responseText)
	}

	return &match, nil
}

func saveJobRating(db *database.DB, match *OptimizedJobMatch) error {
	criteriaJSON, err := json.Marshal(map[string]interface{}{
		"location":     match.Reasoning.Location,
		"tech_match":   match.Reasoning.TechMatch,
		"company_fit":  match.Reasoning.CompanyFit,
		"seniority_fit": match.Reasoning.SeniorityFit,
		"work_type_fit": match.Reasoning.WorkTypeFit,
		"summary":      match.Reasoning.Summary,
		"confidence":   match.Confidence,
	})
	if err != nil {
		return fmt.Errorf("failed to marshal criteria: %w", err)
	}

	query := `
		INSERT INTO job_ratings (
			job_id, overall_score, location_score, tech_score, 
			team_size_score, leadership_score, criteria, rating_type, rated_at
		) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
		match.JobID, match.OverallScore, match.Scores.Location, match.Scores.TechMatch,
		match.Scores.CompanyFit, match.Scores.SeniorityFit, string(criteriaJSON), ratingType,
	)

	return err
}

func displayResults(results []OptimizedJobMatch, minScore int, verbose bool, db *database.DB, config JobMatchConfig) {
	fmt.Printf("\n🎯 OPTIMIZED JOB MATCH RESULTS (Min score: %d)\n", minScore)
	fmt.Printf("📊 Weights: Location:%d%% | Tech:%d%% | Company:%d%% | Seniority:%d%% | WorkType:%d%%\n",
		config.Weights.Location, config.Weights.TechMatch, config.Weights.CompanyFit, 
		config.Weights.SeniorityFit, config.Weights.WorkTypeFit)
	fmt.Printf(strings.Repeat("=", 80) + "\n\n")

	if len(results) == 0 {
		fmt.Printf("❌ No jobs found with score >= %d\n", minScore)
		return
	}

	for i, match := range results {
		job, _ := getJobByID(db, match.JobID)
		
		fmt.Printf("%d. 🏆 SCORE: %d/100 (Confidence: %d%%)\n", 
			i+1, match.OverallScore, match.Confidence)
		fmt.Printf("   📋 %s\n", job.Title)
		fmt.Printf("   🏢 %s\n", job.CompanyName)
		fmt.Printf("   📍 %s\n", job.Location)
		fmt.Printf("   🔗 %s\n", job.ApplyURL)
		
		if job.Applicants != nil {
			fmt.Printf("   👥 %d applicants\n", *job.Applicants)
		}

		fmt.Printf("\n   📊 SCORE BREAKDOWN:\n")
		fmt.Printf("   • Location:     %d/100 (weight: %d%%)\n", match.Scores.Location, config.Weights.Location)
		fmt.Printf("   • Tech Match:   %d/100 (weight: %d%%)\n", match.Scores.TechMatch, config.Weights.TechMatch)
		fmt.Printf("   • Company Fit:  %d/100 (weight: %d%%)\n", match.Scores.CompanyFit, config.Weights.CompanyFit)
		fmt.Printf("   • Seniority:    %d/100 (weight: %d%%)\n", match.Scores.SeniorityFit, config.Weights.SeniorityFit)
		fmt.Printf("   • Work Type:    %d/100 (weight: %d%%)\n", match.Scores.WorkTypeFit, config.Weights.WorkTypeFit)

		if verbose {
			fmt.Printf("\n   🧠 REASONING:\n")
			fmt.Printf("   • Location: %s\n", match.Reasoning.Location)
			fmt.Printf("   • Tech: %s\n", match.Reasoning.TechMatch)
			fmt.Printf("   • Company: %s\n", match.Reasoning.CompanyFit)
			fmt.Printf("   • Seniority: %s\n", match.Reasoning.SeniorityFit)
			fmt.Printf("   • Work Type: %s\n", match.Reasoning.WorkTypeFit)
			fmt.Printf("   • Summary: %s\n", match.Reasoning.Summary)
		}

		fmt.Printf("\n" + strings.Repeat("-", 80) + "\n")
	}
}

func getJobByID(db *database.DB, jobID int) (*models.JobPosting, error) {
	query := `
		SELECT j.job_id, j.title, j.location, j.description, j.apply_url,
		       j.posted_date, j.applicants, j.work_type, c.name as company_name
		FROM job_postings j
		LEFT JOIN companies c ON j.company_id = c.company_id
		WHERE j.job_id = ?
	`

	row := db.QueryRow(query, jobID)
	var job models.JobPosting
	err := row.Scan(&job.JobID, &job.Title, &job.Location, &job.Description, &job.ApplyURL,
		&job.PostedDate, &job.Applicants, &job.WorkType, &job.CompanyName)
	
	return &job, err
}
