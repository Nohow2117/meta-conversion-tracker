# Istruzioni per lo Sviluppatore - Integrazione Meta Conversion Tracker

## Panoramica

Questo documento spiega come integrare il plugin **Meta Conversion Tracker** nella landing page WarCry. Il sistema traccia automaticamente le conversioni quando gli utenti risolvono il CAPTCHA e vengono ridirezionati su Discord o Telegram.

---

## Step 1: Aggiungere lo Script Tracker

### Dove Aggiungere

Aggiungi questi script **prima del tag `</body>`** della tua landing page HTML:

```html
<!-- Meta Conversion Tracker - Carica la libreria -->
<script src="https://warcry-mmorpg.online/wp-content/plugins/meta-conversion-tracker/assets/js/tracker.js"></script>

<!-- Meta Conversion Tracker - Inizializza il tracker -->
<script>
const tracker = new MCTTracker({
    apiUrl: 'https://warcry-mmorpg.online/wp-json/mct/v1/track',
    apiKey: 'YOUR-API-KEY-HERE',
    debug: false  // Metti true per vedere i log in console
});
</script>
```

### Cosa Sostituire

1. **`TUOSITE.com`** → Il dominio del tuo WordPress
   - Esempio: `https://warcry-mmorpg.online/wp-content/plugins/...`

2. **`YOUR-API-KEY-HERE`** → La tua API key
   - Dove trovarla: WordPress Admin → Conversion Tracker → Settings → API Key
   - È una stringa di 32 caratteri

### Esempio Completo

```html
<!-- Meta Conversion Tracker - Carica la libreria -->
<script src="https://warcry-mmorpg.online/wp-content/plugins/meta-conversion-tracker/assets/js/tracker.js"></script>

<!-- Meta Conversion Tracker - Inizializza il tracker -->
<script>
const tracker = new MCTTracker({
    apiUrl: 'https://warcry-mmorpg.online/wp-json/mct/v1/track',
    apiKey: 'abc123def456ghi789jkl012mno345pqr',
    debug: false
});
</script>
```

---

## Step 2: Modificare la Funzione CAPTCHA

### Situazione Attuale

Nel file `warcry-lp-v2.js`, la funzione `verifyCaptcha()` attualmente:
1. Mostra il messaggio "Verified!"
2. Redirige l'utente a Discord/Telegram

### Cosa Fare

Devi modificare la funzione `verifyCaptcha()` per:
1. **Tracciare la conversione** (inviare dati a WordPress)
2. **Aspettare la risposta** dal server
3. **Poi redirigere** l'utente

### Codice da Modificare

**Trova questa funzione nel file `warcry-lp-v2.js` (intorno alla riga 116):**

```javascript
function verifyCaptcha() {
    isDragging = false;
    slider.style.left = maxPosition + 'px';
    slider.style.cursor = 'default';
    
    // Mostra successo
    track.style.display = 'none';
    successDiv.style.display = 'block';
    
    // Track evento
    trackPlatformChoice(targetPlatform);
    
    // Redirect dopo 1.5 secondi
    setTimeout(() => {
        window.open(targetUrl, '_blank');
        closeModal();
        // Reset per prossimo uso
        setTimeout(() => {
            track.style.display = 'block';
            successDiv.style.display = 'none';
            slider.style.left = '0px';
            slider.style.cursor = 'grab';
        }, 500);
    }, 1500);
}
```

**Sostituiscilo con questo:**

```javascript
function verifyCaptcha() {
    isDragging = false;
    slider.style.left = maxPosition + 'px';
    slider.style.cursor = 'default';
    
    // Mostra successo
    track.style.display = 'none';
    successDiv.style.display = 'block';
    
    // Track evento Meta
    trackPlatformChoice(targetPlatform);
    
    // NUOVO: Traccia conversione nel plugin WordPress
    tracker.track({
        platform: targetPlatform.toLowerCase(), // 'discord' o 'telegram'
        event_name: 'Lead'
    }).then(function(response) {
        // Conversione tracciata con successo
        console.log('Conversione tracciata:', response);
        
        // Redirect dopo 1.5 secondi
        setTimeout(function() {
            window.open(targetUrl, '_blank');
            closeModal();
            
            // Reset per prossimo uso
            setTimeout(function() {
                track.style.display = 'block';
                successDiv.style.display = 'none';
                slider.style.left = '0px';
                slider.style.cursor = 'grab';
            }, 500);
        }, 1500);
    }).catch(function(error) {
        // Se il tracking fallisce, redirige comunque
        console.error('Tracking fallito:', error);
        
        setTimeout(function() {
            window.open(targetUrl, '_blank');
            closeModal();
            
            setTimeout(function() {
                track.style.display = 'block';
                successDiv.style.display = 'none';
                slider.style.left = '0px';
                slider.style.cursor = 'grab';
            }, 500);
        }, 1500);
    });
}
```

### Cosa Cambia

**Linee Aggiunte (dopo `trackPlatformChoice(targetPlatform);`):**

```javascript
// NUOVO: Traccia conversione nel plugin WordPress
tracker.track({
    platform: targetPlatform.toLowerCase(), // 'discord' o 'telegram'
    event_name: 'Lead'
}).then(function(response) {
    // Conversione tracciata con successo
    console.log('Conversione tracciata:', response);
    
    // ... resto del codice di redirect ...
}).catch(function(error) {
    // Se il tracking fallisce, redirige comunque
    console.error('Tracking fallito:', error);
    
    // ... resto del codice di redirect ...
});
```

---

## Step 3: Cosa Succede Quando l'Utente Clicca

### Flusso Completo

```
1. Utente arriva su landing page
   ↓
2. tracker.js carica e cattura automaticamente:
   - Parametri UTM dall'URL
   - FBCLID da URL/cookies
   - Browser fingerprint
   - IP address (server-side)
   ↓
3. Utente risolve CAPTCHA
   ↓
4. verifyCaptcha() viene chiamata
   ↓
5. tracker.track() invia dati a WordPress:
   POST /wp-json/mct/v1/track
   {
     utm_source: "facebook",
     utm_campaign: "warcry_launch",
     fbclid: "IwAR...",
     platform: "discord",
     fingerprint: "abc123...",
     ...
   }
   ↓
6. WordPress riceve, salva nel database, invia a Meta
   ↓
7. Risposta torna a landing page
   ↓
8. Utente viene rediretto a Discord/Telegram
```

---

## Step 4: Verificare che Funziona

### Test Manuale

1. **Apri la landing page con parametri UTM:**
   ```
   https://tualanding.com?utm_campaign=test&utm_source=facebook&fbclid=IwAR123456789
   ```

2. **Apri la console del browser (F12)**
   - Vai su Tab "Console"

3. **Risolvi CAPTCHA e clicca Discord/Telegram**

4. **Nella console dovresti vedere:**
   ```
   Conversione tracciata: {
     success: true,
     conversion_id: 123,
     event_id: "mct_abc123...",
     message: "Conversion tracked successfully"
   }
   ```

5. **Vai su WordPress Admin:**
   - Conversion Tracker → Dashboard
   - Dovresti vedere la conversione nella lista "Recent Conversions"

### Se Vedi Errori

**Errore: "401 Unauthorized"**
- Controlla che l'API key sia corretta
- Verifica che sia copiata esattamente (senza spazi)

**Errore: "Cannot POST /wp-json/mct/v1/track"**
- Verifica che il dominio WordPress sia corretto
- Assicurati che il plugin sia attivato

**Errore: "tracker is not defined"**
- Verifica che lo script tracker.js sia caricato
- Controlla che sia prima della funzione verifyCaptcha()

---

## Step 5: Dati Tracciati Automaticamente

Il tracker cattura automaticamente:

### Parametri UTM
- `utm_source` - Fonte del traffico (es: "facebook")
- `utm_medium` - Mezzo (es: "cpc")
- `utm_campaign` - Campagna (es: "warcry_launch")
- `utm_content` - Variante creativa
- `utm_term` - Keyword

### Facebook Parameters
- `fbclid` - Facebook Click ID (da URL)
- `fbc` - Facebook Click ID cookie
- `fbp` - Facebook Browser ID cookie

### Browser Data
- `ip_address` - Indirizzo IP dell'utente
- `user_agent` - Browser info
- `fingerprint` - Hash unico del browser
- `browser_fingerprint` - Dettagli browser (screen, timezone, language, etc.)

### Page Data
- `landing_page` - URL della landing page
- `referrer` - Pagina da cui viene l'utente
- `platform` - Discord o Telegram (da te specificato)

### Dati Inviati a Meta
Tutto viene inviato a Meta Conversions API per:
- Attribuire la conversione all'utente
- Migliorare il targeting
- Misurare il ROAS

---

## Step 6: Opzioni Avanzate (Opzionali)

### Aggiungere Dati Custom

Se vuoi inviare dati aggiuntivi (email, phone, etc.):

```javascript
tracker.track({
    platform: targetPlatform.toLowerCase(),
    event_name: 'Lead',
    custom_data: {
        email: 'user@example.com',
        phone: '+1234567890',
        first_name: 'John',
        last_name: 'Doe'
    }
});
```

Questi dati verranno hashati e inviati a Meta per migliorare il matching.

### Debug Mode

Per vedere tutti i log in console:

```javascript
const tracker = new MCTTracker({
    apiUrl: 'https://warcry.com/wp-json/mct/v1/track',
    apiKey: 'your-api-key',
    debug: true  // Abilita i log
});
```

---

## Checklist di Implementazione

- [ ] Script tracker.js aggiunto prima di `</body>`
- [ ] API key copiata correttamente in `apiKey`
- [ ] Dominio WordPress corretto in `apiUrl`
- [ ] Funzione `verifyCaptcha()` modificata
- [ ] `tracker.track()` aggiunto con `platform` e `event_name`
- [ ] Codice testato con parametri UTM
- [ ] Conversione visibile in WordPress Dashboard
- [ ] Console browser pulita (no errori)

---

## Supporto

Se hai domande:

1. **Controlla la console browser (F12)** per errori
2. **Abilita debug mode** per vedere i log
3. **Verifica l'API key** in WordPress Admin
4. **Controlla che il plugin sia attivato**

---

## File da Modificare

- **`warcry-lp-v2.html`** - Aggiungi gli script tracker
- **`warcry-lp-v2.js`** - Modifica la funzione `verifyCaptcha()`

---

**Versione:** 1.0.0  
**Data:** 2025-11-05  
**Plugin:** Meta Conversion Tracker
