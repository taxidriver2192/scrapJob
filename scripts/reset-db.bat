@echo off
echo 🗑️ Resetting database...
echo ⚠️ This will delete ALL scraped data!
set /p confirm="Are you sure? [y/N] "
if /i not "%confirm%"=="y" exit /b

echo 🔄 Dropping and recreating tables...
docker-compose exec mysql mysql -u root linkedin_jobs -e "DROP TABLE IF EXISTS job_ratings; DROP TABLE IF EXISTS job_queue; DROP TABLE IF EXISTS job_postings; DROP TABLE IF EXISTS companies;"

echo 🏗️ Running migrations to recreate tables...
linkedin-scraper.exe migrate

echo ✅ Database reset complete!
