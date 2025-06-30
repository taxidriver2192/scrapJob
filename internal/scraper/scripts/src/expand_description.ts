/// <reference path="types.ts" />
/// <reference path="utils.ts" />

// Expand job description by clicking "Show more" buttons
(function(): boolean {
    const showMoreButtons = Utils.safeQueryAll<HTMLButtonElement>('button[aria-expanded="false"]');
    
    if (showMoreButtons) {
        for (const button of showMoreButtons) {
            const buttonText = button.innerText || '';
            if (buttonText.includes('Show more') || buttonText.includes('Se mere')) {
                console.log('Clicking show more button:', buttonText);
                button.click();
                return true;
            }
        }
    }
    
    // Also try alternative selectors for show more
    const moreButtons = Utils.safeQueryAll<HTMLButtonElement>(
        '.jobs-description-content__toggle, .show-more-less-html__button, [data-tracking-control-name*="show_more"]'
    );
    
    if (moreButtons) {
        for (const button of moreButtons) {
            console.log('Clicking description toggle button');
            button.click();
            return true;
        }
    }
    
    return false;
})();
