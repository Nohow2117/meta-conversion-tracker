# 🐛 Bug Fix: Referrer Parameter Error

## Problema Risolto

**Errore:**
```
POST /wp-json/mct/v1/track 400 (Bad Request)
Invalid parameter(s): referrer
```

## Causa del Bug

Il file `tracker.js` stava inviando `null` come valore per il parametro `referrer` quando `document.referrer` era vuoto. WordPress REST API valida strettamente i tipi dei parametri, e `null` non è considerato una stringa valida.

**Codice problematico (riga 53 di tracker.js):**
```javascript
referrer: document.referrer || null,  // ❌ null non è una stringa valida
```

## Soluzione

### Fix 1: tracker.js

**File:** `assets/js/tracker.js`  
**Riga:** 53

**Prima:**
```javascript
referrer: document.referrer || null,
```

**Dopo:**
```javascript
referrer: document.referrer || '',  // ✅ Stringa vuota invece di null
```

### Fix 2: class-mct-api.php (Miglioramento)

**File:** `includes/class-mct-api.php`  
**Righe:** 432-449

Aggiunti `sanitize_callback` a tutti i parametri per una migliore gestione dei dati:

```php
private function get_track_args() {
    return array(
        'utm_source' => array('type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'),
        'utm_medium' => array('type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'),
        'utm_campaign' => array('type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'),
        'utm_content' => array('type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'),
        'utm_term' => array('type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'),
        'fbclid' => array('type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'),
        'fbc' => array('type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'),
        'fbp' => array('type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'),
        'user_agent' => array('type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'),
        'fingerprint' => array('type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'),
        'browser_fingerprint' => array('required' => false),
        'platform' => array('type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'),
        'landing_page' => array('type' => 'string', 'required' => false, 'sanitize_callback' => 'esc_url_raw'),
        'referrer' => array('type' => 'string', 'required' => false, 'sanitize_callback' => 'esc_url_raw'),
        'event_name' => array('type' => 'string', 'required' => false, 'default' => 'Lead', 'sanitize_callback' => 'sanitize_text_field'),
        'custom_data' => array('required' => false),
    );
}
```

## Come Aggiornare

### Opzione 1: Aggiorna Solo tracker.js (Più Veloce)

Se hai già caricato il plugin su WordPress, devi solo aggiornare il file JavaScript sulla landing page:

1. Scarica il nuovo `tracker.js` dal plugin WordPress:
   ```
   https://tuosite.com/wp-content/plugins/meta-conversion-tracker/assets/js/tracker.js
   ```

2. Oppure modifica manualmente il file sulla landing page:
   - Trova la riga con `referrer: document.referrer || null,`
   - Cambiala in `referrer: document.referrer || '',`

3. Svuota la cache del browser (Ctrl+Shift+R)

4. Testa di nuovo

### Opzione 2: Aggiorna Plugin Completo (Consigliato)

1. **Scarica i file aggiornati** dal repository GitHub:
   ```
   https://github.com/Nohow2117/meta-conversion-tracker
   ```

2. **Sostituisci i file sul server WordPress:**
   - `wp-content/plugins/meta-conversion-tracker/assets/js/tracker.js`
   - `wp-content/plugins/meta-conversion-tracker/includes/class-mct-api.php`

3. **Svuota la cache:**
   - Cache del browser (Ctrl+Shift+R)
   - Cache di WordPress (se usi plugin di cache)
   - Cache CDN (se applicabile)

4. **Testa:**
   - Visita landing page con UTM
   - Risolvi CAPTCHA
   - Controlla console (F12) - non dovrebbero esserci errori
   - Verifica conversione in WordPress Dashboard

## Verifica che Funziona

### Test Completo

1. **Apri landing page con parametri UTM:**
   ```
   https://tualanding.com?utm_campaign=test&utm_source=facebook
   ```

2. **Apri console browser (F12)**

3. **Risolvi CAPTCHA e clicca Discord/Telegram**

4. **Nella console dovresti vedere:**
   ```javascript
   MCTTracker: Captured data {
     utm_source: 'facebook',
     utm_campaign: 'test',
     platform: 'discord',
     referrer: '',  // ✅ Stringa vuota, non null
     ...
   }
   
   MCTTracker: Sending data {...}
   
   MCTTracker: Response {
     success: true,
     conversion_id: 123,
     event_id: 'mct_xxx',
     message: 'Conversion tracked successfully'
   }
   ```

5. **Vai su WordPress Admin → Conversion Tracker → Dashboard**
   - Dovresti vedere la conversione nella lista

### Se Vedi Ancora Errori

**Errore: "Invalid parameter(s): referrer"**
- Svuota cache del browser (Ctrl+Shift+R)
- Verifica che il file tracker.js sia stato aggiornato
- Controlla che non ci sia una versione cached del file

**Errore: "401 Unauthorized"**
- Verifica che l'API key sia corretta
- Controlla che il plugin sia attivato

**Nessun errore ma conversione non appare**
- Controlla che Meta API sia configurata
- Verifica i log in WordPress Admin → Conversion Tracker → Settings

## Nota sull'Errore Meta Pixel

L'errore che vedi in console:
```
[Meta Pixel] - An invalid email address was specified for 'em'
```

**Non è causato dal nostro plugin.** Questo è un warning del Meta Pixel che hai installato separatamente sulla landing page. Il nostro plugin non invia email al Meta Pixel browser-side, solo server-side tramite Conversions API.

Per risolvere questo warning:
- Rimuovi il parametro `em` dal tuo Meta Pixel code
- Oppure assicurati che l'email sia in formato valido
- Oppure ignora il warning (non blocca il tracking)

## Changelog

**Versione 1.0.1** - 2025-11-06
- ✅ Fixed referrer parameter validation error
- ✅ Added sanitize callbacks to API parameters
- ✅ Improved error handling

**Versione 1.0.0** - 2025-11-05
- Initial release

## Supporto

Se hai ancora problemi dopo l'aggiornamento:

1. Svuota tutte le cache
2. Abilita debug mode nel tracker:
   ```javascript
   const tracker = new MCTTracker({
       apiUrl: 'https://tuosite.com/wp-json/mct/v1/track',
       apiKey: 'your-api-key',
       debug: true  // ✅ Abilita log dettagliati
   });
   ```
3. Controlla i log in console
4. Verifica che i file siano stati aggiornati correttamente

---

**File modificati:**
- ✅ `assets/js/tracker.js` (riga 53)
- ✅ `includes/class-mct-api.php` (righe 432-449)

**Versione corrente:** 1.0.1  
**Data fix:** 2025-11-06
