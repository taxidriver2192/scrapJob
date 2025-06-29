#!/bin/bash

echo "🚀 LinkedIn Job Scraper - Quick Start"
echo ""

show_usage() {
    echo "Usage: $0 [OPTION]"
    echo ""
    echo "Options:"
    echo "  --docker    Start with Docker (MySQL + phpMyAdmin)"
    echo "  --local     Use local/Herd MySQL"
    echo "  --help      Show this help"
    echo ""
    echo "Examples:"
    echo "  $0 --docker   # Recommended for development"
    echo "  $0 --local    # Use existing MySQL installation"
}

setup_docker() {
    echo "🐳 Setting up with Docker..."
    
    # Check Docker
    if ! docker info >/dev/null 2>&1; then
        echo "❌ Docker is not running. Please start Docker Desktop first."
        exit 1
    fi
    
    # Stop existing containers
    docker stop linkedin-mysql linkedin-phpmyadmin 2>/dev/null || true
    docker rm linkedin-mysql linkedin-phpmyadmin 2>/dev/null || true
    
    # Create network
    docker network create linkedin-network 2>/dev/null || true
    
    # Start MySQL
    echo "�️  Starting MySQL container..."
    docker run -d \
      --name linkedin-mysql \
      --network linkedin-network \
      -e MYSQL_ROOT_PASSWORD="" \
      -e MYSQL_ALLOW_EMPTY_PASSWORD=yes \
      -e MYSQL_DATABASE=linkedin_jobs \
      -p 3307:3306 \
      mysql:8.0 \
      --default-authentication-plugin=mysql_native_password
    
    # Start phpMyAdmin
    echo "🌐 Starting phpMyAdmin..."
    docker run -d \
      --name linkedin-phpmyadmin \
      --network linkedin-network \
      -e PMA_HOST=linkedin-mysql \
      -e PMA_USER=root \
      -e PMA_PASSWORD="" \
      -p 8080:80 \
      phpmyadmin/phpmyadmin:latest
    
    # Update .env for Docker
    echo "📝 Updating .env for Docker MySQL..."
    sed -i 's/DB_PORT=.*/DB_PORT=3307/' .env
    
    echo "⏳ Waiting for MySQL to be ready..."
    sleep 15
    
    # Run migrations
    echo "📊 Running database migrations..."
    go build -o linkedin-scraper cmd/main.go
    ./linkedin-scraper migrate
    
    echo ""
    echo "🎉 Setup complete!"
    echo ""
    echo "📊 phpMyAdmin: http://localhost:8080"
    echo "   Username: root"
    echo "   Password: (empty)"
    echo ""
    echo "🔧 Ready to scrape:"
    echo "   ./linkedin-scraper scrape --keywords 'software engineer' --location 'Copenhagen'"
}

setup_local() {
    echo "🏠 Setting up with local MySQL..."
    
    # Update .env for local MySQL
    echo "📝 Updating .env for local MySQL..."
    sed -i 's/DB_PORT=.*/DB_PORT=3306/' .env
    
    # Test MySQL connection
    if ! mysql -u root -e "SELECT 1;" 2>/dev/null; then
        echo "❌ Cannot connect to local MySQL."
        echo "💡 Try: sudo mysql -e \"ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';\""
        exit 1
    fi
    
    # Run migrations
    echo "📊 Running database migrations..."
    go build -o linkedin-scraper cmd/main.go
    ./linkedin-scraper migrate
    
    echo ""
    echo "🎉 Setup complete!"
    echo ""
    echo "🔧 Ready to scrape:"
    echo "   ./linkedin-scraper scrape --keywords 'software engineer' --location 'Copenhagen'"
}

# Parse arguments
case "$1" in
    --docker)
        setup_docker
        ;;
    --local)
        setup_local
        ;;
    --help|"")
        show_usage
        ;;
    *)
        echo "❌ Unknown option: $1"
        show_usage
        exit 1
        ;;
esac
