<?php
/**
 * ACF Helper functions for the bulk image uploader
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ACFBIU_ACF_Helpers {
    
    /**
     * Get field statistics for all pages
     *
     * @return array
     */
    public static function get_all_pages_field_stats() {
        $stats = array();
        
        $pages = get_pages(array(
            'sort_column' => 'post_title',
            'sort_order' => 'ASC',
            'post_status' => array('publish', 'private', 'draft'),
            'number' => 0 // Get all pages
        ));
        
        foreach ($pages as $page) {
            $fields = self::get_image_fields($page->ID);
            $total_fields = count($fields);
            $empty_fields = 0;
            
            foreach ($fields as $field) {
                if (empty($field['current_value'])) {
                    $empty_fields++;
                }
            }
            
            $stats[$page->ID] = array(
                'total' => $total_fields,
                'empty' => $empty_fields,
                'filled' => $total_fields - $empty_fields
            );
        }
        
        return $stats;
    }
    
    /**
     * Get all ACF image fields for a given post
     *
     * @param int $post_id
     * @return array
     */
    public static function get_image_fields($post_id) {
        $image_fields = array();
        
        if (!function_exists('get_field_objects')) {
            return $image_fields;
        }
        
        // Get all ACF fields for this post
        $fields = get_field_objects($post_id);
        
        if (!$fields) {
            return $image_fields;
        }
        
        foreach ($fields as $field) {
            // Process each field to find image fields
            $extracted_fields = self::extract_image_fields($field);
            $image_fields = array_merge($image_fields, $extracted_fields);
        }
        
        return $image_fields;
    }
    
    /**
     * Extract image fields from a field (handles nested fields like repeaters)
     *
     * @param array $field
     * @param string $parent_key
     * @return array
     */
    private static function extract_image_fields($field, $parent_key = '') {
        $image_fields = array();
        
        // Handle single image field
        if ($field['type'] === 'image') {
            $image_fields[] = array(
                'key' => $field['key'],
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => 'image',
                'parent_key' => $parent_key,
                'current_value' => $field['value']
            );
        }
        
        // Handle gallery field
        elseif ($field['type'] === 'gallery') {
            $image_fields[] = array(
                'key' => $field['key'],
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => 'gallery',
                'parent_key' => $parent_key,
                'current_value' => $field['value'],
                'multiple' => true
            );
        }
        
        // Handle repeater field
        elseif ($field['type'] === 'repeater' && isset($field['sub_fields'])) {
            foreach ($field['sub_fields'] as $sub_field) {
                if ($sub_field['type'] === 'image') {
                    $image_fields[] = array(
                        'key' => $sub_field['key'],
                        'name' => $sub_field['name'],
                        'label' => $field['label'] . ' → ' . $sub_field['label'],
                        'type' => 'repeater_image',
                        'parent_key' => $field['key'],
                        'parent_name' => $field['name'],
                        'repeater_label' => $field['label'],
                        'current_value' => null
                    );
                }
            }
        }
        
        // Handle flexible content field
        elseif ($field['type'] === 'flexible_content' && isset($field['layouts'])) {
            foreach ($field['layouts'] as $layout) {
                if (isset($layout['sub_fields'])) {
                    foreach ($layout['sub_fields'] as $sub_field) {
                        if ($sub_field['type'] === 'image') {
                            $image_fields[] = array(
                                'key' => $sub_field['key'],
                                'name' => $sub_field['name'],
                                'label' => $field['label'] . ' → ' . $layout['label'] . ' → ' . $sub_field['label'],
                                'type' => 'flexible_image',
                                'parent_key' => $field['key'],
                                'parent_name' => $field['name'],
                                'layout_name' => $layout['name'],
                                'layout_label' => $layout['label'],
                                'current_value' => null
                            );
                        }
                    }
                }
            }
        }
        
        // Handle group field
        elseif ($field['type'] === 'group' && isset($field['sub_fields'])) {
            foreach ($field['sub_fields'] as $sub_field) {
                $parent_key_new = $parent_key ? $parent_key . '_' . $field['name'] : $field['name'];
                $extracted = self::extract_image_fields($sub_field, $parent_key_new);
                foreach ($extracted as &$extracted_field) {
                    $extracted_field['label'] = $field['label'] . ' → ' . $extracted_field['label'];
                }
                $image_fields = array_merge($image_fields, $extracted);
            }
        }
        
        return $image_fields;
    }
    
    /**
     * Update ACF image fields with new values
     *
     * @param int $post_id
     * @param array $field_values Array of field_key => attachment_id pairs
     * @return bool
     */
    public static function update_image_fields($post_id, $field_values) {
        $success = true;
        
        foreach ($field_values as $field_data) {
            $field_key = $field_data['field_key'];
            $attachment_ids = $field_data['attachment_ids'];
            $field_type = $field_data['field_type'];
            
            try {
                switch ($field_type) {
                    case 'image':
                        // Single image field - use first attachment
                        if (!empty($attachment_ids)) {
                            update_field($field_key, $attachment_ids[0], $post_id);
                        }
                        break;
                        
                    case 'gallery':
                        // Gallery field - use all attachments
                        update_field($field_key, $attachment_ids, $post_id);
                        break;
                        
                    case 'repeater_image':
                        // Repeater field with image sub-fields
                        $parent_name = $field_data['parent_name'];
                        $sub_field_name = $field_data['field_name'];
                        
                        // Create repeater rows for each image
                        $repeater_data = array();
                        foreach ($attachment_ids as $attachment_id) {
                            $repeater_data[] = array(
                                $sub_field_name => $attachment_id
                            );
                        }
                        
                        // Update the repeater field
                        if (!empty($repeater_data)) {
                            update_field($field_data['parent_key'], $repeater_data, $post_id);
                        }
                        break;
                        
                    case 'flexible_image':
                        // Flexible content with image fields
                        $parent_name = $field_data['parent_name'];
                        $layout_name = $field_data['layout_name'];
                        $sub_field_name = $field_data['field_name'];
                        
                        // Create flexible content rows
                        $flexible_data = array();
                        foreach ($attachment_ids as $attachment_id) {
                            $flexible_data[] = array(
                                'acf_fc_layout' => $layout_name,
                                $sub_field_name => $attachment_id
                            );
                        }
                        
                        // Update the flexible content field
                        if (!empty($flexible_data)) {
                            update_field($field_data['parent_key'], $flexible_data, $post_id);
                        }
                        break;
                }
            } catch (Exception $e) {
                error_log('ACFBIU Error updating field ' . $field_key . ': ' . $e->getMessage());
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Validate that attachment IDs are valid images
     *
     * @param array $attachment_ids
     * @return array Valid attachment IDs
     */
    public static function validate_image_attachments($attachment_ids) {
        $valid_ids = array();
        
        foreach ($attachment_ids as $attachment_id) {
            if (wp_attachment_is_image($attachment_id)) {
                $valid_ids[] = $attachment_id;
            }
        }
        
        return $valid_ids;
    }
}