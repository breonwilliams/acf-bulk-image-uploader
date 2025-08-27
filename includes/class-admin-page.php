<?php
/**
 * Admin page for ACF Bulk Image Uploader
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ACFBIU_Admin_Page {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            __('ACF Bulk Image Uploader', 'acf-bulk-image-uploader'),
            __('ACF Image Uploader', 'acf-bulk-image-uploader'),
            'manage_options',
            'acf-bulk-image-uploader',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="acfbiu-container">
                <form id="acfbiu-upload-form" method="post">
                    
                    <div class="acfbiu-section">
                        <h2><?php _e('Step 1: Select a Page', 'acf-bulk-image-uploader'); ?></h2>
                        <p class="description"><?php _e('Choose the page where you want to upload images to ACF fields.', 'acf-bulk-image-uploader'); ?></p>
                        
                        <select id="acfbiu-page-select" name="page_id" class="acfbiu-page-select">
                            <option value=""><?php _e('-- Select a Page --', 'acf-bulk-image-uploader'); ?></option>
                            <?php
                            $pages = get_pages(array(
                                'sort_column' => 'post_title',
                                'sort_order' => 'ASC',
                                'post_status' => array('publish', 'private', 'draft')
                            ));
                            
                            // Get field statistics for all pages
                            $page_stats = ACFBIU_ACF_Helpers::get_all_pages_field_stats();
                            
                            foreach ($pages as $page) {
                                $status = '';
                                if ($page->post_status !== 'publish') {
                                    $status = ' (' . $page->post_status . ')';
                                }
                                
                                // Add field statistics
                                $field_info = '';
                                if (isset($page_stats[$page->ID])) {
                                    $stats = $page_stats[$page->ID];
                                    if ($stats['total'] > 0) {
                                        $field_info = sprintf(' — %d empty / %d total', $stats['empty'], $stats['total']);
                                        if ($stats['empty'] === 0) {
                                            $field_info .= ' ✓';
                                        }
                                    }
                                }
                                
                                $data_attrs = '';
                                if (isset($page_stats[$page->ID])) {
                                    $data_attrs = sprintf(
                                        'data-empty="%d" data-total="%d" data-filled="%d"',
                                        $page_stats[$page->ID]['empty'],
                                        $page_stats[$page->ID]['total'],
                                        $page_stats[$page->ID]['filled']
                                    );
                                }
                                
                                echo '<option value="' . esc_attr($page->ID) . '" ' . $data_attrs . '>' . 
                                     esc_html($page->post_title . $status . $field_info) . 
                                     '</option>';
                            }
                            ?>
                        </select>
                        <button type="button" id="acfbiu-refresh-stats" class="button button-small" style="margin-left: 10px;">
                            <?php _e('Refresh Stats', 'acf-bulk-image-uploader'); ?>
                        </button>
                    </div>
                    
                    <div class="acfbiu-section" id="acfbiu-fields-section" style="display: none;">
                        <h2><?php _e('Available ACF Image Fields', 'acf-bulk-image-uploader'); ?></h2>
                        <p class="description"><?php _e('These image fields were found on the selected page:', 'acf-bulk-image-uploader'); ?></p>
                        <div id="acfbiu-fields-list" class="acfbiu-fields-list"></div>
                    </div>
                    
                    <div class="acfbiu-section" id="acfbiu-images-section" style="display: none;">
                        <h2><?php _e('Step 2: Select Images', 'acf-bulk-image-uploader'); ?></h2>
                        <p class="description"><?php _e('Select images to upload. They will be assigned to fields in order.', 'acf-bulk-image-uploader'); ?></p>
                        
                        <button type="button" id="acfbiu-select-images" class="button button-secondary">
                            <?php _e('Select Images from Media Library', 'acf-bulk-image-uploader'); ?>
                        </button>
                        
                        <div id="acfbiu-selected-images" class="acfbiu-selected-images"></div>
                    </div>
                    
                    <div class="acfbiu-section" id="acfbiu-submit-section" style="display: none;">
                        <button type="button" id="acfbiu-submit" class="button button-primary button-large">
                            <?php _e('Upload Images to ACF Fields', 'acf-bulk-image-uploader'); ?>
                        </button>
                        
                        <div id="acfbiu-message" class="acfbiu-message"></div>
                    </div>
                    
                </form>
                
                <div class="acfbiu-info">
                    <h3><?php _e('How it works:', 'acf-bulk-image-uploader'); ?></h3>
                    <ol>
                        <li><?php _e('Select a page from the dropdown above', 'acf-bulk-image-uploader'); ?></li>
                        <li><?php _e('The plugin will detect all ACF image fields on that page', 'acf-bulk-image-uploader'); ?></li>
                        <li><?php _e('Select images from your media library', 'acf-bulk-image-uploader'); ?></li>
                        <li><?php _e('Images will be automatically assigned to the ACF fields in order', 'acf-bulk-image-uploader'); ?></li>
                        <li><?php _e('Click upload to save the images to the page', 'acf-bulk-image-uploader'); ?></li>
                    </ol>
                    
                    <h3><?php _e('Supported Field Types:', 'acf-bulk-image-uploader'); ?></h3>
                    <ul>
                        <li><?php _e('Single Image fields', 'acf-bulk-image-uploader'); ?></li>
                        <li><?php _e('Gallery fields', 'acf-bulk-image-uploader'); ?></li>
                        <li><?php _e('Repeater fields with image sub-fields', 'acf-bulk-image-uploader'); ?></li>
                        <li><?php _e('Flexible Content fields with image sub-fields', 'acf-bulk-image-uploader'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
}