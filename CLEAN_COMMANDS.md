# LinkedIn Job Scraper - Clean Commands Reference

## 🚀 Essential Commands (Most Used)

### Quick Start
```bash
make setup          # Full setup for new users
make dev            # Setup + start services + migrate (one command setup)
```

### Daily Usage
```bash
make start          # Start all services (MySQL, phpMyAdmin, Web Dashboard)
make scrape         # Scrape new jobs
make match-jobs     # Find your best job matches (MAIN FEATURE)
make show-jobs      # View recent jobs
make stop           # Stop services when done
```

### Job Matching (AI-Powered)
```bash
make match-jobs            # Smart job matching (uses config if available)
make match-jobs-config     # Create custom configuration file
make match-jobs-verbose    # Detailed reasoning for matches
make match-jobs-all        # Process ALL jobs (full database)
```

## 🗄️ Database Management

```bash
make db-status      # Show statistics (jobs, companies, etc.)
make db-shell       # Open MySQL shell
make backup         # Create database backup
make restore        # Restore from backup
make reset          # ⚠️ Delete all data and start fresh
```

## 🤖 AI Features

```bash
make extract-addresses  # Extract standardized addresses
make analyze-data      # Analyze data quality and coverage
```

## 🌐 Web Interfaces

After `make start`, access these URLs:
- **Web Dashboard**: http://localhost:8081 (job browsing interface)
- **phpMyAdmin**: http://localhost:8080 (database management)
- **MySQL**: localhost:3307 (direct database access)

## 🔧 Development

```bash
make build          # Build the application
make test           # Run tests
make clean          # Clean build artifacts
make logs           # Show application logs
```

## What Was Removed?

The Makefile has been cleaned up by removing:
- ❌ Redundant scraping modes (headless, visible, debug) - use environment variables instead
- ❌ Old job matching versions (v1, v2, v3) - consolidated into one optimized version
- ❌ Complex queue management commands - simplified to essential functions
- ❌ Docker management duplication - use `make start/stop/restart`
- ❌ Granular extract-addresses variations - use command line flags instead

## Environment Variables (Advanced)

For advanced scraping options, use environment variables:
```bash
# Headless scraping
HEADLESS_BROWSER=true make scrape

# Visible browser with debug
LOG_LEVEL=debug HEADLESS_BROWSER=false make scrape

# Custom scraping parameters
./linkedin-scraper scrape --keywords "python" --location "Stockholm" --total-jobs 100
```

## Configuration Files

- `job_match_config.json` - Custom job matching preferences (created with `make match-jobs-config`)
- `.env` - Environment variables (OpenAI API key, etc.)
- `docker-compose.yml` - Service configuration

## Quick Workflow Example

```bash
# 1. First time setup
make setup

# 2. Start services  
make start

# 3. Scrape some jobs
make scrape

# 4. Create custom matching profile
make match-jobs-config
# Edit job_match_config.json with your preferences

# 5. Find your best matches
make match-jobs

# 6. View results in web dashboard
# Open http://localhost:8081

# 7. Stop when done
make stop
```

The cleaned up Makefile focuses on the essential commands you actually use, making the system much easier to navigate and understand.
