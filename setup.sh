#!/usr/bin/env bash
set -euo pipefail

RED='\033[0;31m'   GREEN='\033[0;32m'   YELLOW='\033[1;33m'
BLUE='\033[0;34m'  NC='\033[0m'

info()    { echo -e "${BLUE}[INFO]${NC} $*"; }
success() { echo -e "${GREEN}[OK]${NC}   $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $*"; }
error()   { echo -e "${RED}[ERR]${NC}  $*"; exit 1; }

# 1) Preconditions: Docker & Compose
info "Verifying Docker..."
command -v docker >/dev/null || error "Docker not found. Install Docker Desktop."
info "$(docker --version)"

info "Verifying Docker Compose..."
if command -v docker-compose &>/dev/null; then
  COMPOSE_CMD="docker-compose"
  info "$(docker-compose --version)"
elif docker compose version &>/dev/null; then
  COMPOSE_CMD="docker compose"
  info "$(docker compose version)"
else
  error "Docker Compose not found. It should ship with Docker Desktop."
fi

# 2) Check for Go application
info "Checking for Go application..."
if [ ! -f "cmd/main.go" ]; then
  warn "Go application not found. Building with 'go build'..."
  go build -o linkedin-scraper cmd/main.go && success "Go application built"
else
  info "Go application source found"
fi


# 3) Ensure .env is present for Laravel
if [ ! -f "./laravel-dashboard/.env" ]; then
  info "Creating .env from template"
  cp ./laravel-dashboard/.env.example ./laravel-dashboard/.env
  success ".env created"
else
  info ".env already exists"
fi

# 🔑 2b) Always regenerate a proper APP_KEY on the host
info "Generating a fresh APP_KEY in laravel-dashboard/.env (host)…"
docker run --rm \
  -v "$PWD/laravel-dashboard":/app \
  -w /app \
  php:8.3-cli \
  php artisan key:generate --ansi --force
success "Host .env now has a valid 32-byte key"


# 4) Build & start containers
info "Building & starting Docker containers…"
$COMPOSE_CMD up -d --build
success "Containers are starting"

# 5) Wait for main database (for Go app)
info "Waiting for main MySQL database (port 3307)..."
max_attempts=60
attempt=0
while [ $attempt -lt $max_attempts ]; do
  if $COMPOSE_CMD exec -T db mysql -u root -prootpass -e "SELECT 1;" >/dev/null 2>&1; then
    success "Main MySQL database is ready"
    break
  fi
  sleep 2
  attempt=$((attempt + 1))
  info "Waiting for database... ($attempt/$max_attempts)"
done

if [ $attempt -eq $max_attempts ]; then
  error "Main MySQL database failed to start after $max_attempts attempts"
fi

# 6) Create database for Go application if it doesn't exist
info "Setting up linkedin_jobs database for Go application..."
$COMPOSE_CMD exec -T db mysql -u root -prootpass -e "CREATE DATABASE IF NOT EXISTS linkedin_jobs;" || warn "Database creation failed"
success "linkedin_jobs database ready"

# 7) Build Go application if needed
if [ ! -f "linkedin-scraper" ]; then
  info "Building Go application..."
  go build -o linkedin-scraper cmd/main.go && success "Go application built"
fi


# helper: wait for health
wait_for() {
  local svc=$1; local max=${2:-30}
  info "Waiting for [$svc] to be healthy…"
  for i in $(seq 1 $max); do
    if [ "$(docker inspect --format='{{.State.Health.Status}}' $svc 2>/dev/null)" = "healthy" ]; then
      success "[$svc] is healthy"; return
    fi
    sleep 1
  done
  warn "[$svc] never became healthy"
}

# 9) Handle Laravel setup if containers exist
if docker ps --format "table {{.Names}}" | grep -q "scrapjob-app"; then
  wait_for "scrapjob-db" 60
  wait_for "scrapjob-app" 60

  APP_SVC="scrapjob-app"
  # Use the HOST_UID from .env file
  HOST_UID=$(grep "^HOST_UID=" .env | cut -d'=' -f2)
  HOST_GID=$(grep "^HOST_GID=" .env | cut -d'=' -f2)
  
  # Default to 1000 if not found
  HOST_UID=${HOST_UID:-1000}
  HOST_GID=${HOST_GID:-1000}
  
  exec_in_app="docker exec -u ${HOST_UID}:${HOST_GID} $APP_SVC bash -lc"

  # 5) Composer install & key
  info "Installing Composer deps…"
  $exec_in_app "cd /var/www/html && composer install --no-dev --no-interaction --no-scripts"
  success "Composer deps installed"

  info "Generating APP_KEY…"
  $exec_in_app "cd /var/www/html && php artisan key:generate --force"
  success "APP_KEY set"

  info "Clearing Laravel caches…"
  $exec_in_app "cd /var/www/html && php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan route:clear"
  success "Caches cleared"

  # 6) Fix permissions with proper UID/GID
  info "Creating Laravel storage directories and fixing permissions…"
  
  # Create all required Laravel storage directories
  docker exec $APP_SVC bash -lc "
    mkdir -p /var/www/html/storage/framework/{cache/data,sessions,views,testing} && \
    mkdir -p /var/www/html/storage/{app/public,logs} && \
    mkdir -p /var/www/html/bootstrap/cache
  "
  
  # Set proper ownership and permissions
  docker exec $APP_SVC bash -lc "
    chown -R ${HOST_UID}:${HOST_GID} /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true && \
    chmod -R g+s /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
  "
  
  success "Storage directories created and permissions fixed"

else
  info "Laravel containers not found, skipping Laravel setup"
fi

echo; echo "✅  Setup complete!"; echo
info "🔍 To start scraping: make scrape"
info "📊 phpMyAdmin: http://localhost:8080"
info "🌐 scrapjob-app: http://localhost:8082"
