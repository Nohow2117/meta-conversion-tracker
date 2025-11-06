<?php
/**
 * Database Management Class
 * Handles table creation, queries, and database user management
 */

if (!defined('ABSPATH')) {
    exit;
}

class MCT_Database {
    
    /**
     * Table name
     */
    const TABLE_NAME = 'meta_conversions';
    
    /**
     * Data retention period in days
     */
    const DATA_RETENTION_DAYS = 30;
    
    /**
     * Get full table name with WordPress prefix
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            
            -- Tracking Parameters
            utm_source VARCHAR(255) DEFAULT NULL,
            utm_medium VARCHAR(255) DEFAULT NULL,
            utm_campaign VARCHAR(255) DEFAULT NULL,
            utm_content VARCHAR(255) DEFAULT NULL,
            utm_term VARCHAR(255) DEFAULT NULL,
            fbclid VARCHAR(255) DEFAULT NULL,
            fbc VARCHAR(255) DEFAULT NULL,
            fbp VARCHAR(255) DEFAULT NULL,
            
            -- User Data
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            fingerprint VARCHAR(64) DEFAULT NULL,
            browser_fingerprint TEXT DEFAULT NULL,
            
            -- Conversion Data
            platform VARCHAR(20) DEFAULT NULL,
            landing_page VARCHAR(500) DEFAULT NULL,
            referrer VARCHAR(500) DEFAULT NULL,
            
            -- Geolocation (optional)
            country VARCHAR(2) DEFAULT NULL,
            city VARCHAR(100) DEFAULT NULL,
            
            -- Meta API Integration
            event_id VARCHAR(64) DEFAULT NULL,
            event_name VARCHAR(50) DEFAULT 'Lead',
            meta_sent TINYINT(1) DEFAULT 0,
            meta_response TEXT DEFAULT NULL,
            meta_sent_at DATETIME DEFAULT NULL,
            
            -- Additional Data (JSON format for flexibility)
            custom_data TEXT DEFAULT NULL,
            
            -- Timestamps
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            
            PRIMARY KEY (id),
            INDEX idx_fbclid (fbclid),
            INDEX idx_event_id (event_id),
            INDEX idx_utm_campaign (utm_campaign),
            INDEX idx_platform (platform),
            INDEX idx_created_at (created_at),
            INDEX idx_meta_sent (meta_sent),
            INDEX idx_fingerprint (fingerprint)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create logs table
        self::create_logs_table();
    }
    
    /**
     * Create logs table for debugging
     */
    private static function create_logs_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'meta_conversion_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            conversion_id BIGINT(20) UNSIGNED DEFAULT NULL,
            log_level VARCHAR(20) DEFAULT 'info',
            message TEXT NOT NULL,
            context TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            
            PRIMARY KEY (id),
            INDEX idx_conversion_id (conversion_id),
            INDEX idx_log_level (log_level),
            INDEX idx_created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Insert conversion record
     */
    public static function insert_conversion($data) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        // Generate unique event ID for Meta deduplication
        if (empty($data['event_id'])) {
            $data['event_id'] = self::generate_event_id();
        }
        
        // Set created_at timestamp
        if (empty($data['created_at'])) {
            $data['created_at'] = current_time('mysql');
        }
        
        // Prepare data
        $insert_data = array(
            'utm_source' => isset($data['utm_source']) ? sanitize_text_field($data['utm_source']) : null,
            'utm_medium' => isset($data['utm_medium']) ? sanitize_text_field($data['utm_medium']) : null,
            'utm_campaign' => isset($data['utm_campaign']) ? sanitize_text_field($data['utm_campaign']) : null,
            'utm_content' => isset($data['utm_content']) ? sanitize_text_field($data['utm_content']) : null,
            'utm_term' => isset($data['utm_term']) ? sanitize_text_field($data['utm_term']) : null,
            'fbclid' => isset($data['fbclid']) ? sanitize_text_field($data['fbclid']) : null,
            'fbc' => isset($data['fbc']) ? sanitize_text_field($data['fbc']) : null,
            'fbp' => isset($data['fbp']) ? sanitize_text_field($data['fbp']) : null,
            'ip_address' => isset($data['ip_address']) ? sanitize_text_field($data['ip_address']) : null,
            'user_agent' => isset($data['user_agent']) ? sanitize_text_field($data['user_agent']) : null,
            'fingerprint' => isset($data['fingerprint']) ? sanitize_text_field($data['fingerprint']) : null,
            'browser_fingerprint' => isset($data['browser_fingerprint']) ? wp_json_encode($data['browser_fingerprint']) : null,
            'platform' => isset($data['platform']) ? sanitize_text_field($data['platform']) : null,
            'landing_page' => isset($data['landing_page']) ? esc_url_raw($data['landing_page']) : null,
            'referrer' => isset($data['referrer']) ? esc_url_raw($data['referrer']) : null,
            'country' => isset($data['country']) ? sanitize_text_field($data['country']) : null,
            'city' => isset($data['city']) ? sanitize_text_field($data['city']) : null,
            'event_id' => $data['event_id'],
            'event_name' => isset($data['event_name']) ? sanitize_text_field($data['event_name']) : 'Lead',
            'custom_data' => isset($data['custom_data']) ? wp_json_encode($data['custom_data']) : null,
            'created_at' => $data['created_at'],
        );
        
        $format = array(
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
        );
        
        $result = $wpdb->insert($table_name, $insert_data, $format);
        
        if ($result === false) {
            self::log_error('Failed to insert conversion', array(
                'error' => $wpdb->last_error,
                'data' => $insert_data
            ));
            return false;
        }
        
        $conversion_id = $wpdb->insert_id;
        
        // Send to Meta if enabled
        if (get_option('mct_enable_meta_api', false)) {
            MCT_Meta_API::send_conversion($conversion_id);
        }
        
        return $conversion_id;
    }
    
    /**
     * Get conversion by ID
     */
    public static function get_conversion($id) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id);
        return $wpdb->get_row($query, ARRAY_A);
    }
    
    /**
     * Get conversions with filters
     */
    public static function get_conversions($args = array()) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $defaults = array(
            'limit' => 100,
            'offset' => 0,
            'order_by' => 'created_at',
            'order' => 'DESC',
            'where' => array(),
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause
        $where_clauses = array('1=1');
        $where_values = array();
        
        foreach ($args['where'] as $field => $value) {
            $where_clauses[] = "$field = %s";
            $where_values[] = $value;
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Build query
        $query = "SELECT * FROM $table_name WHERE $where_sql ORDER BY {$args['order_by']} {$args['order']} LIMIT %d OFFSET %d";
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        $prepared_query = $wpdb->prepare($query, $where_values);
        
        return $wpdb->get_results($prepared_query, ARRAY_A);
    }
    
    /**
     * Count conversions with filters
     */
    public static function count_conversions($where = array()) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $where_clauses = array('1=1');
        $where_values = array();
        
        foreach ($where as $field => $value) {
            $where_clauses[] = "$field = %s";
            $where_values[] = $value;
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        $query = "SELECT COUNT(*) FROM $table_name WHERE $where_sql";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return (int) $wpdb->get_var($query);
    }
    
    /**
     * Update conversion
     */
    public static function update_conversion($id, $data) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update(
            $table_name,
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
    }
    
    /**
     * Generate unique event ID
     */
    private static function generate_event_id() {
        return uniqid('mct_', true) . '_' . wp_generate_password(8, false);
    }
    
    /**
     * Create dedicated database user for external access
     */
    public static function create_db_user() {
        global $wpdb;
        
        // Check if already created
        if (get_option('mct_db_user_created', false)) {
            return;
        }
        
        $username = 'mct_readonly_' . substr(md5(site_url()), 0, 8);
        $password = wp_generate_password(20, true, true);
        $table_name = self::get_table_name();
        
        // Store credentials in options (encrypted would be better in production)
        update_option('mct_db_username', $username);
        update_option('mct_db_password', $password);
        update_option('mct_db_host', DB_HOST);
        update_option('mct_db_name', DB_NAME);
        update_option('mct_db_table', $table_name);
        
        // Try to create user (may fail if WordPress user doesn't have GRANT privileges)
        try {
            $wpdb->query($wpdb->prepare("CREATE USER IF NOT EXISTS %s@'%%' IDENTIFIED BY %s", $username, $password));
            $wpdb->query($wpdb->prepare("GRANT SELECT ON %s.%s TO %s@'%%'", DB_NAME, $table_name, $username));
            $wpdb->query("FLUSH PRIVILEGES");
            
            update_option('mct_db_user_created', true);
        } catch (Exception $e) {
            // Log error but don't fail - user can create manually
            self::log_error('Failed to create database user', array('error' => $e->getMessage()));
        }
    }
    
    /**
     * Log message
     */
    public static function log($message, $context = array(), $level = 'info') {
        if (!get_option('mct_enable_logging', true)) {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'meta_conversion_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'conversion_id' => isset($context['conversion_id']) ? $context['conversion_id'] : null,
                'log_level' => $level,
                'message' => $message,
                'context' => wp_json_encode($context),
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Log error
     */
    public static function log_error($message, $context = array()) {
        self::log($message, $context, 'error');
    }
    
    /**
     * Clean old conversions (older than retention period)
     */
    public static function cleanup_old_conversions() {
        global $wpdb;
        
        $table_name = self::get_table_name();
        $logs_table = $wpdb->prefix . 'meta_conversion_logs';
        $retention_days = self::DATA_RETENTION_DAYS;
        
        // Delete old conversions
        $deleted_conversions = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $retention_days
        ));
        
        // Delete old logs
        $deleted_logs = $wpdb->query($wpdb->prepare(
            "DELETE FROM $logs_table WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $retention_days
        ));
        
        // Log cleanup action
        self::log_info('Data cleanup completed', array(
            'deleted_conversions' => $deleted_conversions,
            'deleted_logs' => $deleted_logs,
            'retention_days' => $retention_days
        ));
        
        return array(
            'conversions_deleted' => $deleted_conversions,
            'logs_deleted' => $deleted_logs
        );
    }
    
    /**
     * Get database connection info for external access
     */
    public static function get_db_connection_info() {
        return array(
            'host' => get_option('mct_db_host', DB_HOST),
            'database' => get_option('mct_db_name', DB_NAME),
            'username' => get_option('mct_db_username', ''),
            'password' => get_option('mct_db_password', ''),
            'table' => get_option('mct_db_table', self::get_table_name()),
            'port' => defined('DB_PORT') ? DB_PORT : 3306,
        );
    }
}
