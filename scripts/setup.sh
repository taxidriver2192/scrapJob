#!/bin/bash

# LinkedIn Job Scraper - Setup Script

echo "🚀 Setting up LinkedIn Job Scraper..."

# Check if Go is installed
if ! command -v go &> /dev/null; then
    echo "❌ Go is not installed. Please install Go 1.21 or later."
    exit 1
fi

echo "✅ Go found: $(go version)"

# Check if MySQL is installed
if ! command -v mysql &> /dev/null; then
    echo "⚠️  MySQL not found. Please ensure MySQL is installed and running."
    echo "   Or use Docker: docker-compose up -d mysql"
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
    echo "✅ .env file created. Please edit it with your credentials."
else
    echo "✅ .env file already exists."
fi

# Install Go dependencies
echo "📦 Installing Go dependencies..."
go mod tidy
echo "✅ Dependencies installed."

# Create necessary directories
echo "📁 Creating directories..."
mkdir -p logs
mkdir -p chrome-profile
echo "✅ Directories created."

# Build the application
echo "🔨 Building application..."
if go build -o linkedin-scraper cmd/main.go; then
    echo "✅ Application built successfully."
else
    echo "❌ Build failed! Please check the errors above."
    exit 1
fi

echo ""
echo "🎉 Setup complete!"
echo ""
echo "🚀 Quick start:"
echo "1. Start services: docker-compose up -d"
echo "2. Run migrations: ./linkedin-scraper migrate"
echo "3. Open phpMyAdmin: http://localhost:8080"
echo "4. Start scraping: ./linkedin-scraper scrape --keywords \"software engineer\" --location \"Copenhagen\""
echo ""
echo "📝 Don't forget to edit .env with your LinkedIn credentials!"
