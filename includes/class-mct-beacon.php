<?php
/**
 * Beacon API Class
 * 
 * Gestisce l'endpoint beacon per tracking garantito di tutti i completamenti captcha
 * 
 * @package Meta_Conversion_Tracker
 * @since 1.0.5
 */

if (!defined('ABSPATH')) {
    exit;
}

class MCT_Beacon {
    
    /**
     * Nome della tabella beacon log
     */
    const TABLE_NAME = 'mct_beacon_log';
    
    /**
     * Inizializza la classe beacon
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Registra gli endpoint REST API
     */
    public function register_routes() {
        register_rest_route('mct/v1', '/beacon', array(
            'methods' => 'POST',
            'callback' => array($this, 'log_beacon'),
            'permission_callback' => '__return_true', // Pubblico per garantire tracking
            'args' => array(
                'action' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($param) {
                        return in_array($param, array('wc_captcha_completed', 'page_view', 'custom'));
                    }
                ),
                'platform' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($param) {
                        return in_array($param, array('discord', 'telegram', 'web', 'other'));
                    }
                ),
                'timestamp' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'user_agent' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => ''
                ),
                'referrer' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'esc_url_raw',
                    'default' => 'direct'
                ),
                'fingerprint' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => ''
                ),
                'custom_data' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => ''
                )
            )
        ));
        
        // Endpoint per ottenere statistiche beacon (richiede autenticazione)
        register_rest_route('mct/v1', '/beacon/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_beacon_stats'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'start_date' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => date('Y-m-d', strtotime('-7 days'))
                ),
                'end_date' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => date('Y-m-d')
                ),
                'platform' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => ''
                )
            )
        ));
        
        // Endpoint per confrontare beacon con conversioni
        register_rest_route('mct/v1', '/beacon/compare', array(
            'methods' => 'GET',
            'callback' => array($this, 'compare_beacon_conversions'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'start_date' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => date('Y-m-d', strtotime('-7 days'))
                ),
                'end_date' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => date('Y-m-d')
                )
            )
        ));
    }
    
    /**
     * Logga un beacon
     */
    public function log_beacon($request) {
        global $wpdb;
        
        $params = $request->get_params();
        
        // Prepara i dati
        $data = array(
            'action' => $params['action'],
            'platform' => $params['platform'],
            'timestamp' => $params['timestamp'],
            'user_agent' => !empty($params['user_agent']) ? $params['user_agent'] : $_SERVER['HTTP_USER_AGENT'],
            'referrer' => !empty($params['referrer']) ? $params['referrer'] : 'direct',
            'fingerprint' => !empty($params['fingerprint']) ? $params['fingerprint'] : '',
            'custom_data' => !empty($params['custom_data']) ? $params['custom_data'] : '',
            'ip_address' => $this->get_client_ip(),
            'created_at' => current_time('mysql')
        );
        
        // Inserisci nel database
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $result = $wpdb->insert($table_name, $data);
        
        if ($result === false) {
            // Log l'errore ma non fallire la risposta
            error_log(sprintf(
                '[MCT Beacon] DB Insert Error: %s | Data: %s',
                $wpdb->last_error,
                json_encode($data)
            ));
        }
        
        // Log su file per debugging
        if (get_option('mct_enable_logging', false)) {
            error_log(sprintf(
                '[MCT Beacon] Action: %s, Platform: %s, Timestamp: %s, UA: %s, Referrer: %s, IP: %s',
                $data['action'],
                $data['platform'],
                $data['timestamp'],
                substr($data['user_agent'], 0, 50),
                $data['referrer'],
                $data['ip_address']
            ));
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Beacon logged',
            'beacon_id' => $wpdb->insert_id
        ), 200);
    }
    
    /**
     * Ottieni statistiche beacon
     */
    public function get_beacon_stats($request) {
        global $wpdb;
        
        $params = $request->get_params();
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $where_clauses = array("created_at >= %s", "created_at <= %s");
        $where_values = array(
            $params['start_date'] . ' 00:00:00',
            $params['end_date'] . ' 23:59:59'
        );
        
        if (!empty($params['platform'])) {
            $where_clauses[] = "platform = %s";
            $where_values[] = $params['platform'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Statistiche per giorno e piattaforma
        $query = $wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                platform,
                action,
                COUNT(*) as total,
                COUNT(DISTINCT ip_address) as unique_ips,
                COUNT(DISTINCT fingerprint) as unique_fingerprints
            FROM {$table_name}
            WHERE {$where_sql}
            GROUP BY DATE(created_at), platform, action
            ORDER BY date DESC, total DESC
        ", $where_values);
        
        $results = $wpdb->get_results($query);
        
        // Totali complessivi
        $totals_query = $wpdb->prepare("
            SELECT 
                COUNT(*) as total_beacons,
                COUNT(DISTINCT ip_address) as unique_ips,
                COUNT(DISTINCT fingerprint) as unique_fingerprints,
                COUNT(DISTINCT platform) as platforms_count
            FROM {$table_name}
            WHERE {$where_sql}
        ", $where_values);
        
        $totals = $wpdb->get_row($totals_query);
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $results,
            'totals' => $totals,
            'period' => array(
                'start' => $params['start_date'],
                'end' => $params['end_date']
            )
        ), 200);
    }
    
    /**
     * Confronta beacon con conversioni tracciate
     */
    public function compare_beacon_conversions($request) {
        global $wpdb;
        
        $params = $request->get_params();
        $beacon_table = $wpdb->prefix . self::TABLE_NAME;
        $conversions_table = $wpdb->prefix . 'meta_conversions';
        
        $query = $wpdb->prepare("
            SELECT 
                DATE(b.created_at) as date,
                b.platform,
                COUNT(DISTINCT b.id) as beacon_count,
                COUNT(DISTINCT c.id) as conversion_count,
                ROUND((COUNT(DISTINCT c.id) / COUNT(DISTINCT b.id) * 100), 2) as success_rate
            FROM {$beacon_table} b
            LEFT JOIN {$conversions_table} c 
                ON DATE(b.created_at) = DATE(c.created_at)
                AND b.platform = c.platform
            WHERE b.created_at >= %s 
                AND b.created_at <= %s
                AND b.action = 'wc_captcha_completed'
            GROUP BY DATE(b.created_at), b.platform
            ORDER BY date DESC, beacon_count DESC
        ", 
            $params['start_date'] . ' 00:00:00',
            $params['end_date'] . ' 23:59:59'
        );
        
        $results = $wpdb->get_results($query);
        
        // Calcola totali
        $total_beacons = array_sum(array_column($results, 'beacon_count'));
        $total_conversions = array_sum(array_column($results, 'conversion_count'));
        $overall_success_rate = $total_beacons > 0 ? round(($total_conversions / $total_beacons * 100), 2) : 0;
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $results,
            'totals' => array(
                'total_beacons' => $total_beacons,
                'total_conversions' => $total_conversions,
                'success_rate' => $overall_success_rate,
                'alert' => $overall_success_rate < 80 ? 'Tasso di successo sotto l\'80%!' : null
            ),
            'period' => array(
                'start' => $params['start_date'],
                'end' => $params['end_date']
            )
        ), 200);
    }
    
    /**
     * Check admin permission
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }
    
    /**
     * Ottieni IP del client
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Crea la tabella beacon log
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            action varchar(100) NOT NULL,
            platform varchar(50) NOT NULL,
            timestamp bigint(20) NOT NULL,
            user_agent text NOT NULL,
            referrer text,
            fingerprint varchar(255),
            custom_data text,
            ip_address varchar(45),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_platform (platform),
            KEY idx_action (action),
            KEY idx_created_at (created_at),
            KEY idx_fingerprint (fingerprint)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Log la creazione
        if (get_option('mct_enable_logging', false)) {
            error_log('[MCT Beacon] Tabella beacon log creata/aggiornata');
        }
    }
    
    /**
     * Pulisce vecchi log beacon (30 giorni)
     */
    public static function cleanup_old_beacons() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $days = 30;
        
        $deleted = $wpdb->query($wpdb->prepare("
            DELETE FROM {$table_name}
            WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
        
        if ($deleted !== false && get_option('mct_enable_logging', false)) {
            error_log(sprintf('[MCT Beacon] Cleanup: %d beacon vecchi eliminati', $deleted));
        }
        
        return $deleted;
    }
}
