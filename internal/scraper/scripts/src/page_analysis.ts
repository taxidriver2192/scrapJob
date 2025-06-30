/// <reference path="types.ts" />
/// <reference path="utils.ts" />

// Job search page debugging and container detection
(function(): boolean {
    console.log('=== DEBUGGING JOB RESULTS PAGE ===');
    
    // Check various indicators that this is a job search page
    const url: string = window.location.href;
    const title: string = document.title;
    const isJobSearchPage: boolean = url.includes('/jobs/search');
    
    console.log('Current URL:', url);
    console.log('Page title:', title);
    console.log('Is job search page:', isJobSearchPage);
    
    // Check if logged in
    const hasUserMenu: boolean = Utils.safeQuery('[data-tracking-control-name*="nav.feed"]') !== null;
    console.log('Appears to be logged in:', hasUserMenu);
    
    console.log('=== END DEBUGGING ===');
    
    // Look for job results container
    const containerSelectors: string[] = [
        '.jobs-search-results-list',
        '.jobs-search__results-list',
        '[data-total-results]',
        '.search-results-container',
        'ul.jobs-search__results-list'
    ];
    
    let foundContainer: boolean = false;
    for (const selector of containerSelectors) {
        const container = Utils.safeQuery(selector);
        if (container) {
            console.log('Found job results container with selector:', selector);
            foundContainer = true;
            break;
        }
    }
    
    // Count total job links as backup
    const jobLinks = Utils.safeQueryAll('a[href*="/jobs/view/"]');
    const totalJobLinks: number = jobLinks ? jobLinks.length : 0;
    console.log('Total job links found:', totalJobLinks);
    
    return foundContainer || totalJobLinks > 0;
})();
