# 🚀 Beacon Endpoint - Quick Start

## 📋 Cos'è?
Un endpoint che traccia **TUTTI** i completamenti captcha, anche se il tracker principale fallisce. Perfetto per Meta WebView!

---

## 🎯 URL Endpoint
```
POST https://play.warcry-mmorpg.online/wp-json/mct/v1/beacon
```

---

## 💻 Codice Minimo (Copia/Incolla)

```javascript
// STEP 1: Aggiungi questa funzione al tuo codice
const BEACON_URL = 'https://play.warcry-mmorpg.online/wp-json/mct/v1/beacon';

// Estrae parametri UTM dall'URL
function getUtmParams() {
    const params = new URLSearchParams(window.location.search);
    return {
        utm_source: params.get('utm_source') || '',
        utm_medium: params.get('utm_medium') || '',
        utm_campaign: params.get('utm_campaign') || '',
        utm_content: params.get('utm_content') || '',
        utm_term: params.get('utm_term') || ''
    };
}

function sendBeacon(platform) {
    const utmParams = getUtmParams();
    
    const data = {
        action: 'wc_captcha_completed',
        platform: platform,
        timestamp: Date.now(),
        user_agent: navigator.userAgent,
        referrer: document.referrer || 'direct',
        page_url: window.location.href,
        utm_source: utmParams.utm_source,
        utm_medium: utmParams.utm_medium,
        utm_campaign: utmParams.utm_campaign,
        utm_content: utmParams.utm_content,
        utm_term: utmParams.utm_term
    };

    if (navigator.sendBeacon) {
        const formData = new FormData();
        Object.keys(data).forEach(key => formData.append(key, data[key]));
        navigator.sendBeacon(BEACON_URL, formData);
    } else {
        fetch(BEACON_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
            keepalive: true
        });
    }
    console.log('[Beacon] Inviato per', platform);
}

// STEP 2: Chiama quando l'utente completa il captcha
sendBeacon('discord');  // o 'telegram', 'web', 'other'
```

---

## 📝 Parametri Richiesti

| Campo | Valore | Note |
|-------|--------|------|
| `action` | `wc_captcha_completed` | Sempre questo |
| `platform` | `discord` / `telegram` / `web` / `other` | La tua piattaforma |
| `timestamp` | `Date.now()` | Timestamp in millisecondi |

**Parametri opzionali**: `user_agent`, `referrer`, `page_url`, `fingerprint`, `custom_data`, `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`

**Note sui parametri UTM**: Vengono estratti automaticamente dall'URL (es. `?utm_source=facebook&utm_campaign=summer2024`) se usi la funzione `getUtmParams()`

---

## 🔧 Integrazioni Comuni

### Turnstile (Cloudflare)
```html
<div class="cf-turnstile" data-callback="onCaptchaDone"></div>
<script>
function onCaptchaDone() {
    sendBeacon('web');
    // ... resto del tuo codice
}
</script>
```

### hCaptcha
```html
<div class="h-captcha" data-callback="onCaptchaDone"></div>
<script>
function onCaptchaDone() {
    sendBeacon('web');
    // ... resto del tuo codice
}
</script>
```

### Discord Bot
```javascript
// Dopo che l'utente completa il captcha nel tuo bot
sendBeacon('discord');
```

### Telegram Bot
```javascript
// Dopo che l'utente completa il captcha nel tuo bot
sendBeacon('telegram');
```

---

## 📊 Monitoraggio

### 🎨 WordPress Admin Dashboard (CONSIGLIATO)

La via più semplice per visualizzare i beacon:

1. Vai su **WordPress Admin → Conversion Tracker → Beacon Log**
2. Visualizza:
   - 📊 Statistiche: Total Beacons, Unique IPs, Unique Fingerprints, Success Rate
   - 📋 Tabella beacon con tutti i dettagli (ID, Date/Time, Platform, Country 🇮🇹, Page URL, UTM Campaign, IP Address)
   - 🔍 Filtri per piattaforma, azione, date
   - ⚠️ Alert automatico se success rate < 80%
   - 📄 Paginazione (20 per pagina)
   - 📝 Modal "View Details" con tutte le informazioni complete (country, page_url, tutti i 5 parametri UTM, referrer, fingerprint, custom data)

### 🔌 API Endpoints (Alternativa)

```bash
# Statistiche Beacon
GET https://play.warcry-mmorpg.online/wp-json/mct/v1/beacon/stats

# Confronto Beacon vs Conversioni
GET https://play.warcry-mmorpg.online/wp-json/mct/v1/beacon/compare
```

⚠️ **Nota**: Questi endpoint richiedono autenticazione admin

---

## 🧪 Test Rapido

```bash
# Test con curl (con parametri UTM)
curl -X POST https://play.warcry-mmorpg.online/wp-json/mct/v1/beacon \
  -H "Content-Type: application/json" \
  -d '{
    "action": "wc_captcha_completed",
    "platform": "discord",
    "timestamp": 1699534567890,
    "user_agent": "Test",
    "referrer": "direct",
    "page_url": "https://play.warcry-mmorpg.online/landing",
    "utm_source": "facebook",
    "utm_campaign": "test2024"
  }'

# Risposta attesa:
# {"success":true,"message":"Beacon logged","beacon_id":123}
```

---

## 🔍 Verifica Database

```sql
-- Vedi gli ultimi 10 beacon
SELECT * FROM wp_mct_beacon_log 
ORDER BY created_at DESC 
LIMIT 10;

-- Conta beacon di oggi per piattaforma
SELECT platform, COUNT(*) as total
FROM wp_mct_beacon_log
WHERE DATE(created_at) = CURDATE()
GROUP BY platform;
```

---

## ⚡ Best Practices

1. ✅ **Chiama sendBeacon() PRIMA** del tracking normale
2. ✅ **Usa sempre `navigator.sendBeacon`** (fallback a fetch)
3. ✅ **NON aspettare la risposta** (fire-and-forget)
4. ✅ **Includi fingerprint** se possibile per deduplicare
5. ✅ **Monitora success_rate** settimanalmente

---

## 🎯 Ordine Corretto di Chiamata

```javascript
// ✅ GIUSTO - Beacon prima, tracking dopo
function onCaptchaComplete() {
    sendBeacon('discord');           // 1. Beacon (garantito)
    setTimeout(() => {
        trackConversion();           // 2. Tracking normale (può fallire)
    }, 100);
}

// ❌ SBAGLIATO - Tracking prima
function onCaptchaComplete() {
    trackConversion();               // Se fallisce, perdi tutto!
    sendBeacon('discord');
}
```

---

## 📦 File da Scaricare

- **Esempio completo**: [`examples/beacon-example.js`](../examples/beacon-example.js)
- **Documentazione full**: [`docs/BEACON-API.md`](./BEACON-API.md)

---

## ❓ FAQ

**Q: Devo usare API key?**  
A: NO, l'endpoint è pubblico (nessuna auth richiesta)

**Q: Funziona in Meta WebView?**  
A: SÌ, è progettato appositamente per questo

**Q: Quanto pesano i dati?**  
A: Ogni beacon ~500 bytes, cleanup automatico dopo 30 giorni

**Q: Posso usarlo senza il tracker principale?**  
A: SÌ, ma è pensato come backup/fallback

**Q: Come calcolo il success rate?**  
A: `(conversioni tracciate / beacon inviati) * 100`

---

## 🆘 Troubleshooting

**Beacon non salvati?**
```bash
# Verifica tabella
SHOW TABLES LIKE 'wp_mct_beacon_log';

# Ricrea tabella
wp plugin deactivate meta-conversion-tracker
wp plugin activate meta-conversion-tracker
```

**Success rate < 80%?**
1. Controlla CORS sul tracker principale
2. Verifica Meta WebView compatibility
3. Aumenta timeout del tracker

---

## 📞 Support

- Documentazione completa: [`BEACON-API.md`](./BEACON-API.md)
- Esempi: [`../examples/beacon-example.js`](../examples/beacon-example.js)
- Main README: [`../README.md`](../README.md)

---

**✨ Fatto! Ora hai tracking garantito al 100%**
