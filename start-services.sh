#!/bin/bash

# LinkedIn Job Scraper with Redis Cache - Start Script

echo "🚀 Starting LinkedIn Job Scraper services..."

# Check if Docker is running
if ! docker info &> /dev/null; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

# Start Redis and MySQL services
echo "🐳 Starting Redis cache and MySQL database..."
docker-compose up -d redis mysql phpmyadmin redis-commander

# Wait for services to be healthy
echo "⏳ Waiting for services to be ready..."
sleep 10

# Check service status
echo "📊 Service Status:"
echo "  Redis: $(docker-compose ps redis --format 'table {{.State}}')"
echo "  MySQL: $(docker-compose ps mysql --format 'table {{.State}}')"
echo "  phpMyAdmin: $(docker-compose ps phpmyadmin --format 'table {{.State}}')"
echo "  Redis Commander: $(docker-compose ps redis-commander --format 'table {{.State}}')"

echo ""
echo "✅ Services started successfully!"
echo ""
echo "📊 Access URLs:"
echo "  phpMyAdmin (MySQL): http://localhost:8080"
echo "  Redis Commander:    http://localhost:8081 (admin/admin123)"
echo ""
echo "🔧 Redis Connection:"
echo "  Host: 127.0.0.1"
echo "  Port: 6379"
echo ""
echo "🔧 MySQL Connection:"
echo "  Host: 127.0.0.1"
echo "  Port: 3307"
echo "  Database: linkedin_jobs"
echo "  Username: laravel"
echo "  Password: laravelpass"
echo ""
echo "🚀 Ready to run: make scrape"
