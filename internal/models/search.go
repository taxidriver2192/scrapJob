package models

// SearchParams represents search parameters for job scraping
type SearchParams struct {
	Keywords string
	Location string
	GeoID    string
	Start    int
	MaxPages int
}
