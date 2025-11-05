<?php
/**
 * Meta Conversions API Integration
 * Sends conversion events to Facebook/Meta
 */

if (!defined('ABSPATH')) {
    exit;
}

class MCT_Meta_API {
    
    /**
     * Meta API version
     */
    const API_VERSION = 'v18.0';
    
    /**
     * Send conversion to Meta
     */
    public static function send_conversion($conversion_id) {
        $conversion = MCT_Database::get_conversion($conversion_id);
        
        if (!$conversion) {
            MCT_Database::log_error('Conversion not found for Meta API', array('conversion_id' => $conversion_id));
            return false;
        }
        
        // Check if already sent
        if ($conversion['meta_sent'] == 1) {
            MCT_Database::log('Conversion already sent to Meta', array('conversion_id' => $conversion_id));
            return true;
        }
        
        // Get Meta credentials
        $pixel_id = get_option('mct_meta_pixel_id');
        $access_token = get_option('mct_meta_access_token');
        $test_code = get_option('mct_meta_test_code', '');
        
        if (empty($pixel_id) || empty($access_token)) {
            MCT_Database::log_error('Meta API credentials not configured', array('conversion_id' => $conversion_id));
            return false;
        }
        
        // Build event data
        $event_data = self::build_event_data($conversion);
        
        // Build request payload
        $payload = array(
            'data' => array($event_data),
            'access_token' => $access_token,
        );
        
        // Add test event code if provided
        if (!empty($test_code)) {
            $payload['test_event_code'] = $test_code;
        }
        
        // API endpoint
        $url = "https://graph.facebook.com/" . self::API_VERSION . "/{$pixel_id}/events";
        
        // Send request
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($payload),
            'timeout' => 30,
        ));
        
        // Handle response
        if (is_wp_error($response)) {
            MCT_Database::log_error('Meta API request failed', array(
                'conversion_id' => $conversion_id,
                'error' => $response->get_error_message(),
            ));
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        // Update conversion record
        MCT_Database::update_conversion($conversion_id, array(
            'meta_sent' => ($response_code === 200) ? 1 : 0,
            'meta_response' => $response_body,
            'meta_sent_at' => current_time('mysql'),
        ));
        
        if ($response_code === 200) {
            MCT_Database::log('Conversion sent to Meta successfully', array(
                'conversion_id' => $conversion_id,
                'response' => $response_data,
            ));
            return true;
        } else {
            MCT_Database::log_error('Meta API returned error', array(
                'conversion_id' => $conversion_id,
                'status_code' => $response_code,
                'response' => $response_data,
            ));
            return false;
        }
    }
    
    /**
     * Build event data for Meta API
     */
    private static function build_event_data($conversion) {
        $event_time = strtotime($conversion['created_at']);
        
        // User data
        $user_data = array();
        
        // Email and phone (if available in custom_data)
        $custom_data = !empty($conversion['custom_data']) ? json_decode($conversion['custom_data'], true) : array();
        
        if (!empty($custom_data['email'])) {
            $user_data['em'] = hash('sha256', strtolower(trim($custom_data['email'])));
        }
        
        if (!empty($custom_data['phone'])) {
            $user_data['ph'] = hash('sha256', preg_replace('/[^0-9]/', '', $custom_data['phone']));
        }
        
        if (!empty($custom_data['first_name'])) {
            $user_data['fn'] = hash('sha256', strtolower(trim($custom_data['first_name'])));
        }
        
        if (!empty($custom_data['last_name'])) {
            $user_data['ln'] = hash('sha256', strtolower(trim($custom_data['last_name'])));
        }
        
        // IP and User Agent
        if (!empty($conversion['ip_address'])) {
            $user_data['client_ip_address'] = $conversion['ip_address'];
        }
        
        if (!empty($conversion['user_agent'])) {
            $user_data['client_user_agent'] = $conversion['user_agent'];
        }
        
        // FBC and FBP
        if (!empty($conversion['fbc'])) {
            $user_data['fbc'] = $conversion['fbc'];
        } elseif (!empty($conversion['fbclid'])) {
            // Build fbc from fbclid if not provided
            $user_data['fbc'] = 'fb.1.' . $event_time . '.' . $conversion['fbclid'];
        }
        
        if (!empty($conversion['fbp'])) {
            $user_data['fbp'] = $conversion['fbp'];
        }
        
        // Country and city
        if (!empty($conversion['country'])) {
            $user_data['country'] = hash('sha256', strtolower($conversion['country']));
        }
        
        if (!empty($conversion['city'])) {
            $user_data['ct'] = hash('sha256', strtolower($conversion['city']));
        }
        
        // Custom data for the event
        $event_custom_data = array(
            'currency' => 'USD',
            'value' => 0.00,
        );
        
        if (!empty($conversion['platform'])) {
            $event_custom_data['content_category'] = $conversion['platform'];
        }
        
        if (!empty($conversion['utm_campaign'])) {
            $event_custom_data['content_name'] = $conversion['utm_campaign'];
        }
        
        // Build event
        $event = array(
            'event_name' => $conversion['event_name'],
            'event_time' => $event_time,
            'event_id' => $conversion['event_id'],
            'event_source_url' => !empty($conversion['landing_page']) ? $conversion['landing_page'] : site_url(),
            'action_source' => 'website',
            'user_data' => $user_data,
            'custom_data' => $event_custom_data,
        );
        
        return $event;
    }
    
    /**
     * Test Meta API connection
     */
    public static function test_connection() {
        $pixel_id = get_option('mct_meta_pixel_id');
        $access_token = get_option('mct_meta_access_token');
        
        if (empty($pixel_id) || empty($access_token)) {
            return array(
                'success' => false,
                'message' => 'Meta API credentials not configured',
            );
        }
        
        // Create test event
        $test_event = array(
            'event_name' => 'Test',
            'event_time' => time(),
            'event_id' => 'test_' . uniqid(),
            'event_source_url' => site_url(),
            'action_source' => 'website',
            'user_data' => array(
                'client_ip_address' => '127.0.0.1',
                'client_user_agent' => 'Meta Conversion Tracker Test',
            ),
        );
        
        $payload = array(
            'data' => array($test_event),
            'access_token' => $access_token,
            'test_event_code' => get_option('mct_meta_test_code', 'TEST12345'),
        );
        
        $url = "https://graph.facebook.com/" . self::API_VERSION . "/{$pixel_id}/events";
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($payload),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message(),
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code === 200) {
            return array(
                'success' => true,
                'message' => 'Connection successful! Check your Meta Events Manager.',
                'response' => $response_body,
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Meta API returned error',
                'response' => $response_body,
            );
        }
    }
    
    /**
     * Retry failed conversions
     */
    public static function retry_failed_conversions($limit = 50) {
        global $wpdb;
        $table_name = MCT_Database::get_table_name();
        
        // Get conversions that failed to send
        $query = $wpdb->prepare(
            "SELECT id FROM $table_name WHERE meta_sent = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) LIMIT %d",
            $limit
        );
        
        $conversion_ids = $wpdb->get_col($query);
        
        $success_count = 0;
        $fail_count = 0;
        
        foreach ($conversion_ids as $conversion_id) {
            if (self::send_conversion($conversion_id)) {
                $success_count++;
            } else {
                $fail_count++;
            }
            
            // Small delay to avoid rate limiting
            usleep(100000); // 0.1 seconds
        }
        
        return array(
            'total' => count($conversion_ids),
            'success' => $success_count,
            'failed' => $fail_count,
        );
    }
}
