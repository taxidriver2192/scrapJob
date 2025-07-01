# Quick Start Guide

Get the LinkedIn Job Scraper up and running in 5 minutes.

## Prerequisites Check

Before starting, verify you have these installed:

```bash
# Check Docker
docker --version
docker-compose --version

# Check Node.js
node --version
npm --version

# Check Go
go version
```

If any are missing, install them first:
- **Docker**: https://www.docker.com/products/docker-desktop
- **Node.js**: https://nodejs.org/ (v18 or later)
- **Go**: https://golang.org/dl/ (v1.21 or later)

## Setup Process

### 1. Clone and Setup

```bash
git clone <repository-url>
cd linkedin-job-scraper
chmod +x setup.sh
./setup.sh
```

The setup script will:
- Install TypeScript and dependencies
- Compile TypeScript to JavaScript
- Build the Go application
- Start Docker services
- Run database migrations

### 2. Configure Credentials

Edit the `.env` file:

```bash
# Required for scraping
LINKEDIN_EMAIL=your-email@example.com
LINKEDIN_PASSWORD=your-password

# Required for AI features
OPENAI_API_KEY=sk-your-openai-api-key

# Database settings (auto-configured)
DB_HOST=127.0.0.1
DB_PORT=3307
DB_USER=root
DB_PASSWORD=
DB_NAME=linkedin_jobs
```

### 3. Verify Services

Check that Docker services are running:

```bash
docker-compose ps
```

You should see:
- linkedin-web-dashboard (Up, healthy)
- linkedin-mysql (Up, healthy)  
- linkedin-phpmyadmin (Up)

Access these URLs:
- Web Dashboard: http://localhost:8081
- phpMyAdmin: http://localhost:8080

### 4. Run Your First Scrape

```bash
# Basic scraping (headless)
./linkedin-scraper scrape --keywords "software engineer" --location "Copenhagen" --total-jobs 25

# With visible browser (for first-time setup)
HEADLESS_BROWSER=false ./linkedin-scraper scrape --keywords "python developer" --location "remote" --total-jobs 10
```

### 5. View Results

Open the web dashboard at http://localhost:8081 to see your scraped jobs.

## Common Issues

### LinkedIn Verification

If LinkedIn asks for email verification:
1. The scraper will pause and ask for a verification code
2. Check your email for the LinkedIn verification code
3. Enter the code in the terminal
4. Scraping will continue automatically

### Permission Errors

On Linux/Mac, you might need to make the setup script executable:

```bash
chmod +x setup.sh
```

### Docker Issues

If Docker services fail to start:

```bash
# Stop and restart
docker-compose down
docker-compose up -d

# Check logs
docker-compose logs
```

### Build Errors

If the Go build fails:

```bash
# Clean and rebuild
make clean
go mod tidy
go build -o linkedin-scraper cmd/main.go
```

## What's Next

After your first successful scrape:

1. **Explore the Web Dashboard** at http://localhost:8081
2. **Run AI job matching**: `./linkedin-scraper match-jobs`
3. **Set up regular scraping** with different keywords and locations
4. **Use the queue system** for batch AI processing
5. **Create database backups**: `make backup`

## Daily Usage

```bash
# Start services (if not running)
docker-compose up -d

# Scrape jobs
./linkedin-scraper scrape --keywords "your-skills" --location "your-city" --total-jobs 50

# Process with AI
./linkedin-scraper match-jobs

# View in dashboard
open http://localhost:8081
```

## Getting Help

- Check the main README.md for detailed documentation
- Use `./linkedin-scraper help` for command-line options
- Use `make help` for available build commands
- Check logs: `make logs` or `docker-compose logs`

## Security Reminder

- Never commit your `.env` file with real credentials
- Use a strong, unique password for LinkedIn
- Keep your OpenAI API key secure
- Respect LinkedIn's terms of service and rate limits
