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
        
        foreach ($fields as $field_name => $field) {
            // For repeaters with existing rows, we need to extract fields for each row
            if ($field['type'] === 'repeater' && is_array($field['value']) && !empty($field['value'])) {
                $image_fields = array_merge($image_fields, self::extract_repeater_row_fields($field, $post_id));
            } else {
                // Process field normally for structure
                $extracted_fields = self::extract_image_fields($field);
                
                
                $image_fields = array_merge($image_fields, $extracted_fields);
            }
        }
        
        return $image_fields;
    }
    
    /**
     * Extract image fields from repeater rows
     *
     * @param array $repeater_field The repeater field with values
     * @param int $post_id
     * @return array
     */
    private static function extract_repeater_row_fields($repeater_field, $post_id) {
        $image_fields = array();
        $row_count = is_array($repeater_field['value']) ? count($repeater_field['value']) : 0;
        
        
        // Process each row
        foreach ($repeater_field['value'] as $row_index => $row_data) {
            $row_number = $row_index + 1;
            
            
            // Check each sub_field definition
            if (isset($repeater_field['sub_fields']) && is_array($repeater_field['sub_fields'])) {
                foreach ($repeater_field['sub_fields'] as $sub_field) {
                    // Get the actual value for this row and field
                    $field_value = isset($row_data[$sub_field['name']]) ? $row_data[$sub_field['name']] : null;
                    
                    // Handle image fields
                    if ($sub_field['type'] === 'image') {
                        $label = 'Row ' . $row_number . ': ' . $sub_field['label'];
                        
                        $image_fields[] = array(
                            'key' => $sub_field['key'],
                            'name' => $sub_field['name'],
                            'label' => $repeater_field['label'] . ' → ' . $label,
                            'type' => 'repeater_row_image',
                            'parent_key' => $repeater_field['key'],
                            'parent_name' => $repeater_field['name'],
                            'row_index' => $row_index,
                            'row_number' => $row_number,
                            'current_value' => $field_value,
                            'parent_hierarchy' => array(
                                array(
                                    'key' => $repeater_field['key'],
                                    'name' => $repeater_field['name'],
                                    'label' => $repeater_field['label'],
                                    'type' => 'repeater',
                                    'row_index' => $row_index
                                )
                            )
                        );
                    }
                    
                    // Handle nested repeaters
                    elseif ($sub_field['type'] === 'repeater') {
                        $nested_repeater_value = $field_value;
                        
                        if (is_array($nested_repeater_value) && !empty($nested_repeater_value)) {
                            
                            // Process nested repeater rows
                            foreach ($nested_repeater_value as $nested_row_index => $nested_row_data) {
                                $nested_row_number = $nested_row_index + 1;
                                
                                // Check nested sub_fields
                                if (isset($sub_field['sub_fields']) && is_array($sub_field['sub_fields'])) {
                                    foreach ($sub_field['sub_fields'] as $nested_sub_field) {
                                        if ($nested_sub_field['type'] === 'image') {
                                            $nested_field_value = isset($nested_row_data[$nested_sub_field['name']]) ? $nested_row_data[$nested_sub_field['name']] : null;
                                            
                                            $label = 'Row ' . $row_number . ' → ' . $sub_field['label'] . ' ' . $nested_row_number . ' → ' . $nested_sub_field['label'];
                                            
                                            
                                            $image_fields[] = array(
                                                'key' => $nested_sub_field['key'],
                                                'name' => $nested_sub_field['name'],
                                                'label' => $repeater_field['label'] . ' → ' . $label,
                                                'type' => 'nested_repeater_row_image',
                                                'parent_key' => $sub_field['key'],
                                                'parent_name' => $sub_field['name'],
                                                'row_index' => $row_index,
                                                'row_number' => $row_number,
                                                'nested_row_index' => $nested_row_index,
                                                'nested_row_number' => $nested_row_number,
                                                'current_value' => $nested_field_value,
                                                'parent_hierarchy' => array(
                                                    array(
                                                        'key' => $repeater_field['key'],
                                                        'name' => $repeater_field['name'],
                                                        'label' => $repeater_field['label'],
                                                        'type' => 'repeater',
                                                        'row_index' => $row_index
                                                    ),
                                                    array(
                                                        'key' => $sub_field['key'],
                                                        'name' => $sub_field['name'],
                                                        'label' => $sub_field['label'],
                                                        'type' => 'repeater',
                                                        'row_index' => $nested_row_index
                                                    )
                                                )
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
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
            
            foreach ($field['sub_fields'] as $sub_index => $sub_field) {
                
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
        
        // Group updates by parent repeater to avoid overwriting
        $repeater_updates = array();
        $simple_updates = array();
        
        // First, organize updates by type and parent
        foreach ($field_values as $field_data) {
            $field_type = $field_data['field_type'];
            
            // Check if this is a repeater row update
            if (in_array($field_type, array('repeater_image', 'nested_repeater_image', 'repeater_row_image', 'nested_repeater_row_image'))) {
                // Check for row_index, including 0 which is a valid index
                if (array_key_exists('row_index', $field_data) && $field_data['row_index'] !== null) {
                    // Group by parent key for batch processing
                    $parent_key = $field_data['parent_key'];
                    if (!isset($repeater_updates[$parent_key])) {
                        $repeater_updates[$parent_key] = array(
                            'parent_name' => $field_data['parent_name'],
                            'parent_key' => $parent_key,
                            'updates' => array()
                        );
                    }
                    $repeater_updates[$parent_key]['updates'][] = $field_data;
                } else {
                    // Non-row specific repeater update (creating new rows)
                    $simple_updates[] = $field_data;
                }
            } else {
                // Non-repeater updates
                $simple_updates[] = $field_data;
            }
        }
        
        // Process grouped repeater updates
        foreach ($repeater_updates as $parent_key => $repeater_group) {
            try {
                // Check if ACF functions exist
                if (!function_exists('get_field') || !function_exists('update_field')) {
                    $success = false;
                    continue;
                }
                
                // Get the current repeater value ONCE for all updates
                // Try with parent_name first (more reliable for repeaters)
                $repeater_value = get_field($repeater_group['parent_name'], $post_id, false);
                
                // If that didn't work, try with the key
                if ($repeater_value === false || $repeater_value === null) {
                    $repeater_value = get_field($parent_key, $post_id, false);
                }
                
                if (!is_array($repeater_value)) {
                    $repeater_value = array();
                }
                
                // Apply all updates to this repeater
                foreach ($repeater_group['updates'] as $field_data) {
                    $field_name = $field_data['field_name'];
                    $row_index = $field_data['row_index'];
                    $attachment_ids = $field_data['attachment_ids'];
                    
                    // Ensure the row exists and preserve existing data
                    if (!isset($repeater_value[$row_index])) {
                        // This shouldn't happen for existing rows, but create if needed
                        $repeater_value[$row_index] = array();
                    }
                    
                    // IMPORTANT: Preserve all existing fields in the row!
                    // We're only updating the specific image field, not replacing the entire row
                    
                    // Handle nested repeater row
                    if (isset($field_data['nested_row_index']) && $field_data['nested_row_index'] !== null) {
                        $nested_row_index = $field_data['nested_row_index'];
                        $nested_parent_name = isset($field_data['nested_parent_name']) ? $field_data['nested_parent_name'] : $field_data['parent_name'];
                        
                        // Ensure nested array structure exists
                        if (!isset($repeater_value[$row_index][$nested_parent_name])) {
                            $repeater_value[$row_index][$nested_parent_name] = array();
                        }
                        if (!isset($repeater_value[$row_index][$nested_parent_name][$nested_row_index])) {
                            $repeater_value[$row_index][$nested_parent_name][$nested_row_index] = array();
                        }
                        
                        // Update the specific nested field
                        if (!empty($attachment_ids)) {
                            $repeater_value[$row_index][$nested_parent_name][$nested_row_index][$field_name] = $attachment_ids[0];
                        }
                    } else {
                        // Update the specific field in the row
                        if (!empty($attachment_ids)) {
                            $repeater_value[$row_index][$field_name] = $attachment_ids[0];
                        }
                    }
                }
                
                // Save the repeater ONCE with all updates applied
                // Try with parent_name first (more reliable for repeaters)
                $update_result = false;
                if (isset($repeater_group['parent_name']) && !empty($repeater_group['parent_name'])) {
                    $update_result = update_field($repeater_group['parent_name'], $repeater_value, $post_id);
                }
                
                // If that didn't work, try with the key
                if (!$update_result) {
                    $update_result = update_field($parent_key, $repeater_value, $post_id);
                }
                
                if (!$update_result) {
                    // Try alternative method - update each row's meta directly
                    // ACF stores repeater count
                    $repeater_count = count($repeater_value);
                    update_post_meta($post_id, $repeater_group['parent_name'], $repeater_count);
                    update_post_meta($post_id, '_' . $repeater_group['parent_name'], $parent_key);
                    
                    // Update each row
                    foreach ($repeater_group['updates'] as $update) {
                        $row_index = $update['row_index'];
                        $field_name = $update['field_name'];
                        $attachment_id = !empty($update['attachment_ids']) ? $update['attachment_ids'][0] : '';
                        
                        // ACF stores repeater row data as: {repeater_name}_{row}_{field_name}
                        $meta_key = $repeater_group['parent_name'] . '_' . $row_index . '_' . $field_name;
                        update_post_meta($post_id, $meta_key, $attachment_id);
                        
                        // Also store the field key reference
                        update_post_meta($post_id, '_' . $meta_key, $update['field_key']);
                    }
                    
                    $success = true; // Mark as successful if we got this far
                }
                
            } catch (Exception $e) {
                // Log critical error only if WP_DEBUG is enabled
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('ACFBIU Error updating repeater ' . $parent_key . ': ' . $e->getMessage());
                }
                $success = false;
            }
        }
        
        // Process simple updates (non-grouped)
        foreach ($simple_updates as $field_data) {
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
                        // Handle nested repeater structures (for new rows)
                        if (isset($field_data['parent_hierarchy']) && !empty($field_data['parent_hierarchy'])) {
                            // For nested repeaters, we need to build the proper structure
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
                // Log critical error only if WP_DEBUG is enabled
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('ACFBIU Error updating field ' . $field_key . ': ' . $e->getMessage());
                }
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