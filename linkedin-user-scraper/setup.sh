#!/bin/bash

# LinkedIn User Scraper Quick Start Script

set -e

echo "🚀 LinkedIn User Scraper - Quick Start"
echo "======================================="

# Check if .env exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
    echo "⚠️  Please edit .env with your LinkedIn credentials and configuration"
    echo "   Required: LINKEDIN_EMAIL, LINKEDIN_PASSWORD, API_BASE_URL, API_KEY"
    echo ""
    read -p "Press Enter when you have configured your .env file..."
fi

# Build the application
echo "🔨 Building application..."
make build

echo ""
echo "✅ Setup complete! You can now use the scraper:"
echo ""
echo "Available commands:"
echo "  ./bin/linkedin-user-scraper scrape-user --username williamhgates"
echo "  ./bin/linkedin-user-scraper add-to-queue --usernames 'user1,user2,user3'"
echo "  ./bin/linkedin-user-scraper add-to-queue --file usernames.txt"
echo "  ./bin/linkedin-user-scraper process-queue --limit 10"
echo "  ./bin/linkedin-user-scraper clear-queue"
echo ""
echo "Or use make commands:"
echo "  make scrape-user USER=williamhgates"
echo "  make add-to-queue USERS=user1,user2,user3"
echo "  make process-queue LIMIT=10"
echo "  make clear-queue"
echo ""
echo "For more information, see README.md or run:"
echo "  ./bin/linkedin-user-scraper --help"
