package scraper

import (
	"context"

	"github.com/chromedp/chromedp"
	"github.com/sirupsen/logrus"
)

// clickInsightsButton attempts to find and click the job insights button to open skills modal
func (s *LinkedInScraper) clickInsightsButton(ctx context.Context) error {
	var skillsModalOpened bool
	err := chromedp.Run(ctx,
		chromedp.Evaluate(s.buildClickInsightsScript(), &skillsModalOpened),
		// Wait for modal to open or timeout (whichever comes first)
		chromedp.WaitReady(`.modal, .artdeco-modal, body`, chromedp.ByQuery),
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
