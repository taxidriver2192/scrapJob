/// <reference path="types.ts" />

// Detailed page analysis for troubleshooting
(function(): PageAnalysis {
    return {
        url: window.location.href,
        title: document.title,
        bodyClasses: document.body ? document.body.className : 'no body',
        mainFound: document.querySelector('main') !== null,
        jobLinksCount: document.querySelectorAll('a[href*="/jobs/view/"]').length,
        hasLoginForm: document.querySelector('input[name="session_key"]') !== null,
        pageText: document.body ? document.body.innerText.substring(0, 500) : 'no body text'
    };
})();
