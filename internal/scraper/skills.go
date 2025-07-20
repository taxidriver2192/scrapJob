package scraper

import (
	"context"

	"github.com/chromedp/chromedp"
)

// clickInsightsButton attempts to find and click the job insights button to open skills modal
func (s *LinkedInScraper) clickInsightsButton(ctx context.Context) error {
	var skillsModalOpened bool
	err := chromedp.Run(ctx,
		chromedp.Evaluate(s.buildClickInsightsScript(), &skillsModalOpened),
		chromedp.WaitReady(`.modal, .artdeco-modal, body`, chromedp.ByQuery),
	)

	return err
}
