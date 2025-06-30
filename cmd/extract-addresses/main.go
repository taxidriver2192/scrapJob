package main

import (
	"context"
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

const addressExtractionPrompt = `Du er en AI-assistent der er ekspert i at finde og standardisere adresser fra jobbeskrivelser.

Din opgave er at analysere jobbeskrivelsen og finde den specifikke arbejdsadresse hvis den findes.

VIGTIGE REGLER:
1. Returner kun FAKTISKE arbejdsadresser (ikke virksomhedens hovedkontor medmindre det er arbejdspladsen)
2. Standardiser adressen til dette format: "Vejnavn Nummer, Postnummer By"
3. Hvis der ikke er en specifik adresse, returner: "Ikke angivet"
4. Hvis det er remote/hjemmefra, returner: "Remote"
5. Hvis det er flere lokationer, returner den primære adresse

EKSEMPLER:
- "Arbejdspladsen er på Vesterbrogade 123, 1620 København V" → "Vesterbrogade 123, 1620 København V"
- "Vi er placeret på Nørrebrogade 45 i København" → "Nørrebrogade 45, 2200 København N"
- "Kontoret ligger i Aarhus C" → "Aarhus C"
- "Remote arbejde fra hjemmet" → "Remote"
- "Både København og Aarhus kontorer" → "København" fordi jeg befinder mig på Sjælland"

Analyser følgende jobbeskrivelse og returner kun adressen i det standardiserede format:

%s`

func main() {
	var (
		limit  = flag.Int("limit", 10, "Antal jobs at behandle (0 for alle)")
		dryRun = flag.Bool("dry-run", false, "Vis kun hvad der ville blive gjort")
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

	// Get jobs that need address extraction
	jobs, err := getJobsNeedingAddressExtraction(db, *limit)
	if err != nil {
		log.Fatalf("Failed to get jobs: %v", err)
	}

	log.Printf("Found %d jobs that need address extraction", len(jobs))

	if *dryRun {
		log.Println("DRY RUN - Would process these jobs:")
		for _, job := range jobs {
			fmt.Printf("Job ID: %d, Title: %s, Company: %s\n", job.JobID, job.Title, job.CompanyName)
		}
		return
	}

	// Process each job
	processed := 0
	errors := 0

	for _, job := range jobs {
		log.Printf("Processing job %d: %s at %s", job.JobID, job.Title, job.CompanyName)

		address, err := extractAddressWithOpenAI(client, job.Description)
		if err != nil {
			log.Printf("Error extracting address for job %d: %v", job.JobID, err)
			errors++
			continue
		}

		// Update job with extracted address
		if err := updateJobAddress(db, job.JobID, address); err != nil {
			log.Printf("Error updating job %d: %v", job.JobID, err)
			errors++
			continue
		}

		log.Printf("✅ Job %d: %s", job.JobID, address)
		processed++
	}

	log.Printf("Done! Processed: %d, Errors: %d", processed, errors)
}

func getJobsNeedingAddressExtraction(db *database.DB, limit int) ([]models.JobPosting, error) {
	query := `
		SELECT 
			j.job_id, j.title, j.description, c.name as company_name
		FROM job_postings j
		LEFT JOIN companies c ON j.company_id = c.company_id
		WHERE j.openai_adresse IS NULL 
		AND j.description IS NOT NULL 
		AND j.description != ''
		ORDER BY j.created_at DESC
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
			&job.Description,
			&job.CompanyName,
		)
		if err != nil {
			return nil, fmt.Errorf("failed to scan job: %w", err)
		}
		jobs = append(jobs, job)
	}

	return jobs, nil
}

func extractAddressWithOpenAI(client *openai.Client, description string) (string, error) {
	// Clean description (remove HTML tags, extra whitespace)
	cleanDesc := strings.TrimSpace(description)
	if len(cleanDesc) > 4000 {
		// Truncate very long descriptions
		cleanDesc = cleanDesc[:4000] + "..."
	}

	prompt := fmt.Sprintf(addressExtractionPrompt, cleanDesc)

	resp, err := client.CreateChatCompletion(
		context.Background(),
		openai.ChatCompletionRequest{
			Model: openai.GPT3Dot5Turbo,
			Messages: []openai.ChatCompletionMessage{
				{
					Role:    openai.ChatMessageRoleUser,
					Content: prompt,
				},
			},
			MaxTokens:   100,
			Temperature: 0.1, // Low temperature for consistent formatting
		},
	)

	if err != nil {
		return "", fmt.Errorf("OpenAI API error: %w", err)
	}

	if len(resp.Choices) == 0 {
		return "", fmt.Errorf("no response from OpenAI")
	}

	address := strings.TrimSpace(resp.Choices[0].Message.Content)
	return address, nil
}

func updateJobAddress(db *database.DB, jobID int, address string) error {
	query := `UPDATE job_postings SET openai_adresse = ? WHERE job_id = ?`
	_, err := db.Exec(query, address, jobID)
	if err != nil {
		return fmt.Errorf("failed to update job address: %w", err)
	}
	return nil
}
