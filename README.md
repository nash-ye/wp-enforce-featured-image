# WP Enforce Featured Image
Enforce certain post types to be published with a featured image with certain dimensions if specified.

## Requirements
WordPress 4.4+

## Credits
This plugin is based on [Force Featured Image](https://wordpress.org/plugins/force-featured-image/) plugin by XWP.

## Usage
Write an another plugin file, or paste the example below in your theme `functions.php` file:

```
if ( class_exists( 'Enforce_Featured_Image' ) ) {
    Enforce_Featured_Image::enforce_on_post_type( 'post', array(
        'min_width'                => 640,
        'min_height'               => 300,
        'force_on_published_posts' => false,
    ) );
}
```
