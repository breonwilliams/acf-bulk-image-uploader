<?php
/**
 * Uninstall script for ACF Bulk Image Uploader
 * 
 * This file is executed when the plugin is deleted from WordPress
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up any transients created by the plugin
delete_transient('acfbiu_page_stats');

// Note: This plugin doesn't create any database tables or options,
// so there's minimal cleanup needed