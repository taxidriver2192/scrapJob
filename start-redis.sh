#!/bin/bash

echo "🚀 Starting LinkedIn Job Scraper with Redis Cache"
echo ""

# Check if .env file exists
if [ ! -f .env ]; then
    echo "⚠️  .env file not found. Copying from .env.example..."
    cp .env.example .env
    echo "✅ Please edit .env file with your LinkedIn credentials and API settings"
    echo ""
fi

# Start Redis service
echo "🐳 Starting Redis service..."
docker-compose up -d

# Wait for Redis to be ready
echo "⏳ Waiting for Redis to be ready..."
sleep 5

# Check Redis connection
echo "🔍 Testing Redis connection..."
if docker-compose exec redis redis-cli ping | grep -q PONG; then
    echo "✅ Redis is running successfully"
else
    echo "❌ Redis connection failed"
    exit 1
fi

echo ""
echo "🎯 Redis Cache is ready!"
echo "📊 Redis Commander: http://localhost:8081 (admin/admin123)"
echo ""
echo "To test the scraper, run:"
echo "  make scrape"
echo ""
echo "To stop services, run:"
echo "  make stop"
