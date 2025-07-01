# LinkedIn Job Scraper

A comprehensive LinkedIn job scraping tool with AI-powered job matching, built with Go, TypeScript, and Docker.

## Architecture

- **Local**: Web scraping with Chrome automation, AI processing, queue management
- **Docker**: MySQL database, phpMyAdmin, web dashboard

## Features

- **Smart Job Scraping**: Automated LinkedIn job search with pagination
- **AI-Powered Matching**: OpenAI integration for intelligent job recommendations
- **Web Dashboard**: Modern interface for viewing jobs, companies, and ratings
- **Queue System**: Batch processing for AI analysis
- **Database Management**: MySQL with automated migrations and backups
- **Deduplication**: Automatic removal of duplicate job postings

## Prerequisites

Before running the setup script, ensure you have:

- **Docker & Docker Compose**: For database and web dashboard
- **Node.js (18+)**: For TypeScript compilation  
- **Go (1.21+)**: For building the application
- **LinkedIn Account**: For job scraping
- **OpenAI API Key**: For AI-powered features

## Quick Start

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd linkedin-job-scraper
   ```

2. **Run the setup script**
   ```bash
   chmod +x setup.sh
   ./setup.sh
   ```

   The setup script will:
   - Check all prerequisites
   - Install TypeScript dependencies
   - Compile TypeScript to JavaScript
   - Build the Go application
   - Start Docker services (MySQL, phpMyAdmin, Web Dashboard)
   - Run database migrations

3. **Configure credentials**
   
   Edit the `.env` file with your credentials:
   ```bash
   LINKEDIN_EMAIL=your-email@example.com
   LINKEDIN_PASSWORD=your-password
   OPENAI_API_KEY=your-openai-api-key
   ```

4. **Start scraping**
   ```bash
   ./linkedin-scraper scrape --keywords "software engineer" --location "Copenhagen" --total-jobs 50
   ```

## Usage

### Scraping Jobs

```bash
# Basic scraping
./linkedin-scraper scrape --keywords "php developer" --location "Copenhagen" --total-jobs 100

# Visible browser (for debugging)
HEADLESS_BROWSER=false ./linkedin-scraper scrape --keywords "python" --location "remote" --total-jobs 50

# Debug mode
LOG_LEVEL=debug HEADLESS_BROWSER=false ./linkedin-scraper scrape --keywords "react" --location "Berlin" --total-jobs 25
```

### AI Processing

```bash
# Find best job matches
./linkedin-scraper match-jobs --limit 0 --min-score 70

# Extract addresses from job descriptions
./linkedin-scraper extract-addresses --limit 10

# Queue management
./linkedin-scraper queue --action enqueue --limit 50
./linkedin-scraper queue --action status
```

### Web Dashboard

Access the web dashboard at `http://localhost:8081` to:
- Browse all scraped jobs
- View company information
- See AI match ratings
- Filter and search results
- View detailed job analysis

### Database Management

```bash
# Create backup
make backup

# Restore from backup
make restore

# Database statistics
make db-status

# Reset database
make reset

# Direct MySQL access
make db-shell
```

## Services

After running setup, the following services are available:

- **Web Dashboard**: http://localhost:8081
- **phpMyAdmin**: http://localhost:8080 
- **MySQL**: localhost:3307

## Configuration

### Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| `LINKEDIN_EMAIL` | LinkedIn account email | Yes |
| `LINKEDIN_PASSWORD` | LinkedIn account password | Yes |
| `OPENAI_API_KEY` | OpenAI API key for AI features | Yes |
| `DB_HOST` | Database host | Auto-configured |
| `DB_PORT` | Database port | Auto-configured |
| `DB_USER` | Database user | Auto-configured |
| `DB_PASSWORD` | Database password | Auto-configured |
| `HEADLESS_BROWSER` | Run browser in headless mode | Optional |
| `LOG_LEVEL` | Logging level (debug, info, warn, error) | Optional |

### Scraping Parameters

- `--keywords`: Job search keywords (required)
- `--location`: Job location (required)
- `--total-jobs`: Number of jobs to scrape (default: 50)

## Development

### Building

```bash
# Build Go application
make build

# Compile TypeScript
make build-ts

# Build everything
make setup
```

### Testing

```bash
# Run Go tests
make test

# View logs
make logs

# Clean build artifacts
make clean
```

### Docker Management

```bash
# Start services
make start

# Stop services  
make stop

# Restart services
make restart

# View web dashboard logs
make web-dashboard-logs
```

## Project Structure

```
├── cmd/                    # Go applications
│   ├── main.go            # Main scraper application
│   ├── web-dashboard/     # Web dashboard server
│   ├── match-jobs/        # AI job matching
│   └── queue-manager/     # Queue management
├── internal/              # Internal Go packages
│   ├── scraper/           # Core scraping logic
│   ├── models/            # Data models
│   ├── database/          # Database operations
│   └── config/            # Configuration management
├── scripts/               # Database scripts
├── logs/                  # Application logs
├── backups/               # Database backups
├── chrome-profile/        # Chrome browser profile
├── docker-compose.yml     # Docker services
├── Dockerfile             # Web dashboard container
├── setup.sh              # Setup script
└── Makefile              # Build automation
```

## Troubleshooting

### LinkedIn Authentication

If LinkedIn requires verification:
1. The scraper will prompt for a verification code
2. Check your email for the code
3. Enter the code in the terminal
4. The scraper will continue automatically

### Database Connection Issues

```bash
# Check if services are running
docker-compose ps

# View MySQL logs
docker-compose logs mysql

# Restart services
make restart
```

### Build Issues

```bash
# Clean and rebuild
make clean
make setup
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## Security Notes

- Store credentials securely in the `.env` file
- Never commit the `.env` file to version control
- Use strong passwords for LinkedIn account
- Respect LinkedIn's rate limits and terms of service
- Keep your OpenAI API key secure

## License

This project is intended for educational and personal use only. Please respect LinkedIn's Terms of Service and robots.txt when using this tool.
