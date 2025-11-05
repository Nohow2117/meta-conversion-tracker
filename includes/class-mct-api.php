<?php
/**
 * REST API Endpoints
 * Handles all API requests for conversion tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

class MCT_API {
    
    /**
     * API namespace
     */
    const NAMESPACE = 'mct/v1';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        
        // POST /mct/v1/track - Track new conversion
        register_rest_route(self::NAMESPACE, '/track', array(
            'methods' => 'POST',
            'callback' => array($this, 'track_conversion'),
            'permission_callback' => array($this, 'check_api_key'),
            'args' => $this->get_track_args(),
        ));
        
        // GET /mct/v1/conversions - Get all conversions
        register_rest_route(self::NAMESPACE, '/conversions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_conversions'),
            'permission_callback' => array($this, 'check_api_key_or_admin'),
            'args' => $this->get_query_args(),
        ));
        
        // GET /mct/v1/conversions/{id} - Get single conversion
        register_rest_route(self::NAMESPACE, '/conversions/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_conversion'),
            'permission_callback' => array($this, 'check_api_key_or_admin'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
        
        // GET /mct/v1/stats - Get conversion statistics
        register_rest_route(self::NAMESPACE, '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_stats'),
            'permission_callback' => array($this, 'check_api_key_or_admin'),
            'args' => array(
                'start_date' => array(
                    'required' => false,
                    'type' => 'string',
                ),
                'end_date' => array(
                    'required' => false,
                    'type' => 'string',
                ),
                'group_by' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('day', 'week', 'month', 'campaign', 'platform'),
                    'default' => 'day',
                ),
            ),
        ));
        
        // POST /mct/v1/test - Test endpoint
        register_rest_route(self::NAMESPACE, '/test', array(
            'methods' => 'GET',
            'callback' => array($this, 'test_endpoint'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Track conversion endpoint
     */
    public function track_conversion($request) {
        $params = $request->get_params();
        
        // Get IP address
        $ip_address = $this->get_client_ip();
        
        // Prepare data
        $data = array(
            'utm_source' => isset($params['utm_source']) ? $params['utm_source'] : null,
            'utm_medium' => isset($params['utm_medium']) ? $params['utm_medium'] : null,
            'utm_campaign' => isset($params['utm_campaign']) ? $params['utm_campaign'] : null,
            'utm_content' => isset($params['utm_content']) ? $params['utm_content'] : null,
            'utm_term' => isset($params['utm_term']) ? $params['utm_term'] : null,
            'fbclid' => isset($params['fbclid']) ? $params['fbclid'] : null,
            'fbc' => isset($params['fbc']) ? $params['fbc'] : null,
            'fbp' => isset($params['fbp']) ? $params['fbp'] : null,
            'ip_address' => $ip_address,
            'user_agent' => isset($params['user_agent']) ? $params['user_agent'] : $_SERVER['HTTP_USER_AGENT'],
            'fingerprint' => isset($params['fingerprint']) ? $params['fingerprint'] : null,
            'browser_fingerprint' => isset($params['browser_fingerprint']) ? $params['browser_fingerprint'] : null,
            'platform' => isset($params['platform']) ? $params['platform'] : null,
            'landing_page' => isset($params['landing_page']) ? $params['landing_page'] : null,
            'referrer' => isset($params['referrer']) ? $params['referrer'] : null,
            'event_name' => isset($params['event_name']) ? $params['event_name'] : 'Lead',
            'custom_data' => isset($params['custom_data']) ? $params['custom_data'] : null,
        );
        
        // Insert into database
        $conversion_id = MCT_Database::insert_conversion($data);
        
        if ($conversion_id === false) {
            return new WP_Error(
                'insert_failed',
                'Failed to save conversion',
                array('status' => 500)
            );
        }
        
        // Get the saved conversion
        $conversion = MCT_Database::get_conversion($conversion_id);
        
        return new WP_REST_Response(array(
            'success' => true,
            'conversion_id' => $conversion_id,
            'event_id' => $conversion['event_id'],
            'message' => 'Conversion tracked successfully',
        ), 201);
    }
    
    /**
     * Get conversions endpoint
     */
    public function get_conversions($request) {
        $params = $request->get_params();
        
        // Build query args
        $args = array(
            'limit' => isset($params['limit']) ? intval($params['limit']) : 100,
            'offset' => isset($params['offset']) ? intval($params['offset']) : 0,
            'order_by' => isset($params['order_by']) ? $params['order_by'] : 'created_at',
            'order' => isset($params['order']) ? strtoupper($params['order']) : 'DESC',
            'where' => array(),
        );
        
        // Add filters
        $filterable_fields = array(
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
            'fbclid', 'platform', 'event_name', 'meta_sent'
        );
        
        foreach ($filterable_fields as $field) {
            if (isset($params[$field]) && $params[$field] !== '') {
                $args['where'][$field] = $params[$field];
            }
        }
        
        // Date range filter
        if (isset($params['start_date']) || isset($params['end_date'])) {
            // This requires custom SQL - simplified version
            global $wpdb;
            $table_name = MCT_Database::get_table_name();
            
            $where_clauses = array('1=1');
            $where_values = array();
            
            if (isset($params['start_date'])) {
                $where_clauses[] = 'created_at >= %s';
                $where_values[] = $params['start_date'];
            }
            
            if (isset($params['end_date'])) {
                $where_clauses[] = 'created_at <= %s';
                $where_values[] = $params['end_date'];
            }
            
            foreach ($args['where'] as $field => $value) {
                $where_clauses[] = "$field = %s";
                $where_values[] = $value;
            }
            
            $where_sql = implode(' AND ', $where_clauses);
            $query = "SELECT * FROM $table_name WHERE $where_sql ORDER BY {$args['order_by']} {$args['order']} LIMIT %d OFFSET %d";
            $where_values[] = $args['limit'];
            $where_values[] = $args['offset'];
            
            $conversions = $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
            $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE $where_sql", array_slice($where_values, 0, -2)));
        } else {
            $conversions = MCT_Database::get_conversions($args);
            $total = MCT_Database::count_conversions($args['where']);
        }
        
        // Parse JSON fields
        foreach ($conversions as &$conversion) {
            if (!empty($conversion['browser_fingerprint'])) {
                $conversion['browser_fingerprint'] = json_decode($conversion['browser_fingerprint'], true);
            }
            if (!empty($conversion['custom_data'])) {
                $conversion['custom_data'] = json_decode($conversion['custom_data'], true);
            }
            if (!empty($conversion['meta_response'])) {
                $conversion['meta_response'] = json_decode($conversion['meta_response'], true);
            }
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $conversions,
            'total' => $total,
            'limit' => $args['limit'],
            'offset' => $args['offset'],
        ), 200);
    }
    
    /**
     * Get single conversion endpoint
     */
    public function get_conversion($request) {
        $id = $request->get_param('id');
        $conversion = MCT_Database::get_conversion($id);
        
        if (!$conversion) {
            return new WP_Error(
                'not_found',
                'Conversion not found',
                array('status' => 404)
            );
        }
        
        // Parse JSON fields
        if (!empty($conversion['browser_fingerprint'])) {
            $conversion['browser_fingerprint'] = json_decode($conversion['browser_fingerprint'], true);
        }
        if (!empty($conversion['custom_data'])) {
            $conversion['custom_data'] = json_decode($conversion['custom_data'], true);
        }
        if (!empty($conversion['meta_response'])) {
            $conversion['meta_response'] = json_decode($conversion['meta_response'], true);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $conversion,
        ), 200);
    }
    
    /**
     * Get statistics endpoint
     */
    public function get_stats($request) {
        global $wpdb;
        $table_name = MCT_Database::get_table_name();
        
        $params = $request->get_params();
        $group_by = isset($params['group_by']) ? $params['group_by'] : 'day';
        
        $where_clauses = array('1=1');
        $where_values = array();
        
        if (isset($params['start_date'])) {
            $where_clauses[] = 'created_at >= %s';
            $where_values[] = $params['start_date'];
        }
        
        if (isset($params['end_date'])) {
            $where_clauses[] = 'created_at <= %s';
            $where_values[] = $params['end_date'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Build GROUP BY clause
        $group_clause = '';
        $select_clause = '';
        
        switch ($group_by) {
            case 'day':
                $select_clause = "DATE(created_at) as group_key";
                $group_clause = "DATE(created_at)";
                break;
            case 'week':
                $select_clause = "YEARWEEK(created_at) as group_key";
                $group_clause = "YEARWEEK(created_at)";
                break;
            case 'month':
                $select_clause = "DATE_FORMAT(created_at, '%Y-%m') as group_key";
                $group_clause = "DATE_FORMAT(created_at, '%Y-%m')";
                break;
            case 'campaign':
                $select_clause = "utm_campaign as group_key";
                $group_clause = "utm_campaign";
                break;
            case 'platform':
                $select_clause = "platform as group_key";
                $group_clause = "platform";
                break;
        }
        
        $query = "SELECT 
            $select_clause,
            COUNT(*) as total_conversions,
            COUNT(DISTINCT fbclid) as unique_clicks,
            SUM(CASE WHEN meta_sent = 1 THEN 1 ELSE 0 END) as sent_to_meta
        FROM $table_name 
        WHERE $where_sql 
        GROUP BY $group_clause 
        ORDER BY group_key DESC";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $stats = $wpdb->get_results($query, ARRAY_A);
        
        // Get overall totals
        $totals_query = "SELECT 
            COUNT(*) as total_conversions,
            COUNT(DISTINCT fbclid) as unique_clicks,
            COUNT(DISTINCT platform) as platforms_used,
            SUM(CASE WHEN meta_sent = 1 THEN 1 ELSE 0 END) as sent_to_meta
        FROM $table_name 
        WHERE $where_sql";
        
        if (!empty($where_values)) {
            $totals_query = $wpdb->prepare($totals_query, $where_values);
        }
        
        $totals = $wpdb->get_row($totals_query, ARRAY_A);
        
        return new WP_REST_Response(array(
            'success' => true,
            'stats' => $stats,
            'totals' => $totals,
            'group_by' => $group_by,
        ), 200);
    }
    
    /**
     * Test endpoint
     */
    public function test_endpoint($request) {
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Meta Conversion Tracker API is working!',
            'version' => MCT_VERSION,
            'timestamp' => current_time('mysql'),
        ), 200);
    }
    
    /**
     * Check API key permission
     */
    public function check_api_key($request) {
        $api_key = $request->get_header('X-API-Key');
        
        if (empty($api_key)) {
            $api_key = $request->get_param('api_key');
        }
        
        $stored_key = get_option('mct_api_key');
        
        if ($api_key === $stored_key) {
            return true;
        }
        
        return new WP_Error(
            'invalid_api_key',
            'Invalid API key',
            array('status' => 401)
        );
    }
    
    /**
     * Check API key or admin permission
     */
    public function check_api_key_or_admin($request) {
        // Check if user is admin
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Otherwise check API key
        return $this->check_api_key($request);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Get track endpoint arguments
     */
    private function get_track_args() {
        return array(
            'utm_source' => array('type' => 'string', 'required' => false),
            'utm_medium' => array('type' => 'string', 'required' => false),
            'utm_campaign' => array('type' => 'string', 'required' => false),
            'utm_content' => array('type' => 'string', 'required' => false),
            'utm_term' => array('type' => 'string', 'required' => false),
            'fbclid' => array('type' => 'string', 'required' => false),
            'fbc' => array('type' => 'string', 'required' => false),
            'fbp' => array('type' => 'string', 'required' => false),
            'user_agent' => array('type' => 'string', 'required' => false),
            'fingerprint' => array('type' => 'string', 'required' => false),
            'browser_fingerprint' => array('type' => 'object', 'required' => false),
            'platform' => array('type' => 'string', 'required' => false),
            'landing_page' => array('type' => 'string', 'required' => false),
            'referrer' => array('type' => 'string', 'required' => false),
            'event_name' => array('type' => 'string', 'required' => false, 'default' => 'Lead'),
            'custom_data' => array('type' => 'object', 'required' => false),
        );
    }
    
    /**
     * Get query endpoint arguments
     */
    private function get_query_args() {
        return array(
            'limit' => array('type' => 'integer', 'default' => 100, 'minimum' => 1, 'maximum' => 1000),
            'offset' => array('type' => 'integer', 'default' => 0, 'minimum' => 0),
            'order_by' => array('type' => 'string', 'default' => 'created_at'),
            'order' => array('type' => 'string', 'enum' => array('ASC', 'DESC'), 'default' => 'DESC'),
            'utm_source' => array('type' => 'string', 'required' => false),
            'utm_campaign' => array('type' => 'string', 'required' => false),
            'platform' => array('type' => 'string', 'required' => false),
            'fbclid' => array('type' => 'string', 'required' => false),
            'start_date' => array('type' => 'string', 'required' => false),
            'end_date' => array('type' => 'string', 'required' => false),
        );
    }
}
