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
		chromedp.Evaluate(`
			(function() {
				console.log('=== SEARCHING FOR JOB INSIGHT BUTTON ===');
				
				// Log all buttons on the page for debugging
				const allButtons = document.querySelectorAll('button');
				console.log('Total buttons found:', allButtons.length);
				
				// Log first 10 buttons for analysis
				for (let i = 0; i < Math.min(10, allButtons.length); i++) {
					const btn = allButtons[i];
					console.log('Button', i + ':', {
						text: btn.innerText || 'No text',
						ariaLabel: btn.getAttribute('aria-label') || 'No aria-label',
						className: btn.className || 'No class',
						id: btn.id || 'No id'
					});
				}
				
				// Enhanced insight button selectors
				const insightSelectors = [
					// Specific LinkedIn insight selectors
					'.job-details-jobs-unified-top-card__job-insight-text-button',
					'button[aria-label*="kvalifikation"]',
					'button[aria-label*="qualification"]',
					'button[aria-label*="kompetence"]',
					'button[aria-label*="skills"]',
					'button[data-test-modal="job-details-skill-match-modal"]',
					'button[data-test-skill-match-button]',
					
					// Generic insight-related selectors
					'button[aria-label*="insight"]',
					'button[aria-label*="Se"]',
					'button[aria-label*="View"]',
					'button[class*="insight"]',
					'button[class*="skill"]',
					'button[class*="match"]',
					
					// More specific LinkedIn patterns
					'button[aria-describedby*="job-details"]',
					'.job-details-jobs-unified-top-card button',
					'.job-details-jobs-unified-top-card__insights button',
					
					// Danish text patterns
					'button:contains("kompetence")',
					'button:contains("kvalifikation")',
					'button:contains("færdigheder")',
					'button:contains("skills")'
				];
				
				console.log('Trying specific insight selectors...');
				for (const selector of insightSelectors) {
					try {
						const buttons = document.querySelectorAll(selector);
						console.log('Selector', selector, 'found', buttons.length, 'buttons');
						for (const button of buttons) {
							if (button.offsetParent !== null) { // Check if button is visible
								console.log('Found visible job insight button:', {
									text: button.innerText || 'No text',
									ariaLabel: button.getAttribute('aria-label') || 'No aria-label',
									className: button.className
								});
								button.click();
								console.log('✅ Clicked insight button, waiting for modal...');
								return true;
							}
						}
					} catch (e) {
						console.log('Error with selector', selector, ':', e.message);
					}
				}
				
				// More comprehensive generic button search
				console.log('Trying comprehensive generic button search...');
				const genericButtons = document.querySelectorAll('button');
				for (const button of genericButtons) {
					if (button.offsetParent === null) continue; // Skip hidden buttons
					
					const text = (button.innerText || '').toLowerCase();
					const ariaLabel = (button.getAttribute('aria-label') || '').toLowerCase();
					const className = (button.className || '').toLowerCase();
					
					// Check for skill/insight related terms
					const skillTerms = [
						'kompetenc', 'skill', 'kvalifik', 'færdighed', 'insight', 
						'se dine', 'view your', 'match', 'profil', 'profile'
					];
					
					const hasSkillTerm = skillTerms.some(term => 
						text.includes(term) || ariaLabel.includes(term) || className.includes(term)
					);
					
					if (hasSkillTerm) {
						console.log('Found potential insight button:', {
							text: button.innerText || 'No text',
							ariaLabel: button.getAttribute('aria-label') || 'No aria-label',
							className: button.className
						});
						button.click();
						console.log('✅ Clicked potential insight button, waiting for modal...');
						return true;
					}
				}
				
				console.log('❌ No job insight button found after comprehensive search');
				
				// Final fallback: log structure for debugging
				console.log('=== PAGE STRUCTURE DEBUG ===');
				const topCard = document.querySelector('.job-details-jobs-unified-top-card');
				if (topCard) {
					console.log('Found top card element');
					const topCardButtons = topCard.querySelectorAll('button');
					console.log('Buttons in top card:', topCardButtons.length);
					for (const btn of topCardButtons) {
						console.log('Top card button:', {
							text: btn.innerText || 'No text',
							ariaLabel: btn.getAttribute('aria-label') || 'No aria-label'
						});
					}
				} else {
					console.log('No top card found');
				}
				
				return false;
			})();
		`, &skillsModalOpened),
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
