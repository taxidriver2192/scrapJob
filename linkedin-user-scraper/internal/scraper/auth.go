package scraper

import (
	"bufio"
	"context"
	"fmt"
	"os"
	"strings"
	"time"

	"github.com/chromedp/chromedp"
	"github.com/sirupsen/logrus"
)

// login performs LinkedIn login using the same logic as the job scraper
func (s *LinkedInUserScraper) login(ctx context.Context) error {
	logrus.Info("🔐 Checking if already logged in to LinkedIn...")

	var isLoggedIn bool
	
	// Check if already logged in by navigating to LinkedIn and checking for login form
	err := chromedp.Run(ctx,
		chromedp.Navigate("https://www.linkedin.com/login"),
		chromedp.WaitVisible(`body`, chromedp.ByQuery),
	)
	
	if err != nil {
		return fmt.Errorf("failed to navigate to login page: %w", err)
	}
	
	// Try intelligent wait first, fallback to sleep if it fails
	waitErr := chromedp.Run(ctx,
		chromedp.WaitReady(`input[name="session_key"], nav.global-nav, .global-nav`, chromedp.ByQuery),
	)
	
	// If intelligent wait fails, use short fallback sleep
	if waitErr != nil {
		logrus.Debug("Intelligent wait failed, using fallback sleep")
		err = chromedp.Run(ctx,
			chromedp.Sleep(2*time.Second),
		)
		if err != nil {
			return fmt.Errorf("fallback wait failed: %w", err)
		}
	}
	
	// Now evaluate login status
	err = chromedp.Run(ctx,
		chromedp.Evaluate(s.buildIsLoggedInScript(), &isLoggedIn),
	)
	
	if err != nil {
		return fmt.Errorf("failed to check login status: %w", err)
	}

	if isLoggedIn {
		logrus.Info("✅ Already logged in to LinkedIn")
		return nil
	}

	logrus.Info("🔐 Not logged in, proceeding with login...")

	// Check if we have email and password
	if s.config.LinkedIn.Email == "" || s.config.LinkedIn.Password == "" {
		return fmt.Errorf("LinkedIn email and password must be set in environment variables")
	}

	// Fill login form
	err = chromedp.Run(ctx,
		chromedp.WaitVisible(`input[name="session_key"]`, chromedp.ByQuery),
		chromedp.Clear(`input[name="session_key"]`),
		chromedp.SendKeys(`input[name="session_key"]`, s.config.LinkedIn.Email, chromedp.ByQuery),
		chromedp.Clear(`input[name="session_password"]`),
		chromedp.SendKeys(`input[name="session_password"]`, s.config.LinkedIn.Password, chromedp.ByQuery),
		chromedp.Click(`button[type="submit"]`, chromedp.ByQuery),
	)

	if err != nil {
		return fmt.Errorf("failed to submit login form: %w", err)
	}

	// Wait for login to complete
	logrus.Info("⏳ Waiting for login to complete...")
	time.Sleep(3 * time.Second)

	// Check for various possible post-login states
	var currentURL string
	err = chromedp.Run(ctx,
		chromedp.Location(&currentURL),
	)

	if err != nil {
		return fmt.Errorf("failed to get current URL: %w", err)
	}

	logrus.Debugf("Current URL after login attempt: %s", currentURL)

	// Handle different post-login scenarios
	if strings.Contains(currentURL, "challenge") || strings.Contains(currentURL, "checkpoint") {
		logrus.Warn("🛡️  LinkedIn security challenge detected")
		logrus.Info("Please complete the security challenge manually in the browser")
		logrus.Info("Press Enter when you have completed the challenge...")
		
		reader := bufio.NewReader(os.Stdin)
		reader.ReadString('\n')
		
		logrus.Info("Continuing...")
	}

	// Final verification that we're logged in
	err = chromedp.Run(ctx,
		chromedp.Navigate("https://www.linkedin.com/feed/"),
		chromedp.WaitVisible(`body`, chromedp.ByQuery),
		chromedp.Sleep(2*time.Second),
		chromedp.Evaluate(s.buildIsLoggedInScript(), &isLoggedIn),
	)

	if err != nil {
		return fmt.Errorf("failed final login verification: %w", err)
	}

	if !isLoggedIn {
		return fmt.Errorf("login failed - please check your credentials")
	}

	logrus.Info("✅ Successfully logged in to LinkedIn")
	return nil
}

// buildIsLoggedInScript returns JavaScript to check if user is logged in
func (s *LinkedInUserScraper) buildIsLoggedInScript() string {
	return `
		(function() {
			// Check for login form elements (if present, not logged in)
			if (document.querySelector('input[name="session_key"]') || 
				document.querySelector('input[name="session_password"]')) {
				return false;
			}
			
			// Check for authenticated navigation elements
			if (document.querySelector('nav.global-nav') || 
				document.querySelector('.global-nav') ||
				document.querySelector('[data-test-id="nav-profile-photo"]') ||
				document.querySelector('.nav-item__profile-member-photo')) {
				return true;
			}
			
			// Check URL patterns
			const url = window.location.href;
			if (url.includes('/feed/') || 
				url.includes('/in/') || 
				url.includes('/mynetwork/') ||
				url.includes('/jobs/')) {
				return true;
			}
			
			return false;
		})();
	`
}
