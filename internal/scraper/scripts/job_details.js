// job_details.js - LinkedIn job details extraction
(function() {
    console.log('Scraping job details from:', window.location.href);
    
    // Job Title
    const getTitleText = function() {
        const selectors = [
            'h1.topcard__title',
            'div[data-job-id] .job-details-headline__title',
            'h1.t-24.t-bold.inline',
            '.job-details-jobs-unified-top-card__job-title h1'
        ];
        for (const sel of selectors) {
            const elem = document.querySelector(sel);
            if (elem && elem.innerText) {
                console.log('Found title with selector:', sel, 'text:', elem.innerText.trim());
                return elem.innerText.trim();
            }
        }
        console.log('No title found');
        return '';
    };
    
    // Company Name
    const getCompanyText = function() {
        const selectors = [
            'a.topcard__org-name-link',
            'span.topcard__flavor-row > a',
            '.job-details-jobs-unified-top-card__company-name a',
            '.job-details-jobs-unified-top-card__company-name'
        ];
        for (const sel of selectors) {
            const elem = document.querySelector(sel);
            if (elem && elem.innerText) {
                console.log('Found company with selector:', sel, 'text:', elem.innerText.trim());
                return elem.innerText.trim();
            }
        }
        console.log('No company found');
        return '';
    };
    
    // Location with applicants and posted date parsing
    const getLocationData = function() {
        const selectors = [
            'span.topcard__flavor-row--bullet',
            'span[aria-label^="Location"]',
            '.job-details-jobs-unified-top-card__primary-description-container .t-black--light',
            '.topcard__flavor-row .topcard__flavor--bullet',
            '.job-details-jobs-unified-top-card__primary-description-container span',
            '.topcard__flavor-row span'
        ];
        
        for (const sel of selectors) {
            const elem = document.querySelector(sel);
            if (elem && elem.innerText) {
                const text = elem.innerText.trim();
                console.log('Found location element with selector:', sel, 'text:', text);
                
                // Check if this contains location + time + applicants info
                if (text.includes('·') && (text.includes('siden') || text.includes('ago') || text.includes('ansøgere') || text.includes('applicants'))) {
                    console.log('✅ Found full location data:', text);
                    return text;
                }
                
                // Filter out non-location text but keep substantial location info
                if (!text.includes('employees') && !text.includes('followers') && text.length > 2) {
                    console.log('Found basic location with selector:', sel, 'text:', text);
                    // Keep searching for better location data, but save this as fallback
                    if (!window.locationFallback) {
                        window.locationFallback = text;
                    }
                }
            }
        }
        
        console.log('No comprehensive location data found, using fallback:', window.locationFallback || '');
        return window.locationFallback || '';
    };
    
    // Description
    const getDescriptionText = function() {
        const selectors = [
            'div.description__text',
            'div#job-details > span.formatted-content',
            '.job-details-jobs-unified-top-card__job-description',
            '.jobs-description-content__text',
            '.jobs-description__content',
            '.show-more-less-html__markup',
            '.jobs-box__html-content'
        ];
        
        for (const sel of selectors) {
            const elem = document.querySelector(sel);
            if (elem && elem.innerText && elem.innerText.trim().length > 50) {
                console.log('✅ Found description with selector:', sel, 'length:', elem.innerText.length);
                return elem.innerText.trim();
            }
        }
        
        console.log('❌ No description found');
        return '';
    };
    
    // Apply URL
    const getApplyUrl = function() {
        const selectors = [
            'a.apply-button',
            'a[data-tracking-control-name="public_jobs_apply_action"]',
            '.job-details-jobs-unified-top-card__container--two-pane a[href*="apply"]'
        ];
        for (const sel of selectors) {
            const elem = document.querySelector(sel);
            if (elem && elem.href) {
                console.log('Found apply URL with selector:', sel, 'url:', elem.href);
                return elem.href;
            }
        }
        // Fallback to current URL if no specific apply URL found
        console.log('No apply URL found, using current URL');
        return window.location.href;
    };
    
    return {
        title: getTitleText(),
        company: getCompanyText(),
        location: getLocationData(),
        description: getDescriptionText(),
        applyUrl: getApplyUrl()
    };
})();
