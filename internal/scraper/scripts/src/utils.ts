/// <reference path="types.ts" />

// Simple utility scripts for basic operations
const Utils = {
    // Check if user is logged in by looking for login form
    isLoggedIn: (): boolean => document.querySelector('input[name="session_key"]') === null,
    
    // Check if we're on a page that has a login form
    hasLoginForm: (): boolean => document.querySelector('input[name="session_key"]') !== null,
    
    // Scroll to bottom of page
    scrollToBottom: (): void => window.scrollTo(0, document.body.scrollHeight),
    
    // Scroll to top of page  
    scrollToTop: (): void => window.scrollTo(0, 0),
    
    // Safe query selector with type checking
    safeQuery: <T extends Element = Element>(selector: string, parent?: Document | Element): T | null => {
        try {
            const context = parent || document;
            return context.querySelector<T>(selector);
        } catch (e) {
            console.warn(`Query selector failed: ${selector}`, e);
            return null;
        }
    },
    
    // Safe query selector all with type checking
    safeQueryAll: <T extends Element = Element>(selector: string, parent?: Document | Element): NodeListOf<T> | null => {
        try {
            const context = parent || document;
            return context.querySelectorAll<T>(selector);
        } catch (e) {
            console.warn(`Query selector all failed: ${selector}`, e);
            return null;
        }
    }
};
