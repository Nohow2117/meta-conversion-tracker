# Beacon API - Documentazione Completa

## 📋 Panoramica

L'endpoint Beacon è un sistema di tracking garantito che registra **TUTTI** i completamenti captcha, anche quando il tracker principale fallisce. È progettato per funzionare in qualsiasi ambiente, incluso Meta WebView.

## 🎯 Vantaggi

- ✅ **Tracciamento garantito**: Funziona anche se il tracker principale fallisce
- ✅ **Compatibilità WebView**: Funziona anche in Meta/Instagram/Facebook WebView
- ✅ **Nessun CORS**: Endpoint pubblico senza restrizioni
- ✅ **Analisi affidabilità**: Confronta beacon con conversioni per calcolare il tasso di successo
- ✅ **Debug facilitato**: Identifica problemi di tracking
- ✅ **Dati puliti**: Cleanup automatico dopo 30 giorni

---

## 📡 Endpoint Disponibili

### 1. POST `/wp-json/mct/v1/beacon`
**Traccia un evento captcha completato**

#### Headers
Nessun header richiesto (endpoint pubblico)

#### Parametri (POST body)

| Parametro | Tipo | Richiesto | Valori | Descrizione |
|-----------|------|-----------|--------|-------------|
| `action` | string | ✅ | `wc_captcha_completed`, `page_view`, `custom` | Tipo di azione |
| `platform` | string | ✅ | `discord`, `telegram`, `web`, `other` | Piattaforma di provenienza |
| `timestamp` | integer | ✅ | Unix timestamp (ms) | Timestamp dell'evento |
| `user_agent` | string | ❌ | - | User Agent (auto-detect se vuoto) |
| `referrer` | string | ❌ | URL | URL di provenienza (default: "direct") |
| `page_url` | string | ❌ | URL | URL della pagina dove è stato completato il captcha |
| `fingerprint` | string | ❌ | - | Fingerprint del browser |
| `custom_data` | string | ❌ | JSON string | Dati custom aggiuntivi |
| `utm_source` | string | ❌ | max 255 chars | Sorgente della campagna (es. facebook, google) |
| `utm_medium` | string | ❌ | max 255 chars | Mezzo della campagna (es. cpc, email) |
| `utm_campaign` | string | ❌ | max 255 chars | Nome della campagna |
| `utm_content` | string | ❌ | max 255 chars | Contenuto dell'annuncio |
| `utm_term` | string | ❌ | max 255 chars | Parole chiave della campagna |

#### Esempio Request

```bash
curl -X POST https://play.warcry-mmorpg.online/wp-json/mct/v1/beacon \
  -H "Content-Type: application/json" \
  -d '{
    "action": "wc_captcha_completed",
    "platform": "discord",
    "timestamp": 1699534567890,
    "user_agent": "Mozilla/5.0...",
    "referrer": "https://discord.com/invite/abc123",
    "page_url": "https://play.warcry-mmorpg.online/landing",
    "fingerprint": "fp_abc123xyz",
    "custom_data": "{\"campaign\":\"summer2024\",\"channel\":\"general\"}",
    "utm_source": "facebook",
    "utm_medium": "cpc",
    "utm_campaign": "summer2024",
    "utm_content": "banner-blue",
    "utm_term": "mmorpg"
  }'
```

#### Esempio Response

```json
{
  "success": true,
  "message": "Beacon logged",
  "beacon_id": 12345
}
```

---

### 2. GET `/wp-json/mct/v1/beacon/stats`
**Ottieni statistiche beacon** (richiede autenticazione admin)

#### Parametri Query

| Parametro | Tipo | Default | Descrizione |
|-----------|------|---------|-------------|
| `start_date` | string | -7 giorni | Data inizio (YYYY-MM-DD) |
| `end_date` | string | oggi | Data fine (YYYY-MM-DD) |
| `platform` | string | - | Filtra per piattaforma |

#### Esempio Request

```bash
curl "https://play.warcry-mmorpg.online/wp-json/mct/v1/beacon/stats?start_date=2024-01-01&end_date=2024-01-31&platform=discord"
```

#### Esempio Response

```json
{
  "success": true,
  "data": [
    {
      "date": "2024-01-15",
      "platform": "discord",
      "action": "wc_captcha_completed",
      "total": 245,
      "unique_ips": 198,
      "unique_fingerprints": 187
    }
  ],
  "totals": {
    "total_beacons": 1523,
    "unique_ips": 987,
    "unique_fingerprints": 912,
    "platforms_count": 3
  },
  "period": {
    "start": "2024-01-01",
    "end": "2024-01-31"
  }
}
```

---

### 3. GET `/wp-json/mct/v1/beacon/compare`
**Confronta beacon con conversioni** (richiede autenticazione admin)

#### Parametri Query

| Parametro | Tipo | Default | Descrizione |
|-----------|------|---------|-------------|
| `start_date` | string | -7 giorni | Data inizio (YYYY-MM-DD) |
| `end_date` | string | oggi | Data fine (YYYY-MM-DD) |

#### Esempio Request

```bash
curl "https://play.warcry-mmorpg.online/wp-json/mct/v1/beacon/compare?start_date=2024-01-01&end_date=2024-01-31"
```

#### Esempio Response

```json
{
  "success": true,
  "data": [
    {
      "date": "2024-01-15",
      "platform": "discord",
      "beacon_count": 245,
      "conversion_count": 198,
      "success_rate": 80.82
    }
  ],
  "totals": {
    "total_beacons": 1523,
    "total_conversions": 1287,
    "success_rate": 84.50,
    "alert": null
  },
  "period": {
    "start": "2024-01-01",
    "end": "2024-01-31"
  }
}
```

⚠️ **Alert**: Se `success_rate < 80%`, il campo `alert` conterrà un messaggio di avviso.

---

## 🎨 WordPress Admin Dashboard

Il modo più semplice per visualizzare e monitorare i beacon è attraverso la dashboard WordPress.

### Accesso

**WordPress Admin → Conversion Tracker → Beacon Log**

### Funzionalità

#### 📊 Statistiche in Real-Time
- **Total Beacons**: Numero totale di beacon nel periodo filtrato
- **Unique IPs**: Numero di IP unici (deduplica utenti)
- **Unique Fingerprints**: Numero di fingerprint unici (tracking più accurato)
- **Success Rate**: Percentuale conversioni/beacon
  - 🟢 Verde se ≥ 80%
  - 🔴 Rosso se < 80% con alert visivo

#### 🔍 Filtri Avanzati
- **Platform**: Discord, Telegram, Web, Other
- **Action Type**: wc_captcha_completed, page_view, custom
- **Date Range**: From/To date picker
- Pulsante **Reset** per pulire tutti i filtri

#### 📋 Tabella Beacon
Colonne visualizzate:
- **ID**: ID univoco del beacon
- **Date/Time**: Data e ora del beacon
- **Platform**: Badge colorato per piattaforma
  - 🔵 Discord (blu)
  - 🔷 Telegram (azzurro)
  - 🟢 Web (verde)
  - ⚫ Other (grigio)
- **Country**: Paese di provenienza con flag emoji (🇮🇹 IT, 🇺🇸 US, 🌍 others)
- **Page URL**: URL della pagina (troncato a 40 caratteri, cliccabile)
- **UTM Campaign**: Nome campagna con badge blu, UTM Source sotto con emoji 📍
- **IP Address**: IP del client
- **Details**: Pulsante "View Details" che apre modal con tutte le informazioni

#### 📄 Paginazione
- 20 beacon per pagina
- Navigazione prev/next
- Contatore totale items

#### 📝 Modal Beacon Details
- Click su "View Details" apre modal completo
- Mostra tutti i dati del beacon:
  - Platform, Action, Timestamp
  - Country con flag emoji
  - Page URL completo (cliccabile)
  - Tutti i 5 parametri UTM (source, medium, campaign, content, term)
  - IP Address
  - User Agent
  - Referrer (cliccabile se presente)
  - Fingerprint completo
  - Custom Data (JSON formattato)
- Pulsante Close per chiudere

#### ⚠️ Alert Automatici
Se `success_rate < 80%` e ci sono almeno 10 beacon, appare un warning box:
```
⚠️ Warning: Success rate is below 80%. 
This means some beacons are not being converted to tracked conversions. 
Check your main tracker implementation.
```

### Screenshot Features

```
╔═══════════════════════════════════════════════╗
║ Beacon Log                    [1,234 Total]  ║
╠═══════════════════════════════════════════════╣
║                                               ║
║  [Total Beacons]  [Unique IPs]  [Unique FP]  ║
║      1,234            987          912        ║
║                                               ║
║  [Success Rate: 84.5%] 🟢                     ║
║  1,287 / 1,523 conversions                    ║
║                                               ║
╠═══════════════════════════════════════════════╣
║  Filters: [Platform ▼] [Action ▼]            ║
║           [From Date] [To Date]               ║
║           [Filter] [Reset]                    ║
╠═══════════════════════════════════════════════╣
║  ID | Date/Time | Platform | Action | ...    ║
║  123| 2024-01-15| Discord  | wc_... | ...    ║
║  122| 2024-01-15| Telegram | wc_... | ...    ║
╚═══════════════════════════════════════════════╝
```

---

## 💻 Implementazione JavaScript

### Metodo Base (con navigator.sendBeacon)

```javascript
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

function sendBeacon(platform, options = {}) {
    const utmParams = getUtmParams();
    
    const beaconData = {
        action: 'wc_captcha_completed',
        platform: platform,
        timestamp: Date.now(),
        user_agent: navigator.userAgent,
        referrer: document.referrer || 'direct',
        page_url: window.location.href,
        fingerprint: options.fingerprint || '',
        custom_data: options.custom_data || '',
        utm_source: utmParams.utm_source,
        utm_medium: utmParams.utm_medium,
        utm_campaign: utmParams.utm_campaign,
        utm_content: utmParams.utm_content,
        utm_term: utmParams.utm_term
    };

    if (navigator.sendBeacon) {
        const formData = new FormData();
        Object.keys(beaconData).forEach(key => {
            formData.append(key, beaconData[key]);
        });
        
        navigator.sendBeacon(BEACON_URL, formData);
        console.log('[Beacon] Inviato:', beaconData);
    } else {
        // Fallback per browser vecchi
        fetch(BEACON_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(beaconData),
            keepalive: true
        });
    }
}
```

### Integrazione con Turnstile (Cloudflare)

```html
<!-- HTML -->
<div 
    class="cf-turnstile" 
    data-sitekey="YOUR_SITE_KEY"
    data-callback="onTurnstileComplete"
></div>

<script>
function onTurnstileComplete(token) {
    // Invia beacon
    sendBeacon('web', {
        fingerprint: getFingerprint(),
        custom_data: JSON.stringify({ 
            captcha: 'turnstile',
            page: window.location.pathname 
        })
    });
    
    // Continua con il tuo tracking normale
    trackConversion();
}
</script>
```

### Integrazione con hCaptcha

```html
<!-- HTML -->
<div 
    class="h-captcha" 
    data-sitekey="YOUR_SITE_KEY"
    data-callback="onHCaptchaComplete"
></div>

<script>
function onHCaptchaComplete(token) {
    sendBeacon('web', {
        fingerprint: getFingerprint(),
        custom_data: JSON.stringify({ 
            captcha: 'hcaptcha',
            page: window.location.pathname 
        })
    });
    
    trackConversion();
}
</script>
```

### Integrazione con reCAPTCHA v3

```javascript
grecaptcha.ready(function() {
    grecaptcha.execute('YOUR_SITE_KEY', {action: 'submit'}).then(function(token) {
        sendBeacon('web', {
            fingerprint: getFingerprint(),
            custom_data: JSON.stringify({ 
                captcha: 'recaptcha_v3',
                action: 'submit'
            })
        });
        
        trackConversion();
    });
});
```

---

## 🔧 Utility Functions

### Generazione Fingerprint

```javascript
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
```

### Tracking Doppione (Beacon + Normale)

```javascript
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
```

---

## 📊 Database

### Tabella: `wp_mct_beacon_log`

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| `id` | bigint(20) | Primary key |
| `action` | varchar(100) | Tipo di azione |
| `platform` | varchar(50) | Piattaforma |
| `timestamp` | bigint(20) | Timestamp evento (ms) |
| `user_agent` | text | User Agent |
| `referrer` | text | URL referrer |
| `page_url` | text | URL della pagina |
| `country` | varchar(2) | Codice paese ISO (IT, US, etc) |
| `fingerprint` | varchar(255) | Browser fingerprint |
| `custom_data` | text | Dati custom JSON |
| `utm_source` | varchar(255) | Sorgente campagna |
| `utm_medium` | varchar(255) | Mezzo campagna |
| `utm_campaign` | varchar(255) | Nome campagna |
| `utm_content` | varchar(255) | Contenuto annuncio |
| `utm_term` | varchar(255) | Parole chiave |
| `ip_address` | varchar(45) | IP del client |
| `created_at` | datetime | Data creazione record |

### Indici

- `idx_platform` su `platform`
- `idx_action` su `action`
- `idx_created_at` su `created_at`
- `idx_fingerprint` su `fingerprint`
- `idx_country` su `country`
- `idx_utm_campaign` su `utm_campaign`

---

## 📈 Query SQL Utili

### Contare completamenti per piattaforma (ultimi 7 giorni)

```sql
SELECT 
    platform,
    COUNT(*) as total,
    DATE(created_at) as date
FROM wp_mct_beacon_log
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY platform, DATE(created_at)
ORDER BY date DESC, total DESC;
```

### Confrontare beacon con conversioni

```sql
SELECT 
    DATE(b.created_at) as date,
    COUNT(DISTINCT b.id) as beacon_count,
    COUNT(DISTINCT c.id) as conversion_count,
    ROUND((COUNT(DISTINCT c.id) / COUNT(DISTINCT b.id) * 100), 2) as success_rate
FROM wp_mct_beacon_log b
LEFT JOIN wp_meta_conversions c 
    ON DATE(b.created_at) = DATE(c.created_at)
    AND b.platform = c.platform
WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(b.created_at)
ORDER BY date DESC;
```

### Top referrer per conversioni

```sql
SELECT 
    referrer,
    platform,
    COUNT(*) as total,
    COUNT(DISTINCT fingerprint) as unique_users
FROM wp_mct_beacon_log
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND referrer != 'direct'
GROUP BY referrer, platform
ORDER BY total DESC
LIMIT 20;
```

### Analisi oraria (picchi di traffico)

```sql
SELECT 
    HOUR(created_at) as hour,
    platform,
    COUNT(*) as total
FROM wp_mct_beacon_log
WHERE DATE(created_at) = CURDATE()
GROUP BY HOUR(created_at), platform
ORDER BY hour, total DESC;
```

---

## 🛠️ Maintenance

### Cleanup Automatico

Il plugin pulisce automaticamente i beacon più vecchi di **30 giorni** con un cron job giornaliero.

```php
// In WordPress
add_action('mct_daily_cleanup', array('MCT_Beacon', 'cleanup_old_beacons'));
```

### Cleanup Manuale

```php
// Eseguire in WordPress
MCT_Beacon::cleanup_old_beacons();
```

### Monitoraggio Spazio Database

```sql
SELECT 
    table_name AS "Table",
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
FROM information_schema.TABLES
WHERE table_schema = DATABASE()
    AND table_name = 'wp_mct_beacon_log';
```

---

## ⚠️ Troubleshooting

### Il beacon non viene salvato

1. **Verifica che la tabella esista**:
   ```sql
   SHOW TABLES LIKE 'wp_mct_beacon_log';
   ```

2. **Riattiva il plugin** per ricreare le tabelle:
   ```bash
   wp plugin deactivate meta-conversion-tracker
   wp plugin activate meta-conversion-tracker
   ```

3. **Verifica i log WordPress**:
   ```bash
   tail -f wp-content/debug.log | grep "MCT Beacon"
   ```

### Success rate troppo basso (< 80%)

1. **Analizza i dati** con `/beacon/compare`
2. **Verifica CORS** sul tracker principale
3. **Controlla Meta WebView** issues
4. **Valuta timeout** del tracker

### Troppi dati nella tabella

1. **Riduci retention** da 30 a 7 giorni:
   ```php
   // In class-mct-beacon.php, modifica:
   $days = 7; // invece di 30
   ```

2. **Esegui cleanup manuale**:
   ```sql
   DELETE FROM wp_mct_beacon_log 
   WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
   ```

---

## 🎯 Use Cases

### 1. Discord Invite Tracking
Traccia quanti utenti completano il captcha dopo aver cliccato su un invite link Discord.

### 2. Telegram Bot Onboarding
Monitora il funnel di onboarding del tuo bot Telegram.

### 3. Meta Ads Landing Pages
Traccia TUTTI i completamenti captcha, anche se Meta WebView blocca il tracker normale.

### 4. A/B Testing Captcha
Confronta tassi di successo tra diversi provider di captcha (hCaptcha vs Turnstile vs reCAPTCHA).

### 5. Fraud Detection
Identifica pattern sospetti confrontando IP, fingerprint e timestamp.

---

## 📝 Best Practices

1. **Invia sempre il beacon PRIMA** del tracking normale
2. **Usa `navigator.sendBeacon`** per garantire l'invio anche se la pagina si chiude
3. **Includi fingerprint** per deduplicate utenti
4. **Logga custom_data** per analisi avanzate
5. **Monitora il success_rate** settimanalmente
6. **Imposta alert** se success_rate < 80%

---

## 📚 Risorse

- [Esempio JavaScript completo](../examples/beacon-example.js)
- [Codice sorgente PHP](../includes/class-mct-beacon.php)
- [Main plugin file](../meta-conversion-tracker.php)

---

## 🚀 Quick Start

```javascript
// 1. Includi il codice nel tuo HTML
<script src="path/to/beacon-example.js"></script>

// 2. Chiama quando l'utente completa il captcha
sendBeacon('discord', {
    fingerprint: getFingerprint(),
    custom_data: JSON.stringify({ 
        campaign: 'summer2024' 
    })
});

// 3. Monitora i risultati
// GET /wp-json/mct/v1/beacon/compare
```

---

## 📞 Support

Per supporto o domande, consulta:
- [README principale](../README.md)
- [CHANGELOG](../CHANGELOG.md)
- [GitHub Issues](https://github.com/yourrepo/meta-conversion-tracker/issues)
