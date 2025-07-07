#!/bin/bash

# LinkedIn Job Scraper - Setup Script
# This script sets up the development environment for the LinkedIn Job Scraper

set -e

echo "==========================================="
echo "  LinkedIn Job Scraper - Setup Script"
echo "==========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running on Windows (WSL/Git Bash)
if [[ "$OSTYPE" == "msys" || "$OSTYPE" == "cygwin" || -n "$WSL_DISTRO_NAME" ]]; then
    WINDOWS=true
    print_status "Detected Windows environment"
else
    WINDOWS=false
    print_status "Detected Unix-like environment"
fi

# Check if Docker is installed
check_docker() {
    print_status "Checking Docker installation..."
    if command -v docker &> /dev/null; then
        print_success "Docker is installed"
        docker --version
    else
        print_error "Docker is not installed"
        echo "Please install Docker Desktop from: https://www.docker.com/products/docker-desktop"
        if [ "$WINDOWS" = true ]; then
            echo "For Windows: Download Docker Desktop for Windows"
        else
            echo "For Linux: Follow instructions at https://docs.docker.com/engine/install/"
            echo "For macOS: Download Docker Desktop for Mac"
        fi
        exit 1
    fi
}

# Check if Docker Compose is installed
check_docker_compose() {
    print_status "Checking Docker Compose installation..."
    if command -v docker-compose &> /dev/null; then
        print_success "Docker Compose is installed"
        docker-compose --version
    else
        print_error "Docker Compose is not installed"
        echo "Docker Compose should come with Docker Desktop"
        echo "If you installed Docker separately, install Docker Compose from:"
        echo "https://docs.docker.com/compose/install/"
        exit 1
    fi
}

# Check if Node.js and npm are installed
check_nodejs() {
    print_status "Checking Node.js installation..."
    if command -v node &> /dev/null; then
        NODE_VERSION=$(node --version)
        print_success "Node.js is installed: $NODE_VERSION"

        if command -v npm &> /dev/null; then
            NPM_VERSION=$(npm --version)
            print_success "npm is installed: $NPM_VERSION"
        else
            print_error "npm is not installed"
            exit 1
        fi
    else
        print_error "Node.js is not installed"
        echo "Please install Node.js from: https://nodejs.org/"
        echo "Recommended version: 18.x or later"
        exit 1
    fi
}

# Check if TypeScript is installed
check_typescript() {
    print_status "Checking TypeScript installation..."
    if command -v tsc &> /dev/null; then
        TS_VERSION=$(tsc --version)
        print_success "TypeScript is installed: $TS_VERSION"
    else
        print_warning "TypeScript is not installed globally"
        print_status "Installing TypeScript globally..."
        npm install -g typescript
        if [ $? -eq 0 ]; then
            print_success "TypeScript installed successfully"
        else
            print_error "Failed to install TypeScript"
            exit 1
        fi
    fi
}

# Check if Go is installed
check_go() {
    print_status "Checking Go installation..."
    if command -v go &> /dev/null; then
        GO_VERSION=$(go version)
        print_success "Go is installed: $GO_VERSION"
    else
        print_error "Go is not installed"
        echo "Please install Go from: https://golang.org/dl/"
        echo "Recommended version: 1.21 or later"
        exit 1
    fi
}

# Check if PHP and Composer are installed for Laravel
check_php_composer() {
    print_status "Checking PHP and Composer installation..."
    if command -v php &> /dev/null; then
        PHP_VERSION=$(php --version | head -n 1)
        print_success "PHP is installed: $PHP_VERSION"
    else
        print_error "PHP is not installed"
        echo "Please install PHP 8.2 or later"
        exit 1
    fi

    if command -v composer &> /dev/null; then
        COMPOSER_VERSION=$(composer --version | head -n 1)
        print_success "Composer is installed: $COMPOSER_VERSION"
    else
        print_error "Composer is not installed"
        echo "Please install Composer from: https://getcomposer.org/"
        exit 1
    fi
}

# Install Node.js dependencies
install_node_dependencies() {
    print_status "Installing Node.js dependencies..."
    if [ -f "package.json" ]; then
        npm install
        if [ $? -eq 0 ]; then
            print_success "Node.js dependencies installed"
        else
            print_error "Failed to install Node.js dependencies"
            exit 1
        fi
    else
        print_error "package.json not found"
        exit 1
    fi
}

# Compile TypeScript
compile_typescript() {
    print_status "Compiling TypeScript scripts..."
    npm run compile-scripts
    if [ $? -eq 0 ]; then
        print_success "TypeScript compilation completed"
    else
        print_error "TypeScript compilation failed"
        exit 1
    fi
}

# Build Go application
build_go_app() {
    print_status "Building Go application..."
    go mod download
    go build -o linkedin-scraper cmd/main.go
    if [ $? -eq 0 ]; then
        print_success "Go application built successfully"
    else
        print_error "Failed to build Go application"
        exit 1
    fi
}

# Create necessary directories
create_directories() {
    print_status "Creating necessary directories..."
    mkdir -p logs
    mkdir -p chrome-profile
    mkdir -p backups
    print_success "Directories created"
}

# Setup environment file
setup_env_file() {
    print_status "Setting up environment file..."
    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            cp .env.example .env
            print_success "Environment file created from template"
            print_warning "Please edit .env file with your LinkedIn credentials and OpenAI API key"
        else
            print_error ".env.example file not found"
            exit 1
        fi
    else
        print_success "Environment file already exists"
    fi
}

# Setup Laravel dependencies
setup_laravel() {
    print_status "Setting up Laravel dashboard..."

    if [ -d "laravel-dashboard" ]; then
        print_status "Laravel dashboard directory found"
        
        # Don't run composer install on host - Docker will handle it
        print_status "Skipping host composer install (Docker will handle dependencies)"
        
        # Don't run Laravel commands on host - Docker will handle them
        print_status "Skipping host Laravel commands (Docker will handle setup)"
        
        print_success "Laravel dashboard setup prepared for Docker"
    else
        print_error "laravel-dashboard directory not found"
        exit 1
    fi
}

# Start Docker services
start_docker_services() {
    print_status "Starting Docker services (MySQL, phpMyAdmin, Go Dashboard, Laravel Dashboard)..."
    docker-compose up -d
    if [ $? -eq 0 ]; then
        print_success "Docker services started successfully"
        echo ""
        echo "Services available at:"
        echo "  - Laravel Dashboard (NEW): http://localhost:8082"
        echo "  - Go Web Dashboard: http://localhost:8081"
        echo "  - phpMyAdmin: http://localhost:8080"
        echo "  - MySQL: localhost:3307"
    else
        print_error "Failed to start Docker services"
        exit 1
    fi
}

# Fix Laravel permissions after Docker starts
fix_laravel_permissions() {
    print_status "Fixing Laravel permissions in Docker container..."
    
    # Wait for container to be ready
    sleep 10
    
    # Check if container is running
    if docker ps | grep -q "linkedin-laravel-dashboard"; then
        print_status "Running permission fix in Docker container..."
        
        # Fix permissions inside the container
        docker exec linkedin-laravel-dashboard bash -c "
            mkdir -p /var/www/html/storage/framework/{cache,sessions,views}
            mkdir -p /var/www/html/bootstrap/cache
            chmod -R 777 /var/www/html/storage
            chmod -R 777 /var/www/html/bootstrap/cache
        "
        
        if [ $? -eq 0 ]; then
            print_success "Laravel permissions fixed"
        else
            print_warning "Could not fix Laravel permissions automatically"
        fi
    else
        print_error "Laravel container not running"
        exit 1
    fi
}

# Run database migrations
run_migrations() {
    print_status "Running database migrations..."
    sleep 5  # Wait for MySQL to be ready
    ./linkedin-scraper migrate
    if [ $? -eq 0 ]; then
        print_success "Database migrations completed"
    else
        print_error "Database migrations failed"
        exit 1
    fi
}

# Main setup process
main() {
    echo "Starting setup process..."
    echo ""

    # Check prerequisites
    check_docker
    check_docker_compose
    check_nodejs
    check_go
    check_typescript
    # Remove PHP/Composer check since we use Docker
    # check_php_composer

    echo ""
    print_status "All prerequisites are installed"
    echo ""

    # Setup project
    create_directories
    setup_env_file
    install_node_dependencies
    compile_typescript
    build_go_app
    setup_laravel

    echo ""
    print_status "Starting services..."
    start_docker_services

    echo ""
    print_status "Fixing Laravel permissions..."
    fix_laravel_permissions

    echo ""
    print_status "Setting up database..."
    run_migrations

    echo ""
    echo "==========================================="
    print_success "Setup completed successfully!"
    echo "==========================================="
    echo ""
    echo "Next steps:"
    echo "1. Edit .env file with your credentials:"
    echo "   - LINKEDIN_EMAIL=your-email@example.com"
    echo "   - LINKEDIN_PASSWORD=your-password"
    echo "   - OPENAI_API_KEY=your-openai-api-key"
    echo ""
    echo "2. Start scraping:"
    echo "   ./linkedin-scraper scrape --keywords \"software engineer\" --location \"Copenhagen\" --total-jobs 50"
    echo ""
    echo "3. View results in the new Laravel dashboard:"
    echo "   http://localhost:8082"
    echo ""
    echo "4. Or view results in the original Go dashboard:"
    echo "   http://localhost:8081"
    echo ""
    echo "5. Manage database with phpMyAdmin:"
    echo "   http://localhost:8080"
    echo ""
}

# Run main function
main "$@"
