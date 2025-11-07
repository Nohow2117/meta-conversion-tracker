<?php
/**
 * Plugin Name: Meta Conversion Tracker
 * Plugin URI: https://yoursite.com/meta-conversion-tracker
 * Description: Advanced conversion tracking system for Meta Ads with REST API and direct database access. Captures UTM parameters, FBCLID, user fingerprints and sends events to Meta Conversions API.
 * Version: 1.0.5
 * Author: Your Name
 * Author URI: https://yoursite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: meta-conversion-tracker
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MCT_VERSION', '1.0.5');
define('MCT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MCT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MCT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Meta_Conversion_Tracker {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once MCT_PLUGIN_DIR . 'includes/class-mct-database.php';
        require_once MCT_PLUGIN_DIR . 'includes/class-mct-api.php';
        require_once MCT_PLUGIN_DIR . 'includes/class-mct-meta-api.php';
        require_once MCT_PLUGIN_DIR . 'includes/class-mct-fingerprint.php';
        require_once MCT_PLUGIN_DIR . 'includes/class-mct-beacon.php';
        require_once MCT_PLUGIN_DIR . 'admin/class-mct-admin.php';
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize components
        add_action('plugins_loaded', array($this, 'init'));
        
        // Add CORS headers for API
        add_action('rest_api_init', array($this, 'add_cors_headers'));
        
        // Schedule automatic data cleanup
        add_action('mct_daily_cleanup', array('MCT_Database', 'cleanup_old_conversions'));
        add_action('mct_daily_cleanup', array('MCT_Beacon', 'cleanup_old_beacons'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        MCT_Database::create_tables();
        MCT_Beacon::create_table();
        MCT_Database::create_db_user();
        $this->set_default_options();
        $this->schedule_cleanup();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        $this->unschedule_cleanup();
        flush_rewrite_rules();
    }
    
    /**
     * Schedule daily cleanup cron job
     */
    private function schedule_cleanup() {
        if (!wp_next_scheduled('mct_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'mct_daily_cleanup');
        }
    }
    
    /**
     * Unschedule cleanup cron job
     */
    private function unschedule_cleanup() {
        $timestamp = wp_next_scheduled('mct_daily_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'mct_daily_cleanup');
        }
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'mct_api_key' => wp_generate_password(32, false),
            'mct_meta_pixel_id' => '',
            'mct_meta_access_token' => '',
            'mct_meta_test_code' => '',
            'mct_enable_meta_api' => false,
            'mct_enable_logging' => true,
            'mct_db_user_created' => false,
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    /**
     * Initialize plugin components
     */
    public function init() {
        // Initialize API
        new MCT_API();
        
        // Initialize Beacon
        new MCT_Beacon();
        
        // Initialize Admin
        if (is_admin()) {
            new MCT_Admin();
        }
        
        // Load text domain
        load_plugin_textdomain('meta-conversion-tracker', false, dirname(MCT_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Add CORS headers for external landing pages
     */
    public function add_cors_headers() {
        // Get allowed origins from settings (you can make this configurable)
        $allowed_origins = apply_filters('mct_allowed_origins', array('*'));
        
        remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
        add_filter('rest_pre_serve_request', function($value) use ($allowed_origins) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-API-Key');
            return $value;
        });
    }
}

/**
 * Initialize the plugin
 */
function mct_init() {
    return Meta_Conversion_Tracker::get_instance();
}

// Start the plugin
mct_init();
