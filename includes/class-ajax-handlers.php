<?php
/**
 * AJAX handlers for ACF Bulk Image Uploader
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ACFBIU_Ajax_Handlers {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_acfbiu_get_fields', array($this, 'get_fields'));
        add_action('wp_ajax_acfbiu_upload_images', array($this, 'upload_images'));
        add_action('wp_ajax_acfbiu_get_page_stats', array($this, 'get_page_stats'));
    }
    
    /**
     * Get ACF image fields for selected page
     */
    public function get_fields() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'acfbiu_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get page ID
        $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
        
        if (!$page_id) {
            wp_send_json_error('Invalid page ID');
        }
        
        // Get image fields for this page
        $fields = ACFBIU_ACF_Helpers::get_image_fields($page_id);
        
        if (empty($fields)) {
            wp_send_json_success(array(
                'fields' => array(),
                'message' => __('No ACF image fields found on this page.', 'acf-bulk-image-uploader')
            ));
        }
        
        // Format fields for display
        $formatted_fields = array();
        foreach ($fields as $field) {
            $formatted_fields[] = array(
                'key' => $field['key'],
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => $field['type'],
                'parent_key' => isset($field['parent_key']) ? $field['parent_key'] : '',
                'parent_name' => isset($field['parent_name']) ? $field['parent_name'] : '',
                'layout_name' => isset($field['layout_name']) ? $field['layout_name'] : '',
                'has_value' => !empty($field['current_value'])
            );
        }
        
        wp_send_json_success(array(
            'fields' => $formatted_fields,
            'count' => count($formatted_fields)
        ));
    }
    
    /**
     * Upload images to ACF fields
     */
    public function upload_images() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'acfbiu_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get and validate input
        $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
        $image_assignments = isset($_POST['assignments']) ? $_POST['assignments'] : array();
        
        if (!$page_id) {
            wp_send_json_error(__('Invalid page ID', 'acf-bulk-image-uploader'));
        }
        
        if (empty($image_assignments)) {
            wp_send_json_error(__('No image assignments provided', 'acf-bulk-image-uploader'));
        }
        
        // Process assignments
        $field_updates = array();
        $processed_count = 0;
        
        foreach ($image_assignments as $assignment) {
            $field_key = sanitize_text_field($assignment['field_key']);
            $field_type = sanitize_text_field($assignment['field_type']);
            $attachment_ids = array_map('intval', (array) $assignment['attachment_ids']);
            
            // Validate attachment IDs
            $valid_ids = ACFBIU_ACF_Helpers::validate_image_attachments($attachment_ids);
            
            if (empty($valid_ids)) {
                continue;
            }
            
            $field_updates[] = array(
                'field_key' => $field_key,
                'field_type' => $field_type,
                'attachment_ids' => $valid_ids,
                'field_name' => isset($assignment['field_name']) ? $assignment['field_name'] : '',
                'parent_key' => isset($assignment['parent_key']) ? $assignment['parent_key'] : '',
                'parent_name' => isset($assignment['parent_name']) ? $assignment['parent_name'] : '',
                'layout_name' => isset($assignment['layout_name']) ? $assignment['layout_name'] : ''
            );
            
            $processed_count++;
        }
        
        if (empty($field_updates)) {
            wp_send_json_error(__('No valid images to upload', 'acf-bulk-image-uploader'));
        }
        
        // Update the fields
        $success = ACFBIU_ACF_Helpers::update_image_fields($page_id, $field_updates);
        
        if ($success) {
            wp_send_json_success(array(
                'message' => sprintf(
                    __('%d images uploaded successfully to %d fields!', 'acf-bulk-image-uploader'),
                    $processed_count,
                    count($field_updates)
                ),
                'processed' => $processed_count,
                'fields_updated' => count($field_updates)
            ));
        } else {
            wp_send_json_error(__('Some images could not be uploaded. Please check the page and try again.', 'acf-bulk-image-uploader'));
        }
    }
    
    /**
     * Get page statistics for all pages
     */
    public function get_page_stats() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'acfbiu_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get cached stats if available
        $cached_stats = get_transient('acfbiu_page_stats');
        
        if (false === $cached_stats) {
            // Generate fresh stats
            $stats = ACFBIU_ACF_Helpers::get_all_pages_field_stats();
            
            // Cache for 5 minutes
            set_transient('acfbiu_page_stats', $stats, 300);
        } else {
            $stats = $cached_stats;
        }
        
        wp_send_json_success($stats);
    }
}