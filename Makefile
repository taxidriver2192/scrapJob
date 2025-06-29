.PHONY: help setup build start stop migrate reset db-status db-shell scrape test clean logs dev

# Default target
help:
	@echo "🚀 LinkedIn Job Scraper - Available Commands"
	@echo ""
	@echo "Setup & Build:"
	@echo "  make setup     - Install dependencies and build application"
	@echo "  make build     - Build the Go application"
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
	@echo "  make scrape    - Start scraping (with default params)"
	@echo "  make test      - Show recent scraped jobs"
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
	go build -o linkedin-scraper cmd/main.go

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
	./linkedin-scraper scrape --keywords "software engineer" --location "Copenhagen" --max-pages 1

test:
	@echo "📋 Recent scraped jobs:"
	go run cmd/test/main.go

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
