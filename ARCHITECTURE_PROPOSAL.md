# LinkedIn Job Scraper - Improved Architecture Proposal

## Current Issues
1. **Scraper complexity**: 8 files with complex interdependencies
2. **JavaScript management**: Embedded strings are hard to maintain
3. **Scattered utilities**: Multiple helper files in different locations
4. **Test organization**: Tests in subdirectories instead of following Go conventions
5. **Script proliferation**: Too many setup/start scripts

## Proposed New Structure

```
├── cmd/
│   ├── scraper/           # Main scraper application
│   │   └── main.go
│   └── tools/             # Additional tools and utilities
│       ├── test-recent/
│       │   └── main.go
│       └── db-migrate/
│           └── main.go
├── internal/
│   ├── config/
│   │   ├── config.go
│   │   └── config_test.go
│   ├── database/
│   │   ├── database.go
│   │   ├── repositories.go
│   │   ├── migrations.go
│   │   └── database_test.go
│   ├── models/
│   │   ├── job.go
│   │   ├── company.go
│   │   ├── search.go
│   │   ├── skills.go
│   │   └── models_test.go   # All model tests in one file
│   ├── linkedin/            # Renamed from 'scraper' for clarity
│   │   ├── client.go        # Main LinkedIn client
│   │   ├── auth.go          # Authentication logic
│   │   ├── navigation.go    # Page navigation
│   │   ├── extractor.go     # Data extraction coordination
│   │   ├── parser.go        # Data parsing and conversion
│   │   ├── linkedin_test.go # All LinkedIn tests
│   │   └── scripts/         # JavaScript extraction scripts
│   │       ├── job_details.js
│   │       ├── skills.js
│   │       └── work_type.js
│   ├── storage/             # Renamed from 'database' for clarity
│   │   ├── db.go
│   │   ├── job_repo.go
│   │   ├── company_repo.go
│   │   └── storage_test.go
│   └── utils/               # Consolidated utilities
│       ├── helpers.go
│       ├── logging.go
│       └── utils_test.go
├── web/                     # Future web interface
│   └── static/
├── scripts/                 # Consolidated scripts
│   ├── setup.sh
│   ├── reset-db.sh
│   └── docker-start.sh
└── assets/                  # Static assets
    └── sql/
        ├── init.sql
        └── migrations/
```

## Key Improvements

### 1. Simplified LinkedIn Package
- **client.go**: Main LinkedIn scraper client with clean interface
- **extractor.go**: Coordinates extraction process
- **parser.go**: Handles data parsing and conversion
- **scripts/**: JavaScript files as separate .js files for better maintenance

### 2. External JavaScript Files
Move JavaScript extraction logic to separate files:
- Better syntax highlighting and validation
- Easier to test and maintain
- Version control friendly

### 3. Consolidated Testing
- Follow Go conventions: `package_test.go` files
- Easier to run all tests for a package
- Better organization

### 4. Clearer Separation of Concerns
- **linkedin**: Handles LinkedIn-specific scraping logic
- **storage**: Handles all database operations
- **utils**: Consolidated utility functions

### 5. Future-Proof Structure
- **web/**: Ready for web interface
- **cmd/tools/**: Additional command-line tools
- **assets/**: Static resources

## Migration Strategy

1. **Phase 1**: Extract JavaScript to separate files
2. **Phase 2**: Reorganize scraper package
3. **Phase 3**: Consolidate tests
4. **Phase 4**: Clean up scripts and utilities
5. **Phase 5**: Validate and optimize

Would you like to proceed with this restructuring?
