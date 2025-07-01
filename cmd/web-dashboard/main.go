package main

import (
	"encoding/json"
	"html/template"
	"log"
	"net/http"
	"time"

	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/database"

	"github.com/joho/godotenv"
)

const (
	ContentTypeHeader = "Content-Type"
	ContentTypeJSON   = "application/json"
	ContentTypeHTML   = "text/html"
)

type WebJob struct {
	JobID        int       `json:"job_id"`
	LinkedInID   int64     `json:"linkedin_job_id"`
	Title        string    `json:"title"`
	CompanyName  string    `json:"company_name"`
	Location     string    `json:"location"`
	Description  string    `json:"description"`
	ApplyURL     string    `json:"apply_url"`
	PostedDate   time.Time `json:"posted_date"`
	Applicants   *int      `json:"applicants"`
	WorkType     *string   `json:"work_type"`
	Skills       *string   `json:"skills"`
	CreatedAt    time.Time `json:"created_at"`
	
	// Rating fields (if exists)
	OverallScore     *int    `json:"overall_score"`
	LocationScore    *int    `json:"location_score"`
	TechScore        *int    `json:"tech_score"`
	TeamSizeScore    *int    `json:"team_size_score"`
	LeadershipScore  *int    `json:"leadership_score"`
	RatingType       *string `json:"rating_type"`
	RatedAt          *time.Time `json:"rated_at"`
}

type WebCompany struct {
	CompanyID int    `json:"company_id"`
	Name      string `json:"name"`
	JobCount  int    `json:"job_count"`
}

type WebQueue struct {
	QueueID    int       `json:"queue_id"`
	JobID      int       `json:"job_id"`
	JobTitle   string    `json:"job_title"`
	Company    string    `json:"company"`
	QueuedAt   time.Time `json:"queued_at"`
	StatusCode int       `json:"status_code"`
	StatusText string    `json:"status_text"`
}

type WebRating struct {
	RatingID        int       `json:"rating_id"`
	JobID           int       `json:"job_id"`
	JobTitle        string    `json:"job_title"`
	Company         string    `json:"company"`
	OverallScore    *int      `json:"overall_score"`
	LocationScore   *int      `json:"location_score"`
	TechScore       *int      `json:"tech_score"`
	TeamSizeScore   *int      `json:"team_size_score"`
	LeadershipScore *int      `json:"leadership_score"`
	RatingType      *string   `json:"rating_type"`
	RatedAt         time.Time `json:"rated_at"`
	Criteria        string    `json:"criteria"`
}

type DashboardData struct {
	TotalJobs      int
	TotalCompanies int
	TotalRatings   int
	QueuedJobs     int
	AvgScore       *float64
	RecentJobs     []WebJob
}

var db *database.DB

func main() {
	// Load environment variables
	if err := godotenv.Load(); err != nil {
		log.Printf("Warning: .env file not found")
	}

	// Load configuration
	cfg := config.Load()

	// Initialize database
	var err error
	db, err = database.NewConnection(cfg.Database)
	if err != nil {
		log.Fatalf("Failed to connect to database: %v", err)
	}
	defer db.Close()

	// Setup routes
	http.HandleFunc("/", dashboardHandler)
	http.HandleFunc("/health", healthHandler)
	http.HandleFunc("/api/jobs", apiJobsHandler)
	http.HandleFunc("/api/companies", apiCompaniesHandler)
	http.HandleFunc("/api/queue", apiQueueHandler)
	http.HandleFunc("/api/ratings", apiRatingsHandler)
	http.HandleFunc("/api/job", apiJobDetailHandler)
	http.HandleFunc("/jobs", jobsPageHandler)
	http.HandleFunc("/companies", companiesPageHandler)
	http.HandleFunc("/queue", queuePageHandler)
	http.HandleFunc("/ratings", ratingsPageHandler)

	// Serve static files
	fs := http.FileServer(http.Dir("./web/static/"))
	http.Handle("/static/", http.StripPrefix("/static/", fs))

	port := ":8081"
	log.Printf("🌐 Starting web dashboard on all network interfaces (port 8081)")
	log.Printf("📊 Local access: http://localhost%s/", port)
	log.Printf("🌍 Network access: http://[YOUR-IP]%s/", port)
	log.Printf("💼 Jobs: http://localhost%s/jobs", port)
	log.Printf("🏢 Companies: http://localhost%s/companies", port)
	log.Printf("📋 Queue: http://localhost%s/queue", port)
	log.Printf("⭐ Ratings: http://localhost%s/ratings", port)
	log.Printf("")
	log.Printf("💡 To find your IP address, run: ipconfig (Windows) or ifconfig (Linux/Mac)")
	
	log.Fatal(http.ListenAndServe(port, nil))
}

func dashboardHandler(w http.ResponseWriter, r *http.Request) {
	data, err := getDashboardData()
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	tmpl := `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinkedIn Job Scraper Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .navbar-brand { font-weight: bold; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="fas fa-briefcase me-2"></i>LinkedIn Job Scraper</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/"><i class="fas fa-chart-line me-1"></i>Dashboard</a>
                <a class="nav-link" href="/jobs"><i class="fas fa-briefcase me-1"></i>Jobs</a>
                <a class="nav-link" href="/companies"><i class="fas fa-building me-1"></i>Companies</a>
                <a class="nav-link" href="/queue"><i class="fas fa-list me-1"></i>Queue</a>
                <a class="nav-link" href="/ratings"><i class="fas fa-star me-1"></i>Ratings</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1><i class="fas fa-chart-line text-primary me-2"></i>Dashboard Overview</h1>
        
        <div class="row mt-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{.TotalJobs}}</h4>
                                <p class="mb-0">Total Jobs</p>
                            </div>
                            <i class="fas fa-briefcase fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{.TotalCompanies}}</h4>
                                <p class="mb-0">Companies</p>
                            </div>
                            <i class="fas fa-building fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{.TotalRatings}}</h4>
                                <p class="mb-0">AI Ratings</p>
                            </div>
                            <i class="fas fa-star fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{if .AvgScore}}{{printf "%.0f" .AvgScore}}{{else}}N/A{{end}}</h4>
                                <p class="mb-0">Avg Match Score</p>
                            </div>
                            <i class="fas fa-chart-bar fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-clock me-2"></i>Recent Jobs</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Company</th>
                                        <th>Location</th>
                                        <th>Posted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{range .RecentJobs}}
                                    <tr>
                                        <td>{{.Title}}</td>
                                        <td>{{.CompanyName}}</td>
                                        <td>{{.Location}}</td>
                                        <td>{{.PostedDate.Format "2006-01-02"}}</td>
                                        <td>
                                            <a href="{{.ApplyURL}}" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-external-link-alt"></i> Apply
                                            </a>
                                        </td>
                                    </tr>
                                    {{end}}
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="/jobs" class="btn btn-primary">View All Jobs</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
`

	t, _ := template.New("dashboard").Parse(tmpl)
	t.Execute(w, data)
}

func getDashboardData() (*DashboardData, error) {
	data := &DashboardData{}

	// Get total jobs
	err := db.QueryRow("SELECT COUNT(*) FROM job_postings").Scan(&data.TotalJobs)
	if err != nil {
		return nil, err
	}

	// Get total companies
	err = db.QueryRow("SELECT COUNT(*) FROM companies").Scan(&data.TotalCompanies)
	if err != nil {
		return nil, err
	}

	// Get total ratings
	err = db.QueryRow("SELECT COUNT(*) FROM job_ratings").Scan(&data.TotalRatings)
	if err != nil {
		return nil, err
	}

	// Get queued jobs
	err = db.QueryRow("SELECT COUNT(*) FROM job_queue WHERE status_code = 1").Scan(&data.QueuedJobs)
	if err != nil {
		return nil, err
	}

	// Get average score
	var avgScore *float64
	err = db.QueryRow("SELECT AVG(overall_score) FROM job_ratings WHERE overall_score IS NOT NULL").Scan(&avgScore)
	if err != nil {
		return nil, err
	}
	data.AvgScore = avgScore

	// Get recent jobs
	rows, err := db.Query(`
		SELECT j.job_id, j.linkedin_job_id, j.title, c.name, j.location, j.apply_url, j.posted_date
		FROM job_postings j
		LEFT JOIN companies c ON j.company_id = c.company_id
		ORDER BY j.created_at DESC
		LIMIT 10
	`)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	for rows.Next() {
		var job WebJob
		err := rows.Scan(&job.JobID, &job.LinkedInID, &job.Title, &job.CompanyName, 
			&job.Location, &job.ApplyURL, &job.PostedDate)
		if err != nil {
			continue
		}
		data.RecentJobs = append(data.RecentJobs, job)
	}

	return data, nil
}

func apiJobsHandler(w http.ResponseWriter, r *http.Request) {
	search := r.URL.Query().Get("search")
	
	query := `
		SELECT 
			j.job_id, j.linkedin_job_id, j.title, c.name, j.location, 
			SUBSTRING(j.description, 1, 200) as description, j.apply_url, 
			j.posted_date, j.applicants, j.work_type, j.skills, j.created_at,
			jr.overall_score, jr.location_score, jr.tech_score, 
			jr.team_size_score, jr.leadership_score, jr.rating_type, jr.rated_at
		FROM job_postings j
		LEFT JOIN companies c ON j.company_id = c.company_id
		LEFT JOIN job_ratings jr ON j.job_id = jr.job_id AND jr.rating_type = 'ai_match'
	`
	
	var args []interface{}
	if search != "" {
		query += " WHERE j.title LIKE ? OR c.name LIKE ? OR j.location LIKE ?"
		searchTerm := "%" + search + "%"
		args = append(args, searchTerm, searchTerm, searchTerm)
	}
	
	query += " ORDER BY j.created_at DESC LIMIT 100"

	rows, err := db.Query(query, args...)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	var jobs []WebJob
	for rows.Next() {
		var job WebJob
		err := rows.Scan(
			&job.JobID, &job.LinkedInID, &job.Title, &job.CompanyName, &job.Location,
			&job.Description, &job.ApplyURL, &job.PostedDate, &job.Applicants,
			&job.WorkType, &job.Skills, &job.CreatedAt,
			&job.OverallScore, &job.LocationScore, &job.TechScore,
			&job.TeamSizeScore, &job.LeadershipScore, &job.RatingType, &job.RatedAt,
		)
		if err != nil {
			continue
		}
		jobs = append(jobs, job)
	}

	w.Header().Set(ContentTypeHeader, ContentTypeJSON)
	json.NewEncoder(w).Encode(jobs)
}

func jobsPageHandler(w http.ResponseWriter, r *http.Request) {
	tmpl := `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobs - LinkedIn Job Scraper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="fas fa-briefcase me-2"></i>LinkedIn Job Scraper</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/"><i class="fas fa-chart-line me-1"></i>Dashboard</a>
                <a class="nav-link active" href="/jobs"><i class="fas fa-briefcase me-1"></i>Jobs</a>
                <a class="nav-link" href="/companies"><i class="fas fa-building me-1"></i>Companies</a>
                <a class="nav-link" href="/queue"><i class="fas fa-list me-1"></i>Queue</a>
                <a class="nav-link" href="/ratings"><i class="fas fa-star me-1"></i>Ratings</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <h1><i class="fas fa-briefcase text-primary me-2"></i>Job Postings</h1>
        
        <div class="card mt-3">
            <div class="card-body">
                <table id="jobsTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Posted</th>
                            <th>Score</th>
                            <th>Tech</th>
                            <th>Location</th>
                            <th>Team Size</th>
                            <th>Leadership</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="jobsTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Job Details Modal -->
    <div class="modal fade" id="jobDetailsModal" tabindex="-1" aria-labelledby="jobDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jobDetailsModalLabel">Job Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="jobDetailsContent">
                    <!-- Job details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a id="modalApplyBtn" href="#" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt"></i> Apply for Job
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#jobsTable').DataTable({
                "ajax": {
                    "url": "/api/jobs",
                    "dataSrc": ""
                },
                "columns": [
                    { "data": "job_id" },
                    { "data": "title" },
                    { "data": "company_name" },
                    { "data": "location" },
                    { 
                        "data": "posted_date",
                        "render": function(data) {
                            return new Date(data).toLocaleDateString();
                        }
                    },
                    { 
                        "data": "overall_score",
                        "render": function(data) {
                            if (data) {
                                let color = data >= 70 ? 'success' : data >= 50 ? 'warning' : 'danger';
                                return '<span class="badge bg-' + color + '">' + data + '</span>';
                            }
                            return '<span class="text-muted">N/A</span>';
                        }
                    },
                    { 
                        "data": "tech_score",
                        "render": function(data) {
                            return data ? data : '<span class="text-muted">N/A</span>';
                        }
                    },
                    { 
                        "data": "location_score",
                        "render": function(data) {
                            return data ? data : '<span class="text-muted">N/A</span>';
                        }
                    },
                    { 
                        "data": "team_size_score",
                        "render": function(data) {
                            return data ? data : '<span class="text-muted">N/A</span>';
                        }
                    },
                    { 
                        "data": "leadership_score",
                        "render": function(data) {
                            return data ? data : '<span class="text-muted">N/A</span>';
                        }
                    },
                    { 
                        "data": null,
                        "render": function(data, type, row) {
                            return '<div class="btn-group" role="group">' +
                                   '<a href="' + row.apply_url + '" target="_blank" class="btn btn-sm btn-primary">' +
                                   '<i class="fas fa-external-link-alt"></i> Apply</a>' +
                                   '<button onclick="showJobDetails(' + row.job_id + ')" class="btn btn-sm btn-info">' +
                                   '<i class="fas fa-info-circle"></i> More Info</button>' +
                                   '</div>';
                        }
                    }
                ],
                "pageLength": 25,
                "order": [[ 0, "desc" ]],
                "responsive": true
            });
        });

        function showJobDetails(jobId) {
            // Show loading state
            $('#jobDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
            $('#jobDetailsModal').modal('show');

            // Fetch job details
            $.get('/api/job?id=' + jobId)
                .done(function(response) {
                    const job = response.job;
                    const criteria = response.criteria;
                    
                    let html = '<div class="row">';
                    
                    // Basic Information
                    html += '<div class="col-md-6">';
                    html += '<h6><i class="fas fa-briefcase text-primary"></i> Basic Information</h6>';
                    html += '<table class="table table-sm">';
                    html += '<tr><td><strong>Job ID:</strong></td><td>' + job.job_id + '</td></tr>';
                    html += '<tr><td><strong>LinkedIn ID:</strong></td><td>' + job.linkedin_job_id + '</td></tr>';
                    html += '<tr><td><strong>Title:</strong></td><td>' + job.title + '</td></tr>';
                    html += '<tr><td><strong>Company:</strong></td><td>' + job.company_name + '</td></tr>';
                    html += '<tr><td><strong>Location:</strong></td><td>' + job.location + '</td></tr>';
                    html += '<tr><td><strong>Posted Date:</strong></td><td>' + new Date(job.posted_date).toLocaleDateString() + '</td></tr>';
                    if (job.applicants) html += '<tr><td><strong>Applicants:</strong></td><td>' + job.applicants + '</td></tr>';
                    if (job.work_type) html += '<tr><td><strong>Work Type:</strong></td><td>' + job.work_type + '</td></tr>';
                    html += '</table>';
                    html += '</div>';
                    
                    // AI Scores
                    html += '<div class="col-md-6">';
                    html += '<h6><i class="fas fa-star text-warning"></i> AI Match Scores</h6>';
                    html += '<table class="table table-sm">';
                    if (job.overall_score) {
                        let color = job.overall_score >= 70 ? 'success' : job.overall_score >= 50 ? 'warning' : 'danger';
                        html += '<tr><td><strong>Overall Score:</strong></td><td><span class="badge bg-' + color + '">' + job.overall_score + '/100</span></td></tr>';
                    }
                    if (job.location_score) html += '<tr><td><strong>Location Score:</strong></td><td>' + job.location_score + '/100</td></tr>';
                    if (job.tech_score) html += '<tr><td><strong>Tech Score:</strong></td><td>' + job.tech_score + '/100</td></tr>';
                    if (job.team_size_score) html += '<tr><td><strong>Team Size Score:</strong></td><td>' + job.team_size_score + '/100</td></tr>';
                    if (job.leadership_score) html += '<tr><td><strong>Leadership Score:</strong></td><td>' + job.leadership_score + '/100</td></tr>';
                    if (job.rated_at) html += '<tr><td><strong>Rated At:</strong></td><td>' + new Date(job.rated_at).toLocaleString() + '</td></tr>';
                    if (!job.overall_score) html += '<tr><td colspan="2"><span class="text-muted">No AI scores available</span></td></tr>';
                    html += '</table>';
                    html += '</div>';
                    
                    html += '</div>';
                    
                    // Skills
                    if (job.skills) {
                        html += '<div class="mt-3">';
                        html += '<h6><i class="fas fa-cogs text-info"></i> Skills</h6>';
                        html += '<p class="bg-light p-2 rounded">' + job.skills + '</p>';
                        html += '</div>';
                    }
                    
                    // Full Description
                    html += '<div class="mt-3">';
                    html += '<h6><i class="fas fa-file-text text-success"></i> Full Job Description</h6>';
                    html += '<div class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;">';
                    html += '<pre style="white-space: pre-wrap; font-family: inherit;">' + job.description + '</pre>';
                    html += '</div>';
                    html += '</div>';

                    // AI Criteria (if available)
                    if (criteria) {
                        try {
                            const criteriaObj = JSON.parse(criteria);
                            html += '<div class="mt-3">';
                            html += '<h6><i class="fas fa-brain text-warning"></i> AI Analysis Criteria</h6>';
                            html += '<div class="row">';
                            
                            if (criteriaObj.location) {
                                html += '<div class="col-md-6 mb-2">';
                                html += '<strong>Location Analysis:</strong><br>';
                                html += '<small class="text-muted">' + criteriaObj.location + '</small>';
                                html += '</div>';
                            }
                            
                            if (criteriaObj.tech_match) {
                                html += '<div class="col-md-6 mb-2">';
                                html += '<strong>Tech Match Analysis:</strong><br>';
                                html += '<small class="text-muted">' + criteriaObj.tech_match + '</small>';
                                html += '</div>';
                            }
                            
                            if (criteriaObj.team_size) {
                                html += '<div class="col-md-6 mb-2">';
                                html += '<strong>Team Size Analysis:</strong><br>';
                                html += '<small class="text-muted">' + criteriaObj.team_size + '</small>';
                                html += '</div>';
                            }
                            
                            if (criteriaObj.leadership_fit) {
                                html += '<div class="col-md-6 mb-2">';
                                html += '<strong>Leadership Fit Analysis:</strong><br>';
                                html += '<small class="text-muted">' + criteriaObj.leadership_fit + '</small>';
                                html += '</div>';
                            }
                            
                            html += '</div>';
                            html += '</div>';
                        } catch (e) {
                            // Ignore JSON parse errors
                        }
                    }
                    
                    $('#jobDetailsContent').html(html);
                    $('#jobDetailsModalLabel').text(job.title + ' at ' + job.company_name);
                    $('#modalApplyBtn').attr('href', job.apply_url);
                })
                .fail(function() {
                    $('#jobDetailsContent').html('<div class="alert alert-danger">Failed to load job details.</div>');
                });
        }
    </script>
</body>
</html>`

	w.Header().Set(ContentTypeHeader, ContentTypeHTML)
	w.Write([]byte(tmpl))
}

func apiCompaniesHandler(w http.ResponseWriter, r *http.Request) {
	search := r.URL.Query().Get("search")
	
	query := `
		SELECT c.company_id, c.name, COUNT(j.job_id) as job_count
		FROM companies c
		LEFT JOIN job_postings j ON c.company_id = j.company_id
	`
	
	var args []interface{}
	if search != "" {
		query += " WHERE c.name LIKE ?"
		args = append(args, "%"+search+"%")
	}
	
	query += " GROUP BY c.company_id, c.name ORDER BY job_count DESC, c.name ASC"

	rows, err := db.Query(query, args...)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	var companies []WebCompany
	for rows.Next() {
		var company WebCompany
		err := rows.Scan(&company.CompanyID, &company.Name, &company.JobCount)
		if err != nil {
			continue
		}
		companies = append(companies, company)
	}

	w.Header().Set(ContentTypeHeader, ContentTypeJSON)
	json.NewEncoder(w).Encode(companies)
}

func companiesPageHandler(w http.ResponseWriter, r *http.Request) {
	tmpl := `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Companies - LinkedIn Job Scraper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="fas fa-briefcase me-2"></i>LinkedIn Job Scraper</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/"><i class="fas fa-chart-line me-1"></i>Dashboard</a>
                <a class="nav-link" href="/jobs"><i class="fas fa-briefcase me-1"></i>Jobs</a>
                <a class="nav-link active" href="/companies"><i class="fas fa-building me-1"></i>Companies</a>
                <a class="nav-link" href="/queue"><i class="fas fa-list me-1"></i>Queue</a>
                <a class="nav-link" href="/ratings"><i class="fas fa-star me-1"></i>Ratings</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <h1><i class="fas fa-building text-primary me-2"></i>Companies</h1>
        
        <div class="card mt-3">
            <div class="card-body">
                <table id="companiesTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Company Name</th>
                            <th>Job Count</th>
                        </tr>
                    </thead>
                    <tbody id="companiesTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#companiesTable').DataTable({
                "ajax": {
                    "url": "/api/companies",
                    "dataSrc": ""
                },
                "columns": [
                    { "data": "company_id" },
                    { "data": "name" },
                    { 
                        "data": "job_count",
                        "render": function(data) {
                            return '<span class="badge bg-primary">' + data + '</span>';
                        }
                    }
                ],
                "pageLength": 25,
                "order": [[ 2, "desc" ]],
                "responsive": true
            });
        });
    </script>
</body>
</html>`

	w.Header().Set(ContentTypeHeader, ContentTypeHTML)
	w.Write([]byte(tmpl))
}

func apiQueueHandler(w http.ResponseWriter, r *http.Request) {
	query := `
		SELECT 
			q.queue_id, q.job_id, j.title, c.name, q.queued_at, q.status_code,
			CASE 
				WHEN q.status_code = 1 THEN 'Queued'
				WHEN q.status_code = 2 THEN 'Processing'
				WHEN q.status_code = 3 THEN 'Completed'
				WHEN q.status_code = 4 THEN 'Failed'
				ELSE 'Unknown'
			END as status_text
		FROM job_queue q
		LEFT JOIN job_postings j ON q.job_id = j.job_id
		LEFT JOIN companies c ON j.company_id = c.company_id
		ORDER BY q.queued_at DESC
		LIMIT 500
	`

	rows, err := db.Query(query)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	var queueItems []WebQueue
	for rows.Next() {
		var item WebQueue
		err := rows.Scan(
			&item.QueueID, &item.JobID, &item.JobTitle, &item.Company,
			&item.QueuedAt, &item.StatusCode, &item.StatusText,
		)
		if err != nil {
			continue
		}
		queueItems = append(queueItems, item)
	}

	w.Header().Set(ContentTypeHeader, ContentTypeJSON)
	json.NewEncoder(w).Encode(queueItems)
}

func queuePageHandler(w http.ResponseWriter, r *http.Request) {
	tmpl := `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue - LinkedIn Job Scraper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="fas fa-briefcase me-2"></i>LinkedIn Job Scraper</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/"><i class="fas fa-chart-line me-1"></i>Dashboard</a>
                <a class="nav-link" href="/jobs"><i class="fas fa-briefcase me-1"></i>Jobs</a>
                <a class="nav-link" href="/companies"><i class="fas fa-building me-1"></i>Companies</a>
                <a class="nav-link active" href="/queue"><i class="fas fa-list me-1"></i>Queue</a>
                <a class="nav-link" href="/ratings"><i class="fas fa-star me-1"></i>Ratings</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <h1><i class="fas fa-list text-primary me-2"></i>Job Queue</h1>
        
        <div class="card mt-3">
            <div class="card-body">
                <table id="queueTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Queue ID</th>
                            <th>Job ID</th>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Queued At</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="queueTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#queueTable').DataTable({
                "ajax": {
                    "url": "/api/queue",
                    "dataSrc": ""
                },
                "columns": [
                    { "data": "queue_id" },
                    { "data": "job_id" },
                    { "data": "job_title" },
                    { "data": "company" },
                    { 
                        "data": "queued_at",
                        "render": function(data) {
                            return new Date(data).toLocaleString();
                        }
                    },
                    { 
                        "data": "status_text",
                        "render": function(data, type, row) {
                            let color = 'secondary';
                            switch(row.status_code) {
                                case 1: color = 'primary'; break;  // Queued
                                case 2: color = 'warning'; break;  // Processing
                                case 3: color = 'success'; break;  // Completed
                                case 4: color = 'danger'; break;   // Failed
                            }
                            return '<span class="badge bg-' + color + '">' + data + '</span>';
                        }
                    }
                ],
                "pageLength": 25,
                "order": [[ 4, "desc" ]],
                "responsive": true
            });
        });
    </script>
</body>
</html>`

	w.Header().Set(ContentTypeHeader, ContentTypeHTML)
	w.Write([]byte(tmpl))
}

func apiRatingsHandler(w http.ResponseWriter, r *http.Request) {
	query := `
		SELECT 
			jr.rating_id, jr.job_id, j.title, c.name,
			jr.overall_score, jr.location_score, jr.tech_score,
			jr.team_size_score, jr.leadership_score, jr.rating_type,
			jr.rated_at, jr.criteria
		FROM job_ratings jr
		LEFT JOIN job_postings j ON jr.job_id = j.job_id
		LEFT JOIN companies c ON j.company_id = c.company_id
		ORDER BY jr.rated_at DESC
		LIMIT 500
	`

	rows, err := db.Query(query)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	var ratings []WebRating
	for rows.Next() {
		var rating WebRating
		err := rows.Scan(
			&rating.RatingID, &rating.JobID, &rating.JobTitle, &rating.Company,
			&rating.OverallScore, &rating.LocationScore, &rating.TechScore,
			&rating.TeamSizeScore, &rating.LeadershipScore, &rating.RatingType,
			&rating.RatedAt, &rating.Criteria,
		)
		if err != nil {
			continue
		}
		ratings = append(ratings, rating)
	}

	w.Header().Set(ContentTypeHeader, ContentTypeJSON)
	json.NewEncoder(w).Encode(ratings)
}

func ratingsPageHandler(w http.ResponseWriter, r *http.Request) {
	tmpl := `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ratings - LinkedIn Job Scraper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="fas fa-briefcase me-2"></i>LinkedIn Job Scraper</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/"><i class="fas fa-chart-line me-1"></i>Dashboard</a>
                <a class="nav-link" href="/jobs"><i class="fas fa-briefcase me-1"></i>Jobs</a>
                <a class="nav-link" href="/companies"><i class="fas fa-building me-1"></i>Companies</a>
                <a class="nav-link" href="/queue"><i class="fas fa-list me-1"></i>Queue</a>
                <a class="nav-link active" href="/ratings"><i class="fas fa-star me-1"></i>Ratings</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <h1><i class="fas fa-star text-primary me-2"></i>AI Job Ratings</h1>
        
        <div class="card mt-3">
            <div class="card-body">
                <table id="ratingsTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Rating ID</th>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Overall</th>
                            <th>Tech</th>
                            <th>Location</th>
                            <th>Team Size</th>
                            <th>Leadership</th>
                            <th>Type</th>
                            <th>Rated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ratingsTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Job Details Modal (reused from jobs page) -->
    <div class="modal fade" id="jobDetailsModal" tabindex="-1" aria-labelledby="jobDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jobDetailsModalLabel">Job Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="jobDetailsContent">
                    <!-- Job details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a id="modalApplyBtn" href="#" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt"></i> Apply for Job
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#ratingsTable').DataTable({
                "ajax": {
                    "url": "/api/ratings",
                    "dataSrc": ""
                },
                "columns": [
                    { "data": "rating_id" },
                    { "data": "job_title" },
                    { "data": "company" },
                    { 
                        "data": "overall_score",
                        "render": function(data) {
                            if (data) {
                                let color = data >= 70 ? 'success' : data >= 50 ? 'warning' : 'danger';
                                return '<span class="badge bg-' + color + '">' + data + '</span>';
                            }
                            return '<span class="text-muted">N/A</span>';
                        }
                    },
                    { 
                        "data": "tech_score",
                        "render": function(data) {
                            if (data) {
                                let color = data >= 70 ? 'success' : data >= 50 ? 'warning' : 'danger';
                                return '<span class="badge bg-' + color + '">' + data + '</span>';
                            }
                            return '<span class="text-muted">N/A</span>';
                        }
                    },
                    { 
                        "data": "location_score",
                        "render": function(data) {
                            if (data) {
                                let color = data >= 70 ? 'success' : data >= 50 ? 'warning' : 'danger';
                                return '<span class="badge bg-' + color + '">' + data + '</span>';
                            }
                            return '<span class="text-muted">N/A</span>';
                        }
                    },
                    { 
                        "data": "team_size_score",
                        "render": function(data) {
                            if (data) {
                                let color = data >= 70 ? 'success' : data >= 50 ? 'warning' : 'danger';
                                return '<span class="badge bg-' + color + '">' + data + '</span>';
                            }
                            return '<span class="text-muted">N/A</span>';
                        }
                    },
                    { 
                        "data": "leadership_score",
                        "render": function(data) {
                            if (data) {
                                let color = data >= 70 ? 'success' : data >= 50 ? 'warning' : 'danger';
                                return '<span class="badge bg-' + color + '">' + data + '</span>';
                            }
                            return '<span class="text-muted">N/A</span>';
                        }
                    },
                    { "data": "rating_type" },
                    { 
                        "data": "rated_at",
                        "render": function(data) {
                            return new Date(data).toLocaleString();
                        }
                    },
                    { 
                        "data": null,
                        "render": function(data, type, row) {
                            return '<button onclick="showJobDetails(' + row.job_id + ')" class="btn btn-sm btn-info">' +
                                   '<i class="fas fa-info-circle"></i> Job Details</button>';
                        }
                    }
                ],
                "pageLength": 25,
                "order": [[ 9, "desc" ]],
                "responsive": true
            });
        });

        // Reuse the same showJobDetails function from jobs page
        function showJobDetails(jobId) {
            // Show loading state
            $('#jobDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
            $('#jobDetailsModal').modal('show');

            // Fetch job details
            $.get('/api/job?id=' + jobId)
                .done(function(response) {
                    const job = response.job;
                    const criteria = response.criteria;
                    
                    let html = '<div class="row">';
                    
                    // Basic Information
                    html += '<div class="col-md-6">';
                    html += '<h6><i class="fas fa-briefcase text-primary"></i> Basic Information</h6>';
                    html += '<table class="table table-sm">';
                    html += '<tr><td><strong>Job ID:</strong></td><td>' + job.job_id + '</td></tr>';
                    html += '<tr><td><strong>LinkedIn ID:</strong></td><td>' + job.linkedin_job_id + '</td></tr>';
                    html += '<tr><td><strong>Title:</strong></td><td>' + job.title + '</td></tr>';
                    html += '<tr><td><strong>Company:</strong></td><td>' + job.company_name + '</td></tr>';
                    html += '<tr><td><strong>Location:</strong></td><td>' + job.location + '</td></tr>';
                    html += '<tr><td><strong>Posted Date:</strong></td><td>' + new Date(job.posted_date).toLocaleDateString() + '</td></tr>';
                    if (job.applicants) html += '<tr><td><strong>Applicants:</strong></td><td>' + job.applicants + '</td></tr>';
                    if (job.work_type) html += '<tr><td><strong>Work Type:</strong></td><td>' + job.work_type + '</td></tr>';
                    html += '</table>';
                    html += '</div>';
                    
                    // AI Scores
                    html += '<div class="col-md-6">';
                    html += '<h6><i class="fas fa-star text-warning"></i> AI Match Scores</h6>';
                    html += '<table class="table table-sm">';
                    if (job.overall_score) {
                        let color = job.overall_score >= 70 ? 'success' : job.overall_score >= 50 ? 'warning' : 'danger';
                        html += '<tr><td><strong>Overall Score:</strong></td><td><span class="badge bg-' + color + '">' + job.overall_score + '/100</span></td></tr>';
                    }
                    if (job.location_score) html += '<tr><td><strong>Location Score:</strong></td><td>' + job.location_score + '/100</td></tr>';
                    if (job.tech_score) html += '<tr><td><strong>Tech Score:</strong></td><td>' + job.tech_score + '/100</td></tr>';
                    if (job.team_size_score) html += '<tr><td><strong>Team Size Score:</strong></td><td>' + job.team_size_score + '/100</td></tr>';
                    if (job.leadership_score) html += '<tr><td><strong>Leadership Score:</strong></td><td>' + job.leadership_score + '/100</td></tr>';
                    if (job.rated_at) html += '<tr><td><strong>Rated At:</strong></td><td>' + new Date(job.rated_at).toLocaleString() + '</td></tr>';
                    if (!job.overall_score) html += '<tr><td colspan="2"><span class="text-muted">No AI scores available</span></td></tr>';
                    html += '</table>';
                    html += '</div>';
                    
                    html += '</div>';
                    
                    // Skills
                    if (job.skills) {
                        html += '<div class="mt-3">';
                        html += '<h6><i class="fas fa-cogs text-info"></i> Skills</h6>';
                        html += '<p class="bg-light p-2 rounded">' + job.skills + '</p>';
                        html += '</div>';
                    }
                    
                    // Full Description
                    html += '<div class="mt-3">';
                    html += '<h6><i class="fas fa-file-text text-success"></i> Full Job Description</h6>';
                    html += '<div class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;">';
                    html += '<pre style="white-space: pre-wrap; font-family: inherit;">' + job.description + '</pre>';
                    html += '</div>';
                    html += '</div>';

                    // AI Criteria (if available)
                    if (criteria) {
                        try {
                            const criteriaObj = JSON.parse(criteria);
                            html += '<div class="mt-3">';
                            html += '<h6><i class="fas fa-brain text-warning"></i> AI Analysis Criteria</h6>';
                            html += '<div class="row">';
                            
                            if (criteriaObj.location) {
                                html += '<div class="col-md-6 mb-2">';
                                html += '<strong>Location Analysis:</strong><br>';
                                html += '<small class="text-muted">' + criteriaObj.location + '</small>';
                                html += '</div>';
                            }
                            
                            if (criteriaObj.tech_match) {
                                html += '<div class="col-md-6 mb-2">';
                                html += '<strong>Tech Match Analysis:</strong><br>';
                                html += '<small class="text-muted">' + criteriaObj.tech_match + '</small>';
                                html += '</div>';
                            }
                            
                            if (criteriaObj.team_size) {
                                html += '<div class="col-md-6 mb-2">';
                                html += '<strong>Team Size Analysis:</strong><br>';
                                html += '<small class="text-muted">' + criteriaObj.team_size + '</small>';
                                html += '</div>';
                            }
                            
                            if (criteriaObj.leadership_fit) {
                                html += '<div class="col-md-6 mb-2">';
                                html += '<strong>Leadership Fit Analysis:</strong><br>';
                                html += '<small class="text-muted">' + criteriaObj.leadership_fit + '</small>';
                                html += '</div>';
                            }
                            
                            html += '</div>';
                            html += '</div>';
                        } catch (e) {
                            // Ignore JSON parse errors
                        }
                    }
                    
                    $('#jobDetailsContent').html(html);
                    $('#jobDetailsModalLabel').text(job.title + ' at ' + job.company_name);
                    $('#modalApplyBtn').attr('href', job.apply_url);
                })
                .fail(function() {
                    $('#jobDetailsContent').html('<div class="alert alert-danger">Failed to load job details.</div>');
                });
        }
    </script>
</body>
</html>`

	w.Header().Set(ContentTypeHeader, ContentTypeHTML)
	w.Write([]byte(tmpl))
}

func apiJobDetailHandler(w http.ResponseWriter, r *http.Request) {
	jobID := r.URL.Query().Get("id")
	if jobID == "" {
		http.Error(w, "Job ID is required", http.StatusBadRequest)
		return
	}

	query := `
		SELECT 
			j.job_id, j.linkedin_job_id, j.title, c.name, j.location, 
			j.description, j.apply_url, j.posted_date, j.applicants, 
			j.work_type, j.skills, j.created_at,
			jr.overall_score, jr.location_score, jr.tech_score, 
			jr.team_size_score, jr.leadership_score, jr.rating_type, 
			jr.rated_at, jr.criteria
		FROM job_postings j
		LEFT JOIN companies c ON j.company_id = c.company_id
		LEFT JOIN job_ratings jr ON j.job_id = jr.job_id AND jr.rating_type = 'ai_match'
		WHERE j.job_id = ?
	`

	row := db.QueryRow(query, jobID)
	
	var job WebJob
	var criteria *string
	err := row.Scan(
		&job.JobID, &job.LinkedInID, &job.Title, &job.CompanyName, &job.Location,
		&job.Description, &job.ApplyURL, &job.PostedDate, &job.Applicants,
		&job.WorkType, &job.Skills, &job.CreatedAt,
		&job.OverallScore, &job.LocationScore, &job.TechScore,
		&job.TeamSizeScore, &job.LeadershipScore, &job.RatingType, 
		&job.RatedAt, &criteria,
	)
	if err != nil {
		http.Error(w, "Job not found", http.StatusNotFound)
		return
	}

	// Create response with additional details
	response := map[string]interface{}{
		"job": job,
		"criteria": criteria,
	}

	w.Header().Set(ContentTypeHeader, ContentTypeJSON)
	json.NewEncoder(w).Encode(response)
}

func healthHandler(w http.ResponseWriter, r *http.Request) {
	w.Header().Set(ContentTypeHeader, ContentTypeJSON)
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{
		"status": "healthy",
		"timestamp": time.Now().UTC().Format(time.RFC3339),
	})
}

// Additional handler implementations would continue here...
