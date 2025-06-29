-- Initial setup for LinkedIn Job Scraper Database

-- Ensure UTF8MB4 charset for proper emoji and international character support
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Create user if not exists (for Docker setup)
CREATE USER IF NOT EXISTS 'scraper'@'%' IDENTIFIED BY 'scraperpassword';
GRANT ALL PRIVILEGES ON linkedin_jobs.* TO 'scraper'@'%';
FLUSH PRIVILEGES;
