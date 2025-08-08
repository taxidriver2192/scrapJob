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
	@echo "  make start          - Start Redis services"
	@echo "  make stop           - Stop Redis services"
	@echo "  make restart        - Restart Redis services"
	@echo ""
	@echo "🔧 Job Operations:"
	@echo "  make discover       - Discover new job IDs and queue them in Redis (auto-resume)"
	@echo "  make discover-from START=150 - Discover starting from specific result number"
	@echo "  make discover-smart - Smart discovery (auto-resume from Redis queue size)"
	@echo "  make process        - Process queued job IDs and scrape details"
	@echo "  make discover-loop  - Run 100 cycles of discovery (10 pages/5min per cycle)"  
	@echo "  make discover-smart-loop - Smart discovery+process loop (100 cycles)"
	@echo "  make process-loop   - Continuously process jobs from queue"
	@echo "  make discover-process-loop - Run 100 cycles of discover + process"
	@echo "  make scrape         - Legacy: discover + scrape in one command"
	@echo ""
	@echo "🔧 Development:"
	@echo "  make test           - Run tests"
	@echo "  make redis-status   - Check Redis queue status"
	@echo ""
	@echo "📊 Access URLs after 'make start':"
	@echo "  Redis Commander: http://localhost:8081 (admin/admin123)"

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
	
#go build -o job-status-checker cmd/job-status-checker/main.go
# Docker services
start:
	@echo "🐳 Starting Redis services..."
	docker-compose up -d
	@echo "✅ Services started!"
	@echo "📊 Redis Commander: http://localhost:8081 (admin/admin123)"

stop:
	@echo "🛑 Stopping services..."
	docker-compose down

restart:
	@echo "🔄 Restarting Redis services..."
	docker-compose down && docker-compose up -d

scrape:
	@echo "🔍 Starting job scraping (legacy)..."
	@echo "💡 Consider using 'make discover' then 'make process' instead"
	./bin/scraper scrape --keywords "" --location "denmark" --total-jobs 50 

discover:
	@echo "🔍 Discovering new job IDs and adding to Redis queue (auto-resume)..."
	./linkedin-scraper discover --keywords "" --location "denmark" --total-jobs 150

process:
	@echo "⚙️  Processing jobs from Redis queue..."
	./linkedin-scraper process --limit 1000

discover-loop:
	@echo "🔄 Starting 30 cycles of job ID discovery (10 pages / 5 min per cycle)..."
	@for i in $$(seq 1 30); do \
		echo ""; \
		echo "🔍 Starting discovery cycle $$i of 30 (max 10 pages or 5 minutes)..."; \
		timeout 300 ./linkedin-scraper discover --keywords "" --location "denmark" --total-jobs 150 || { \
			echo "⏰ Cycle $$i stopped after 5 minutes or completed"; \
		}; \
		if [ $$i -lt 30 ]; then \
			echo "⏳ Waiting 10 seconds before next cycle..."; \
			sleep 10; \
		fi; \
	done
	@echo "✅ All 30 discovery cycles completed!"

discover-smart:
	@echo "🧠 Smart discovery - checking Redis queue first..."
	@QUEUE_SIZE=$$(redis-cli -h localhost -p 6379 LLEN linkedin_jobs_queue 2>/dev/null || echo "0"); \
	echo "📊 Current Redis queue size: $$QUEUE_SIZE jobs"; \
	START_FROM=$$(($$QUEUE_SIZE)); \
	echo "🔍 Starting discovery from result $$START_FROM..."; \
	./linkedin-scraper discover --keywords "" --location "denmark" --total-jobs 15000 --start-from $$START_FROM

discover-parallel:
	@echo "🚀 Starting 10 parallel discovery processes..."
	@echo "📊 Each process will handle 100 jobs (10 pages) in parallel"
	@for i in $$(seq 0 9); do \
		START_FROM=$$(($$i * 100)); \
		END_AT=$$(($$START_FROM + 100)); \
		echo "🔍 Starting parallel process $$(($$i + 1))/10: results $$START_FROM-$$END_AT"; \
		timeout 600 ./linkedin-scraper discover --keywords "" --location "denmark" --total-jobs 100 --start-from $$START_FROM & \
	done; \
	echo "⏳ Waiting for all 10 parallel processes to complete..."; \
	wait; \
	echo "✅ All parallel discovery processes completed!"


process-loop:
	@echo "🔄 Starting continuous job processing..."
	@while true; do \
		echo ""; \
		echo "⚙️  Processing batch of jobs from queue..."; \
		./linkedin-scraper process --limit 30; \
		echo "⏳ Waiting 15 seconds before next batch..."; \
		sleep 15; \
	done 




redis-status:
	@echo "📊 Redis Queue Status:"
	@echo "Queue size: $(shell docker exec scrapjob-redis-1 redis-cli LLEN linkedin_jobs_queue) jobs"
	@echo "Memory usage: $(shell docker exec scrapjob-redis-1 redis-cli INFO memory | grep used_memory_human | cut -d: -f2)"
	@echo "Redis Commander: http://localhost:8081 (admin/admin123)"

test:
	@echo "🧪 Running tests..."
	go test ./...


cleanup-chrome:
	@echo "🧹 Cleaning up Chrome profile and processes..."
	@pkill -f chrome || true
	@pkill -f chromium || true