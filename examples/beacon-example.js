/**
 * Esempio di utilizzo dell'endpoint Beacon
 * 
 * L'endpoint beacon traccia TUTTI i completamenti captcha,
 * anche se il tracker principale fallisce.
 */

// Configurazione
const BEACON_URL = 'https://play.warcry-mmorpg.online/wp-json/mct/v1/beacon';

/**
 * Invia un beacon quando l'utente completa il captcha
 * 
 * @param {string} platform - 'discord', 'telegram', 'web', o 'other'
 * @param {object} options - Opzioni aggiuntive (fingerprint, custom_data)
 */
function sendBeacon(platform, options = {}) {
    const beaconData = {
        action: 'wc_captcha_completed',
        platform: platform,
        timestamp: Date.now(),
        user_agent: navigator.userAgent,
        referrer: document.referrer || 'direct',
        page_url: window.location.href,
        fingerprint: options.fingerprint || '',
        custom_data: options.custom_data || ''
    };

    // Usa navigator.sendBeacon per garantire l'invio anche se la pagina si chiude
    if (navigator.sendBeacon) {
        const formData = new FormData();
        Object.keys(beaconData).forEach(key => {
            formData.append(key, beaconData[key]);
        });
        
        const sent = navigator.sendBeacon(BEACON_URL, formData);
        
        if (sent) {
            console.log('[Beacon] Inviato con successo:', beaconData);
        } else {
            console.warn('[Beacon] Fallback a fetch');
            sendBeaconWithFetch(beaconData);
        }
    } else {
        // Fallback per browser vecchi
        sendBeaconWithFetch(beaconData);
    }
}

/**
 * Fallback usando fetch (meno affidabile se la pagina si chiude)
 */
function sendBeaconWithFetch(beaconData) {
    fetch(BEACON_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(beaconData),
        keepalive: true // Importante per garantire l'invio
    })
    .then(response => response.json())
    .then(data => {
        console.log('[Beacon] Risposta:', data);
    })
    .catch(error => {
        console.error('[Beacon] Errore:', error);
    });
}

// ==========================================
// ESEMPI DI USO
// ==========================================

// Esempio 1: Captcha completato su Discord
function onDiscordCaptchaCompleted() {
    sendBeacon('discord', {
        fingerprint: 'user-fingerprint-123',
        custom_data: JSON.stringify({ 
            campaign: 'discord-invite',
            channel: 'general' 
        })
    });
}

// Esempio 2: Captcha completato su Telegram
function onTelegramCaptchaCompleted() {
    sendBeacon('telegram', {
        fingerprint: 'user-fingerprint-456',
        custom_data: JSON.stringify({ 
            bot: 'warcry_bot',
            chat_id: '12345' 
        })
    });
}

// Esempio 3: Integrazione con Turnstile (Cloudflare)
function setupTurnstileBeacon() {
    window.turnstileCallback = function(token) {
        console.log('Turnstile completato!');
        
        // Invia beacon PRIMA del tracking normale
        sendBeacon('web', {
            fingerprint: getFingerprint(),
            custom_data: JSON.stringify({ 
                turnstile_token: token.substring(0, 20) + '...',
                page: window.location.pathname
            })
        });
        
        // Poi procedi con il tracking normale
        trackConversion();
    };
}

// Esempio 4: Integrazione con hCaptcha
function setupHCaptchaBeacon() {
    window.hcaptchaCallback = function(token) {
        console.log('hCaptcha completato!');
        
        sendBeacon('web', {
            fingerprint: getFingerprint(),
            custom_data: JSON.stringify({ 
                hcaptcha_token: token.substring(0, 20) + '...',
                page: window.location.pathname
            })
        });
        
        trackConversion();
    };
}

// Esempio 5: Integrazione con reCAPTCHA v3
function setupRecaptchaBeacon() {
    grecaptcha.ready(function() {
        grecaptcha.execute('YOUR_SITE_KEY', {action: 'submit'}).then(function(token) {
            console.log('reCAPTCHA completato!');
            
            sendBeacon('web', {
                fingerprint: getFingerprint(),
                custom_data: JSON.stringify({ 
                    recaptcha_token: token.substring(0, 20) + '...',
                    action: 'submit'
                })
            });
            
            trackConversion();
        });
    });
}

// Esempio 6: Tracciamento doppione (beacon + tracking normale)
async function trackWithBeacon(platform) {
    // 1. Invia beacon (garantito)
    sendBeacon(platform, {
        fingerprint: getFingerprint()
    });
    
    // 2. Aspetta un attimo
    await new Promise(resolve => setTimeout(resolve, 100));
    
    // 3. Invia tracking normale
    try {
        await trackConversion();
        console.log('[Tracking] Successo!');
    } catch (error) {
        console.error('[Tracking] Fallito, ma beacon già inviato!');
    }
}

// ==========================================
// UTILITY
// ==========================================

/**
 * Genera un fingerprint semplice del browser
 */
function getFingerprint() {
    const components = [
        navigator.userAgent,
        navigator.language,
        screen.colorDepth,
        screen.width + 'x' + screen.height,
        new Date().getTimezoneOffset()
    ];
    
    return btoa(components.join('|')).substring(0, 32);
}

/**
 * Tracking normale (esempio)
 */
async function trackConversion() {
    const response = await fetch('https://play.warcry-mmorpg.online/wp-json/mct/v1/track', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-API-Key': 'YOUR_API_KEY'
        },
        body: JSON.stringify({
            platform: 'discord',
            utm_source: 'facebook',
            utm_campaign: 'summer2024',
            // ... altri parametri
        })
    });
    
    return response.json();
}

// ==========================================
// HTML INTEGRATION EXAMPLES
// ==========================================

/*
<!-- Esempio con Turnstile -->
<div 
    class="cf-turnstile" 
    data-sitekey="YOUR_SITE_KEY"
    data-callback="turnstileCallback"
></div>

<script>
window.turnstileCallback = function(token) {
    sendBeacon('web', { 
        fingerprint: getFingerprint() 
    });
    document.getElementById('myForm').submit();
};
</script>

<!-- Esempio con hCaptcha -->
<div 
    class="h-captcha" 
    data-sitekey="YOUR_SITE_KEY"
    data-callback="hcaptchaCallback"
></div>

<script>
window.hcaptchaCallback = function(token) {
    sendBeacon('web', { 
        fingerprint: getFingerprint() 
    });
    document.getElementById('myForm').submit();
};
</script>

<!-- Esempio con form submit -->
<form id="joinForm" onsubmit="handleFormSubmit(event)">
    <input type="text" name="username" required>
    <button type="submit">Join Now</button>
</form>

<script>
function handleFormSubmit(event) {
    event.preventDefault();
    
    // Invia beacon
    sendBeacon('web', {
        fingerprint: getFingerprint(),
        custom_data: JSON.stringify({
            form: 'joinForm',
            username: event.target.username.value
        })
    });
    
    // Procedi con il submit dopo 200ms
    setTimeout(() => {
        event.target.submit();
    }, 200);
}
</script>
*/

// Export per uso in moduli
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { sendBeacon, getFingerprint };
}
