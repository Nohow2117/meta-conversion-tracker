# Changelog

All notable changes to Meta Conversion Tracker will be documented in this file.

## [1.0.3] - 2025-11-06

### Fixed
- **Bug Fix: View conversion details modal**
  - Added missing AJAX handler `ajax_get_conversion_details()`
  - Fixed "Request failed" error when clicking "View" button
  - Modal now correctly displays all conversion data
  
  **Files changed:**
  - `admin/class-mct-admin.php` - Added AJAX handler and registered action

## [1.0.2] - 2025-11-06

### Added
- **Automatic Data Cleanup (GDPR Compliance)**
  - Conversions older than 30 days are automatically deleted daily
  - WordPress cron job runs daily to clean old data
  - Scheduled on plugin activation, unscheduled on deactivation
  
- **Manual Data Cleanup**
  - New "Data Management" section in Settings page
  - Manual cleanup button to delete old data immediately
  - Shows next scheduled cleanup time
  - AJAX-powered with real-time feedback
  
  **Files changed:**
  - `includes/class-mct-database.php` - Added `cleanup_old_conversions()` method and `DATA_RETENTION_DAYS` constant
  - `meta-conversion-tracker.php` - Added cron job scheduling/unscheduling
  - `admin/class-mct-admin.php` - Added AJAX handler for manual cleanup
  - `admin/views/settings.php` - Added Data Management section
  - `assets/js/admin.js` - Added manual cleanup button handler

### Technical Details
- Cleanup deletes both conversions and logs older than 30 days
- Uses WordPress cron system (`wp_schedule_event`)
- Prepared SQL statements for security
- Logs cleanup actions for audit trail

## [1.0.1] - 2025-11-06

### Fixed
- **Bug Fix: referrer parameter validation error**
  - Fixed `tracker.js` sending `null` instead of empty string for referrer when `document.referrer` is empty
  - Changed `referrer: document.referrer || null` to `referrer: document.referrer || ''`
  - Added `sanitize_callback` to all API endpoint parameters for better data handling
  - Removed strict `type: 'object'` validation for `browser_fingerprint` and `custom_data` to be more permissive
  
  **Error before fix:**
  ```
  POST /wp-json/mct/v1/track 400 (Bad Request)
  Invalid parameter(s): referrer
  ```
  
  **Files changed:**
  - `assets/js/tracker.js` - Line 53: Changed null to empty string
  - `includes/class-mct-api.php` - Lines 432-449: Added sanitize callbacks and relaxed validation

### Technical Details
The WordPress REST API strictly validates parameter types. When `document.referrer` was empty, `tracker.js` was sending `null` which failed the `type: 'string'` validation. The fix ensures an empty string is sent instead, which passes validation.

---

## [1.0.0] - 2025-11-05

### Added
- Initial release
- WordPress plugin for Meta Ads conversion tracking
- REST API endpoints for tracking and querying conversions
- Meta Conversions API integration
- Browser fingerprinting
- UTM and FBCLID capture
- Direct database access with read-only user
- WordPress admin dashboard
- Complete documentation
- Landing page JavaScript tracker
- Example integration files

### Features
- Automatic UTM parameter capture
- Facebook Click ID (FBCLID) tracking
- Browser fingerprint generation
- IP address logging
- Meta Conversions API integration with retry logic
- REST API with API key authentication
- Direct database access for external systems
- WordPress admin interface with:
  - Dashboard with statistics
  - Conversions list with filters
  - Settings page
  - API documentation
  - Database access information
- CORS support for cross-origin requests
- Event deduplication
- Custom data support
- Comprehensive logging system

### Security
- API key authentication
- Read-only database user
- Input sanitization
- Output escaping
- SQL injection prevention
- XSS protection
- HTTPS required for Meta API

### Documentation
- Complete README with architecture overview
- Quick start guide (10 minutes setup)
- Detailed architecture documentation
- API examples in multiple languages (Python, PHP, Node.js, cURL, etc.)
- Developer integration instructions
- Troubleshooting guide
- Security best practices
