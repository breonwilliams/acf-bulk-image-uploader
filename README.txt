=== ACF Bulk Image Uploader ===
Contributors: breonwilliams
Tags: acf, advanced-custom-fields, bulk upload, images, media
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bulk upload images to ACF fields on pages with a simple, intuitive interface.

== Description ==

ACF Bulk Image Uploader simplifies the process of uploading multiple images to Advanced Custom Fields (ACF) image fields on your WordPress pages. Perfect for quickly populating galleries, repeater fields, and other ACF image fields.

= Key Features =

* **Bulk Upload**: Upload multiple images to ACF fields in one operation
* **Smart Detection**: Automatically detects all ACF image fields on selected pages
* **Field Statistics**: Shows empty vs filled fields for each page
* **Repeater Support**: Works with repeater fields and nested repeaters
* **Flexible Content**: Supports flexible content fields with image sub-fields
* **Gallery Support**: Compatible with ACF gallery fields
* **Visual Interface**: See thumbnails of selected images before uploading
* **Field Preservation**: Updates only selected fields without affecting others

= Supported ACF Field Types =

* Single Image fields
* Gallery fields
* Repeater fields with image sub-fields
* Nested repeater fields
* Flexible Content fields with image sub-fields
* Group fields with image sub-fields

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure Advanced Custom Fields (ACF) is installed and activated
4. Navigate to Tools > ACF Image Uploader to begin

== Requirements ==

* WordPress 5.0 or higher
* Advanced Custom Fields (ACF) plugin
* PHP 7.2 or higher

== Frequently Asked Questions ==

= Does this plugin require ACF Pro? =

The plugin works with both the free and Pro versions of ACF. However, features like repeater fields and flexible content require ACF Pro.

= Can I upload to custom post types? =

Currently, the plugin is designed for pages, but support for custom post types is planned for future releases.

= Will this overwrite existing images? =

The plugin only updates the fields you select. Existing data in other fields remains untouched.

= How are images assigned to fields? =

Images are assigned to fields in the order they appear. The first selected image goes to the first selected field, and so on.

== Screenshots ==

1. Main interface showing page selection and field detection
2. Field list with empty/filled indicators
3. Image selection from media library
4. Upload confirmation

== Changelog ==

= 1.0.0 =
* Initial release
* Support for all major ACF field types
* Repeater row instance detection
* Field statistics display
* Fallback update method for complex field structures

== Upgrade Notice ==

= 1.0.0 =
Initial release of ACF Bulk Image Uploader.