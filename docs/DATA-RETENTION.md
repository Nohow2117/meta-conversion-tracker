# Data Retention & Auto-Cleanup

## Overview

Il plugin Meta Conversion Tracker include un sistema automatico di pulizia dei dati per la conformità GDPR e la gestione dello spazio su database.

---

## 🕐 Retention Period

**Periodo di conservazione:** 30 giorni

Tutti i dati di conversione più vecchi di 30 giorni vengono automaticamente eliminati dal database.

---

## 🤖 Automatic Cleanup

### Come Funziona

1. **Cron Job WordPress**
   - Esegue automaticamente ogni giorno
   - Elimina conversioni più vecchie di 30 giorni
   - Elimina anche i log associati

2. **Scheduling**
   - Attivato quando il plugin viene attivato
   - Disattivato quando il plugin viene disattivato
   - Usa il sistema WordPress `wp_schedule_event`

3. **Cosa Viene Eliminato**
   - Tutte le righe in `wp_meta_conversions` con `created_at < NOW() - 30 days`
   - Tutte le righe in `wp_meta_conversion_logs` con `created_at < NOW() - 30 days`

### Verifica Prossima Esecuzione

Vai su:
```
WordPress Admin → Conversion Tracker → Settings → Data Management
```

Vedrai:
```
Next cleanup: 2025-11-07 03:00:00
```

---

## 🔧 Manual Cleanup

### Come Eseguire

1. Vai su **WordPress Admin → Conversion Tracker → Settings**
2. Sezione **"Data Management"**
3. Clicca **"Clean Old Data Now"**
4. Conferma l'azione
5. Vedrai il risultato:
   ```
   Cleanup completed!
   Conversions deleted: 15
   Logs deleted: 42
   ```

### Quando Usarlo

- Per liberare spazio immediatamente
- Prima di un export dei dati
- Per test e debug
- Se il cron job non funziona

---

## 📊 Cosa Succede ai Dati

### Dati Eliminati

| Tabella | Dati Eliminati |
|---------|----------------|
| `wp_meta_conversions` | Tutte le conversioni > 30 giorni |
| `wp_meta_conversion_logs` | Tutti i log > 30 giorni |

### Dati Conservati

- Conversioni degli ultimi 30 giorni
- Tutte le impostazioni del plugin
- API key
- Configurazione Meta API

---

## 🔒 GDPR Compliance

### Conformità

✅ **Right to be forgotten** - I dati vengono eliminati automaticamente dopo 30 giorni  
✅ **Data minimization** - Solo i dati necessari vengono conservati  
✅ **Storage limitation** - Retention period definito e automatizzato  
✅ **Transparency** - L'utente può vedere quando avverrà la prossima pulizia  

### Privacy Policy

Puoi includere questo testo nella tua privacy policy:

```
I dati di conversione (parametri UTM, IP, fingerprint browser) vengono 
conservati per un massimo di 30 giorni e poi eliminati automaticamente 
dal nostro sistema. Questi dati vengono utilizzati esclusivamente per 
l'ottimizzazione delle campagne pubblicitarie e non vengono condivisi 
con terze parti ad eccezione di Meta (Facebook) per scopi di attribution.
```

---

## ⚙️ Configurazione Avanzata

### Cambiare il Retention Period

Se vuoi modificare il periodo di conservazione (es: 60 giorni invece di 30):

1. Apri il file: `includes/class-mct-database.php`
2. Trova la riga:
   ```php
   const DATA_RETENTION_DAYS = 30;
   ```
3. Cambia il valore:
   ```php
   const DATA_RETENTION_DAYS = 60;  // 60 giorni
   ```
4. Salva il file

### Disabilitare l'Auto-Cleanup

**Non consigliato**, ma se necessario:

1. Apri il file: `meta-conversion-tracker.php`
2. Commenta questa riga:
   ```php
   // add_action('mct_daily_cleanup', array('MCT_Database', 'cleanup_old_conversions'));
   ```
3. Salva il file

**Nota:** Il database crescerà indefinitamente se disabiliti la pulizia automatica.

---

## 🐛 Troubleshooting

### Il Cron Job Non Funziona

**Problema:** I dati vecchi non vengono eliminati automaticamente.

**Soluzioni:**

1. **Verifica che il cron sia schedulato:**
   ```php
   wp_next_scheduled('mct_daily_cleanup');
   ```

2. **Usa un plugin per gestire i cron:**
   - Installa "WP Crontrol"
   - Vai su Tools → Cron Events
   - Cerca `mct_daily_cleanup`
   - Esegui manualmente per testare

3. **Usa un cron esterno:**
   Se il cron di WordPress non funziona, configura un cron esterno:
   ```bash
   # Crontab
   0 3 * * * curl https://tuosite.com/wp-cron.php
   ```

### Cleanup Manuale Fallisce

**Problema:** Errore quando clicchi "Clean Old Data Now"

**Soluzioni:**

1. Verifica permessi database
2. Controlla i log di WordPress
3. Esegui query SQL manualmente:
   ```sql
   DELETE FROM wp_meta_conversions 
   WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
   ```

---

## 📈 Monitoring

### Verifica Cleanup Logs

I cleanup vengono loggati nella tabella `wp_meta_conversion_logs`:

```sql
SELECT * FROM wp_meta_conversion_logs 
WHERE message LIKE '%cleanup%' 
ORDER BY created_at DESC 
LIMIT 10;
```

Vedrai:
```
message: Data cleanup completed
context: {
  "deleted_conversions": 15,
  "deleted_logs": 42,
  "retention_days": 30
}
```

### Statistiche Database

Controlla la dimensione del database:

```sql
SELECT 
    COUNT(*) as total_conversions,
    MIN(created_at) as oldest,
    MAX(created_at) as newest,
    DATEDIFF(NOW(), MIN(created_at)) as days_of_data
FROM wp_meta_conversions;
```

---

## 🔄 Backup Prima della Pulizia

**Consigliato:** Esegui un backup prima di ogni cleanup manuale.

### Backup Rapido

```sql
-- Crea tabella di backup
CREATE TABLE wp_meta_conversions_backup AS 
SELECT * FROM wp_meta_conversions;

-- Esegui cleanup
DELETE FROM wp_meta_conversions 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Se qualcosa va storto, ripristina
INSERT INTO wp_meta_conversions 
SELECT * FROM wp_meta_conversions_backup;
```

---

## 📝 Best Practices

1. **Export regolari** - Esporta i dati prima che vengano eliminati
2. **Monitoring** - Controlla i log di cleanup regolarmente
3. **Test** - Testa il cleanup manuale prima di affidarti all'automatico
4. **Backup** - Mantieni backup del database
5. **Privacy Policy** - Informa gli utenti del retention period

---

## 🎯 Summary

| Feature | Status |
|---------|--------|
| **Auto-cleanup** | ✅ Attivo (daily) |
| **Retention period** | 30 giorni |
| **Manual cleanup** | ✅ Disponibile |
| **GDPR compliant** | ✅ Sì |
| **Logging** | ✅ Sì |
| **Backup** | ⚠️ Manuale |

---

**Versione:** 1.0.2  
**Data:** 2025-11-06  
**Plugin:** Meta Conversion Tracker
