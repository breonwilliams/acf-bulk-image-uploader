# ACF Bulk Image Uploader

A WordPress plugin that streamlines the process of uploading multiple images to ACF (Advanced Custom Fields) image fields on your pages. Save time by bulk uploading images instead of manually adding them one by one through the page editor.

## Features

### 🚀 Core Functionality
- **Bulk Image Upload**: Select multiple images from the WordPress Media Library and automatically assign them to ACF fields
- **Smart Field Detection**: Automatically detects all ACF image fields on any selected page
- **Selective Field Assignment**: Choose which specific fields to populate using checkboxes
- **Field Statistics**: View image field counts (empty/total) for all pages directly in the dropdown
- **Replace/Skip Mode**: Option to replace existing images or only fill empty fields

### 📊 Supported ACF Field Types
- Single Image fields
- Gallery fields (accepts multiple images)
- Repeater fields with image sub-fields
- Flexible Content fields with image sub-fields
- Group fields containing image fields

### 🎯 Smart Assignment Features
- **Sequential Assignment**: Images are assigned to fields in order
- **Skip Specific Fields**: Uncheck fields you want to skip (e.g., skip fields 4, 5, 6 but fill 1, 2, 3, 7, 8, 9, 10)
- **Visual Feedback**: Real-time indicators showing which fields will receive images
- **Mismatch Warnings**: Alerts when image count doesn't match selected field count

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Advanced Custom Fields (ACF) plugin installed and activated

## Installation

1. Download the plugin files
2. Upload the `acf-bulk-image-uploader` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Ensure Advanced Custom Fields (ACF) is also activated

## Usage

### Basic Workflow

1. Navigate to **Tools → ACF Image Uploader** in your WordPress admin
2. **Select a page** from the dropdown (shows empty/total field counts)
3. Review detected ACF image fields
4. **Select images** from the Media Library
5. Click **Upload Images to ACF Fields**

### Advanced Features

#### Selective Field Population
- Use checkboxes to select/deselect specific fields
- Click "Select All" or "Select None" for quick selection
- Selected fields show blue borders and full opacity
- Unselected fields appear grayed out

#### Replace Mode Toggle
- **ON (Default)**: Replaces existing images in populated fields
- **OFF**: Only fills empty fields, preserves existing images
- Visual indicators show which fields will be affected

#### Page Statistics
- View field counts in page dropdown: "Page Title — 3 empty / 10 total"
- Pages with all fields filled show a checkmark ✓
- Click "Refresh Stats" to update counts without reloading

## Interface Overview

### Step 1: Page Selection
- Dropdown list of all pages with field statistics
- Shows draft/private page status
- Real-time field count display

### Step 2: Field Detection & Selection
- Visual grid of all detected ACF image fields
- Numbered fields for easy reference
- Checkboxes for selective assignment
- Status badges (Empty/Has existing image/Will be replaced/Will be skipped)

### Step 3: Image Selection
- WordPress Media Library integration
- Multi-select capability
- Thumbnail preview of selected images
- Remove individual images with X button

### Step 4: Upload
- One-click upload to all selected fields
- Progress feedback
- Success/error messages
- Automatic field refresh after upload

## Example Use Cases

### Scenario 1: New Page Setup
1. Create a new page with 10 ACF image fields
2. Select the page in the uploader
3. Choose 10 images from Media Library
4. Upload all at once

### Scenario 2: Partial Update
1. Page has 10 fields, 3 already filled
2. Turn OFF "Replace existing images"
3. Select 7 new images
4. Only empty fields are filled, existing images preserved

### Scenario 3: Selective Replacement
1. Page has 10 fields, you want to skip fields 4-6
2. Uncheck fields 4, 5, and 6
3. Select your images
4. Images assigned to fields 1, 2, 3, 7, 8, 9, 10

## Troubleshooting

### No fields detected
- Ensure the selected page has ACF field groups assigned
- Check that field groups contain image-type fields
- Verify ACF is activated and working properly

### Images not uploading
- Check file permissions on uploads directory
- Verify images are valid and not corrupted
- Ensure you have proper WordPress capabilities

### Field counts not updating
- Click "Refresh Stats" button
- Statistics are cached for 5 minutes for performance
- Clear browser cache if issues persist

## Performance Notes

- Page statistics are cached for 5 minutes to improve performance
- Large sites with many pages may take a moment to load initially
- Batch processing ensures efficient database updates

## Developer Information

### Hooks and Filters
The plugin uses WordPress and ACF standard hooks:
- `get_field_objects()` for field detection
- `update_field()` for value updates
- WordPress AJAX for asynchronous operations
- Transient API for caching

### File Structure
```
acf-bulk-image-uploader/
├── acf-bulk-image-uploader.php     # Main plugin file
├── includes/
│   ├── class-admin-page.php        # Admin interface
│   ├── class-ajax-handlers.php     # AJAX endpoints
│   └── class-acf-helpers.php       # ACF utility functions
├── assets/
│   ├── js/
│   │   └── admin.js                # JavaScript functionality
│   └── css/
│       └── admin.css                # Admin styles
└── README.md                        # Documentation
```

## Support

For bug reports, feature requests, or general questions, please contact the plugin developer or submit an issue through the appropriate channels.

## Changelog

### Version 1.0.0
- Initial release
- Core bulk upload functionality
- Field selection with checkboxes
- Replace/Skip mode toggle
- Page statistics display
- Support for various ACF field types

## License

GPL v2 or later

## Credits

Developed by **Breon Williams** for WordPress sites using Advanced Custom Fields to streamline content management workflows.