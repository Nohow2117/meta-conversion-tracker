<?php
/**
 * Browser Fingerprinting Utilities
 * Helper functions for generating and validating fingerprints
 */

if (!defined('ABSPATH')) {
    exit;
}

class MCT_Fingerprint {
    
    /**
     * Generate server-side fingerprint from available data
     */
    public static function generate_server_fingerprint($data = array()) {
        $components = array();
        
        // IP address
        if (!empty($data['ip_address'])) {
            $components[] = $data['ip_address'];
        }
        
        // User agent
        if (!empty($data['user_agent'])) {
            $components[] = $data['user_agent'];
        }
        
        // Accept language
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $components[] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }
        
        // Accept encoding
        if (!empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            $components[] = $_SERVER['HTTP_ACCEPT_ENCODING'];
        }
        
        // Generate hash
        $fingerprint_string = implode('|', $components);
        return hash('sha256', $fingerprint_string);
    }
    
    /**
     * Validate fingerprint format
     */
    public static function validate_fingerprint($fingerprint) {
        // Check if it's a valid SHA256 hash (64 hex characters)
        return preg_match('/^[a-f0-9]{64}$/i', $fingerprint) === 1;
    }
    
    /**
     * Parse browser fingerprint data
     */
    public static function parse_browser_fingerprint($fingerprint_data) {
        if (is_string($fingerprint_data)) {
            $fingerprint_data = json_decode($fingerprint_data, true);
        }
        
        if (!is_array($fingerprint_data)) {
            return null;
        }
        
        $parsed = array(
            'screen_resolution' => isset($fingerprint_data['screen']) ? $fingerprint_data['screen'] : null,
            'timezone' => isset($fingerprint_data['timezone']) ? $fingerprint_data['timezone'] : null,
            'language' => isset($fingerprint_data['language']) ? $fingerprint_data['language'] : null,
            'platform' => isset($fingerprint_data['platform']) ? $fingerprint_data['platform'] : null,
            'canvas_hash' => isset($fingerprint_data['canvas']) ? $fingerprint_data['canvas'] : null,
            'webgl_hash' => isset($fingerprint_data['webgl']) ? $fingerprint_data['webgl'] : null,
            'plugins' => isset($fingerprint_data['plugins']) ? $fingerprint_data['plugins'] : null,
            'fonts' => isset($fingerprint_data['fonts']) ? $fingerprint_data['fonts'] : null,
        );
        
        return $parsed;
    }
    
    /**
     * Get browser info from user agent
     */
    public static function parse_user_agent($user_agent) {
        $browser_info = array(
            'browser' => 'Unknown',
            'version' => 'Unknown',
            'platform' => 'Unknown',
            'is_mobile' => false,
            'is_bot' => false,
        );
        
        // Check if bot
        $bots = array('bot', 'crawler', 'spider', 'scraper', 'curl', 'wget');
        foreach ($bots as $bot) {
            if (stripos($user_agent, $bot) !== false) {
                $browser_info['is_bot'] = true;
                break;
            }
        }
        
        // Detect browser
        if (preg_match('/MSIE|Trident/i', $user_agent)) {
            $browser_info['browser'] = 'Internet Explorer';
        } elseif (preg_match('/Edge/i', $user_agent)) {
            $browser_info['browser'] = 'Edge';
        } elseif (preg_match('/Chrome/i', $user_agent)) {
            $browser_info['browser'] = 'Chrome';
        } elseif (preg_match('/Safari/i', $user_agent)) {
            $browser_info['browser'] = 'Safari';
        } elseif (preg_match('/Firefox/i', $user_agent)) {
            $browser_info['browser'] = 'Firefox';
        } elseif (preg_match('/Opera|OPR/i', $user_agent)) {
            $browser_info['browser'] = 'Opera';
        }
        
        // Detect platform
        if (preg_match('/Windows/i', $user_agent)) {
            $browser_info['platform'] = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $user_agent)) {
            $browser_info['platform'] = 'Mac';
        } elseif (preg_match('/Linux/i', $user_agent)) {
            $browser_info['platform'] = 'Linux';
        } elseif (preg_match('/Android/i', $user_agent)) {
            $browser_info['platform'] = 'Android';
            $browser_info['is_mobile'] = true;
        } elseif (preg_match('/iPhone|iPad|iPod/i', $user_agent)) {
            $browser_info['platform'] = 'iOS';
            $browser_info['is_mobile'] = true;
        }
        
        // Detect mobile
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $user_agent)) {
            $browser_info['is_mobile'] = true;
        }
        
        return $browser_info;
    }
    
    /**
     * Check if fingerprint exists in database
     */
    public static function fingerprint_exists($fingerprint) {
        global $wpdb;
        $table_name = MCT_Database::get_table_name();
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE fingerprint = %s",
            $fingerprint
        );
        
        return $wpdb->get_var($query) > 0;
    }
    
    /**
     * Get conversions by fingerprint
     */
    public static function get_conversions_by_fingerprint($fingerprint, $limit = 10) {
        global $wpdb;
        $table_name = MCT_Database::get_table_name();
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE fingerprint = %s ORDER BY created_at DESC LIMIT %d",
            $fingerprint,
            $limit
        );
        
        return $wpdb->get_results($query, ARRAY_A);
    }
}
