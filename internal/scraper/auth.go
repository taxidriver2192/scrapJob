package scraper

import (
	"context"
	"fmt"
	"time"

	"github.com/chromedp/chromedp"
	"github.com/sirupsen/logrus"
)

// login performs LinkedIn login
func (s *LinkedInScraper) login(ctx context.Context) error {
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

	// Perform login
	logrus.Info("🔑 Performing login...")
	if s.config.LinkedIn.Email == "" || s.config.LinkedIn.Password == "" {
		return fmt.Errorf("LinkedIn credentials not provided in config")
	}

	err = chromedp.Run(ctx,
		chromedp.WaitVisible(`input[name="session_key"]`, chromedp.ByQuery),
		chromedp.SendKeys(`input[name="session_key"]`, s.config.LinkedIn.Email, chromedp.ByQuery),
		chromedp.SendKeys(`input[name="session_password"]`, s.config.LinkedIn.Password, chromedp.ByQuery),
		chromedp.Click(`button[type="submit"]`, chromedp.ByQuery),
		// Wait for login redirect - either to feed or login error
		chromedp.WaitReady(`nav.global-nav, .alert, .form__input--error`, chromedp.ByQuery),
	)

	if err != nil {
		return fmt.Errorf("login process failed: %w", err)
	}

	// Verify login was successful
	var loginSuccess bool
	err = chromedp.Run(ctx,
		chromedp.Evaluate(s.buildIsLoggedInScript(), &loginSuccess),
	)

	if err != nil || !loginSuccess {
		return fmt.Errorf("login verification failed - check credentials")
	}

	logrus.Info("✅ Successfully logged in to LinkedIn")
	return nil
}
