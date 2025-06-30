package scraper

import (
	"embed"
	"fmt"
)

//go:embed scripts/*.js
var scriptFiles embed.FS

// loadScript loads a JavaScript file from the embedded filesystem
func loadScript(filename string) (string, error) {
	content, err := scriptFiles.ReadFile("scripts/" + filename)
	if err != nil {
		return "", fmt.Errorf("failed to load script %s: %w", filename, err)
	}
	return string(content), nil
}

// buildJobExtractionScript builds the complete job extraction script
func (s *LinkedInScraper) buildJobExtractionScript() string {
	jobDetailsScript, err := loadScript("job_details.js")
	if err != nil {
		// Fallback to embedded script if file loading fails
		return s.buildFallbackJobExtractionScript()
	}
	
	skillsScript, err := loadScript("skills.js")
	if err != nil {
		// Fallback to embedded script if file loading fails
		return s.buildFallbackJobExtractionScript()
	}
	
	return fmt.Sprintf(`
		(function() {
			// Job details extraction
			const jobDetails = %s;
			
			// Skills and work type extraction
			%s
			const workTypeAndSkills = getWorkTypeAndSkills();
			
			const result = {
				title: jobDetails.title,
				company: jobDetails.company,
				location: jobDetails.location,
				description: jobDetails.description,
				applyUrl: jobDetails.applyUrl,
				workType: workTypeAndSkills.workType,
				skills: workTypeAndSkills.skills
			};
			
			console.log('=== EXTRACTION COMPLETE ===');
			console.log('Final result:', result);
			
			return result;
		})();
	`, jobDetailsScript, skillsScript)
}

// buildFallbackJobExtractionScript provides fallback when external files fail
func (s *LinkedInScraper) buildFallbackJobExtractionScript() string {
	return `
		(function() {
			console.log('Using fallback extraction script');
			
			// Basic title extraction as fallback
			const getTitleText = function() {
				const selectors = [
					'h1.topcard__title',
					'.job-details-jobs-unified-top-card__job-title h1'
				];
				for (const sel of selectors) {
					const elem = document.querySelector(sel);
					if (elem && elem.innerText) {
						return elem.innerText.trim();
					}
				}
				return '';
			};
			
			// Basic company extraction as fallback
			const getCompanyText = function() {
				const selectors = [
					'a.topcard__org-name-link',
					'.job-details-jobs-unified-top-card__company-name a'
				];
				for (const sel of selectors) {
					const elem = document.querySelector(sel);
					if (elem && elem.innerText) {
						return elem.innerText.trim();
					}
				}
				return '';
			};
			
			return {
				title: getTitleText(),
				company: getCompanyText(),
				location: '',
				description: '',
				applyUrl: window.location.href,
				workType: '',
				skills: []
			};
		})();
	`
}
