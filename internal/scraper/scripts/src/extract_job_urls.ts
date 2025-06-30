/// <reference path="types.ts" />
/// <reference path="utils.ts" />

// Extract job URLs from search results page
(function(): string[] {
    console.log('=== EXTRACTING JOB URLs ===');
    
    // Try multiple selectors for job links
    const linkSelectors: string[] = [
        'a[href*="/jobs/view/"]',
        '[data-occludable-job-id] a',
        '.job-search-card a[href*="/jobs/view/"]',
        '.result-card a[href*="/jobs/view/"]',
        '[data-tracking-control-name*="job-result-card"] a'
    ];
    
    const allLinks: string[] = [];
    
    for (const selector of linkSelectors) {
        const links = Utils.safeQueryAll<HTMLAnchorElement>(selector);
        console.log('Selector', selector, 'found', links ? links.length : 0, 'links');
        
        if (links) {
            for (const link of links) {
                if (link.href?.includes('/jobs/view/')) {
                    // Clean the URL - remove any tracking parameters and fragments
                    const cleanURL: string = link.href.split('?')[0].split('#')[0];
                    if (!allLinks.includes(cleanURL)) {
                        allLinks.push(cleanURL);
                        console.log('Found job URL:', cleanURL);
                    }
                }
            }
        }
    }
    
    console.log('Total unique job URLs extracted:', allLinks.length);
    return allLinks;
})();
