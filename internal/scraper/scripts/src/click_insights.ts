/// <reference path="types.ts" />
/// <reference path="utils.ts" />

// Click insights button to open skills modal
(function(): boolean {
    console.log('=== SEARCHING FOR JOB INSIGHT BUTTON ===');
    
    // Log all buttons on the page for debugging
    const allButtons = Utils.safeQueryAll<HTMLButtonElement>('button');
    console.log('Total buttons found:', allButtons ? allButtons.length : 0);
    
    // Log first 10 buttons for analysis
    if (allButtons) {
        for (let i = 0; i < Math.min(10, allButtons.length); i++) {
            const btn = allButtons[i];
            const buttonInfo: ButtonInfo = {
                text: btn.innerText ?? 'No text',
                ariaLabel: btn.getAttribute('aria-label') ?? 'No aria-label',
                className: btn.className ?? 'No class',
                id: btn.id || undefined
            };
            console.log(`Button ${i}:`, buttonInfo);
        }
    }
    
    // Enhanced insight button selectors
    const insightSelectors: string[] = [
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
        '.job-details-jobs-unified-top-card__insights button'
    ];
    
    console.log('Trying specific insight selectors...');
    for (const selector of insightSelectors) {
        try {
            const buttons = Utils.safeQueryAll<HTMLButtonElement>(selector);
            console.log('Selector', selector, 'found', buttons ? buttons.length : 0, 'buttons');
            
            if (buttons) {
                for (const button of buttons) {
                    if (button.offsetParent !== null) { // Check if button is visible
                        const buttonInfo: ButtonInfo = {
                            text: button.innerText ?? 'No text',
                            ariaLabel: button.getAttribute('aria-label') ?? 'No aria-label',
                            className: button.className
                        };
                        console.log('Found visible job insight button:', buttonInfo);
                        button.click();
                        console.log('✅ Clicked insight button, waiting for modal...');
                        return true;
                    }
                }
            }
        } catch (e) {
            console.log('Error with selector', selector, ':', e);
        }
    }
    
    // More comprehensive generic button search
    console.log('Trying comprehensive generic button search...');
    const genericButtons = Utils.safeQueryAll<HTMLButtonElement>('button');
    
    if (genericButtons) {
        for (const button of genericButtons) {
            if (button.offsetParent === null) continue; // Skip hidden buttons
            
            const text = (button.innerText ?? '').toLowerCase();
            const ariaLabel = (button.getAttribute('aria-label') ?? '').toLowerCase();
            const className = (button.className ?? '').toLowerCase();
            
            // Check for skill/insight related terms
            const skillTerms: string[] = [
                'kompetenc', 'skill', 'kvalifik', 'færdighed', 'insight', 
                'se dine', 'view your', 'match', 'profil', 'profile'
            ];
            
            const hasSkillTerm = skillTerms.some(term => 
                text.includes(term) || ariaLabel.includes(term) || className.includes(term)
            );
            
            if (hasSkillTerm) {
                const buttonInfo: ButtonInfo = {
                    text: button.innerText ?? 'No text',
                    ariaLabel: button.getAttribute('aria-label') ?? 'No aria-label',
                    className: button.className
                };
                console.log('Found potential insight button:', buttonInfo);
                button.click();
                console.log('✅ Clicked potential insight button, waiting for modal...');
                return true;
            }
        }
    }
    
    console.log('❌ No job insight button found after comprehensive search');
    
    // Final fallback: log structure for debugging
    console.log('=== PAGE STRUCTURE DEBUG ===');
    const topCard = Utils.safeQuery('.job-details-jobs-unified-top-card');
    if (topCard) {
        console.log('Found top card element');
        const topCardButtons = Utils.safeQueryAll<HTMLButtonElement>('button', topCard);
        console.log('Buttons in top card:', topCardButtons ? topCardButtons.length : 0);
        
        if (topCardButtons) {
            for (const btn of topCardButtons) {
                const buttonInfo: ButtonInfo = {
                    text: btn.innerText ?? 'No text',
                    ariaLabel: btn.getAttribute('aria-label') ?? 'No aria-label',
                    className: btn.className
                };
                console.log('Top card button:', buttonInfo);
            }
        }
    } else {
        console.log('No top card found');
    }
    
    return false;
})();
