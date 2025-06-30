package scraper

import (
	"context"
	"time"

	"github.com/chromedp/chromedp"
	"github.com/sirupsen/logrus"
)

// clickInsightsButton attempts to find and click the job insights button to open skills modal
func (s *LinkedInScraper) clickInsightsButton(ctx context.Context) error {
	var skillsModalOpened bool
	err := chromedp.Run(ctx,
		chromedp.Evaluate(s.buildClickInsightsScript(), &skillsModalOpened),
		chromedp.Sleep(3*time.Second), // Wait longer for modal to open
	)

	if err != nil {
		return err
	}

	if skillsModalOpened {
		logrus.Debug("✅ Skills modal opened successfully")
	} else {
		logrus.Debug("⚠️ No skills modal opened")
	}

	return nil
}
