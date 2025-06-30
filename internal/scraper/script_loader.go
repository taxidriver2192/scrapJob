package scraper

import (
	"embed"
	"fmt"
	"os/exec"
	"strings"
)

//go:embed scripts/dist/*.js scripts/src/*.ts
var scriptFiles embed.FS

// Script file constants
const (
	utilsScript = "utils.js"
	utilsScriptTS = "utils.ts"
)

// loadScript loads a JavaScript file from the embedded filesystem
func loadScript(filename string) (string, error) {
	// Try to load from dist directory first
	content, err := scriptFiles.ReadFile("scripts/dist/" + filename)
	if err != nil {
		// Fallback to old location for backwards compatibility
		content, err = scriptFiles.ReadFile("scripts/" + filename)
		if err != nil {
			return "", fmt.Errorf("failed to load script %s: %w", filename, err)
		}
	}
	return string(content), nil
}

// loadTypeScript loads and compiles a TypeScript file
func loadTypeScript(filename string) (string, error) {
	// First try to load pre-compiled JavaScript version from dist
	jsFilename := strings.Replace(filename, ".ts", ".js", 1)
	if content, err := loadScript(jsFilename); err == nil {
		return content, nil
	}
	
	// If no JS version exists, try to compile TypeScript from src
	// Simple TypeScript compilation using project config
	cmd := exec.Command("npx", "tsc", "--project", "tsconfig.json")
	if err := cmd.Run(); err != nil {
		// Fallback: try to load from src as fallback (though this won't work in browser)
		content, err := scriptFiles.ReadFile("scripts/src/" + filename)
		if err != nil {
			return "", fmt.Errorf("failed to load TypeScript file %s and compilation failed: %w", filename, err)
		}
		return string(content), nil
	}
	
	// After compilation, try to load the JS version again
	return loadScript(jsFilename)
}

// buildJobExtractionScript builds the complete job extraction script
func (s *LinkedInScraper) buildJobExtractionScript() string {
	// Load utils first - required by other scripts
	utilsScriptContent, err := loadScript(utilsScript)
	if err != nil {
		// Fallback to embedded script if file loading fails
		return s.buildFallbackJobExtractionScript()
	}
	
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
		// Utils functionality first - make it globally available
		%s
		
		// Job details extraction functions
		%s
		
		// Skills and work type extraction functions
		%s
		
		// Execute job extraction and return result
		(function() {
			console.log('=== STARTING JOB EXTRACTION ===');
			
			// Execute job details extraction
			const jobDetails = {
				title: getTitleText(),
				company: getCompanyText(),
				location: getLocationData(),
				description: getDescriptionText(),
				applyUrl: getApplyUrl(),
				postedDate: getPostedDate()
			};
			
			// Execute skills extraction
			const workTypeAndSkills = getWorkTypeAndSkills();
			
			// Combine results
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
	`, utilsScriptContent, jobDetailsScript, skillsScript)
}

// buildPageAnalysisScript builds script for analyzing job search page
func (s *LinkedInScraper) buildPageAnalysisScript() string {
	script, err := loadTypeScript("page_analysis.ts")
	if err != nil {
		return `document.querySelectorAll('a[href*="/jobs/view/"]').length > 0`
	}
	return script
}

// buildDetailedAnalysisScript builds script for detailed page analysis
func (s *LinkedInScraper) buildDetailedAnalysisScript() string {
	script, err := loadTypeScript("detailed_analysis.ts")
	if err != nil {
		return `({ url: window.location.href, title: document.title })`
	}
	return script
}

// buildExtractJobURLsScript builds script for extracting job URLs
func (s *LinkedInScraper) buildExtractJobURLsScript() string {
	script, err := loadTypeScript("extract_job_urls.ts")
	if err != nil {
		return `Array.from(document.querySelectorAll('a[href*="/jobs/view/"]')).map(a => a.href.split('?')[0])`
	}
	return script
}

// buildExpandDescriptionScript builds script for expanding job descriptions
func (s *LinkedInScraper) buildExpandDescriptionScript() string {
	utilsScriptContent, err := loadScript(utilsScript)
	if err != nil {
		return `document.querySelector('.show-more-less-html__button')?.click() || false`
	}
	
	script, err := loadTypeScript("expand_description.ts")
	if err != nil {
		return `document.querySelector('.show-more-less-html__button')?.click() || false`
	}
	
	return utilsScriptContent + "\n" + script
}

// buildClickInsightsScript builds script for clicking insights button
func (s *LinkedInScraper) buildClickInsightsScript() string {
	utilsScriptContent, err := loadScript(utilsScript)
	if err != nil {
		return `false` // Fallback returns false if no insights button found
	}
	
	script, err := loadTypeScript("click_insights.ts")
	if err != nil {
		return `false` // Fallback returns false if no insights button found
	}
	
	return utilsScriptContent + "\n" + script
}

// buildIsLoggedInScript builds script for checking login status
func (s *LinkedInScraper) buildIsLoggedInScript() string {
	utilsScriptContent, err := loadScript(utilsScript)
	if err != nil {
		return `document.querySelector('input[name="session_key"]') === null`
	}
	return utilsScriptContent + `
		Utils.isLoggedIn();`
}

// buildHasLoginFormScript builds script for checking if page has login form
func (s *LinkedInScraper) buildHasLoginFormScript() string {
	utilsScriptContent, err := loadScript(utilsScript)
	if err != nil {
		return `document.querySelector('input[name="session_key"]') !== null`
	}
	return utilsScriptContent + `
		Utils.hasLoginForm();`
}

// buildScrollToBottomScript builds script for scrolling to bottom
func (s *LinkedInScraper) buildScrollToBottomScript() string {
	utilsScriptContent, err := loadScript(utilsScript)
	if err != nil {
		return `window.scrollTo(0, document.body.scrollHeight);`
	}
	return utilsScriptContent + `
		Utils.scrollToBottom();`
}

// buildScrollToTopScript builds script for scrolling to top
func (s *LinkedInScraper) buildScrollToTopScript() string {
	utilsScriptContent, err := loadScript(utilsScript)
	if err != nil {
		return `window.scrollTo(0, 0);`
	}
	return utilsScriptContent + `
		Utils.scrollToTop();`
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
