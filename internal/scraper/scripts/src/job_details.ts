/// <reference path="types.ts" />
/// <reference path="utils.ts" />

// LinkedIn job details extraction functions
console.log('Loading job details extraction functions...');

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
        '.topcard__flavor-row span'
    ];
    
    for (const sel of selectors) {
        const elem = Utils.safeQuery<HTMLElement>(sel);
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
                return text;
            }
        }
    }
    console.log('No location data found');
    return '';
};

// Description extraction
const getDescriptionText = function(): string {
    const selectors = [
        'div.description__text',
        'div#job-details > span.formatted-content',
        '.job-details-jobs-unified-top-card__job-description',
        '.jobs-description-content__text',
        '.jobs-description__content',
        '.job-details-jobs-unified-top-card__job-description .jobs-description-content__text',
        'section[data-max-lines] .jobs-description-content__text',
        '.jobs-description .jobs-description-content__text',
        '[data-tracking-control-name="public_jobs_description"] .jobs-description-content__text',
        '.jobs-unified-top-card__content .jobs-description__content',
        'div[data-job-id] .jobs-description-content__text',
        '.show-more-less-html__markup',
        '.jobs-box__html-content'
    ];
    
    console.log('=== SEARCHING FOR DESCRIPTION ===');
    
    for (const sel of selectors) {
        const elem = Utils.safeQuery<HTMLElement>(sel);
        if (elem && elem.innerText && elem.innerText.trim().length > 50) {
            console.log('✅ Found description with selector:', sel, 'length:', elem.innerText.length);
            return elem.innerText.trim();
        }
    }
    
    console.log('❌ No description found');
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
