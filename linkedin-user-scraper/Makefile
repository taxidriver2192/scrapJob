# LinkedIn User Scraper Makefile

# Variables
BINARY_NAME=linkedin-user-scraper
BUILD_DIR=./bin
CMD_DIR=./cmd

# Default target
.PHONY: all
all: build

# Build the application
.PHONY: build
build:
	@echo "🔨 Building $(BINARY_NAME)..."
	@mkdir -p $(BUILD_DIR)
	@go build -o $(BUILD_DIR)/$(BINARY_NAME) $(CMD_DIR)
	@echo "✅ Build complete: $(BUILD_DIR)/$(BINARY_NAME)"

# Install dependencies
.PHONY: deps
deps:
	@echo "📦 Installing dependencies..."
	@go mod tidy
	@echo "✅ Dependencies installed"

# Clean build artifacts
.PHONY: clean
clean:
	@echo "🧹 Cleaning build artifacts..."
	@rm -rf $(BUILD_DIR)
	@echo "✅ Clean complete"

# Run the application with example command
.PHONY: run-example
run-example: build
	@echo "🚀 Running example command..."
	@$(BUILD_DIR)/$(BINARY_NAME) --help

# Run scrape-user command
.PHONY: scrape-user
scrape-user: build
	@$(BUILD_DIR)/$(BINARY_NAME) scrape-user --username $(USER) $(ARGS)

# Run add-to-queue command
.PHONY: add-to-queue
add-to-queue: build
	@$(BUILD_DIR)/$(BINARY_NAME) add-to-queue --usernames $(USERS) $(ARGS)

# Run process-queue command
.PHONY: process-queue
process-queue: build
	@$(BUILD_DIR)/$(BINARY_NAME) process-queue --limit $(LIMIT) $(ARGS)

# Run clear-queue command
.PHONY: clear-queue
clear-queue: build
	@$(BUILD_DIR)/$(BINARY_NAME) clear-queue

# Create .env file from example
.PHONY: setup-env
setup-env:
	@if [ ! -f .env ]; then \
		echo "📝 Creating .env file from .env.example..."; \
		cp .env.example .env; \
		echo "✅ Please edit .env with your configuration"; \
	else \
		echo "⚠️  .env file already exists"; \
	fi

# Help target
.PHONY: help
help:
	@echo "LinkedIn User Scraper - Available commands:"
	@echo ""
	@echo "  make build          - Build the application"
	@echo "  make deps           - Install Go dependencies"
	@echo "  make clean          - Clean build artifacts"
	@echo "  make setup-env      - Create .env from .env.example"
	@echo ""
	@echo "  make scrape-user USER=username [ARGS=--debug]"
	@echo "                      - Scrape a single user"
	@echo ""
	@echo "  make add-to-queue USERS=user1,user2,user3 [ARGS=--debug]"
	@echo "                      - Add users to queue"
	@echo ""
	@echo "  make process-queue LIMIT=50 [ARGS=--debug]"
	@echo "                      - Process users from queue"
	@echo ""
	@echo "  make clear-queue    - Clear the processing queue"
	@echo ""
	@echo "Examples:"
	@echo "  make scrape-user USER=williamhgates"
	@echo "  make add-to-queue USERS=williamhgates,satyanadella"
	@echo "  make process-queue LIMIT=10 ARGS=--debug"
