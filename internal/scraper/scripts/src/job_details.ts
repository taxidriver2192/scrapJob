/// <reference path="types.ts" />
/// <reference path="utils.ts" />

// LinkedIn job details extraction functions
console.log('Loading job details extraction functions...');

// Debug function to inspect the DOM
const debugDOM = function(): void {
    console.log('=== DOM DEBUG INSPECTION ===');
    console.log('Current URL:', window.location.href);
    console.log('Page title:', document.title);
    console.log('Document ready state:', document.readyState);
    
    // Check for job description containers
    const jobDescriptionContainers = document.querySelectorAll('[class*="job"], [class*="description"]');
    console.log('Total job/description elements:', jobDescriptionContainers.length);
    
    // Show top 20 elements with their classes and IDs
    for (let i = 0; i < Math.min(jobDescriptionContainers.length, 20); i++) {
        const elem = jobDescriptionContainers[i];
        console.log(`Element ${i + 1}:`, {
            tag: elem.tagName,
            id: elem.id || 'no-id',
            className: elem.className,
            textLength: elem.textContent?.length || 0,
            textPreview: elem.textContent?.substring(0, 100) + '...'
        });
    }
    
    // Specifically look for job-details ID
    const jobDetailsById = document.getElementById('job-details');
    if (jobDetailsById) {
        console.log('✅ Found #job-details element:', {
            tag: jobDetailsById.tagName,
            className: jobDetailsById.className,
            textLength: jobDetailsById.textContent?.length || 0,
            innerHTML: jobDetailsById.innerHTML.substring(0, 500) + '...'
        });
    } else {
        console.log('❌ No element with id="job-details" found');
    }
    
    // Look for jobs-box__html-content
    const jobsBoxContent = document.querySelector('.jobs-box__html-content');
    if (jobsBoxContent) {
        console.log('✅ Found .jobs-box__html-content element:', {
            tag: jobsBoxContent.tagName,
            id: jobsBoxContent.id || 'no-id',
            className: jobsBoxContent.className,
            textLength: jobsBoxContent.textContent?.length || 0,
            innerHTML: jobsBoxContent.innerHTML.substring(0, 500) + '...'
        });
    } else {
        console.log('❌ No element with class="jobs-box__html-content" found');
    }
    
    console.log('=== END DOM DEBUG ===');
};

// Job Title extraction
const getTitleText = function(): string {
    const selectors = [
        'h1.topcard__title',
        'div[data-job-id] .job-details-headline__title',
        'h1.t-24.t-bold.inline',
        '.job-details-jobs-unified-top-card__job-title h1'
    ];
    for (const sel of selectors) {
        const elem = Utils.safeQuery<HTMLElement>(sel);
        if (elem && elem.innerText) {
            console.log('Found title with selector:', sel, 'text:', elem.innerText.trim());
            return elem.innerText.trim();
        }
    }
    console.log('No title found');
    return '';
};

// Company Name extraction
const getCompanyText = function(): string {
    const selectors = [
        'a.topcard__org-name-link',
        'span.topcard__flavor-row > a',
        '.job-details-jobs-unified-top-card__company-name a',
        '.job-details-jobs-unified-top-card__company-name'
    ];
    for (const sel of selectors) {
        const elem = Utils.safeQuery<HTMLElement>(sel);
        if (elem && elem.innerText) {
            console.log('Found company with selector:', sel, 'text:', elem.innerText.trim());
            return elem.innerText.trim();
        }
    }
    console.log('No company found');
    return '';
};

// Location extraction
const getLocationData = function(): string {
    const selectors = [
        'span.topcard__flavor-row--bullet',
        'span[aria-label^="Location"]',
        '.job-details-jobs-unified-top-card__primary-description-container .t-black--light',
        '.topcard__flavor-row .topcard__flavor--bullet',
        '.job-details-jobs-unified-top-card__primary-description-container span',
        '.topcard__flavor-row span',
        '.job-details-jobs-unified-top-card__primary-description .t-black--light'
    ];
    
    console.log('=== SEARCHING FOR LOCATION ===');
    console.log('Total selectors to try:', selectors.length);
    
    for (let i = 0; i < selectors.length; i++) {
        const sel = selectors[i];
        console.log(`[${i + 1}/${selectors.length}] Trying selector: ${sel}`);
        const elem = Utils.safeQuery<HTMLElement>(sel);
        
        if (elem) {
            console.log('✅ Element found with selector:', sel);
            console.log('Element HTML:', elem.outerHTML.substring(0, 200) + '...');
            
            if (elem.innerText) {
                const text = elem.innerText.trim();
                console.log('Element text:', text);
                
                // Check if this contains location + time + applicants info
                if (text.includes('·') && (text.includes('siden') || text.includes('ago') || text.includes('ansøgere') || text.includes('applicants'))) {
                    console.log('✅ Found full location data:', text);
                    return text;
                }
                
                // Filter out non-location text but keep substantial location info
                if (!text.includes('employees') && !text.includes('followers') && text.length > 2) {
                    console.log('✅ Found basic location with selector:', sel, 'text:', text);
                    return text;
                }
            } else {
                console.log('❌ Element has no innerText');
            }
        } else {
            console.log('❌ Element not found for selector:', sel);
        }
    }
    
    console.log('❌ No location data found');
    return '';
};

// Description extraction
console.log('✅ Description function is being defined!');
const getDescriptionText = function(): string {
    console.log('✅ getDescriptionText function called!');
    
    const selectors = [
        'div#job-details',
        '.jobs-box__html-content#job-details',
        '.jobs-box__html-content',
        'div[data-job-id] .description',
        '.job-description',
        '.job-details-description',
        '.job-details-jobs-unified-top-card__job-description',
        '.jobs-description__container',
        '.jobs-description-content',
        '.jobs-description-content__text',
        '.show-more-less-html__markup',
        '.jobs-box__html-content .show-more-less-html__markup',
        '.jobs-description .show-more-less-html__markup',
        '.jobs-description-content .show-more-less-html__markup',
        '[data-testid="job-description"]',
        '[data-testid="job-details-description"]',
        '.jobs-unified-top-card__job-description',
        '.jobs-description'
    ];
    
    console.log('Total selectors to try:', selectors.length);
    
    for (let i = 0; i < selectors.length; i++) {
        const sel = selectors[i];
        console.log(`[${i + 1}/${selectors.length}] Trying selector: ${sel}`);
        const elem = Utils.safeQuery<HTMLElement>(sel);
        
        if (elem) {
            console.log('✅ Element found with selector:', sel);
            
            if (elem.textContent) {
                const text = elem.textContent.trim();
                console.log('Element text length:', text.length);
                console.log('Element text preview:', text.substring(0, 200) + '...');
                
                if (text.length > 50) {
                    console.log('✅ Found description with selector:', sel);
                    return text;
                }
            }
        } else {
            console.log('❌ Element not found for selector:', sel);
        }
    }
    
    console.log('❌ No description found with any selector');
    return '';
};

// Apply URL extraction
const getApplyUrl = function(): string {
    const selectors = [
        'a.apply-button',
        'a[data-tracking-control-name="public_jobs_apply_action"]',
        '.job-details-jobs-unified-top-card__container--two-pane a[href*="apply"]',
        'a[href*="/jobs/view/"][href*="apply"]'
    ];
    for (const sel of selectors) {
        const elem = Utils.safeQuery<HTMLAnchorElement>(sel);
        if (elem && elem.href) {
            console.log('Found apply URL with selector:', sel, 'url:', elem.href);
            return elem.href;
        }
    }
    // Fallback to current URL if no specific apply URL found
    console.log('No apply URL found, using current URL');
    return window.location.href;
};

// Posted Date extraction
const getPostedDate = function(): string {
    const selectors = [
        'span.posted-date',
        'div.posted-time > time',
        '.job-details-jobs-unified-top-card__primary-description time',
        'time[datetime]'
    ];
    for (const sel of selectors) {
        const elem = Utils.safeQuery<HTMLTimeElement>(sel);
        if (elem) {
            // Try datetime attribute first
            const datetime = elem.getAttribute('datetime') || elem.innerText;
            if (datetime) {
                console.log('Found posted date with selector:', sel, 'date:', datetime);
                return datetime.trim();
            }
        }
    }
    console.log('No posted date found');
    return '';
};
