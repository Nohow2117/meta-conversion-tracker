<?php
/**
 * API Documentation View
 */
if (!defined('ABSPATH')) exit;
?>

<div class="wrap mct-admin">
    <h1>API Documentation</h1>
    
    <div class="mct-card">
        <h2>Authentication</h2>
        <p>All API requests require authentication using your API key.</p>
        
        <h3>Your API Key</h3>
        <div class="mct-code-block">
            <code><?php echo esc_html($api_key); ?></code>
            <button class="button button-small mct-copy-btn" data-copy="<?php echo esc_attr($api_key); ?>">Copy</button>
        </div>
        
        <h3>Authentication Methods</h3>
        <p><strong>Method 1:</strong> Header (Recommended)</p>
        <div class="mct-code-block">
            <code>X-API-Key: <?php echo esc_html($api_key); ?></code>
        </div>
        
        <p><strong>Method 2:</strong> Query Parameter</p>
        <div class="mct-code-block">
            <code>?api_key=<?php echo esc_html($api_key); ?></code>
        </div>
    </div>
    
    <div class="mct-card">
        <h2>Endpoints</h2>
        
        <h3>1. Track Conversion</h3>
        <p>Send a new conversion event.</p>
        
        <div class="mct-endpoint">
            <span class="mct-method mct-method-post">POST</span>
            <code><?php echo esc_url($site_url); ?>/wp-json/mct/v1/track</code>
        </div>
        
        <h4>Request Body (JSON)</h4>
        <pre class="mct-code-block">{
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
    "language": "en-US",
    "platform": "Win32"
  },
  "event_name": "Lead",
  "custom_data": {
    "email": "user@example.com",
    "phone": "+1234567890"
  }
}</pre>
        
        <h4>Response</h4>
        <pre class="mct-code-block">{
  "success": true,
  "conversion_id": 123,
  "event_id": "mct_abc123...",
  "message": "Conversion tracked successfully"
}</pre>
        
        <h4>cURL Example</h4>
        <pre class="mct-code-block">curl -X POST "<?php echo esc_url($site_url); ?>/wp-json/mct/v1/track" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: <?php echo esc_html($api_key); ?>" \
  -d '{
    "utm_campaign": "test_campaign",
    "platform": "discord",
    "landing_page": "https://example.com"
  }'</pre>
    </div>
    
    <div class="mct-card">
        <h3>2. Get Conversions</h3>
        <p>Retrieve conversion records with optional filters.</p>
        
        <div class="mct-endpoint">
            <span class="mct-method mct-method-get">GET</span>
            <code><?php echo esc_url($site_url); ?>/wp-json/mct/v1/conversions</code>
        </div>
        
        <h4>Query Parameters</h4>
        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>limit</code></td>
                    <td>integer</td>
                    <td>Number of results (default: 100, max: 1000)</td>
                </tr>
                <tr>
                    <td><code>offset</code></td>
                    <td>integer</td>
                    <td>Pagination offset (default: 0)</td>
                </tr>
                <tr>
                    <td><code>utm_campaign</code></td>
                    <td>string</td>
                    <td>Filter by campaign</td>
                </tr>
                <tr>
                    <td><code>platform</code></td>
                    <td>string</td>
                    <td>Filter by platform (discord/telegram)</td>
                </tr>
                <tr>
                    <td><code>fbclid</code></td>
                    <td>string</td>
                    <td>Filter by Facebook Click ID</td>
                </tr>
                <tr>
                    <td><code>start_date</code></td>
                    <td>string</td>
                    <td>Start date (YYYY-MM-DD)</td>
                </tr>
                <tr>
                    <td><code>end_date</code></td>
                    <td>string</td>
                    <td>End date (YYYY-MM-DD)</td>
                </tr>
            </tbody>
        </table>
        
        <h4>cURL Example</h4>
        <pre class="mct-code-block">curl "<?php echo esc_url($site_url); ?>/wp-json/mct/v1/conversions?utm_campaign=warcry_launch&limit=50" \
  -H "X-API-Key: <?php echo esc_html($api_key); ?>"</pre>
    </div>
    
    <div class="mct-card">
        <h3>3. Get Single Conversion</h3>
        
        <div class="mct-endpoint">
            <span class="mct-method mct-method-get">GET</span>
            <code><?php echo esc_url($site_url); ?>/wp-json/mct/v1/conversions/{id}</code>
        </div>
        
        <h4>cURL Example</h4>
        <pre class="mct-code-block">curl "<?php echo esc_url($site_url); ?>/wp-json/mct/v1/conversions/123" \
  -H "X-API-Key: <?php echo esc_html($api_key); ?>"</pre>
    </div>
    
    <div class="mct-card">
        <h3>4. Get Statistics</h3>
        
        <div class="mct-endpoint">
            <span class="mct-method mct-method-get">GET</span>
            <code><?php echo esc_url($site_url); ?>/wp-json/mct/v1/stats</code>
        </div>
        
        <h4>Query Parameters</h4>
        <ul>
            <li><code>group_by</code>: day, week, month, campaign, platform</li>
            <li><code>start_date</code>: YYYY-MM-DD</li>
            <li><code>end_date</code>: YYYY-MM-DD</li>
        </ul>
        
        <h4>cURL Example</h4>
        <pre class="mct-code-block">curl "<?php echo esc_url($site_url); ?>/wp-json/mct/v1/stats?group_by=campaign" \
  -H "X-API-Key: <?php echo esc_html($api_key); ?>"</pre>
    </div>
    
    <div class="mct-card">
        <h2>JavaScript Integration</h2>
        <p>Add this code to your landing pages to automatically track conversions.</p>
        
        <h3>Basic Implementation</h3>
        <pre class="mct-code-block">&lt;script src="<?php echo esc_url(MCT_PLUGIN_URL); ?>assets/js/tracker.js"&gt;&lt;/script&gt;
&lt;script&gt;
// Initialize tracker
const tracker = new MCTTracker({
    apiUrl: '<?php echo esc_url($site_url); ?>/wp-json/mct/v1/track',
    apiKey: '<?php echo esc_html($api_key); ?>'
});

// Track conversion when user clicks platform
function redirectToPlatform(platform, url) {
    tracker.track({
        platform: platform,
        landing_page: window.location.href
    }).then(() => {
        window.location.href = url;
    });
}
&lt;/script&gt;</pre>
    </div>
</div>
