<?php
/**
 * Settings View
 */
if (!defined('ABSPATH')) exit;

$meta_pixel_id = get_option('mct_meta_pixel_id', '');
$meta_access_token = get_option('mct_meta_access_token', '');
$meta_test_code = get_option('mct_meta_test_code', '');
$enable_meta_api = get_option('mct_enable_meta_api', false);
$enable_logging = get_option('mct_enable_logging', true);
$api_key = get_option('mct_api_key', '');
?>

<div class="wrap mct-admin">
    <h1>Settings</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('mct_settings_nonce'); ?>
        
        <div class="mct-card">
            <h2>Meta Conversions API</h2>
            <p>Configure your Meta (Facebook) Pixel and Conversions API settings.</p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="mct_meta_pixel_id">Meta Pixel ID</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="mct_meta_pixel_id" 
                               name="mct_meta_pixel_id" 
                               value="<?php echo esc_attr($meta_pixel_id); ?>" 
                               class="regular-text" 
                               placeholder="123456789012345">
                        <p class="description">Your Meta Pixel ID (15-16 digits)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="mct_meta_access_token">Access Token</label>
                    </th>
                    <td>
                        <input type="password" 
                               id="mct_meta_access_token" 
                               name="mct_meta_access_token" 
                               value="<?php echo esc_attr($meta_access_token); ?>" 
                               class="regular-text" 
                               placeholder="Your Meta Access Token">
                        <p class="description">
                            Generate from: 
                            <a href="https://business.facebook.com/events_manager2/list/pixel" target="_blank">Meta Events Manager</a>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="mct_meta_test_code">Test Event Code</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="mct_meta_test_code" 
                               name="mct_meta_test_code" 
                               value="<?php echo esc_attr($meta_test_code); ?>" 
                               class="regular-text" 
                               placeholder="TEST12345">
                        <p class="description">Optional: Test event code for debugging in Meta Events Manager</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Enable Meta API</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="mct_enable_meta_api" 
                                   value="1" 
                                   <?php checked($enable_meta_api, 1); ?>>
                            Send conversion events to Meta automatically
                        </label>
                    </td>
                </tr>
            </table>
            
            <p>
                <button type="button" id="mct-test-connection" class="button">Test Connection</button>
                <span id="mct-test-result"></span>
            </p>
        </div>
        
        <div class="mct-card">
            <h2>API Key</h2>
            <p>This key is required for external systems to send conversion data.</p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">API Key</th>
                    <td>
                        <input type="text" 
                               id="mct_api_key_display" 
                               value="<?php echo esc_attr($api_key); ?>" 
                               class="regular-text" 
                               readonly>
                        <button type="button" id="mct-regenerate-key" class="button">Regenerate Key</button>
                        <p class="description">⚠️ Regenerating will invalidate the old key for all integrations</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="mct-card">
            <h2>General Settings</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Enable Logging</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="mct_enable_logging" 
                                   value="1" 
                                   <?php checked($enable_logging, 1); ?>>
                            Log all conversion events and API calls (useful for debugging)
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="submit" name="mct_save_settings" class="button button-primary" value="Save Settings">
        </p>
    </form>
</div>
