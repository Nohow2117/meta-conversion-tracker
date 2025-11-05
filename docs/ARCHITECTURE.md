# System Architecture

## Overview

Meta Conversion Tracker è un sistema di tracking conversioni distribuito che collega landing pages esterne a WordPress e Meta Ads.

## Componenti del Sistema

### 1. Frontend (Landing Pages)

**File:** `assets/js/tracker.js`

**Responsabilità:**
- Cattura parametri UTM dall'URL
- Estrae FBCLID e cookies Facebook
- Genera fingerprint del browser
- Invia dati al backend via REST API
- Gestisce redirect post-conversione

**Tecnologie:**
- JavaScript ES6+
- Fetch API
- LocalStorage per persistenza
- Canvas/WebGL per fingerprinting

**Flusso:**
```
User lands on page
    ↓
Tracker auto-captures:
- URL parameters (UTM, FBCLID)
- Browser data (fingerprint, user agent)
- Page context (URL, referrer)
    ↓
User clicks CTA button
    ↓
Tracker sends POST to WordPress
    ↓
Redirect to platform
```

### 2. Backend (WordPress Plugin)

**File principale:** `meta-conversion-tracker.php`

**Struttura:**
```
meta-conversion-tracker/
├── meta-conversion-tracker.php    # Entry point, hooks
├── includes/
│   ├── class-mct-database.php     # Database operations
│   ├── class-mct-api.php          # REST API endpoints
│   ├── class-mct-meta-api.php     # Meta Conversions API
│   └── class-mct-fingerprint.php  # Fingerprint utilities
├── admin/
│   ├── class-mct-admin.php        # Admin interface
│   └── views/                     # Admin page templates
├── assets/
│   ├── js/
│   │   ├── tracker.js             # Landing page tracker
│   │   └── admin.js               # Admin panel JS
│   └── css/
│       └── admin.css              # Admin styles
└── docs/                          # Documentation
```

**Classi Principali:**

#### Meta_Conversion_Tracker
- Singleton pattern
- Carica dipendenze
- Registra hooks WordPress
- Gestisce attivazione/disattivazione

#### MCT_Database
- Crea e gestisce tabelle
- CRUD operations
- Query builder
- Logging system
- Database user management

#### MCT_API
- Registra REST endpoints
- Validazione input
- Autenticazione API key
- Gestione errori
- CORS headers

#### MCT_Meta_API
- Integrazione Meta Conversions API
- Build event payload
- Hash dati sensibili (email, phone)
- Retry failed conversions
- Test connection

#### MCT_Admin
- Pannello amministrazione
- Dashboard con statistiche
- Viewer conversioni
- Settings page
- API documentation
- Database access info

### 3. Database Layer

**Tabelle:**

#### wp_meta_conversions
Tabella principale che memorizza ogni conversione.

**Schema:**
```sql
id                  BIGINT PRIMARY KEY
utm_source          VARCHAR(255)
utm_medium          VARCHAR(255)
utm_campaign        VARCHAR(255)
utm_content         VARCHAR(255)
utm_term            VARCHAR(255)
fbclid              VARCHAR(255) INDEXED
fbc                 VARCHAR(255)
fbp                 VARCHAR(255)
ip_address          VARCHAR(45)
user_agent          TEXT
fingerprint         VARCHAR(64) INDEXED
browser_fingerprint TEXT (JSON)
platform            VARCHAR(20) INDEXED
landing_page        VARCHAR(500)
referrer            VARCHAR(500)
country             VARCHAR(2)
city                VARCHAR(100)
event_id            VARCHAR(64) INDEXED
event_name          VARCHAR(50)
meta_sent           TINYINT(1) INDEXED
meta_response       TEXT (JSON)
meta_sent_at        DATETIME
custom_data         TEXT (JSON)
created_at          DATETIME INDEXED
updated_at          DATETIME
```

**Indici:**
- PRIMARY KEY (id)
- INDEX (fbclid) - Per query su click Facebook
- INDEX (utm_campaign) - Per filtri campagna
- INDEX (created_at) - Per ordinamento temporale
- INDEX (meta_sent) - Per retry failed
- INDEX (fingerprint) - Per deduplicazione

#### wp_meta_conversion_logs
Tabella di logging per debug.

**Schema:**
```sql
id             BIGINT PRIMARY KEY
conversion_id  BIGINT (FK to conversions)
log_level      VARCHAR(20) - info, warning, error
message        TEXT
context        TEXT (JSON)
created_at     DATETIME INDEXED
```

### 4. REST API Layer

**Base URL:** `/wp-json/mct/v1`

**Endpoints:**

| Method | Endpoint | Auth | Descrizione |
|--------|----------|------|-------------|
| POST | /track | API Key | Crea nuova conversione |
| GET | /conversions | API Key/Admin | Lista conversioni |
| GET | /conversions/{id} | API Key/Admin | Singola conversione |
| GET | /stats | API Key/Admin | Statistiche aggregate |
| GET | /test | Public | Test API |

**Autenticazione:**
- Header: `X-API-Key: xxx`
- Query: `?api_key=xxx`
- Admin: WordPress session

**Rate Limiting:**
Non implementato di default. Per produzione considerare:
- WordPress plugin: WP REST API Rate Limit
- Server level: Nginx limit_req
- Cloudflare rate limiting

### 5. Meta Conversions API Integration

**Endpoint Meta:**
```
POST https://graph.facebook.com/v18.0/{pixel_id}/events
```

**Payload Structure:**
```json
{
  "data": [{
    "event_name": "Lead",
    "event_time": 1699219200,
    "event_id": "mct_unique_id",
    "event_source_url": "https://landing.com",
    "action_source": "website",
    "user_data": {
      "em": "hashed_email",
      "ph": "hashed_phone",
      "client_ip_address": "1.2.3.4",
      "client_user_agent": "Mozilla...",
      "fbc": "fb.1.xxx",
      "fbp": "fb.1.xxx"
    },
    "custom_data": {
      "currency": "USD",
      "value": 0.00,
      "content_category": "discord"
    }
  }],
  "access_token": "xxx"
}
```

**Deduplicazione:**
- Ogni evento ha `event_id` unico
- Meta deduplica automaticamente eventi duplicati
- Utile se pixel browser + server-side inviano stesso evento

**Hashing:**
Dati sensibili vengono hashati con SHA256:
- Email → lowercase → trim → sha256
- Phone → solo numeri → sha256
- Nome/Cognome → lowercase → trim → sha256

### 6. External Database Access

**Read-Only User:**
```sql
CREATE USER 'mct_readonly_xxx'@'%' 
IDENTIFIED BY 'secure_password';

GRANT SELECT ON database.wp_meta_conversions 
TO 'mct_readonly_xxx'@'%';

FLUSH PRIVILEGES;
```

**Permessi:**
- SELECT only
- Single table access
- No admin privileges

**Use Cases:**
- CRM integration
- Analytics dashboards
- Data warehouse ETL
- Custom reporting tools

## Data Flow Dettagliato

### Scenario: User Conversion

```
1. User clicks Meta Ad
   ↓
2. Lands on page with UTM + FBCLID
   URL: landing.com?utm_campaign=warcry&fbclid=IwAR...
   ↓
3. tracker.js loads and captures:
   - UTM from URL
   - FBCLID from URL
   - _fbc, _fbp from cookies
   - Browser fingerprint (canvas, webgl, screen, etc.)
   - IP address (will be captured server-side)
   ↓
4. User solves captcha and clicks "Discord"
   ↓
5. tracker.track() called:
   POST /wp-json/mct/v1/track
   Headers: X-API-Key: xxx
   Body: {
     utm_campaign: "warcry",
     fbclid: "IwAR...",
     platform: "discord",
     ...all captured data
   }
   ↓
6. WordPress receives request:
   - MCT_API::check_api_key() validates
   - MCT_API::track_conversion() processes
   - Gets IP from $_SERVER
   - Sanitizes all inputs
   ↓
7. MCT_Database::insert_conversion():
   - Generates unique event_id
   - Inserts into wp_meta_conversions
   - Returns conversion_id
   ↓
8. If Meta API enabled:
   MCT_Meta_API::send_conversion()
   - Builds event payload
   - Hashes sensitive data
   - POST to Meta Graph API
   - Stores response
   - Updates meta_sent = 1
   ↓
9. Response to landing page:
   {
     success: true,
     conversion_id: 123,
     event_id: "mct_xxx"
   }
   ↓
10. tracker.js redirects user:
    window.location.href = "discord.gg/xxx"
```

### Scenario: External System Query

```
1. Python script needs conversion data
   ↓
2. Option A - REST API:
   GET /wp-json/mct/v1/conversions?utm_campaign=warcry
   Headers: X-API-Key: xxx
   ↓
   Response: JSON with conversions array
   ↓
3. Option B - Direct Database:
   mysql.connector.connect(
     host=xxx, user=mct_readonly_xxx, ...
   )
   ↓
   SELECT * FROM wp_meta_conversions 
   WHERE utm_campaign = 'warcry'
   ↓
   Returns: Raw database rows
```

## Security Architecture

### Authentication & Authorization

**API Key:**
- 32 caratteri random
- Stored in wp_options (WordPress database)
- Validated on ogni request
- Può essere rigenerato

**Database User:**
- Password 20 caratteri random
- Solo SELECT permission
- Accesso limitato a 1 tabella
- No admin privileges

**WordPress Admin:**
- Standard WordPress authentication
- Requires `manage_options` capability
- Session-based

### Data Protection

**Input Sanitization:**
```php
sanitize_text_field()  // Per stringhe semplici
esc_url_raw()          // Per URLs
wp_json_encode()       // Per JSON
$wpdb->prepare()       // Per SQL queries
```

**Output Escaping:**
```php
esc_html()       // Per HTML output
esc_attr()       // Per HTML attributes
esc_url()        // Per URLs
wp_json_encode() // Per JSON
```

**SQL Injection Prevention:**
- Prepared statements sempre
- No raw SQL queries
- WordPress $wpdb wrapper

**XSS Prevention:**
- Escape all output
- Sanitize all input
- Content Security Policy headers (opzionale)

### HTTPS Requirement

**Meta API richiede HTTPS:**
- Certificato SSL valido
- TLS 1.2 o superiore
- No self-signed certificates

**Best Practice:**
- Force HTTPS su WordPress
- HSTS headers
- Secure cookies

## Performance Considerations

### Database Optimization

**Indici:**
- Tutti i campi filtrabili hanno indici
- Composite index per query comuni
- EXPLAIN query per ottimizzazione

**Query Optimization:**
```sql
-- Good: Uses index
SELECT * FROM wp_meta_conversions 
WHERE utm_campaign = 'warcry' 
ORDER BY created_at DESC 
LIMIT 100;

-- Bad: Full table scan
SELECT * FROM wp_meta_conversions 
WHERE YEAR(created_at) = 2025;

-- Good: Uses index
SELECT * FROM wp_meta_conversions 
WHERE created_at >= '2025-01-01' 
AND created_at < '2026-01-01';
```

**Pagination:**
- LIMIT + OFFSET per grandi dataset
- Evitare OFFSET molto alti (slow)
- Consider cursor-based pagination per scale

### Caching Strategy

**WordPress Object Cache:**
```php
// Cache API key lookup
$api_key = wp_cache_get('mct_api_key');
if (false === $api_key) {
    $api_key = get_option('mct_api_key');
    wp_cache_set('mct_api_key', $api_key, '', 3600);
}
```

**Transients:**
```php
// Cache statistics
$stats = get_transient('mct_dashboard_stats');
if (false === $stats) {
    $stats = calculate_stats();
    set_transient('mct_dashboard_stats', $stats, 300); // 5 min
}
```

**CDN per tracker.js:**
- Serve tracker.js da CDN
- Riduce latency
- Migliora page load time

### Scalability

**Per high-traffic sites:**

1. **Database:**
   - Read replicas per query
   - Partitioning per date
   - Archive old data

2. **API:**
   - Rate limiting
   - Queue system per Meta API
   - Async processing

3. **Caching:**
   - Redis/Memcached
   - Full page cache
   - API response cache

## Error Handling

### Livelli di Error Handling

**1. JavaScript (tracker.js):**
```javascript
try {
    await tracker.track();
} catch (error) {
    console.error('Tracking failed:', error);
    // Still redirect user
    window.location.href = targetUrl;
}
```

**2. REST API:**
```php
if (!$valid) {
    return new WP_Error(
        'invalid_data',
        'Invalid input',
        array('status' => 400)
    );
}
```

**3. Database:**
```php
if ($result === false) {
    MCT_Database::log_error('Insert failed', array(
        'error' => $wpdb->last_error
    ));
    return false;
}
```

**4. Meta API:**
```php
if (is_wp_error($response)) {
    MCT_Database::log_error('Meta API failed', array(
        'error' => $response->get_error_message()
    ));
    return false;
}
```

### Logging System

**Log Levels:**
- `info` - Normal operations
- `warning` - Non-critical issues
- `error` - Failures requiring attention

**Log Retention:**
- Logs stored in database
- Consider cleanup after 30 days
- Export to external logging service per production

## Deployment

### Production Checklist

- [ ] HTTPS enabled
- [ ] API key secured
- [ ] Meta credentials configured
- [ ] Database backups enabled
- [ ] Error logging enabled
- [ ] Rate limiting configured
- [ ] CDN setup for tracker.js
- [ ] Monitoring setup
- [ ] GDPR compliance checked

### Monitoring

**Metrics to Track:**
- Conversion rate
- API response time
- Meta API success rate
- Database query performance
- Error rate

**Tools:**
- WordPress debug log
- Database slow query log
- Meta Events Manager
- Custom dashboard

## Future Enhancements

**Potential Features:**
- Webhook support per real-time notifications
- GraphQL API oltre REST
- Multi-pixel support
- A/B testing integration
- Fraud detection
- Machine learning per attribution
- Data warehouse export
- Advanced analytics dashboard
