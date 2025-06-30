package main

import (
	"testing"
	"os"
)

func TestRootCommand(t *testing.T) {
	// Test that root command exists and has expected sub-commands
	if rootCmd == nil {
		t.Fatal("rootCmd should not be nil")
	}

	// Test that both scrape and migrate commands exist
	commands := rootCmd.Commands()
	if len(commands) < 2 {
		t.Fatalf("Expected at least 2 commands, got %d", len(commands))
	}

	// Check for scrape command
	var foundScrape, foundMigrate bool
	for _, cmd := range commands {
		switch cmd.Use {
		case "scrape":
			foundScrape = true
		case "migrate":
			foundMigrate = true
		}
	}

	if !foundScrape {
		t.Error("scrape command not found")
	}
	if !foundMigrate {
		t.Error("migrate command not found")
	}
}

func TestScrapeCommandFlags(t *testing.T) {
	// Test that scrape command has required flags
	requiredFlags := []string{"keywords", "location"}
	
	for _, flagName := range requiredFlags {
		flag := scrapeCmd.Flags().Lookup(flagName)
		if flag == nil {
			t.Errorf("Flag '%s' should exist on scrape command", flagName)
		}
	}

	// Test optional flags
	optionalFlags := []string{"max-pages", "jobs-per-page"}
	
	for _, flagName := range optionalFlags {
		flag := scrapeCmd.Flags().Lookup(flagName)
		if flag == nil {
			t.Errorf("Flag '%s' should exist on scrape command", flagName)
		} else {
			t.Logf("Flag '%s' default value: %s", flagName, flag.DefValue)
		}
	}
}

func TestSetupLogging(t *testing.T) {
	// Test that setup logging doesn't panic with various levels
	levels := []string{"debug", "info", "warn", "error", "invalid"}
	
	for _, level := range levels {
		t.Run("level_"+level, func(t *testing.T) {
			defer func() {
				if r := recover(); r != nil {
					t.Errorf("setupLogging panicked with level '%s': %v", level, r)
				}
			}()
			setupLogging(level)
		})
	}
}

func TestMain(m *testing.M) {
	// Setup test environment
	os.Setenv("DB_HOST", "localhost")
	os.Setenv("DB_USER", "testuser")
	os.Setenv("DB_PASSWORD", "testpass")
	os.Setenv("DB_NAME", "testdb")
	
	// Run tests
	code := m.Run()
	
	// Cleanup
	os.Exit(code)
}
