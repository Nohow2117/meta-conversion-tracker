/**
 * Meta Conversion Tracker - Landing Page JavaScript
 * 
 * This script automatically captures UTM parameters, FBCLID, and browser fingerprints
 * and sends them to your WordPress API when a conversion happens.
 * 
 * Usage:
 * <script src="path/to/tracker.js"></script>
 * <script>
 *   const tracker = new MCTTracker({
 *     apiUrl: 'https://yoursite.com/wp-json/mct/v1/track',
 *     apiKey: 'your-api-key'
 *   });
 *   
 *   // Track conversion
 *   tracker.track({ platform: 'discord' });
 * </script>
 */

class MCTTracker {
    constructor(config) {
        this.config = {
            apiUrl: config.apiUrl || '',
            apiKey: config.apiKey || '',
            autoCapture: config.autoCapture !== false, // default true
            debug: config.debug || false
        };
        
        if (!this.config.apiUrl) {
            console.error('MCTTracker: apiUrl is required');
            return;
        }
        
        // Auto-capture on init
        if (this.config.autoCapture) {
            this.capturedData = this.captureData();
        }
    }
    
    /**
     * Capture all tracking data
     */
    captureData() {
        const data = {
            // UTM parameters
            ...this.getUTMParameters(),
            
            // Facebook parameters
            ...this.getFacebookParameters(),
            
            // Page info
            landing_page: window.location.href,
            referrer: document.referrer || null,
            
            // User agent
            user_agent: navigator.userAgent,
            
            // Browser fingerprint
            fingerprint: this.generateFingerprint(),
            browser_fingerprint: this.getBrowserFingerprint()
        };
        
        if (this.config.debug) {
            console.log('MCTTracker: Captured data', data);
        }
        
        return data;
    }
    
    /**
     * Get UTM parameters from URL
     */
    getUTMParameters() {
        const params = new URLSearchParams(window.location.search);
        const utm = {};
        
        const utmParams = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
        
        utmParams.forEach(param => {
            const value = params.get(param);
            if (value) {
                utm[param] = value;
            }
        });
        
        // Also check localStorage for persisted UTM
        if (Object.keys(utm).length === 0) {
            const stored = this.getStoredUTM();
            if (stored) {
                return stored;
            }
        } else {
            // Store UTM for future use
            this.storeUTM(utm);
        }
        
        return utm;
    }
    
    /**
     * Get Facebook parameters
     */
    getFacebookParameters() {
        const params = new URLSearchParams(window.location.search);
        const fb = {};
        
        // Get fbclid from URL
        const fbclid = params.get('fbclid');
        if (fbclid) {
            fb.fbclid = fbclid;
            // Store for future use
            localStorage.setItem('mct_fbclid', fbclid);
        } else {
            // Try to get from storage
            const stored = localStorage.getItem('mct_fbclid');
            if (stored) {
                fb.fbclid = stored;
            }
        }
        
        // Get _fbc cookie (Facebook Click ID cookie)
        const fbc = this.getCookie('_fbc');
        if (fbc) {
            fb.fbc = fbc;
        } else if (fbclid) {
            // Generate fbc if we have fbclid
            fb.fbc = 'fb.1.' + Date.now() + '.' + fbclid;
        }
        
        // Get _fbp cookie (Facebook Browser ID cookie)
        const fbp = this.getCookie('_fbp');
        if (fbp) {
            fb.fbp = fbp;
        }
        
        return fb;
    }
    
    /**
     * Generate simple fingerprint hash
     */
    generateFingerprint() {
        const components = [
            navigator.userAgent,
            navigator.language,
            screen.width + 'x' + screen.height,
            screen.colorDepth,
            new Date().getTimezoneOffset(),
            !!window.sessionStorage,
            !!window.localStorage
        ];
        
        const fingerprint = components.join('|');
        return this.simpleHash(fingerprint);
    }
    
    /**
     * Get detailed browser fingerprint
     */
    getBrowserFingerprint() {
        return {
            screen: screen.width + 'x' + screen.height,
            screen_depth: screen.colorDepth,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            timezone_offset: new Date().getTimezoneOffset(),
            language: navigator.language,
            languages: navigator.languages ? navigator.languages.join(',') : navigator.language,
            platform: navigator.platform,
            hardware_concurrency: navigator.hardwareConcurrency || null,
            device_memory: navigator.deviceMemory || null,
            cookies_enabled: navigator.cookieEnabled,
            do_not_track: navigator.doNotTrack || null,
            canvas: this.getCanvasFingerprint(),
            webgl: this.getWebGLFingerprint()
        };
    }
    
    /**
     * Canvas fingerprinting
     */
    getCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const text = 'MCT Fingerprint 🔒';
            
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.textBaseline = 'alphabetic';
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.fillText(text, 2, 15);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText(text, 4, 17);
            
            const dataURL = canvas.toDataURL();
            return this.simpleHash(dataURL);
        } catch (e) {
            return null;
        }
    }
    
    /**
     * WebGL fingerprinting
     */
    getWebGLFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            
            if (!gl) return null;
            
            const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
            if (!debugInfo) return null;
            
            const vendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
            const renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
            
            return this.simpleHash(vendor + '|' + renderer);
        } catch (e) {
            return null;
        }
    }
    
    /**
     * Simple hash function
     */
    simpleHash(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return Math.abs(hash).toString(36);
    }
    
    /**
     * Get cookie value
     */
    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) {
            return parts.pop().split(';').shift();
        }
        return null;
    }
    
    /**
     * Store UTM parameters
     */
    storeUTM(utm) {
        try {
            localStorage.setItem('mct_utm', JSON.stringify(utm));
            localStorage.setItem('mct_utm_timestamp', Date.now().toString());
        } catch (e) {
            // localStorage not available
        }
    }
    
    /**
     * Get stored UTM parameters (if less than 30 days old)
     */
    getStoredUTM() {
        try {
            const stored = localStorage.getItem('mct_utm');
            const timestamp = localStorage.getItem('mct_utm_timestamp');
            
            if (!stored || !timestamp) return null;
            
            // Check if less than 30 days old
            const age = Date.now() - parseInt(timestamp);
            const maxAge = 30 * 24 * 60 * 60 * 1000; // 30 days
            
            if (age < maxAge) {
                return JSON.parse(stored);
            }
        } catch (e) {
            // Error parsing
        }
        
        return null;
    }
    
    /**
     * Track conversion
     */
    async track(additionalData = {}) {
        const data = {
            ...this.capturedData,
            ...additionalData
        };
        
        if (this.config.debug) {
            console.log('MCTTracker: Sending data', data);
        }
        
        try {
            const response = await fetch(this.config.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': this.config.apiKey
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (this.config.debug) {
                console.log('MCTTracker: Response', result);
            }
            
            if (result.success) {
                // Store conversion ID
                localStorage.setItem('mct_last_conversion_id', result.conversion_id);
                return result;
            } else {
                throw new Error(result.message || 'Tracking failed');
            }
        } catch (error) {
            console.error('MCTTracker: Error', error);
            throw error;
        }
    }
    
    /**
     * Track and redirect
     */
    async trackAndRedirect(url, additionalData = {}) {
        try {
            await this.track(additionalData);
            window.location.href = url;
        } catch (error) {
            // Still redirect even if tracking fails
            console.error('MCTTracker: Tracking failed, redirecting anyway', error);
            window.location.href = url;
        }
    }
}

// Make available globally
window.MCTTracker = MCTTracker;
