=== Enforce Featured Image ===
Contributors: alex-ye
Tags: admin, featured, featured image, post thumbnail, image
Requires at least: 4.4
Tested up to: 4.7
Stable tag: 0.1.3
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Enforce certain post types to be published with a featured image with certain dimensions if specified.

== Description ==

Enforce Featured Image is an API to enforce certain post types to be published with a featured image with certain dimensions if specified.

= Usage
Write an another plugin file, or paste the example below in your theme `functions.php` file:

`
if ( class_exists( 'Enforce_Featured_Image' ) ) {
    Enforce_Featured_Image::enforce_on_post_type( 'post', array(
        'min_width'                => 640,
        'min_height'               => 300,
        'force_on_published_posts' => false,
    ) );
}
`

Note: You can use [Code Snippets](https://wordpress.org/plugins/code-snippets) plugin to add the code snippets to your site.

= Credits
This plugin is based on [Force Featured Image](https://wordpress.org/plugins/force-featured-image/) plugin by XWP.

= Contributing =
Developers can contribute to the source code on the [Github Repository](https://github.com/nash-ye/wp-enforce-featured-image).

== Installation ==

1. Upload and install the plugin
2. Use the plugin API to powerful your project.

== Changelog ==

= 0.1.3 =
* The Initial version.