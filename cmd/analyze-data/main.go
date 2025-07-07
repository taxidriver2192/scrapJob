package main

import (
	"fmt"
	"log"
	"strings"

	"github.com/joho/godotenv"

	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/database"
)

func main() {
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

	fmt.Println("🔍 ANALYZING AVAILABLE JOB DATA")
	fmt.Println(strings.Repeat("=", 50))

	// Analyze job data structure
	analyzeJobData(db)
	
	// Analyze data quality
	analyzeDataQuality(db)
	
	// Show sample data
	showSampleData(db)
}

func analyzeJobData(db *database.DB) {
	fmt.Println("\n📊 DATA STRUCTURE ANALYSIS")
	fmt.Println(strings.Repeat("-", 30))

	// Total jobs
	var totalJobs int
	db.QueryRow("SELECT COUNT(*) FROM job_postings").Scan(&totalJobs)
	fmt.Printf("Total Jobs: %d\n", totalJobs)

	// Total companies
	var totalCompanies int
	db.QueryRow("SELECT COUNT(*) FROM companies").Scan(&totalCompanies)
	fmt.Printf("Total Companies: %d\n", totalCompanies)

	// Total ratings
	var totalRatings int
	db.QueryRow("SELECT COUNT(*) FROM job_ratings").Scan(&totalRatings)
	fmt.Printf("Total Ratings: %d\n", totalRatings)

	// Jobs by work type
	fmt.Println("\nJobs by Work Type:")
	rows, err := db.Query(`
		SELECT 
			COALESCE(work_type, 'NULL') as work_type,
			COUNT(*) as count
		FROM job_postings 
		GROUP BY work_type
		ORDER BY count DESC
	`)
	if err != nil {
		log.Printf("Error querying work types: %v", err)
		return
	}
	defer rows.Close()

	for rows.Next() {
		var workType string
		var count int
		rows.Scan(&workType, &count)
		fmt.Printf("  %s: %d jobs\n", workType, count)
	}

	// Jobs by location (top 10)
	fmt.Println("\nTop 10 Job Locations:")
	rows, err = db.Query(`
		SELECT 
			location,
			COUNT(*) as count
		FROM job_postings 
		GROUP BY location
		ORDER BY count DESC
		LIMIT 10
	`)
	if err != nil {
		log.Printf("Error querying locations: %v", err)
		return
	}
	defer rows.Close()

	for rows.Next() {
		var location string
		var count int
		rows.Scan(&location, &count)
		fmt.Printf("  %s: %d jobs\n", location, count)
	}
}

func analyzeDataQuality(db *database.DB) {
	fmt.Println("\n🔍 DATA QUALITY ANALYSIS")
	fmt.Println(strings.Repeat("-", 30))

	// Jobs with empty descriptions
	var emptyDescriptions int
	db.QueryRow("SELECT COUNT(*) FROM job_postings WHERE description IS NULL OR description = ''").Scan(&emptyDescriptions)
	fmt.Printf("Jobs with empty descriptions: %d\n", emptyDescriptions)

	// Jobs with applicant count
	var withApplicants int
	db.QueryRow("SELECT COUNT(*) FROM job_postings WHERE applicants IS NOT NULL").Scan(&withApplicants)
	fmt.Printf("Jobs with applicant count: %d\n", withApplicants)

	// Jobs with skills
	var withSkills int
	db.QueryRow("SELECT COUNT(*) FROM job_postings WHERE skills IS NOT NULL").Scan(&withSkills)
	fmt.Printf("Jobs with skills: %d\n", withSkills)

	// Jobs with OpenAI addresses
	var withOpenAIAddr int
	db.QueryRow("SELECT COUNT(*) FROM job_postings WHERE openai_adresse IS NOT NULL").Scan(&withOpenAIAddr)
	fmt.Printf("Jobs with OpenAI addresses: %d\n", withOpenAIAddr)

	// Average description length
	var avgDescLength float64
	db.QueryRow("SELECT AVG(LENGTH(description)) FROM job_postings WHERE description IS NOT NULL AND description != ''").Scan(&avgDescLength)
	fmt.Printf("Average description length: %.0f characters\n", avgDescLength)

	// Jobs posted in last 30 days
	var recentJobs int
	db.QueryRow("SELECT COUNT(*) FROM job_postings WHERE posted_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)").Scan(&recentJobs)
	fmt.Printf("Jobs posted in last 30 days: %d\n", recentJobs)

	// Rating distribution
	fmt.Println("\nRating Distribution:")
	rows, err := db.Query(`
		SELECT 
			FLOOR(overall_score/10)*10 as score_range,
			COUNT(*) as count
		FROM job_ratings 
		WHERE rating_type = 'ai_match'
		GROUP BY score_range
		ORDER BY score_range DESC
	`)
	if err != nil {
		log.Printf("Error querying ratings: %v", err)
		return
	}
	defer rows.Close()

	for rows.Next() {
		var scoreRange int
		var count int
		rows.Scan(&scoreRange, &count)
		fmt.Printf("  %d-%d: %d jobs\n", scoreRange, scoreRange+9, count)
	}
}

func showSampleData(db *database.DB) {
	fmt.Println("\n📋 SAMPLE DATA")
	fmt.Println(strings.Repeat("-", 30))

	// Show sample job with all fields
	rows, err := db.Query(`
		SELECT 
			j.job_id, j.title, j.location, j.work_type, j.applicants,
			LENGTH(j.description) as desc_length,
			j.skills, j.openai_adresse,
			c.name as company_name,
			r.overall_score, r.location_score, r.tech_score, r.team_size_score, r.leadership_score
		FROM job_postings j
		LEFT JOIN companies c ON j.company_id = c.company_id
		LEFT JOIN job_ratings r ON j.job_id = r.job_id AND r.rating_type = 'ai_match'
		WHERE j.description IS NOT NULL AND j.description != ''
		ORDER BY j.posted_date DESC
		LIMIT 3
	`)
	if err != nil {
		log.Printf("Error querying sample data: %v", err)
		return
	}
	defer rows.Close()

	for rows.Next() {
		var jobID int
		var title, location, companyName string
		var workType, skills, openaiAddr *string
		var applicants, descLength *int
		var overallScore, locationScore, techScore, teamSizeScore, leadershipScore *int

		rows.Scan(&jobID, &title, &location, &workType, &applicants, 
			&descLength, &skills, &openaiAddr, &companyName,
			&overallScore, &locationScore, &techScore, &teamSizeScore, &leadershipScore)

		fmt.Printf("\nJob ID: %d\n", jobID)
		fmt.Printf("Title: %s\n", title)
		fmt.Printf("Company: %s\n", companyName)
		fmt.Printf("Location: %s\n", location)
		
		if workType != nil {
			fmt.Printf("Work Type: %s\n", *workType)
		}
		if applicants != nil {
			fmt.Printf("Applicants: %d\n", *applicants)
		}
		if descLength != nil {
			fmt.Printf("Description Length: %d chars\n", *descLength)
		}
		if skills != nil {
			fmt.Printf("Skills: %s\n", *skills)
		}
		if openaiAddr != nil {
			fmt.Printf("OpenAI Address: %s\n", *openaiAddr)
		}
		
		if overallScore != nil {
			fmt.Printf("Overall Score: %d\n", *overallScore)
			fmt.Printf("  Location: %d, Tech: %d, Team Size: %d, Leadership: %d\n", 
				*locationScore, *techScore, *teamSizeScore, *leadershipScore)
		}
		
		fmt.Println(strings.Repeat("-", 50))
	}
}
