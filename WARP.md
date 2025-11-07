# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

Meta Conversion Tracker is a WordPress plugin that tracks conversions from external landing pages to Meta (Facebook) Ads. It captures UTM parameters, Facebook Click IDs (FBCLID), browser fingerprints, and sends events to Meta Conversions API. The system consists of:

- **Frontend JavaScript tracker** (`assets/js/tracker.js`) deployed on landing pages
- **WordPress plugin backend** providing REST API and admin interface
- **Meta Conversions API integration** for server-side event tracking
- **Direct database access** for external analytics tools and CRM integrations

## Development Commands

### Installation & Setup

```powershell
# Activate plugin in WordPress (no build step required)
# Navigate to: WordPress Admin → Plugins → Activate "Meta Conversion Tracker"

# Install PHP dependencies (if needed for development)
composer install
```

### Testing the Plugin

```powershell
# Test the REST API endpoint
curl https://your-wordpress-site.com/wp-json/mct/v1/test

# Track a test conversion
curl -X POST https://your-wordpress-site.com/wp-json/mct/v1/track `
  -H "X-API-Key: your-api-key" `
  -H "Content-Type: application/json" `
  -d '{"utm_campaign":"test","platform":"discord"}'

# Test Meta API connection from WordPress Admin
# WordPress Admin → Conversion Tracker → Settings → Test Connection
```

### Database Operations

```powershell
# Access WordPress database (requires WP-CLI)
wp db cli

# View conversions table
wp db query "SELECT * FROM wp_meta_conversions LIMIT 10;"

# Check plugin tables exist
wp db query "SHOW TABLES LIKE 'wp_meta_%';"
```

### Plugin Management

```powershell
# Using WP-CLI (if available)
wp plugin list                           # List all plugins
wp plugin activate meta-conversion-tracker   # Activate plugin
wp plugin deactivate meta-conversion-tracker # Deactivate plugin

# Check plugin version
wp plugin get meta-conversion-tracker --field=version
```

### Cleanup & Maintenance

```powershell
# Manual data cleanup (deletes conversions older than 30 days)
# WordPress Admin → Conversion Tracker → Settings → Data Management → Manual Cleanup

# Check scheduled cron jobs
wp cron event list --filter=mct_daily_cleanup
```

## Architecture

### Core Components

The plugin follows a modular WordPress architecture with clear separation of concerns:

#### 1. Main Plugin Class (`meta-conversion-tracker.php`)
- **Singleton pattern** (`Meta_Conversion_Tracker::get_instance()`)
- Loads all dependencies and initializes components
- Registers WordPress activation/deactivation hooks
- Schedules automatic data cleanup cron job (runs daily)
- Handles CORS headers for cross-origin API requests

#### 2. Database Layer (`includes/class-mct-database.php`)
- **MCT_Database**: Static methods for all database operations
- Creates two tables:
  - `wp_meta_conversions`: Main conversions table with indexed fields (fbclid, utm_campaign, created_at, meta_sent, fingerprint)
  - `wp_meta_conversion_logs`: Logging table for debugging
- Handles automatic data cleanup (30-day retention per GDPR)
- Creates read-only database user for external access
- All queries use prepared statements via WordPress `$wpdb`

**Key indexes for query performance:**
- `fbclid`, `utm_campaign`, `created_at` (most queried fields)
- `meta_sent` (for retry logic), `fingerprint` (for deduplication)

#### 3. REST API Layer (`includes/class-mct-api.php`)
- **MCT_API**: Registers REST endpoints under `/wp-json/mct/v1/`
- Endpoints:
  - `POST /track` - Creates new conversion (requires API key)
  - `GET /conversions` - Lists conversions with filtering
  - `GET /conversions/{id}` - Single conversion lookup
  - `GET /stats` - Aggregated statistics
  - `GET /test` - Public test endpoint
- Authentication: API key via header (`X-API-Key`) or query parameter
- Automatically captures client IP from request

#### 4. Meta API Integration (`includes/class-mct-meta-api.php`)
- **MCT_Meta_API**: Handles Meta Conversions API communication
- Posts events to `https://graph.facebook.com/v18.0/{pixel_id}/events`
- Hashes sensitive data (email, phone, name) with SHA256 before sending
- Builds `fbc` parameter from `fbclid` if not provided
- Supports test events via `test_event_code`
- Updates conversion record with API response and timestamp
- Implements retry logic for failed sends (check `meta_sent` flag)

#### 5. Admin Interface (`admin/class-mct-admin.php`)
- **MCT_Admin**: WordPress admin panel integration
- Five main pages:
  - **Dashboard**: Statistics and recent conversions
  - **Conversions**: Searchable list with filters
  - **Settings**: Meta API credentials, API key management
  - **API Docs**: Complete endpoint reference
  - **Database**: Direct database access credentials
- AJAX handlers for:
  - Viewing conversion details
  - Testing Meta API connection
  - Manual data cleanup
  - Regenerating API keys

#### 6. Frontend Tracker (`assets/js/tracker.js`)
- **MCTTracker class**: JavaScript library for landing pages
- Auto-captures on page load:
  - UTM parameters from URL
  - FBCLID from URL
  - Facebook cookies (`_fbc`, `_fbp`)
  - Browser fingerprint (canvas, WebGL, screen, timezone)
- `track()` method: Sends conversion to WordPress API
- `trackAndRedirect()` method: Tracks then redirects user
- Uses Fetch API with error handling
- LocalStorage for parameter persistence

### Data Flow

**Complete conversion tracking flow:**

```
1. User clicks Meta Ad with UTM params + FBCLID
2. Lands on external page (e.g., landing.com?utm_campaign=warcry&fbclid=...)
3. tracker.js loads and captures all parameters + browser fingerprint
4. User interacts with page (e.g., clicks "Join Discord")
5. tracker.trackAndRedirect() called:
   - POSTs to /wp-json/mct/v1/track with all captured data
   - WordPress validates API key (MCT_API::check_api_key)
   - MCT_Database::insert_conversion() saves to database
   - If Meta API enabled: MCT_Meta_API::send_conversion() fires
   - Returns conversion_id and event_id to client
6. tracker.js redirects user to destination (discord.gg/xxx)
```

### Security Model

- **API Key Authentication**: 32-character random key stored in wp_options
- **Read-only Database User**: Separate MySQL user with SELECT-only permission on conversions table
- **Input Sanitization**: All inputs sanitized with WordPress functions (`sanitize_text_field`, `esc_url_raw`)
- **Output Escaping**: All output escaped (`esc_html`, `esc_attr`, `esc_url`)
- **SQL Injection Prevention**: Prepared statements via `$wpdb->prepare()`
- **HTTPS Required**: Meta API only works with valid SSL certificates

### External Integrations

**Two methods for external systems to access conversion data:**

1. **REST API** (recommended for applications):
   - Requires API key authentication
   - Returns JSON responses
   - Supports filtering, pagination, statistics
   - Example: `GET /wp-json/mct/v1/conversions?utm_campaign=warcry`

2. **Direct Database Access** (for analytics/BI tools):
   - Read-only MySQL user created on plugin activation
   - Credentials shown in WordPress Admin → Database page
   - Direct SELECT queries on `wp_meta_conversions` table
   - Use for: Tableau, Power BI, Python scripts, CRM integrations

## Important Implementation Notes

### When Modifying Database Schema

If you need to add fields to `wp_meta_conversions`:

1. Update `MCT_Database::create_tables()` SQL
2. Add field to `MCT_Database::insert_conversion()` data mapping
3. Consider adding index if field will be filtered/sorted
4. Update `MCT_API::get_track_args()` to accept new parameter
5. Update `tracker.js` if field should be captured client-side
6. Increment `MCT_VERSION` constant in main plugin file

**Important**: WordPress `dbDelta()` is picky about SQL formatting - use exact spacing as shown in existing code.

### When Adding REST API Endpoints

1. Register route in `MCT_API::register_routes()`
2. Create callback method in `MCT_API` class
3. Define `permission_callback` (use `check_api_key` or `check_api_key_or_admin`)
4. Validate parameters with `args` array
5. Return `WP_REST_Response` or `WP_Error`
6. Update API documentation in `admin/views/api-docs.php`

### When Modifying Meta API Integration

- Event names must match Meta's standard events: Lead, Purchase, ViewContent, etc.
- Always hash PII (email, phone) with SHA256 before sending
- Use lowercase and trim strings before hashing
- Include `event_id` for deduplication (generated automatically)
- Test events using `test_event_code` before sending real data
- Check responses in Meta Events Manager dashboard

### Fingerprinting Strategy

The browser fingerprint is used for:
- **Deduplication**: Prevent same user from generating multiple conversions
- **Fraud detection**: Identify suspicious patterns
- **Attribution**: Link conversions to specific devices

Components: canvas hash, WebGL renderer, screen resolution, timezone, language, platform

**Do not rely solely on fingerprints** - they can change with browser updates. Use in combination with fbclid and event_id.

### GDPR Compliance

- Automatic cleanup runs daily via WordPress cron (`mct_daily_cleanup`)
- Default retention: 30 days (`MCT_Database::DATA_RETENTION_DAYS`)
- Deletes both conversions and associated logs
- Manual cleanup available in Settings page
- Consider adding privacy policy notice about data collection

## Common Development Tasks

### Adding a New Trackable Parameter

Example: Adding "ad_id" parameter

1. Update database schema in `class-mct-database.php`:
```php
ad_id VARCHAR(100) DEFAULT NULL,
INDEX idx_ad_id (ad_id),  // If you'll query by this field
```

2. Add to insert function:
```php
'ad_id' => isset($data['ad_id']) ? sanitize_text_field($data['ad_id']) : null,
```

3. Update API endpoint in `class-mct-api.php`:
```php
'ad_id' => array(
    'required' => false,
    'type' => 'string',
    'sanitize_callback' => 'sanitize_text_field',
),
```

4. Update tracker.js to capture it:
```javascript
ad_id: this.getUrlParam('ad_id'),
```

### Debugging Conversion Issues

1. Enable logging: WordPress Admin → Settings → Enable Logging
2. Check `wp_meta_conversion_logs` table for errors
3. Use `/wp-json/mct/v1/test` endpoint to verify API is accessible
4. Check browser console on landing page for JavaScript errors
5. Verify API key is correct
6. Check Meta Events Manager for API errors
7. Review WordPress debug log at `wp-content/debug.log`

### Testing Meta API Integration

Always use test events before production:

1. Get test event code from Meta Events Manager → Test Events
2. Enter code in WordPress Admin → Settings → Test Event Code
3. Send test conversion
4. Verify event appears in Meta Events Manager test events section
5. Once validated, remove test code for production events

## File Structure Reference

```
meta-conversion-tracker/
├── meta-conversion-tracker.php          # Main plugin file, entry point
├── includes/                            # Core functionality
│   ├── class-mct-database.php          # Database operations
│   ├── class-mct-api.php               # REST API endpoints
│   ├── class-mct-meta-api.php          # Meta Conversions API
│   └── class-mct-fingerprint.php       # Browser fingerprinting utilities
├── admin/                               # WordPress admin interface
│   ├── class-mct-admin.php             # Admin pages controller
│   └── views/                          # Admin page templates
│       ├── dashboard.php
│       ├── conversions.php
│       ├── settings.php
│       ├── api-docs.php
│       └── database.php
├── assets/                              # Frontend assets
│   ├── js/
│   │   ├── tracker.js                  # Landing page tracker (deploy to external sites)
│   │   └── admin.js                    # Admin panel JavaScript
│   └── css/
│       └── admin.css                   # Admin panel styles
├── docs/                                # Documentation
│   ├── README.md                       # Detailed technical docs
│   ├── QUICK-START.md                  # 10-minute setup guide
│   ├── ARCHITECTURE.md                 # System architecture details
│   ├── API-EXAMPLES.md                 # REST API examples (Python, PHP, Node.js, etc.)
│   └── DATA-RETENTION.md               # GDPR compliance info
└── examples/                            # Example integration files
    └── warcry-integration.html         # Sample landing page
```

## WordPress-Specific Patterns

This codebase follows WordPress plugin development best practices:

- **Prefix everything**: All functions, classes, and database tables use `mct_` or `MCT_` prefix
- **Use WordPress APIs**: `$wpdb` for database, `wp_remote_post` for HTTP, `wp_json_encode` for JSON
- **Sanitize inputs**: Always use `sanitize_text_field()`, `esc_url_raw()`, etc.
- **Escape outputs**: Always use `esc_html()`, `esc_attr()`, `esc_url()`
- **Nonce verification**: AJAX handlers verify nonces for security
- **Capability checks**: Admin pages check `manage_options` capability
- **Translation ready**: All strings wrapped in `__()` or `esc_html__()`

### WordPress Hooks Used

- `plugins_loaded`: Initialize plugin components
- `rest_api_init`: Register REST API endpoints and CORS headers
- `admin_menu`: Add admin menu pages
- `admin_enqueue_scripts`: Load admin CSS/JS
- `wp_ajax_{action}`: Handle AJAX requests
- `register_activation_hook`: Create tables, schedule cron
- `register_deactivation_hook`: Unschedule cron

## Version History

Current version: **1.0.4**

Recent changes:
- **1.0.4**: Fixed JSON parsing error in conversion details modal
- **1.0.3**: Added AJAX handler for viewing conversion details
- **1.0.2**: GDPR compliance with automatic 30-day data retention
- **1.0.1**: Fixed referrer parameter validation bug
- **1.0.0**: Initial release

See `CHANGELOG.md` for complete version history.
