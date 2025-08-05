package models

// Company represents the companies table
type Company struct {
	CompanyID int    `json:"company_id" db:"company_id"`
	Name      string `json:"name" db:"name"`
	ImageURL  string `json:"image_url" db:"image_url"`
}
