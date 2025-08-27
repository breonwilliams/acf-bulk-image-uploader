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
     * @param string $label_prefix
     * @param array $parent_hierarchy
     * @return array
     */
    private static function extract_image_fields($field, $parent_key = '', $label_prefix = '', $parent_hierarchy = array()) {
        $image_fields = array();
        
        // Handle single image field
        if ($field['type'] === 'image') {
            $full_label = $label_prefix ? $label_prefix . ' → ' . $field['label'] : $field['label'];
            $image_fields[] = array(
                'key' => $field['key'],
                'name' => $field['name'],
                'label' => $full_label,
                'type' => count($parent_hierarchy) > 0 ? 'nested_repeater_image' : 'image',
                'parent_key' => $parent_key,
                'current_value' => isset($field['value']) ? $field['value'] : null,
                'parent_hierarchy' => $parent_hierarchy
            );
        }
        
        // Handle gallery field
        elseif ($field['type'] === 'gallery') {
            $full_label = $label_prefix ? $label_prefix . ' → ' . $field['label'] : $field['label'];
            $image_fields[] = array(
                'key' => $field['key'],
                'name' => $field['name'],
                'label' => $full_label,
                'type' => 'gallery',
                'parent_key' => $parent_key,
                'current_value' => isset($field['value']) ? $field['value'] : null,
                'multiple' => true,
                'parent_hierarchy' => $parent_hierarchy
            );
        }
        
        // Handle repeater field - RECURSIVE PROCESSING
        elseif ($field['type'] === 'repeater' && isset($field['sub_fields'])) {
            $new_label_prefix = $label_prefix ? $label_prefix . ' → ' . $field['label'] : $field['label'];
            $new_parent_hierarchy = $parent_hierarchy;
            $new_parent_hierarchy[] = array(
                'key' => $field['key'],
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => 'repeater'
            );
            
            foreach ($field['sub_fields'] as $sub_field) {
                // Recursively extract fields from ALL sub_fields, not just images
                $nested_fields = self::extract_image_fields(
                    $sub_field, 
                    $field['key'], 
                    $new_label_prefix,
                    $new_parent_hierarchy
                );
                $image_fields = array_merge($image_fields, $nested_fields);
            }
        }
        
        // Handle flexible content field - RECURSIVE PROCESSING
        elseif ($field['type'] === 'flexible_content' && isset($field['layouts'])) {
            foreach ($field['layouts'] as $layout) {
                if (isset($layout['sub_fields'])) {
                    $new_label_prefix = $label_prefix ? $label_prefix . ' → ' . $field['label'] . ' → ' . $layout['label'] : $field['label'] . ' → ' . $layout['label'];
                    $new_parent_hierarchy = $parent_hierarchy;
                    $new_parent_hierarchy[] = array(
                        'key' => $field['key'],
                        'name' => $field['name'],
                        'label' => $field['label'],
                        'type' => 'flexible_content',
                        'layout_name' => $layout['name'],
                        'layout_label' => $layout['label']
                    );
                    
                    foreach ($layout['sub_fields'] as $sub_field) {
                        // Recursively process all sub_fields
                        $nested_fields = self::extract_image_fields(
                            $sub_field,
                            $field['key'],
                            $new_label_prefix,
                            $new_parent_hierarchy
                        );
                        $image_fields = array_merge($image_fields, $nested_fields);
                    }
                }
            }
        }
        
        // Handle group field - RECURSIVE PROCESSING
        elseif ($field['type'] === 'group' && isset($field['sub_fields'])) {
            $new_label_prefix = $label_prefix ? $label_prefix . ' → ' . $field['label'] : $field['label'];
            $new_parent_hierarchy = $parent_hierarchy;
            $new_parent_hierarchy[] = array(
                'key' => $field['key'],
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => 'group'
            );
            
            foreach ($field['sub_fields'] as $sub_field) {
                $parent_key_new = $parent_key ? $parent_key . '_' . $field['name'] : $field['name'];
                $nested_fields = self::extract_image_fields(
                    $sub_field,
                    $parent_key_new,
                    $new_label_prefix,
                    $new_parent_hierarchy
                );
                $image_fields = array_merge($image_fields, $nested_fields);
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
                    case 'nested_repeater_image':
                        // Handle nested repeater structures
                        if (isset($field_data['parent_hierarchy']) && !empty($field_data['parent_hierarchy'])) {
                            // For nested repeaters, we need to build the proper structure
                            // This is a simplified approach - for complex nested structures,
                            // you may need to handle existing data merging
                            
                            $repeater_data = array();
                            foreach ($attachment_ids as $attachment_id) {
                                // Create a row for each image
                                $row_data = self::build_nested_repeater_row(
                                    $field_data['parent_hierarchy'],
                                    $field_data['field_name'],
                                    $attachment_id,
                                    0
                                );
                                if ($row_data) {
                                    $repeater_data[] = $row_data;
                                }
                            }
                            
                            // Update the top-level repeater
                            if (!empty($repeater_data) && !empty($field_data['parent_hierarchy'])) {
                                $top_parent = $field_data['parent_hierarchy'][0];
                                update_field($top_parent['key'], $repeater_data, $post_id);
                            }
                        } else {
                            // Simple repeater (non-nested)
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
     * Build nested repeater row structure
     *
     * @param array $hierarchy The parent hierarchy
     * @param string $field_name The image field name
     * @param int $attachment_id The attachment ID
     * @param int $level Current nesting level
     * @return array
     */
    private static function build_nested_repeater_row($hierarchy, $field_name, $attachment_id, $level) {
        if ($level >= count($hierarchy)) {
            // We've reached the image field level
            return array($field_name => $attachment_id);
        }
        
        $current_parent = $hierarchy[$level];
        
        if ($current_parent['type'] === 'repeater') {
            // For repeaters, we need to create a nested structure
            if ($level === count($hierarchy) - 1) {
                // This is the direct parent of the image field
                return array($field_name => $attachment_id);
            } else {
                // This is a nested repeater, recurse deeper
                $nested_data = self::build_nested_repeater_row($hierarchy, $field_name, $attachment_id, $level + 1);
                return array($current_parent['name'] => array($nested_data));
            }
        }
        
        // For other types (group, flexible_content), handle accordingly
        return array($field_name => $attachment_id);
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