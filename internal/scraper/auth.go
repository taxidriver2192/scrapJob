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
	)

	if err != nil {
		return fmt.Errorf("login submission failed: %w", err)
	}

	// Wait for page to load after login submission
	logrus.Info("⏳ Waiting for LinkedIn to process login...")
	err = chromedp.Run(ctx, chromedp.Sleep(5*time.Second))
	if err != nil {
		return fmt.Errorf("failed to wait after login: %w", err)
	}

	// Check if verification is required
	var needsVerification bool
	err = chromedp.Run(ctx,
		chromedp.Evaluate(`document.querySelector('input[name="pin"]') !== null || document.body.innerText.includes('verification code')`, &needsVerification),
	)

	if err == nil && needsVerification {
		logrus.Info("🔐 LinkedIn requires verification code")
		return s.handleVerificationCode(ctx)
	}

	// Verify login was successful using the same logic as verifyLoginSuccess
	logrus.Info("🔍 Verifying login success...")
	maxAttempts := 3
	for attempt := 1; attempt <= maxAttempts; attempt++ {
		logrus.Infof("🔍 Checking login status (attempt %d/%d)...", attempt, maxAttempts)
		
		var loginSuccess bool
		err = chromedp.Run(ctx, 
			chromedp.Evaluate(`
				// Check for multiple indicators of being logged in
				const indicators = [
					document.querySelector('nav.global-nav') !== null,
					document.querySelector('.global-nav') !== null,
					document.querySelector('[data-test-id="nav-menu"]') !== null,
					document.querySelector('.feed-shared-header') !== null,
					document.querySelector('.application-outlet') !== null,
					document.body.classList.contains('chrome'),
					window.location.pathname.includes('/feed') || window.location.pathname.includes('/in/'),
					document.querySelector('input[name="session_key"]') === null
				];
				
				// Return true if at least 2 indicators are true
				indicators.filter(Boolean).length >= 2;
			`, &loginSuccess),
		)
		
		if err == nil && loginSuccess {
			logrus.Info("✅ Successfully logged in to LinkedIn")
			return nil
		}
		
		// Check if there's an error message
		var hasError bool
		err = chromedp.Run(ctx,
			chromedp.Evaluate(`document.querySelector('.form__input--error, .alert--error, [data-test-id="error"]') !== null`, &hasError),
		)
		
		if err == nil && hasError {
			return fmt.Errorf("login failed - LinkedIn showed an error message")
		}
		
		// Wait before next attempt
		if attempt < maxAttempts {
			logrus.Infof("⏳ Waiting %d seconds before next check...", attempt*3)
			err = chromedp.Run(ctx, chromedp.Sleep(time.Duration(attempt*3)*time.Second))
			if err != nil {
				return fmt.Errorf("failed to wait between attempts: %w", err)
			}
		}
	}

	return fmt.Errorf("login verification timeout - unable to confirm login success after %d attempts", maxAttempts)
}

// handleVerificationCode handles LinkedIn email verification code challenge
func (s *LinkedInScraper) handleVerificationCode(ctx context.Context) error {
	logrus.Info("📧 Please check your email for the LinkedIn verification code")
	
	// Wait for verification form to load
	err := chromedp.Run(ctx, chromedp.Sleep(2*time.Second))
	if err != nil {
		return fmt.Errorf("failed to wait for verification form: %w", err)
	}

	// Get verification code from user
	code, err := s.promptForVerificationCode()
	if err != nil {
		return err
	}

	// Submit verification code
	err = s.submitVerificationCode(ctx, code)
	if err != nil {
		return err
	}

	// Verify success
	return s.verifyLoginSuccess(ctx)
}

// promptForVerificationCode prompts user for verification code
func (s *LinkedInScraper) promptForVerificationCode() (string, error) {
	fmt.Print("Enter the verification code from your email: ")
	reader := bufio.NewReader(os.Stdin)
	code, err := reader.ReadString('\n')
	if err != nil {
		return "", fmt.Errorf("failed to read verification code: %w", err)
	}
	
	code = strings.TrimSpace(code)
	if code == "" {
		return "", fmt.Errorf("verification code cannot be empty")
	}
	
	return code, nil
}

// submitVerificationCode submits the verification code to LinkedIn
func (s *LinkedInScraper) submitVerificationCode(ctx context.Context, code string) error {
	logrus.Infof("🔐 Submitting verification code")

	// Use the exact selectors you provided
	err := chromedp.Run(ctx,
		chromedp.WaitVisible(`input[name="pin"]`, chromedp.ByQuery),
		chromedp.Clear(`input[name="pin"]`, chromedp.ByQuery),
		chromedp.SendKeys(`input[name="pin"]`, code, chromedp.ByQuery),
		chromedp.Click(`#email-pin-submit-button`, chromedp.ByQuery),
	)

	if err != nil {
		return fmt.Errorf("failed to submit verification code: %w", err)
	}

	return nil
}

// verifyLoginSuccess verifies that login was successful after verification
func (s *LinkedInScraper) verifyLoginSuccess(ctx context.Context) error {
	logrus.Info("⏳ Waiting for verification to complete...")
	
	// Wait a bit for the page to process the verification
	err := chromedp.Run(ctx, chromedp.Sleep(3*time.Second))
	if err != nil {
		return fmt.Errorf("failed to wait after verification: %w", err)
	}

	// Check multiple times with increasing delays to allow for redirects
	maxAttempts := 5
	for attempt := 1; attempt <= maxAttempts; attempt++ {
		logrus.Infof("🔍 Checking login status (attempt %d/%d)...", attempt, maxAttempts)
		
		var loginSuccess bool
		err = chromedp.Run(ctx, 
			chromedp.Evaluate(`
				// Check for multiple indicators of being logged in
				const indicators = [
					document.querySelector('nav.global-nav') !== null,
					document.querySelector('.global-nav') !== null,
					document.querySelector('[data-test-id="nav-menu"]') !== null,
					document.querySelector('.feed-shared-header') !== null,
					document.querySelector('.application-outlet') !== null,
					document.body.classList.contains('chrome'),
					window.location.pathname.includes('/feed') || window.location.pathname.includes('/in/'),
					document.querySelector('input[name="session_key"]') === null
				];
				
				// Return true if at least 2 indicators are true
				indicators.filter(Boolean).length >= 2;
			`, &loginSuccess),
		)
		
		if err == nil && loginSuccess {
			logrus.Info("✅ Verification successful!")
			return nil
		}
		
		// Check if there's an error message
		var hasError bool
		err = chromedp.Run(ctx,
			chromedp.Evaluate(`document.querySelector('.form__input--error, .alert--error, [data-test-id="error"]') !== null`, &hasError),
		)
		
		if err == nil && hasError {
			return fmt.Errorf("verification failed - LinkedIn showed an error message")
		}
		
		// Wait before next attempt
		if attempt < maxAttempts {
			logrus.Infof("⏳ Waiting %d seconds before next check...", attempt*2)
			err = chromedp.Run(ctx, chromedp.Sleep(time.Duration(attempt*2)*time.Second))
			if err != nil {
				return fmt.Errorf("failed to wait between attempts: %w", err)
			}
		}
	}

	return fmt.Errorf("verification timeout - unable to confirm login success after %d attempts", maxAttempts)
}
