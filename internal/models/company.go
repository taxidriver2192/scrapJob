package models

// Company represents the companies table
type Company struct {
	CompanyID int    `json:"company_id" db:"company_id"`
	Name      string `json:"name" db:"name"`
}
