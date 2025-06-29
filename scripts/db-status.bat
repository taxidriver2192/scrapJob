@echo off
echo 📊 Database status:

echo|set /p="Companies: "
docker-compose exec mysql mysql -u root linkedin_jobs -se "SELECT COUNT(*) FROM companies;" 2>nul || echo N/A

echo|set /p="Jobs: "
docker-compose exec mysql mysql -u root linkedin_jobs -se "SELECT COUNT(*) FROM job_postings;" 2>nul || echo N/A

echo|set /p="Queue: "
docker-compose exec mysql mysql -u root linkedin_jobs -se "SELECT COUNT(*) FROM job_queue;" 2>nul || echo N/A
