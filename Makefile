.PHONY: help setup build start stop migrate reset backup restore db-status db-shell scrape show-jobs extract-addresses extract-addresses-all extract-addresses-dry match-jobs match-jobs-all match-jobs-show-all match-jobs-dry queue-status queue-enqueue queue-list queue-reset web-dashboard test clean logs dev

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
	@echo "  make backup    - Create database backup"
	@echo "  make restore   - Restore database from backup"
	@echo "  make db-status - Show database statistics"
	@echo "  make db-shell  - Open MySQL shell"
	@echo ""
	@echo "Scraping:"
	@echo "  make scrape         - Start scraping (default: 50 jobs)"
	@echo "  make scrape-headless - Start headless scraping (no browser window)"  
	@echo "  make scrape-visible  - Start visible scraping (with browser window)"
	@echo "  make scrape-debug    - Start debug scraping (visible + debug logs)"
	@echo "  make show-jobs      - Show recent scraped jobs"
	@echo ""
	@echo "AI Processing:"
	@echo "  make extract-addresses     - Extract addresses from 10 jobs using OpenAI"
	@echo "  make extract-addresses-all - Extract addresses from ALL jobs using OpenAI"
	@echo "  make extract-addresses-dry - Dry run - show jobs needing address extraction"
	@echo ""
	@echo "Job Matching:"
	@echo "  make match-jobs        - Find your best job matches"
	@echo ""
	@echo "Queue Management:"
	@echo "  make queue-status      - Show job queue status"
	@echo "  make queue-enqueue     - Add jobs to queue for AI processing"
	@echo "  make queue-list        - List jobs in queue"
	@echo "  make queue-reset       - Reset queue (mark all as pending)"
	@echo ""
	@echo "Web Dashboard:"
	@echo "  make web-dashboard     - Start web dashboard server"
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

backup:
	@echo "💾 Creating database backup..."
	@mkdir -p backups
	@backup_file="backups/linkedin_jobs_backup_$$(date +%Y%m%d_%H%M%S).sql"; \
	echo "📂 Backup file: $$backup_file"; \
	docker-compose exec -T mysql mysqldump -u root linkedin_jobs > $$backup_file && \
	echo "✅ Database backup created: $$backup_file" || \
	echo "❌ Backup failed!"

restore:
	@echo "🔄 Restoring database from backup..."
	@echo "📁 Available backups:"
	@ls -la backups/*.sql 2>/dev/null || echo "No backups found. Create one with 'make backup' first."
	@echo ""
	@read -p "Enter backup filename (from backups/ folder): " backup_file; \
	if [ -f "backups/$$backup_file" ]; then \
		echo "🗑️  Dropping existing tables..."; \
		docker-compose exec -T mysql mysql -u root linkedin_jobs -e "DROP TABLE IF EXISTS job_ratings; DROP TABLE IF EXISTS job_queue; DROP TABLE IF EXISTS job_postings; DROP TABLE IF EXISTS companies;"; \
		echo "📥 Restoring from backup: backups/$$backup_file"; \
		docker-compose exec -T mysql mysql -u root linkedin_jobs < "backups/$$backup_file" && \
		echo "✅ Database restored successfully!" || \
		echo "❌ Restore failed!"; \
	else \
		echo "❌ Backup file not found: backups/$$backup_file"; \
	fi

# Scraping
scrape:
	@echo "🔍 Starting scraping (edit Makefile to change keywords/location)..."
	./linkedin-scraper scrape --keywords "php" --location "Copenhagen" --total-jobs 50

# Scraping with different modes
scrape-headless:
	@echo "🔍 Starting headless scraping..."
	HEADLESS_BROWSER=true ./linkedin-scraper scrape --keywords "php" --location "Copenhagen" --total-jobs 599

scrape-visible:
	@echo "🔍 Starting visible scraping (with browser window)..."
	HEADLESS_BROWSER=false ./linkedin-scraper scrape --keywords "php" --location "Copenhagen" --total-jobs 50

scrape-debug:
	@echo "🔍 Starting debug scraping (visible browser + debug logs)..."
	LOG_LEVEL=debug HEADLESS_BROWSER=false ./linkedin-scraper scrape --keywords "php" --location "Copenhagen" --total-jobs 25

show-jobs:
	@echo "📋 Recent scraped jobs:"
	go run cmd/show-jobs/main.go

extract-addresses:
	@echo "🏠 Extracting addresses with OpenAI..."
	go run cmd/extract-addresses/main.go --limit 10

extract-addresses-all:
	@echo "🏠 Extracting addresses for ALL jobs with OpenAI..."
	go run cmd/extract-addresses/main.go --limit 0

extract-addresses-dry:
	@echo "🏠 Dry run - showing jobs that need address extraction..."
	go run cmd/extract-addresses/main.go --limit 10 --dry-run

match-jobs:
	@echo "🎯 Finding your best job matches with AI..."
	go run cmd/match-jobs/main.go --limit 0 --min-score 0

queue-status:
	@echo "📊 Checking job queue status..."
	go run cmd/queue-manager/main.go --action status

queue-enqueue:
	@echo "📝 Adding jobs to queue for AI matching..."
	go run cmd/queue-manager/main.go --action enqueue --limit 50

queue-list:
	@echo "📋 Listing queued jobs..."
	go run cmd/queue-manager/main.go --action list --limit 20

queue-reset:
	@echo "🔄 Resetting job queue..."
	go run cmd/queue-manager/main.go --action reset

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

# Web Dashboard
web-dashboard:
	@echo "🌐 Starting web dashboard..."
	@echo "📊 Local access: http://localhost:8081"
	@echo "🌍 Network access: http://172.30.88.162:8081"
	@echo "💡 Others can access via your IP address"
	go run cmd/web-dashboard/main.go

# Development shortcuts
dev: setup start migrate
	@echo "🎉 Development environment ready!"
	@echo "📊 phpMyAdmin: http://localhost:8080"
	@echo "🔍 Run 'make scrape' to start scraping"
