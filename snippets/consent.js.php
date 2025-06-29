<?php
/**
 * Cookie consent initialization JavaScript
 * This snippet generates the JavaScript code that initializes the cookie consent library
 * with configuration from the Kirby panel and proper error handling
 * 
 * @var \Kirby\Cms\Page $page The Crumble page instance
 * @var \Kirby\Cms\Plugin $plugin The Crumble plugin instance
 */

$jsUrl = $plugin->asset('cookieconsent.esm.js')->url();
$config = $page->config();
?>
import * as CookieConsent from '<?= $jsUrl ?>';

// initialize cookie consent with embedded configuration
(async function() {
    // embedded configuration from server
    const config = <?= json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    
    // helper function to track consent
    async function trackConsent(consentData) {
        try {
            const response = await fetch('/crumble/consent', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(consentData)
            });
            
            if (!response.ok) {
                throw new Error(`Consent tracking failed: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Failed to track consent:', error);
            // invalidate consent on tracking failure
            CookieConsent.reset();
            CookieConsent.show();
            
            throw error; // re-throw to prevent consent from being saved
        }
    }
    
    // merge configuration with consent tracking callbacks
    const finalConfig = {
        ...config,
        
        // track initial consent
        onFirstConsent: async ({ cookie }) => {
            const consentData = {
                consentId: cookie.value,
                acceptedCategories: cookie.categories,
                rejectedCategories: Object.keys(config.categories || {})
                    .filter(cat => !cookie.categories.includes(cat)),
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent,
                url: window.location.href
            };
            
            await trackConsent(consentData);
        },
        
        // track consent changes
        onConsent: async ({ cookie }) => {
            const consentData = {
                consentId: cookie.value,
                acceptedCategories: cookie.categories,
                rejectedCategories: Object.keys(config.categories || {})
                    .filter(cat => !cookie.categories.includes(cat)),
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent,
                url: window.location.href
            };
            
            await trackConsent(consentData);
        },
        
        // track preference changes
        onChange: async ({ cookie, changedCategories }) => {
            const consentData = {
                consentId: cookie.value,
                acceptedCategories: cookie.categories,
                rejectedCategories: Object.keys(config.categories || {})
                    .filter(cat => !cookie.categories.includes(cat)),
                changedCategories: changedCategories,
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent,
                url: window.location.href
            };
            
            await trackConsent(consentData);
        }
    };
    
    // initialize cookie consent
    CookieConsent.run(finalConfig);
    
    // expose CookieConsent to window for external usage
    window.CookieConsent = CookieConsent;
})();