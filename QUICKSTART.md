# 🚀 LinkedIn Job Scraper

Et Go-program til at scrape LinkedIn-jobopslag med MySQL-database og phpMyAdmin-interface.

## 🎯 Hurtigstart

```bash
# Alt i én kommando
make dev

# Eller manuelt:
make start    # Start MySQL + phpMyAdmin
make build    # Byg applikationen
make migrate  # Kør database-migrationer
make scrape   # Start scraping
make test     # Se seneste jobs
make stop     # Stop alle services
````

**phpMyAdmin:** [http://localhost:8080](http://localhost:8080)
**Login:** `root` / (ingen adgangskode som standard)

## 🛠️ Alle Kommandoer

```bash
make help      # Vis alle tilgængelige kommandoer
make setup     # Installer dependencies og byg applikationen
make build     # Byg Go-applikationen
make start     # Start services (MySQL + phpMyAdmin)
make stop      # Stop alle services
make migrate   # Kør database-migrationer
make reset     # Nulstil database (sletter alt data!)
make db-status # Vis database-statistikker
make db-shell  # Åbn MySQL-shell
make scrape    # Start scraping (med default params)
make test      # Kør test for at se nyligt scrape’de job
make clean     # Ryd build-artifakter og Docker-system
make logs      # Følg applikations-logs
make dev       # Setup + start dev-miljø (setup, start, migrate)
```

## 🔧 Konfiguration

Rediger `.env`-filen med dine LinkedIn-legitimationsoplysninger:

```env
LINKEDIN_EMAIL=din_email@example.com
LINKEDIN_PASSWORD=dit_password
HEADLESS_BROWSER=false  # false = synlig browser
```

## 📁 Projektstruktur

```
├── cmd/                 # CLI-applikationer
├── internal/            # Go-packages
│   ├── config/          # Konfiguration
│   ├── database/        # Database & repositories
│   ├── models/          # Data-modeller
│   ├── scraper/         # LinkedIn-scraping
│   └── utils/           # Hjælpefunktioner
├── scripts/             # Setup-scripts (fx reset-db.bat)
├── docker-compose.yml   # Docker-tjenester
├── Makefile             # Build- og drifts-kommandoer
└── .env                 # Miljøvariabler
```

**Start nu:** `make dev` 🚀
