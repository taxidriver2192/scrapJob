.PHONY: help setup setup-manual build build-ts start stop restart migrate reset backup restore db-status db-shell scrape scrape-headless scrape-visible scrape-debug show-jobs extract-addresses extract-addresses-all extract-addresses-dry match-jobs rescrape-empty rescrape-empty-all rescrape-dry-run queue-status queue-check-missing queue-enqueue queue-enqueue-all queue-list queue-reset web-dashboard web-dashboard-logs test clean logs dev docker-build docker-up docker-down docker-logs

# Default target
help:
	@echo "LinkedIn Job Scraper - Available Commands"
	@echo ""
	@echo "Quick Start:"
	@echo "  make setup          - Full setup (recommended for new users)"
	@echo "  make setup-manual   - Manual setup without running setup script"
	@echo ""
	@echo "Building:"
	@echo "  make build          - Build the Go application"
	@echo "  make build-ts       - Compile TypeScript scripts to JavaScript"
	@echo ""
	@echo "Docker Services (MySQL, phpMyAdmin, Web Dashboard):"
	@echo "  make start          - Start Docker services"
	@echo "  make stop           - Stop Docker services"
	@echo "  make restart        - Restart Docker services"
	@echo ""
	@echo "Database Management:"
	@echo "  make migrate        - Run database migrations"
	@echo "  make reset          - Reset database (delete all data)"
	@echo "  make backup         - Create database backup"
	@echo "  make restore        - Restore database from backup"
	@echo "  make db-status      - Show database statistics"
	@echo "  make db-shell       - Open MySQL shell"
	@echo ""
	@echo "Local Scraping (runs on your machine):"
	@echo "  make scrape         - Start scraping"
	@echo "  make scrape-headless - Start headless scraping"  
	@echo "  make scrape-visible  - Start visible scraping (with browser window)"
	@echo "  make scrape-debug    - Start debug scraping (visible + debug logs)"
	@echo "  make show-jobs      - Show recent scraped jobs"
	@echo ""
	@echo "AI Processing (runs locally):"
	@echo "  make extract-addresses     - Extract addresses from jobs using OpenAI"
	@echo "  make extract-addresses-all - Extract addresses from ALL jobs"
	@echo "  make extract-addresses-dry - Dry run address extraction"
	@echo "  make match-jobs            - Find your best job matches"
	@echo ""
	@echo "Rescraping (runs locally):"
	@echo "  make rescrape-empty        - Rescrape jobs with empty descriptions"
	@echo "  make rescrape-empty-all    - Rescrape ALL jobs with empty descriptions"  
	@echo "  make rescrape-dry-run      - Check what jobs would be rescraped"
	@echo ""
	@echo "Queue Management (runs locally):"
	@echo "  make queue-status   - Show job queue status"
	@echo "  make queue-check-missing - Check what jobs are missing from queue"
	@echo "  make queue-enqueue  - Add jobs to queue for AI processing"
	@echo "  make queue-enqueue-all - Add ALL remaining jobs to queue"
	@echo "  make queue-list     - List jobs in queue"
	@echo "  make queue-reset    - Reset queue (mark all as pending)"
	@echo ""
	@echo "Web Dashboard (runs in Docker):"
	@echo "  make web-dashboard-logs - Show web dashboard logs"
	@echo ""
	@echo "Docker Management:"
	@echo "  make docker-build  - Build Docker containers"
	@echo "  make docker-up     - Start all Docker services"
	@echo "  make docker-down   - Stop all Docker services"
	@echo "  make docker-logs   - Show Docker logs (web dashboard)"
	@echo ""
	@echo "Development & Testing:"
	@echo "  make test           - Run Go tests"
	@echo "  make clean          - Clean build artifacts"
	@echo "  make logs           - Show application logs"
	@echo ""
	@echo "Services after setup:"
	@echo "  Web Dashboard: http://localhost:8081"
	@echo "  phpMyAdmin: http://localhost:8080"
	@echo "  MySQL: localhost:3307"

# Setup and build
setup:
	@echo "Setting up LinkedIn Job Scraper..."
	@if [ -f "setup.sh" ]; then \
		echo "Running comprehensive setup script..."; \
		chmod +x setup.sh; \
		./setup.sh; \
	else \
		echo "Setup script not found, running manual setup..."; \
		$(MAKE) setup-manual; \
	fi

setup-manual:
	@echo "Running manual setup..."
	go mod tidy
	mkdir -p logs chrome-profile backups
	go build -o linkedin-scraper cmd/main.go
	@echo "Manual setup complete! You may want to also run 'make start' to start Docker services."

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

# Scraping
scrape:
	@echo "🔍 Starting scraping (edit Makefile to change keywords/location)..."
	./linkedin-scraper scrape --keywords "php" --location "Copenhagen" --total-jobs 50

# Scraping with different modes
scrape-headless:
	@echo "🔍 Starting headless scraping..."
	HEADLESS_BROWSER=true ./linkedin-scraper scrape --keywords "php" --location "Copenhagen" --total-jobs 5000

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

# Rescraping commands (use same scraping logic but get URLs from database)
rescrape-empty:
	@echo "🔄 Rescaping jobs with empty descriptions..."
	go run cmd/rescraper/main.go --limit 50

rescrape-empty-all:
	@echo "🔄 Rescaping ALL jobs with empty descriptions..."
	go run cmd/rescraper/main.go --limit 0

rescrape-dry-run:
	@echo "🔍 Checking what jobs would be rescraped..."
	go run cmd/rescraper/main.go --limit 50 --dry-run

queue-status:
	@echo "📊 Checking job queue status..."
	go run cmd/queue-manager/main.go --action status

queue-check-missing:
	@echo "🔍 Checking for jobs missing from queue..."
	@docker-compose exec -T mysql mysql -u root linkedin_jobs -se "SELECT COUNT(*) as 'Jobs without descriptions' FROM job_postings WHERE description IS NULL OR description = '';" 2>/dev/null || echo "N/A"
	@docker-compose exec -T mysql mysql -u root linkedin_jobs -se "SELECT COUNT(*) as 'Jobs ready for queue' FROM job_postings j LEFT JOIN job_queue q ON j.job_id = q.job_id LEFT JOIN job_ratings r ON j.job_id = r.job_id AND r.rating_type = 'ai_match' WHERE q.job_id IS NULL AND r.job_id IS NULL AND j.description IS NOT NULL AND j.description != '';" 2>/dev/null || echo "N/A"

queue-check-empty-descriptions:
	@echo "🔍 Checking jobs with empty descriptions..."
	@echo "📊 Jobs without descriptions:"
	@docker-compose exec -T mysql mysql -u root linkedin_jobs -se "SELECT COUNT(*) as 'Empty descriptions' FROM job_postings WHERE description IS NULL OR description = '';" 2>/dev/null || echo "N/A"
	@echo "📋 Sample jobs with empty descriptions:"
	@docker-compose exec -T mysql mysql -u root linkedin_jobs -se "SELECT job_id, title, company_name, CASE WHEN description IS NULL THEN 'NULL' WHEN description = '' THEN 'EMPTY' ELSE 'HAS_DATA' END as desc_status FROM job_postings WHERE description IS NULL OR description = '' LIMIT 10;" 2>/dev/null || echo "N/A"

queue-enqueue:
	@echo "📝 Adding jobs to queue for AI matching..."
	go run cmd/queue-manager/main.go --action enqueue --limit 50

queue-enqueue-all:
	@echo "📝 Adding ALL remaining jobs to queue..."
	go run cmd/queue-manager/main.go --action enqueue --limit 0

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

web-dashboard-logs:
	@echo "Web Dashboard logs:"
	docker-compose logs -f web-dashboard

restart:
	@echo "Restarting Docker services..."
	docker-compose down && docker-compose up -d

# Development shortcuts
dev: setup start migrate
	@echo "🎉 Development environment ready!"
	@echo "📊 phpMyAdmin: http://localhost:8080"
	@echo "🔍 Run 'make scrape' to start scraping"

# Docker Commands
docker-build:
	@echo "🐳 Building Docker containers..."
	docker-compose build

docker-up:
	@echo "🚀 Starting all services with Docker..."
	docker-compose up -d

docker-down:
	@echo "🛑 Stopping all Docker services..."
	docker-compose down

docker-logs:
	@echo "📝 Docker logs (web dashboard):"
	docker-compose logs -f web-dashboard
