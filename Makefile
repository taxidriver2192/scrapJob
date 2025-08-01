.PHONY: help setup build start stop restart check-job-status scrape scrape-all scrape-loop analyze-data test

# Default target
help:
	@echo "LinkedIn Job Scraper - Available Commands"
	@echo ""
	@echo "🚀 Quick Start:"
	@echo "  make setup          - Full setup (recommended for new users)"
	@echo ""
	@echo "🏗️  Building:"
	@echo "  make build          - Build the Go application"
	@echo ""
	@echo "🐳 Services:"
	@echo "  make start          - Start Docker services"
	@echo "  make stop           - Stop Docker services"
	@echo "  make restart        - Restart Docker services"
	@echo ""
	@echo "🔧 Job Operations:"
	@echo "  make scrape         - Start job scraping"
	@echo "  make scrape-all     - Scrape all jobs in Denmark"
	@echo "  make scrape-loop    - Run scraping 30 times with 15min timeout each"
	@echo "  make check-job-status - Check if jobs are still open"
	@echo "  make analyze-data   - Analyze job data quality"
	@echo ""
	@echo "🔧 Development:"
	@echo "  make test           - Run tests"
	@echo ""
	@echo "📊 Access URLs after 'make start':"
	@echo "  phpMyAdmin: http://localhost:8080"

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
	go build -o job-status-checker cmd/job-status-checker/main.go

# Docker services
start:
	@echo "🐳 Starting services..."
	docker-compose up -d
	@echo "✅ Services started!"
	@echo "📊 phpMyAdmin: http://localhost:8080"
	@echo "🌐 Web Dashboard: http://localhost:8082"

stop:
	@echo "🛑 Stopping services..."
	docker-compose down

restart:
	@echo "🔄 Restarting Docker services..."
	docker-compose down && docker-compose up -d

check-job-status:
	@echo "🔍 Checking job status..."
	go run cmd/job-status-checker/main.go --limit 0

scrape:
	@echo "🔍 Starting job scraping..."
	@echo "💡 Edit this target to change keywords/location"
	./linkedin-scraper scrape --keywords "php" --location "Copenhagen" --total-jobs 50

scrape-all:
	@echo "🔍 Starting job scraping..."
	@echo "💡 Edit this target to change keywords/location"
	./linkedin-scraper scrape --keywords "" --location "denmark" --total-jobs 50

scrape-loop:
	@echo "🔄 Starting 15 cycles of job scraping with 15-minute timeout per cycle..."
	@for i in $$(seq 1 15); do \
		echo ""; \
		echo "🔍 Starting scraping cycle $$i of 15..."; \
		timeout 900 ./linkedin-scraper scrape --keywords "" --location "denmark" --total-jobs 50 || { \
			echo "⏰ Cycle $$i stopped after 15 minutes or completed"; \
		}; \
		if [ $$i -lt 15 ]; then \
			echo "⏳ Waiting 15 seconds before next cycle..."; \
			sleep 15; \
		fi; \
	done
	@echo "✅ All 15 scraping cycles completed!"

analyze-data:
	@echo "🔍 Analyzing job data quality..."
	go run cmd/analyze-data/main.go

# Utilities
test:
	@echo "🧪 Running tests..."
	go test ./...
