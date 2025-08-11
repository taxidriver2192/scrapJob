# LinkedIn User Profile Scraper

A Go-based CLI tool to scrape LinkedIn user profiles and store them in a database via a Laravel API backend.

## Features

- Scrape detailed LinkedIn user profiles including:
  - Basic profile information (name, headline, summary, location, industry)
  - Work experience/positions
  - Education history
  - Profile images and background images
- Queue-based processing using Redis
- Integration with Laravel API backend
- Chrome browser automation with authentication
- Configurable via environment variables

## Installation

1. Clone or copy this directory
2. Install dependencies:
   ```bash
   go mod tidy
   ```
3. Copy the example environment file:
   ```bash
   cp .env.example .env
   ```
4. Configure your environment variables in `.env`

## Configuration

### Required Environment Variables

- `LINKEDIN_EMAIL`: Your LinkedIn email
- `LINKEDIN_PASSWORD`: Your LinkedIn password
- `API_BASE_URL`: URL to your Laravel API backend
- `API_KEY`: API key for authentication with Laravel backend

### Optional Environment Variables

- `CHROME_EXECUTABLE_PATH`: Path to Chrome executable (default: `/usr/bin/google-chrome`)
- `CHROME_USER_DATA_DIR`: Chrome user data directory (default: `./chrome-profile`)
- `HEADLESS`: Run Chrome in headless mode (default: `true`)
- `DELAY_BETWEEN_REQUESTS`: Delay between requests in seconds (default: `2`)
- `REDIS_HOST`: Redis host (default: `localhost`)
- `REDIS_PORT`: Redis port (default: `6379`)
- `LOG_LEVEL`: Logging level (default: `info`)
- `DUMP_DATA_TO_CONSOLE`: Output scraped data to console instead of API (default: `false`)

## Development Mode

### Console Output Instead of API

For development and testing, you can output scraped data to the console instead of sending it to the API:

```bash
# Set in .env file
DUMP_DATA_TO_CONSOLE=true

# Then run any scraping command
./linkedin-user-scraper scrape-user --username "williamhgates"
```

This will:
- Skip API authentication checks
- Skip user existence checks
- Output beautifully formatted JSON data to the console
- Useful for development before setting up the Laravel API

## Usage

### Build the Application

```bash
go build -o linkedin-user-scraper ./cmd
```

### Commands

#### 1. Scrape a Single User

Scrape a single LinkedIn user profile immediately:

```bash
./linkedin-user-scraper scrape-user --username "williamhgates"
# OR with full URL
./linkedin-user-scraper scrape-user --username "https://www.linkedin.com/in/williamhgates/"
```

#### 2. Add Users to Queue

Add multiple users to the Redis processing queue:

```bash
# Add multiple usernames
./linkedin-user-scraper add-to-queue --usernames "williamhgates,satyanadella,sundarpichai"

# Add from file
./linkedin-user-scraper add-to-queue --file usernames.txt
```

Example `usernames.txt` file:
```
williamhgates
satyanadella
sundarpichai
https://www.linkedin.com/in/jeffweiner08/
# Lines starting with # are ignored
```

#### 3. Process Queue

Process users from the Redis queue:

```bash
./linkedin-user-scraper process-queue --limit 10
```

#### 4. Clear Queue

Clear the user processing queue:

```bash
./linkedin-user-scraper clear-queue
```

### Debug Mode

Add `--debug` flag to any command to enable detailed logging:

```bash
./linkedin-user-scraper scrape-user --username "williamhgates" --debug
```

## Data Structure

The scraper extracts and stores the following user data:

### Users Table
- `linkedin_id`: Stable LinkedIn reference
- `linkedin_url`: Pretty URL slug (williamhgates)
- `headline`: The short tagline
- `summary`: The "About" paragraph
- `location_city`: e.g. "Seattle, Washington"
- `location_country`: Country only
- `industry_name`: "Philanthropy", "Software", etc.
- `avatar`: Profile picture URL
- `background_image`: Banner image URL
- `linkedin_synced_at`: Timestamp of last sync

### Related Tables
- **Positions**: Work experience with company details
- **Education**: Schools and degrees
- **Certifications**: Professional certifications
- **Projects**: Personal/professional projects
- **Publications**: Published works
- **Patents**: Patent information
- **Volunteer Experiences**: Volunteer work

## Integration with Laravel API

The scraper communicates with a Laravel API backend to store user data. The API should provide these endpoints:

- `POST /api/users` - Create/update user profile
- `GET /api/users/check?linkedin_url={url}` - Check if user exists

## Error Handling

- Invalid profiles (404, private profiles) are skipped with logging
- Network errors are retried with exponential backoff
- Authentication challenges prompt manual intervention
- Queue processing continues even if individual users fail

## Security Considerations

- Store LinkedIn credentials securely
- Use environment variables for sensitive configuration
- Consider rate limiting to avoid LinkedIn detection
- Use Chrome user data directory for persistent sessions
- Respect LinkedIn's robots.txt and terms of service

## Troubleshooting

### Chrome Issues
- Ensure Chrome is installed and executable path is correct
- Check Chrome user data directory permissions
- Try running with `HEADLESS=false` for debugging

### Authentication Issues
- Verify LinkedIn credentials
- Check for security challenges in non-headless mode
- Ensure Chrome user data directory persists sessions

### API Issues
- Verify Laravel API is running and accessible
- Check API key configuration
- Review API endpoint responses

## Example Workflow

1. Add users to queue:
   ```bash
   ./linkedin-user-scraper add-to-queue --usernames "user1,user2,user3"
   ```

2. Process the queue:
   ```bash
   ./linkedin-user-scraper process-queue --limit 50
   ```

3. Or scrape individual users directly:
   ```bash
   ./linkedin-user-scraper scrape-user --username "williamhgates"
   ```
