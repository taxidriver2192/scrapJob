package models

import (
	"time"
)

// User represents the users table
type User struct {
	ID                  int                `json:"id" db:"id"`
	LinkedInID          string             `json:"linkedin_id" db:"linkedin_id"`
	LinkedInURL         string             `json:"linkedin_url" db:"linkedin_url"`
	Headline            string             `json:"headline" db:"headline"`
	Summary             string             `json:"summary" db:"summary"`
	LocationCity        string             `json:"location_city" db:"location_city"`
	LocationCountry     string             `json:"location_country" db:"location_country"`
	IndustryName        string             `json:"industry_name" db:"industry_name"`
	Avatar              string             `json:"avatar" db:"avatar"`
	BackgroundImage     string             `json:"background_image" db:"background_image"`
	LinkedInSyncedAt    *time.Time         `json:"linkedin_synced_at" db:"linkedin_synced_at"`
	CreatedAt           time.Time          `json:"created_at" db:"created_at"`
	UpdatedAt           time.Time          `json:"updated_at" db:"updated_at"`
	
	// Relations (populated when needed)
	Positions           []Position         `json:"positions,omitempty"`
	Educations          []Education        `json:"educations,omitempty"`
	Certifications      []Certification    `json:"certifications,omitempty"`
	Projects            []Project          `json:"projects,omitempty"`
	Publications        []Publication      `json:"publications,omitempty"`
	Patents             []Patent           `json:"patents,omitempty"`
	VolunteerExperiences []VolunteerExperience `json:"volunteer_experiences,omitempty"`
	Skills              []Skill            `json:"skills,omitempty"`
	SkillFrequencies    []SkillFrequency   `json:"skill_frequencies,omitempty"`
}

// Position represents the positions table
type Position struct {
	ID                   int       `json:"id" db:"id"`
	UserID               int       `json:"user_id" db:"user_id"`
	Title                string    `json:"title" db:"title"`
	CompanyName          string    `json:"company_name" db:"company_name"`
	Summary              string    `json:"summary" db:"summary"`
	Location             string    `json:"location" db:"location"`
	StartDate            *time.Time `json:"start_date" db:"start_date"`
	EndDate              *time.Time `json:"end_date" db:"end_date"`
	StartYear            *int      `json:"start_year" db:"start_year"`
	StartMonth           *int      `json:"start_month" db:"start_month"`
	EndYear              *int      `json:"end_year" db:"end_year"`
	EndMonth             *int      `json:"end_month" db:"end_month"`
	CompanyUrn           string    `json:"company_urn" db:"company_urn"`
	CompanyIndustry      string    `json:"company_industry" db:"company_industry"`
	CompanyEmployeeRange string    `json:"company_employee_range" db:"company_employee_range"`
	LogoJSON             string    `json:"logo_json" db:"logo_json"`
	CreatedAt            time.Time `json:"created_at" db:"created_at"`
	UpdatedAt            time.Time `json:"updated_at" db:"updated_at"`
	
	// Relations (populated when needed)
	Skills               []Skill   `json:"skills,omitempty"`
}

// Education represents the educations table
type Education struct {
	ID            int       `json:"id" db:"id"`
	UserID        int       `json:"user_id" db:"user_id"`
	SchoolName    string    `json:"school_name" db:"school_name"`
	Degree        string    `json:"degree" db:"degree"`
	FieldOfStudy  string    `json:"field_of_study" db:"field_of_study"`
	StartYear     *int      `json:"start_year" db:"start_year"`
	EndYear       *int      `json:"end_year" db:"end_year"`
	SchoolUrn     string    `json:"school_urn" db:"school_urn"`
	LogoJSON      string    `json:"logo_json" db:"logo_json"`
	CreatedAt     time.Time `json:"created_at" db:"created_at"`
	UpdatedAt     time.Time `json:"updated_at" db:"updated_at"`
	
	// Relations (populated when needed)
	Skills        []Skill   `json:"skills,omitempty"`
}

// Certification represents the certifications table
type Certification struct {
	ID            int       `json:"id" db:"id"`
	UserID        int       `json:"user_id" db:"user_id"`
	Name          string    `json:"name" db:"name"`
	Authority     string    `json:"authority" db:"authority"`
	LicenseNumber string    `json:"license_number" db:"license_number"`
	URL           string    `json:"url" db:"url"`
	StartYear     *int      `json:"start_year" db:"start_year"`
	StartMonth    *int      `json:"start_month" db:"start_month"`
	EndYear       *int      `json:"end_year" db:"end_year"`
	EndMonth      *int      `json:"end_month" db:"end_month"`
	CreatedAt     time.Time `json:"created_at" db:"created_at"`
	UpdatedAt     time.Time `json:"updated_at" db:"updated_at"`
}

// Project represents the projects table
type Project struct {
	ID          int       `json:"id" db:"id"`
	UserID      int       `json:"user_id" db:"user_id"`
	Title       string    `json:"title" db:"title"`
	Description string    `json:"description" db:"description"`
	URL         string    `json:"url" db:"url"`
	StartYear   *int      `json:"start_year" db:"start_year"`
	StartMonth  *int      `json:"start_month" db:"start_month"`
	EndYear     *int      `json:"end_year" db:"end_year"`
	EndMonth    *int      `json:"end_month" db:"end_month"`
	CreatedAt   time.Time `json:"created_at" db:"created_at"`
	UpdatedAt   time.Time `json:"updated_at" db:"updated_at"`
}

// Publication represents the publications table
type Publication struct {
	ID          int       `json:"id" db:"id"`
	UserID      int       `json:"user_id" db:"user_id"`
	Title       string    `json:"title" db:"title"`
	Publisher   string    `json:"publisher" db:"publisher"`
	PublishedOn string    `json:"published_on" db:"published_on"`
	URL         string    `json:"url" db:"url"`
	Description string    `json:"description" db:"description"`
	CreatedAt   time.Time `json:"created_at" db:"created_at"`
	UpdatedAt   time.Time `json:"updated_at" db:"updated_at"`
}

// Patent represents the patents table
type Patent struct {
	ID          int       `json:"id" db:"id"`
	UserID      int       `json:"user_id" db:"user_id"`
	Title       string    `json:"title" db:"title"`
	PatentOffice string   `json:"patent_office" db:"patent_office"`
	PatentNumber string   `json:"patent_number" db:"patent_number"`
	URL         string    `json:"url" db:"url"`
	IssuedOn    string    `json:"issued_on" db:"issued_on"`
	Description string    `json:"description" db:"description"`
	CreatedAt   time.Time `json:"created_at" db:"created_at"`
	UpdatedAt   time.Time `json:"updated_at" db:"updated_at"`
}

// VolunteerExperience represents the volunteer_experiences table
type VolunteerExperience struct {
	ID           int       `json:"id" db:"id"`
	UserID       int       `json:"user_id" db:"user_id"`
	Role         string    `json:"role" db:"role"`
	Organization string    `json:"organization" db:"organization"`
	Cause        string    `json:"cause" db:"cause"`
	Description  string    `json:"description" db:"description"`
	StartYear    *int      `json:"start_year" db:"start_year"`
	StartMonth   *int      `json:"start_month" db:"start_month"`
	EndYear      *int      `json:"end_year" db:"end_year"`
	EndMonth     *int      `json:"end_month" db:"end_month"`
	CreatedAt    time.Time `json:"created_at" db:"created_at"`
	UpdatedAt    time.Time `json:"updated_at" db:"updated_at"`
}

// Skill represents the skills table
type Skill struct {
	ID        int       `json:"id" db:"id"`
	Name      string    `json:"name" db:"name"`
	CreatedAt time.Time `json:"created_at" db:"created_at"`
	UpdatedAt time.Time `json:"updated_at" db:"updated_at"`
}

// PositionSkill represents the position_skills table (many-to-many relationship)
type PositionSkill struct {
	ID         int       `json:"id" db:"id"`
	PositionID int       `json:"position_id" db:"position_id"`
	SkillID    int       `json:"skill_id" db:"skill_id"`
	CreatedAt  time.Time `json:"created_at" db:"created_at"`
	UpdatedAt  time.Time `json:"updated_at" db:"updated_at"`
}

// EducationSkill represents the education_skills table (many-to-many relationship)
type EducationSkill struct {
	ID          int       `json:"id" db:"id"`
	EducationID int       `json:"education_id" db:"education_id"`
	SkillID     int       `json:"skill_id" db:"skill_id"`
	CreatedAt   time.Time `json:"created_at" db:"created_at"`
	UpdatedAt   time.Time `json:"updated_at" db:"updated_at"`
}

// SkillFrequency represents skill frequency data for analysis
type SkillFrequency struct {
	Skill     Skill `json:"skill"`
	Frequency int   `json:"frequency"`
}
