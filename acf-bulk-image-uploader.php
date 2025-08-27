<?php
/**
 * Plugin Name: ACF Bulk Image Uploader
 * Plugin URI: https://yourwebsite.com/
 * Description: Bulk upload images to ACF fields on pages with a simple interface
 * Version: 1.0.0
 * Author: Breon Williams
 * License: GPL v2 or later
 * Text Domain: acf-bulk-image-uploader
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ACFBIU_VERSION', '1.0.0');
define('ACFBIU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACFBIU_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class ACF_Bulk_Image_Uploader {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
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
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if ACF is active
        if (!class_exists('ACF')) {
            add_action('admin_notices', array($this, 'acf_missing_notice'));
            return;
        }
        
        // Load required files
        $this->load_dependencies();
        
        // Hook into WordPress
        $this->setup_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once ACFBIU_PLUGIN_DIR . 'includes/class-admin-page.php';
        require_once ACFBIU_PLUGIN_DIR . 'includes/class-ajax-handlers.php';
        require_once ACFBIU_PLUGIN_DIR . 'includes/class-acf-helpers.php';
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Initialize admin page
        new ACFBIU_Admin_Page();
        
        // Initialize AJAX handlers
        new ACFBIU_Ajax_Handlers();
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin page
        if ($hook !== 'tools_page_acf-bulk-image-uploader') {
            return;
        }
        
        // Enqueue media library
        wp_enqueue_media();
        
        // Enqueue our custom scripts
        wp_enqueue_script(
            'acfbiu-admin-js',
            ACFBIU_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            ACFBIU_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('acfbiu-admin-js', 'acfbiu_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acfbiu_nonce'),
            'messages' => array(
                'select_page' => __('Please select a page first', 'acf-bulk-image-uploader'),
                'select_images' => __('Please select images to upload', 'acf-bulk-image-uploader'),
                'processing' => __('Processing...', 'acf-bulk-image-uploader'),
                'success' => __('Images uploaded successfully!', 'acf-bulk-image-uploader'),
                'error' => __('An error occurred. Please try again.', 'acf-bulk-image-uploader')
            )
        ));
        
        // Enqueue our custom styles
        wp_enqueue_style(
            'acfbiu-admin-css',
            ACFBIU_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ACFBIU_VERSION
        );
    }
    
    /**
     * Show notice if ACF is not active
     */
    public function acf_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('ACF Bulk Image Uploader requires Advanced Custom Fields to be installed and activated.', 'acf-bulk-image-uploader'); ?></p>
        </div>
        <?php
    }
}

// Initialize the plugin
ACF_Bulk_Image_Uploader::get_instance();