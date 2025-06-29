# LinkedIn Job Scraper

Et Go-program til at scrape LinkedIn job postings og gemme dem i en MySQL database.

## Features

- 🔍 Søg efter jobs baseret på keywords og lokation
- 🏢 Automatisk company management med normaliseret database struktur  
- 📊 Job queue system for avanceret processing 
- ⭐ Job rating system med JSON criteria
- 🤖 ChromeDP-baseret browser automation
- 🔐 Session management med cookie persistence
- 📝 Omfattende logging
- 🛠️ CLI interface med Cobra

## Database Struktur

Projektet bruger en normaliseret MySQL database med følgende tabeller:

- **companies**: Gemmer unikke virksomheder
- **job_postings**: Hovedtabellen for job postings  
- **job_queue**: Queue system for job processing
- **job_ratings**: AI-baseret job rating system

## Installation

1. Clone repository:
```bash
git clone <repository-url>
cd linkedin-job-scraper
```

2. Installer dependencies:
```bash
go mod tidy
```

3. Setup environment:
```bash
cp .env.example .env
# Rediger .env med dine credentials
```

4. Setup database:
```bash
# Opret MySQL database
mysql -u root -p -e "CREATE DATABASE linkedin_jobs;"

# Kør migrations
go run cmd/main.go migrate
```

## Brug

### Basic scraping:
```bash
go run cmd/main.go scrape --keywords "software engineer" --location "Copenhagen" --max-pages 5
```

### Vis seneste jobs:
```bash
go run cmd/test/main.go
```

## Projekt Struktur

```
├── cmd/
│   ├── main.go              # Hoved CLI applikation
│   └── test/main.go         # Test utility til at vise data
├── internal/
│   ├── config/
│   │   └── config.go        # Configuration management
│   ├── database/
│   │   ├── database.go      # Database connection og migrations
│   │   └── repositories.go  # Data access layer
│   ├── models/
│   │   └── models.go        # Database models og structs
│   ├── scraper/
│   │   └── linkedin.go      # LinkedIn scraping logic
│   └── utils/
│       └── helpers.go       # Utility functions
├── ea_diagram.json          # Entity Relationship diagram
├── go.mod                   # Go module definition
└── .env.example            # Environment template
```

## Configuration

Alle indstillinger styres via environment variables i `.env` filen:

- `DB_*`: Database forbindelse
- `LINKEDIN_*`: LinkedIn credentials  
- `MAX_PAGES`: Maksimum sider at scrape
- `DELAY_BETWEEN_REQUESTS`: Forsinkelse mellem requests
- `HEADLESS_BROWSER`: Kør browser i headless mode

## Næste Steps

1. **Implementer DetailedScraper**: Scrape fuld job beskrivelse fra individuelle job sider
2. **AI Integration**: Implementer job rating system
3. **API Layer**: Tilføj REST API til at eksponere data
4. **Web Dashboard**: Byg web interface til at browse jobs
5. **Advanced Filtering**: Tilføj mere avancerede søgeparametre
6. **Rate Limiting**: Implementer intelligent rate limiting
7. **Error Recovery**: Forbedret error handling og recovery
8. **Docker Support**: Containerisering til nem deployment

## Teknologier

- **Go 1.21+**: Hovedsprog
- **ChromeDP**: Browser automation
- **MySQL**: Database
- **Cobra**: CLI framework  
- **Logrus**: Structured logging
- **Docker**: Containerization (kommende)

## Bidrag

Velkommen til at bidrage med pull requests og feature suggestions!

## Licens

MIT License
