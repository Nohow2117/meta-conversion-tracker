<?php
/**
 * Admin Panel
 * WordPress admin interface for plugin configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

class MCT_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_mct_test_meta_connection', array($this, 'ajax_test_meta_connection'));
        add_action('wp_ajax_mct_retry_failed', array($this, 'ajax_retry_failed'));
        add_action('wp_ajax_mct_regenerate_api_key', array($this, 'ajax_regenerate_api_key'));
        add_action('wp_ajax_mct_cleanup_now', array($this, 'ajax_cleanup_now'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Meta Conversion Tracker',
            'Conversion Tracker',
            'manage_options',
            'meta-conversion-tracker',
            array($this, 'render_dashboard_page'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'meta-conversion-tracker',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'meta-conversion-tracker',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'meta-conversion-tracker',
            'Conversions',
            'Conversions',
            'manage_options',
            'mct-conversions',
            array($this, 'render_conversions_page')
        );
        
        add_submenu_page(
            'meta-conversion-tracker',
            'Settings',
            'Settings',
            'manage_options',
            'mct-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'meta-conversion-tracker',
            'API Documentation',
            'API Docs',
            'manage_options',
            'mct-api-docs',
            array($this, 'render_api_docs_page')
        );
        
        add_submenu_page(
            'meta-conversion-tracker',
            'Database Access',
            'Database Access',
            'manage_options',
            'mct-database',
            array($this, 'render_database_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Meta API Settings
        register_setting('mct_settings', 'mct_meta_pixel_id');
        register_setting('mct_settings', 'mct_meta_access_token');
        register_setting('mct_settings', 'mct_meta_test_code');
        register_setting('mct_settings', 'mct_enable_meta_api');
        register_setting('mct_settings', 'mct_enable_logging');
        register_setting('mct_settings', 'mct_api_key');
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'meta-conversion-tracker') === false && strpos($hook, 'mct-') === false) {
            return;
        }
        
        wp_enqueue_style('mct-admin-css', MCT_PLUGIN_URL . 'assets/css/admin.css', array(), MCT_VERSION);
        wp_enqueue_script('mct-admin-js', MCT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), MCT_VERSION, true);
        
        wp_localize_script('mct-admin-js', 'mctAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mct_admin_nonce'),
        ));
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        global $wpdb;
        $table_name = MCT_Database::get_table_name();
        
        // Get statistics
        $total_conversions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $today_conversions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = CURDATE()");
        $meta_sent = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE meta_sent = 1");
        $unique_campaigns = $wpdb->get_var("SELECT COUNT(DISTINCT utm_campaign) FROM $table_name WHERE utm_campaign IS NOT NULL");
        
        // Get recent conversions
        $recent_conversions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 10", ARRAY_A);
        
        // Get top campaigns
        $top_campaigns = $wpdb->get_results("
            SELECT utm_campaign, COUNT(*) as count 
            FROM $table_name 
            WHERE utm_campaign IS NOT NULL 
            GROUP BY utm_campaign 
            ORDER BY count DESC 
            LIMIT 5
        ", ARRAY_A);
        
        include MCT_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Render conversions page
     */
    public function render_conversions_page() {
        global $wpdb;
        $table_name = MCT_Database::get_table_name();
        
        // Pagination
        $per_page = 50;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($page - 1) * $per_page;
        
        // Filters
        $where = array('1=1');
        $where_values = array();
        
        if (!empty($_GET['utm_campaign'])) {
            $where[] = 'utm_campaign = %s';
            $where_values[] = sanitize_text_field($_GET['utm_campaign']);
        }
        
        if (!empty($_GET['platform'])) {
            $where[] = 'platform = %s';
            $where_values[] = sanitize_text_field($_GET['platform']);
        }
        
        if (!empty($_GET['meta_sent'])) {
            $where[] = 'meta_sent = %d';
            $where_values[] = intval($_GET['meta_sent']);
        }
        
        $where_sql = implode(' AND ', $where);
        
        // Get conversions
        $query = "SELECT * FROM $table_name WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_values[] = $per_page;
        $where_values[] = $offset;
        
        $conversions = $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
        
        // Get total count
        $total_query = "SELECT COUNT(*) FROM $table_name WHERE $where_sql";
        $total = $wpdb->get_var(!empty(array_slice($where_values, 0, -2)) ? $wpdb->prepare($total_query, array_slice($where_values, 0, -2)) : $total_query);
        
        $total_pages = ceil($total / $per_page);
        
        include MCT_PLUGIN_DIR . 'admin/views/conversions.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (isset($_POST['mct_save_settings']) && check_admin_referer('mct_settings_nonce')) {
            update_option('mct_meta_pixel_id', sanitize_text_field($_POST['mct_meta_pixel_id']));
            update_option('mct_meta_access_token', sanitize_text_field($_POST['mct_meta_access_token']));
            update_option('mct_meta_test_code', sanitize_text_field($_POST['mct_meta_test_code']));
            update_option('mct_enable_meta_api', isset($_POST['mct_enable_meta_api']) ? 1 : 0);
            update_option('mct_enable_logging', isset($_POST['mct_enable_logging']) ? 1 : 0);
            
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        
        include MCT_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Render API docs page
     */
    public function render_api_docs_page() {
        $api_key = get_option('mct_api_key');
        $site_url = get_site_url();
        
        include MCT_PLUGIN_DIR . 'admin/views/api-docs.php';
    }
    
    /**
     * Render database access page
     */
    public function render_database_page() {
        $db_info = MCT_Database::get_db_connection_info();
        
        include MCT_PLUGIN_DIR . 'admin/views/database.php';
    }
    
    /**
     * AJAX: Test Meta connection
     */
    public function ajax_test_meta_connection() {
        check_ajax_referer('mct_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $result = MCT_Meta_API::test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Manual cleanup
     */
    public function ajax_cleanup_now() {
        check_ajax_referer('mct_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $result = MCT_Database::cleanup_old_conversions();
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Retry failed conversions
     */
    public function ajax_retry_failed() {
        check_ajax_referer('mct_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $result = MCT_Meta_API::retry_failed_conversions();
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Regenerate API key
     */
    public function ajax_regenerate_api_key() {
        check_ajax_referer('mct_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $new_key = wp_generate_password(32, false);
        update_option('mct_api_key', $new_key);
        
        wp_send_json_success(array('api_key' => $new_key));
    }
}
