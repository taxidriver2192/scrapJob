-- Create Laravel database and ensure user exists
CREATE DATABASE IF NOT EXISTS laravel_db;

-- Create the laravel user if it doesn't exist
CREATE USER IF NOT EXISTS 'laravel'@'%' IDENTIFIED BY 'laravelpass';

-- Grant privileges
GRANT ALL PRIVILEGES ON laravel_db.* TO 'laravel'@'%';
FLUSH PRIVILEGES;
