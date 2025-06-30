# Scraper Reorganization Plan

## Current Structure Analysis

### Current Files in `/internal/scraper/`:
- `scraper.go` - Main scraper struct and interface
- `auth.go` - LinkedIn authentication
- `navigation.go` - Page navigation logic  
- `extraction.go` - Job URL and details extraction
- `parsing.go` - Data parsing and conversion
- `skills.go` - Skills modal interaction
- `database.go` - Database save operations
- `helpers.go` - Utility functions
- `script_loader.go` - JavaScript file loading
- `scripts/` - JavaScript extraction files

### Issues:
1. Too many small files with unclear boundaries
2. Helper functions duplicated between packages
3. Skills logic could be better integrated
4. Database logic mixed with scraping logic

## Proposed Reorganization

### Option 1: Merge Related Functionality
```
/internal/scraper/
├── client.go        # Main scraper client + auth + navigation
├── extractor.go     # Job extraction + skills + parsing  
├── storage.go       # Database operations
├── script_loader.go # JavaScript management
├── helpers.go       # Consolidated utilities
├── scripts/         # JavaScript files
└── tests/           # Test files
```

### Option 2: Domain-based Organization  
```
/internal/scraper/
├── linkedin_client.go    # Main client interface
├── job_extractor.go     # Job data extraction
├── skills_extractor.go  # Skills and work type extraction
├── data_parser.go       # Data parsing and conversion
├── storage_handler.go   # Database operations
├── script_manager.go    # JavaScript management
├── utils.go            # Utilities
├── scripts/            # JavaScript files
└── tests/             # Test files
```

I prefer Option 1 as it reduces file count while maintaining clear separation of concerns.

## Implementation Steps:
1. Merge auth.go + navigation.go + scraper.go → client.go
2. Merge extraction.go + parsing.go + skills.go → extractor.go  
3. Rename database.go → storage.go and enhance
4. Consolidate helpers from both packages
5. Update imports and test accordingly
