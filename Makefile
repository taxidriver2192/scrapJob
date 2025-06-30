.PHONY: help setup build start stop migrate reset db-status db-shell scrape show-jobs test clean logs dev

# Default target
help:
	@echo "🚀 LinkedIn Job Scraper - Available Commands"
	@echo ""
	@echo "Setup & Build:"
	@echo "  make setup     - Install dependencies and build application"
	@echo "  make setup-ts  - Setup TypeScript environment"
	@echo "  make build     - Build the Go application"
	@echo "  make build-ts  - Compile TypeScript scripts to JavaScript"
	@echo ""
	@echo "Docker Services:"
	@echo "  make start     - Start MySQL + phpMyAdmin (docker-compose up -d)"
	@echo "  make stop      - Stop all services (docker-compose down)"
	@echo ""
	@echo "Database:"
	@echo "  make migrate   - Run database migrations"
	@echo "  make reset     - Reset database (delete all data)"
	@echo "  make db-status - Show database statistics"
	@echo "  make db-shell  - Open MySQL shell"
	@echo ""
	@echo "Scraping:"
	@echo "  make scrape         - Start scraping (with default params: 1 page, 5 jobs per page)"
	@echo "  make scrape-headless - Start headless scraping (no browser window)"  
	@echo "  make scrape-visible  - Start visible scraping (with browser window)"
	@echo "  make scrape-debug    - Start debug scraping (visible + debug logs)"
	@echo "  make show-jobs      - Show recent scraped jobs"
	@echo ""
	@echo "Testing:"
	@echo "  make test      - Run Go tests"
	@echo ""
	@echo "Utilities:"
	@echo "  make clean     - Clean build artifacts"
	@echo "  make logs      - Show application logs"
	@echo ""
	@echo "Development:"
	@echo "  make dev       - Setup everything and start development environment"

# Setup and build
setup:
	@echo "🔧 Setting up LinkedIn Job Scraper..."
	go mod tidy
	mkdir -p logs chrome-profile
	go build -o linkedin-scraper cmd/main.go
	@echo "✅ Setup complete!"

build:
	@echo "🔨 Building application..."
	@make build-ts
	go build -o linkedin-scraper cmd/main.go

# TypeScript compilation
build-ts:
	@echo "📝 Compiling TypeScript scripts..."
	@if command -v tsc >/dev/null 2>&1; then \
		npm run compile-scripts; \
	else \
		echo "⚠️  TypeScript compiler not found. Install with: npm install -g typescript"; \
		echo "🔄 Using existing JavaScript files as fallback"; \
	fi

# Docker services
start:
	@echo "🐳 Starting services..."
	docker-compose up -d
	@echo "📊 phpMyAdmin: http://localhost:8080"

stop:
	@echo "🛑 Stopping services..."
	docker-compose down

# Database
migrate:
	@echo "📊 Running migrations..."
	./linkedin-scraper migrate

db-status:
	@echo "📊 Database status:"
	@echo -n "Companies: " && docker-compose exec -T mysql mysql -u root linkedin_jobs -se "SELECT COUNT(*) FROM companies;" 2>/dev/null || echo " N/A"
	@echo -n "Jobs:      " && docker-compose exec -T mysql mysql -u root linkedin_jobs -se "SELECT COUNT(*) FROM job_postings;" 2>/dev/null || echo " N/A"
	@echo -n "Queue:     " && docker-compose exec -T mysql mysql -u root linkedin_jobs -se "SELECT COUNT(*) FROM job_queue;" 2>/dev/null || echo " N/A"

reset:
	@echo "🗑️  Resetting database..."
	@echo "⚠️  This will delete ALL scraped data!"
	@read -p "Are you sure? [y/N] " confirm && [ "$$confirm" = "y" ] || exit 1
	@echo "🔄 Dropping and recreating tables..."
	docker-compose exec -T mysql mysql -u root linkedin_jobs -e "DROP TABLE IF EXISTS job_ratings; DROP TABLE IF EXISTS job_queue; DROP TABLE IF EXISTS job_postings; DROP TABLE IF EXISTS companies;"
	@echo "🏗️  Running migrations to recreate tables..."
	./linkedin-scraper migrate
	@echo "✅ Database reset complete!"

db-shell:
	@echo "🐚 Opening MySQL shell..."
	docker-compose exec mysql mysql -u root linkedin_jobs

# Scraping
scrape:
	@echo "🔍 Starting scraping (edit Makefile to change keywords/location)..."
	./linkedin-scraper scrape --keywords "php" --location "Copenhagen" --max-pages 1 --jobs-per-page 5

# Scraping with different modes
scrape-headless:
	@echo "🔍 Starting headless scraping..."
	HEADLESS_BROWSER=true ./linkedin-scraper scrape --keywords "php" --location "Copenhagen" --max-pages 50 --jobs-per-page 25

scrape-visible:
	@echo "🔍 Starting visible scraping (with browser window)..."
	HEADLESS_BROWSER=false ./linkedin-scraper scrape --keywords "php" --location "Copenhagen" --max-pages 1 --jobs-per-page 5

scrape-debug:
	@echo "🔍 Starting debug scraping (visible browser + debug logs)..."
	LOG_LEVEL=debug HEADLESS_BROWSER=false ./linkedin-scraper scrape --keywords "php" --location "Copenhagen" --max-pages 1 --jobs-per-page 2

show-jobs:
	@echo "📋 Recent scraped jobs:"
	go run cmd/show-jobs/main.go

test:
	@echo "🧪 Running Go tests..."
	go test ./...

# Utilities
clean:
	@echo "🧹 Cleaning build artifacts..."
	rm -f linkedin-scraper *.exe
	docker system prune -f

logs:
	@echo "📝 Application logs:"
	tail -f logs/scraper.log 2>/dev/null || echo "No logs found. Run scraper first."

# Development shortcuts
dev: setup start migrate
	@echo "🎉 Development environment ready!"
	@echo "📊 phpMyAdmin: http://localhost:8080"
	@echo "🔍 Run 'make scrape' to start scraping"
