# Meta Conversion Tracker - Complete Documentation

## Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [System Architecture](#system-architecture)
4. [Installation](#installation)
5. [Configuration](#configuration)
6. [Usage](#usage)
7. [API Reference](#api-reference)
8. [Database Access](#database-access)
9. [Troubleshooting](#troubleshooting)
10. [Security](#security)

---

## Overview

**Meta Conversion Tracker** is a professional WordPress plugin designed to capture, store, and send conversion data from landing pages to Meta (Facebook) Conversions API. It provides both REST API access and direct database connectivity for maximum flexibility.

### What It Does

1. **Captures** UTM parameters, FBCLID, IP addresses, user agents, and browser fingerprints
2. **Stores** all data in a dedicated MySQL table
3. **Sends** conversion events to Meta Conversions API automatically
4. **Exposes** REST API endpoints for external integrations
5. **Allows** direct database queries for analytics systems

### Use Case

Perfect for marketing teams running Meta Ads campaigns who need to:
- Track conversions from external landing pages
- Send server-side events to Meta (more reliable than pixel alone)
- Access conversion data from multiple systems (CRM, analytics, etc.)
- Maintain a centralized conversion database

---

## Features

### Core Features

- ✅ **Automatic UTM Parameter Capture** - Source, medium, campaign, content, term
- ✅ **Facebook Click ID (FBCLID) Tracking** - Full attribution support
- ✅ **Browser Fingerprinting** - Canvas, WebGL, screen resolution, timezone
- ✅ **IP Address & User Agent** - Complete visitor identification
- ✅ **Meta Conversions API Integration** - Server-side event tracking
- ✅ **REST API** - Full CRUD operations with authentication
- ✅ **Direct Database Access** - Read-only MySQL user for external systems
- ✅ **WordPress Admin Panel** - Dashboard, settings, conversion viewer
- ✅ **Event Deduplication** - Unique event IDs prevent double-counting
- ✅ **Retry Failed Conversions** - Automatic retry mechanism
- ✅ **Logging System** - Debug and track all operations

### Technical Features

- **CORS Support** - Works with external landing pages
- **API Key Authentication** - Secure access control
- **Pagination** - Handle large datasets efficiently
- **Filtering** - Query by campaign, platform, date range, etc.
- **Statistics Endpoint** - Aggregated conversion metrics
- **Custom Data Support** - Store additional JSON data
- **WordPress Standards** - Follows WP coding standards and best practices

---

## System Architecture

### High-Level Flow

```
Landing Page → JavaScript Tracker → WordPress API → Database
                                                   ↓
                                              Meta API
```

### Components

1. **Landing Page (Frontend)**
   - `tracker.js` - JavaScript library
   - Captures user data automatically
   - Sends POST request to WordPress

2. **WordPress Plugin (Backend)**
   - REST API endpoints
   - Database management
   - Meta API integration
   - Admin interface

3. **Database**
   - `wp_meta_conversions` - Main data table
   - `wp_meta_conversion_logs` - Debug logs
   - Read-only user for external access

4. **Meta Conversions API**
   - Receives server-side events
   - Better attribution than pixel alone
   - Deduplication support

### Data Flow Diagram

```
User Visits Landing Page
         ↓
Tracker.js Captures:
- UTM params from URL
- FBCLID from URL/cookies
- Browser fingerprint
- IP address (server-side)
         ↓
POST to /wp-json/mct/v1/track
         ↓
WordPress Plugin:
1. Validates API key
2. Saves to database
3. Generates event_id
4. Returns conversion_id
         ↓
Meta API Integration:
1. Builds event payload
2. Sends to Meta
3. Stores response
         ↓
User Redirected to Platform
```

---

## Installation

### Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher
- HTTPS enabled (required for Meta API)

### Step 1: Upload Plugin

1. Download the `meta-conversion-tracker` folder
2. Upload to `/wp-content/plugins/` on your WordPress server
3. Or upload as ZIP via WordPress admin: Plugins → Add New → Upload Plugin

### Step 2: Activate Plugin

1. Go to WordPress Admin → Plugins
2. Find "Meta Conversion Tracker"
3. Click "Activate"

### Step 3: Verify Installation

The plugin will automatically:
- Create database tables (`wp_meta_conversions`, `wp_meta_conversion_logs`)
- Generate an API key
- Attempt to create a read-only database user
- Set default options

Check: WordPress Admin → Conversion Tracker → Dashboard

---

## Configuration

### Meta Conversions API Setup

#### Step 1: Get Meta Pixel ID

1. Go to [Meta Events Manager](https://business.facebook.com/events_manager2/list/pixel)
2. Select your Pixel
3. Copy the Pixel ID (15-16 digits)

#### Step 2: Generate Access Token

1. In Events Manager, go to Settings → Conversions API
2. Click "Generate Access Token"
3. Copy the token (starts with `EAAG...`)

#### Step 3: Configure Plugin

1. WordPress Admin → Conversion Tracker → Settings
2. Enter:
   - **Meta Pixel ID**: Your pixel ID
   - **Access Token**: Your access token
   - **Test Event Code**: (Optional) For testing in Events Manager
3. Check "Enable Meta API"
4. Click "Save Settings"
5. Click "Test Connection" to verify

### API Key

Your API key is automatically generated on installation.

**To view/regenerate:**
1. Go to Settings
2. Find "API Key" section
3. Copy the key for use in landing pages
4. Click "Regenerate Key" if needed (⚠️ invalidates old key)

### Logging

Enable logging for debugging:
1. Settings → General Settings
2. Check "Enable Logging"
3. View logs in database: `wp_meta_conversion_logs` table

---

## Usage

### Integrating with Landing Pages

#### Method 1: Using tracker.js (Recommended)

Add to your landing page HTML:

```html
<!-- Load tracker -->
<script src="https://yoursite.com/wp-content/plugins/meta-conversion-tracker/assets/js/tracker.js"></script>

<!-- Initialize -->
<script>
const tracker = new MCTTracker({
    apiUrl: 'https://yoursite.com/wp-json/mct/v1/track',
    apiKey: 'your-api-key-here',
    debug: false
});

// Track when user clicks Discord button
function goToDiscord() {
    tracker.trackAndRedirect('https://discord.gg/yourserver', {
        platform: 'discord',
        event_name: 'Lead'
    });
}

// Track when user clicks Telegram button
function goToTelegram() {
    tracker.trackAndRedirect('https://t.me/yourgroup', {
        platform: 'telegram',
        event_name: 'Lead'
    });
}
</script>
```

#### Method 2: Manual API Call

```javascript
fetch('https://yoursite.com/wp-json/mct/v1/track', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'your-api-key-here'
    },
    body: JSON.stringify({
        utm_campaign: 'warcry_launch',
        utm_source: 'facebook',
        platform: 'discord',
        landing_page: window.location.href
    })
})
.then(response => response.json())
.then(data => console.log('Tracked:', data));
```

### What Gets Captured Automatically

The tracker automatically captures:

- **UTM Parameters**: utm_source, utm_medium, utm_campaign, utm_content, utm_term
- **Facebook IDs**: fbclid, _fbc cookie, _fbp cookie
- **Page Info**: Current URL, referrer
- **User Info**: IP address (server-side), user agent
- **Fingerprint**: Browser fingerprint hash
- **Browser Details**: Screen resolution, timezone, language, canvas hash, WebGL hash

### Custom Data

You can send additional data:

```javascript
tracker.track({
    platform: 'discord',
    custom_data: {
        email: 'user@example.com',
        phone: '+1234567890',
        first_name: 'John',
        last_name: 'Doe',
        country: 'US'
    }
});
```

This data will be hashed and sent to Meta for better matching.

---

## API Reference

### Base URL

```
https://yoursite.com/wp-json/mct/v1
```

### Authentication

All endpoints require authentication via API key.

**Method 1: Header (Recommended)**
```
X-API-Key: your-api-key-here
```

**Method 2: Query Parameter**
```
?api_key=your-api-key-here
```

### Endpoints

#### 1. Track Conversion

**POST** `/track`

Creates a new conversion record.

**Request Body:**
```json
{
  "utm_source": "facebook",
  "utm_medium": "cpc",
  "utm_campaign": "warcry_launch",
  "utm_content": "ad_variant_1",
  "utm_term": "metin2",
  "fbclid": "IwAR1234567890...",
  "fbc": "fb.1.1234567890.IwAR...",
  "fbp": "fb.1.1234567890.1234567890",
  "platform": "discord",
  "landing_page": "https://yoursite.com/landing",
  "referrer": "https://facebook.com",
  "fingerprint": "abc123...",
  "browser_fingerprint": {
    "screen": "1920x1080",
    "timezone": "Europe/Rome",
    "language": "en-US"
  },
  "event_name": "Lead",
  "custom_data": {
    "email": "user@example.com"
  }
}
```

**Response:**
```json
{
  "success": true,
  "conversion_id": 123,
  "event_id": "mct_abc123...",
  "message": "Conversion tracked successfully"
}
```

#### 2. Get Conversions

**GET** `/conversions`

Retrieve conversion records with filters.

**Query Parameters:**
- `limit` (integer): Results per page (default: 100, max: 1000)
- `offset` (integer): Pagination offset (default: 0)
- `order_by` (string): Sort field (default: created_at)
- `order` (string): ASC or DESC (default: DESC)
- `utm_campaign` (string): Filter by campaign
- `utm_source` (string): Filter by source
- `platform` (string): Filter by platform
- `fbclid` (string): Filter by Facebook Click ID
- `start_date` (string): Start date (YYYY-MM-DD)
- `end_date` (string): End date (YYYY-MM-DD)

**Example:**
```
GET /conversions?utm_campaign=warcry_launch&platform=discord&limit=50
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "utm_campaign": "warcry_launch",
      "platform": "discord",
      "created_at": "2025-11-05 22:00:00",
      ...
    }
  ],
  "total": 150,
  "limit": 50,
  "offset": 0
}
```

#### 3. Get Single Conversion

**GET** `/conversions/{id}`

Get details of a specific conversion.

**Example:**
```
GET /conversions/123
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "utm_campaign": "warcry_launch",
    "fbclid": "IwAR...",
    "platform": "discord",
    "ip_address": "1.2.3.4",
    "meta_sent": 1,
    "created_at": "2025-11-05 22:00:00",
    ...
  }
}
```

#### 4. Get Statistics

**GET** `/stats`

Get aggregated conversion statistics.

**Query Parameters:**
- `group_by` (string): day, week, month, campaign, platform (default: day)
- `start_date` (string): Start date (YYYY-MM-DD)
- `end_date` (string): End date (YYYY-MM-DD)

**Example:**
```
GET /stats?group_by=campaign&start_date=2025-11-01
```

**Response:**
```json
{
  "success": true,
  "stats": [
    {
      "group_key": "warcry_launch",
      "total_conversions": 150,
      "unique_clicks": 120,
      "sent_to_meta": 148
    }
  ],
  "totals": {
    "total_conversions": 150,
    "unique_clicks": 120,
    "platforms_used": 2,
    "sent_to_meta": 148
  }
}
```

#### 5. Test Endpoint

**GET** `/test`

Test if API is working (no authentication required).

**Response:**
```json
{
  "success": true,
  "message": "Meta Conversion Tracker API is working!",
  "version": "1.0.0",
  "timestamp": "2025-11-05 22:00:00"
}
```

---

## Database Access

### Connection Information

Get credentials from: WordPress Admin → Conversion Tracker → Database Access

- **Host**: Your database host
- **Port**: 3306 (default)
- **Database**: Your WordPress database name
- **Username**: `mct_readonly_XXXXXXXX`
- **Password**: Auto-generated secure password
- **Table**: `wp_meta_conversions`

### Permissions

The read-only user has **SELECT** permission ONLY on the conversions table. It cannot:
- Modify data (INSERT, UPDATE, DELETE)
- Access other WordPress tables
- Execute administrative commands

### Connection Examples

#### Python

```python
import mysql.connector

db = mysql.connector.connect(
    host="your-host",
    port=3306,
    user="mct_readonly_XXXXXXXX",
    password="your-password",
    database="your-database"
)

cursor = db.cursor(dictionary=True)
cursor.execute("SELECT * FROM wp_meta_conversions WHERE utm_campaign = %s LIMIT 100", ('warcry_launch',))
conversions = cursor.fetchall()

for conv in conversions:
    print(f"ID: {conv['id']}, Platform: {conv['platform']}")

cursor.close()
db.close()
```

#### PHP

```php
$pdo = new PDO(
    "mysql:host=your-host;port=3306;dbname=your-database",
    "mct_readonly_XXXXXXXX",
    "your-password"
);

$stmt = $pdo->prepare("SELECT * FROM wp_meta_conversions WHERE utm_campaign = ? LIMIT 100");
$stmt->execute(['warcry_launch']);
$conversions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($conversions as $conv) {
    echo "ID: {$conv['id']}, Platform: {$conv['platform']}\n";
}
```

#### Node.js

```javascript
const mysql = require('mysql2/promise');

const connection = await mysql.createConnection({
    host: 'your-host',
    port: 3306,
    user: 'mct_readonly_XXXXXXXX',
    password: 'your-password',
    database: 'your-database'
});

const [rows] = await connection.execute(
    'SELECT * FROM wp_meta_conversions WHERE utm_campaign = ? LIMIT 100',
    ['warcry_launch']
);

console.log(rows);
await connection.end();
```

### Database Schema

```sql
CREATE TABLE wp_meta_conversions (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Tracking Parameters
    utm_source VARCHAR(255),
    utm_medium VARCHAR(255),
    utm_campaign VARCHAR(255),
    utm_content VARCHAR(255),
    utm_term VARCHAR(255),
    fbclid VARCHAR(255),
    fbc VARCHAR(255),
    fbp VARCHAR(255),
    
    -- User Data
    ip_address VARCHAR(45),
    user_agent TEXT,
    fingerprint VARCHAR(64),
    browser_fingerprint TEXT,
    
    -- Conversion Data
    platform VARCHAR(20),
    landing_page VARCHAR(500),
    referrer VARCHAR(500),
    country VARCHAR(2),
    city VARCHAR(100),
    
    -- Meta API
    event_id VARCHAR(64),
    event_name VARCHAR(50),
    meta_sent TINYINT(1),
    meta_response TEXT,
    meta_sent_at DATETIME,
    
    -- Additional
    custom_data TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME,
    
    PRIMARY KEY (id),
    INDEX idx_fbclid (fbclid),
    INDEX idx_utm_campaign (utm_campaign),
    INDEX idx_created_at (created_at)
);
```

### Common Queries

**Get today's conversions:**
```sql
SELECT * FROM wp_meta_conversions 
WHERE DATE(created_at) = CURDATE();
```

**Count by campaign:**
```sql
SELECT utm_campaign, COUNT(*) as total 
FROM wp_meta_conversions 
GROUP BY utm_campaign 
ORDER BY total DESC;
```

**Get conversion rate by platform:**
```sql
SELECT 
    platform,
    COUNT(*) as total_conversions,
    COUNT(DISTINCT fbclid) as unique_clicks,
    ROUND(COUNT(*) / COUNT(DISTINCT fbclid) * 100, 2) as conversion_rate
FROM wp_meta_conversions 
WHERE fbclid IS NOT NULL
GROUP BY platform;
```

**Find conversions not sent to Meta:**
```sql
SELECT * FROM wp_meta_conversions 
WHERE meta_sent = 0 
ORDER BY created_at DESC;
```

---

## Troubleshooting

### Conversions Not Appearing

**Check:**
1. API key is correct in landing page
2. CORS is enabled (should be automatic)
3. Check browser console for errors
4. Enable debug mode: `debug: true` in tracker config
5. Check WordPress error logs

**Test:**
```bash
curl -X POST "https://yoursite.com/wp-json/mct/v1/track" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-key" \
  -d '{"platform":"test"}'
```

### Meta API Not Sending

**Check:**
1. Meta Pixel ID is correct (15-16 digits)
2. Access Token is valid
3. "Enable Meta API" is checked in settings
4. Test connection in Settings page
5. Check `meta_sent` field in database

**Common Issues:**
- Invalid access token → Regenerate in Meta Events Manager
- Wrong Pixel ID → Verify in Events Manager
- HTTPS required → Meta API requires SSL

**View Meta Response:**
```sql
SELECT id, meta_sent, meta_response 
FROM wp_meta_conversions 
WHERE meta_sent = 0 
ORDER BY created_at DESC 
LIMIT 10;
```

### Database User Not Created

If automatic creation failed:

1. Go to Database Access page
2. Copy the SQL commands
3. Run them manually in phpMyAdmin or MySQL client
4. Requires database admin privileges

### API Returns 401 Unauthorized

- API key is incorrect
- API key was regenerated (update in landing pages)
- Check header format: `X-API-Key: your-key`

### High Database Size

The conversions table can grow large. To manage:

**Delete old conversions:**
```sql
DELETE FROM wp_meta_conversions 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

**Archive old data:**
```sql
CREATE TABLE wp_meta_conversions_archive AS 
SELECT * FROM wp_meta_conversions 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

DELETE FROM wp_meta_conversions 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

## Security

### Best Practices

1. **HTTPS Only** - Always use SSL for API calls
2. **Secure API Key** - Never commit to public repos
3. **Rotate Keys** - Regenerate API key periodically
4. **Read-Only DB User** - External systems can only read
5. **Rate Limiting** - Consider adding rate limits for production
6. **Input Validation** - All inputs are sanitized
7. **SQL Injection Protection** - Uses prepared statements
8. **XSS Protection** - All outputs are escaped

### API Key Storage

**Good:**
- Environment variables
- Server-side config files
- Secrets management systems

**Bad:**
- Hardcoded in JavaScript (visible to users)
- Committed to Git repositories
- Stored in client-side code

### Database Security

- Read-only user prevents data modification
- User has access to ONE table only
- Use SSL for database connections if possible
- Restrict database access by IP if possible

### GDPR Compliance

The plugin stores:
- IP addresses
- Browser fingerprints
- User behavior data

**To comply with GDPR:**
1. Add privacy notice to landing pages
2. Implement data deletion on request
3. Anonymize IP addresses if required
4. Set data retention policy

**Delete user data:**
```sql
DELETE FROM wp_meta_conversions 
WHERE ip_address = '1.2.3.4';
```

---

## Support & Maintenance

### Logs

View logs in database:
```sql
SELECT * FROM wp_meta_conversion_logs 
ORDER BY created_at DESC 
LIMIT 100;
```

### Backup

**Backup conversions table:**
```bash
mysqldump -u user -p database_name wp_meta_conversions > conversions_backup.sql
```

### Updates

When updating the plugin:
1. Backup database first
2. Deactivate plugin
3. Replace plugin files
4. Reactivate plugin
5. Check Settings page

### Performance

For high-traffic sites:
- Add database indexes for frequently queried fields
- Consider caching API responses
- Archive old conversions regularly
- Use CDN for tracker.js

---

## License

GPL v2 or later

---

## Credits

Developed for conversion tracking and Meta Ads attribution.

Version: 1.0.0
