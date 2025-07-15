.PHONY: help setup build start stop restart migrate reset backup restore db-status db-shell scrape show-jobs extract-addresses match-jobs analyze-data rescrape web-dashboard test clean logs dev

# Default target
help:
	@echo "LinkedIn Job Scraper - Available Commands"
	@echo ""
	@echo "🚀 Quick Start:"
	@echo "  make setup          - Full setup (recommended for new users)"
	@echo "  make dev            - Setup + start services + migrate"
	@echo ""
	@echo "🏗️  Building:"
	@echo "  make build          - Build the Go application"
	@echo ""
	@echo "🐳 Services (MySQL, phpMyAdmin, Web Dashboard):"
	@echo "  make start          - Start Docker services"
	@echo "  make stop           - Stop Docker services"
	@echo "  make restart        - Restart Docker services"
	@echo ""
	@echo "🗄️  Database:"
	@echo "  make migrate        - Run database migrations"
	@echo "  make db-status      - Show database statistics"
	@echo "  make db-shell       - Open MySQL shell"
	@echo "  make backup         - Create database backup"
	@echo "  make restore        - Restore database from backup"
	@echo "  make reset          - Reset database (⚠️  DELETES ALL DATA)"
	@echo ""
	@echo "🔍 Job Scraping:"
	@echo "  make scrape         - Start job scraping"
	@echo "  make show-jobs      - Show recent scraped jobs"
	@echo "  make rescrape       - Rescrape jobs with missing data"
	@echo ""
	@echo "🤖 AI Processing:"
	@echo "  make extract-addresses - Extract addresses using AI"
	@echo "  make match-jobs        - Find your best job matches (RECOMMENDED)"
	@echo "  make analyze-data      - Analyze job data quality"
	@echo ""
	@echo "🌐 Web Interface:"
	@echo "  make web-dashboard  - Start web dashboard"
	@echo ""
	@echo "🔧 Development:"
	@echo "  make test           - Run tests"
	@echo "  make clean          - Clean build artifacts"
	@echo "  make logs           - Show application logs"
	@echo ""
	@echo "📊 Access URLs after 'make start':"
	@echo "  Web Dashboard: http://localhost:8081"
	@echo "  phpMyAdmin: http://localhost:8080"
	@echo "  MySQL: localhost:3307"

# Setup and build
setup:
	@echo "🚀 Setting up LinkedIn Job Scraper..."
	@chmod +x setup.sh
	@./setup.sh


build:
	@echo "🔨 Building application..."
	@if command -v tsc >/dev/null 2>&1; then \
		echo "📝 Compiling TypeScript scripts..."; \
		npm run compile-scripts; \
	else \
		echo "⚠️  TypeScript compiler not found. Using existing JavaScript files."; \
	fi
	go build -o linkedin-scraper cmd/main.go

# Docker services
start:
	@echo "🐳 Starting services..."
	docker-compose up -d
	@echo "✅ Services started!"
	@echo "📊 phpMyAdmin: http://localhost:8080"
	@echo "🌐 Web Dashboard: http://localhost:8081"

stop:
	@echo "🛑 Stopping services..."
	docker-compose down

restart:
	@echo "🔄 Restarting Docker services..."
	docker-compose down && docker-compose up -d

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
	docker-compose exec -T mysql mysqldump -u root --single-transaction --routines --triggers linkedin_jobs > $$backup_file && \
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

# Job scraping
scrape:
	@echo "🔍 Starting job scraping..."
	@echo "💡 Edit this target to change keywords/location"
	./linkedin-scraper scrape --keywords "php" --location "Copenhagen" --total-jobs 50

show-jobs:
	@echo "📋 Recent scraped jobs:"
	go run cmd/show-jobs/main.go

rescrape:
	@echo "🔄 Rescaping jobs with missing data..."
	go run cmd/rescraper/main.go --limit 50

# AI processing
extract-addresses:
	@echo "🏠 Extracting addresses with AI..."
	@echo "💡 Run with different --limit or --dry-run as needed"
	go run cmd/extract-addresses/main.go --limit 10

match-jobs:
	@echo "🎯 Finding your best job matches with AI..."
	@echo "💡 This uses the optimized matching system"
	@if [ -f "job_match_config.json" ]; then \
		echo "📋 Using custom configuration from job_match_config.json"; \
		go run cmd/match-jobs-optimized/main.go --config job_match_config.json --limit 10 --min-score 50; \
	else \
		echo "📋 Using default configuration (create custom: make match-jobs --save-config)"; \
		go run cmd/match-jobs-optimized/main.go --limit 10 --min-score 50; \
	fi

analyze-data:
	@echo "🔍 Analyzing job data quality..."
	go run cmd/analyze-data/main.go

# Web dashboard
web-dashboard:
	@echo "🌐 Starting web dashboard..."
	@echo "� Local access: http://localhost:8081"
	@echo "🌍 Network access available on your IP"
	go run cmd/web-dashboard/main.go

# Development shortcuts
dev: setup start migrate
	@echo "🎉 Development environment ready!"
	@echo "📊 phpMyAdmin: http://localhost:8080"
	@echo "🌐 Web Dashboard: http://localhost:8081"
	@echo "� Run 'make scrape' to start scraping"

# Utilities
test:
	@echo "🧪 Running tests..."
	go test ./...

clean:
	@echo "🧹 Cleaning build artifacts..."
	rm -f linkedin-scraper *.exe
	docker system prune -f

logs:
	@echo "📝 Application logs:"
	tail -f logs/scraper.log 2>/dev/null || echo "No logs found. Run scraper first."

# Advanced commands (use carefully)
match-jobs-verbose:
	@echo "� Finding matches with detailed reasoning..."
	@if [ -f "job_match_config.json" ]; then \
		go run cmd/match-jobs-optimized/main.go --config job_match_config.json --limit 10 --min-score 50 --verbose; \
	else \
		go run cmd/match-jobs-optimized/main.go --limit 10 --min-score 50 --verbose; \
	fi

match-jobs-all:
	@echo "🎯 Processing ALL jobs (this may take a while)..."
	@if [ -f "job_match_config.json" ]; then \
		go run cmd/match-jobs-optimized/main.go --config job_match_config.json --limit 0 --min-score 60; \
	else \
		go run cmd/match-jobs-optimized/main.go --limit 0 --min-score 60; \
	fi

match-jobs-config:
	@echo "� Creating custom job matching configuration..."
	go run cmd/match-jobs-optimized/main.go --save-config
	@echo "✅ Configuration saved to job_match_config.json"
	@echo "📝 Edit this file to customize your preferences"
