<?php
/**
 * Plugin Name: Enforce Featured Image
 * Plugin URI: https://github.com/nash-ye/wp-enforce-featured-image
 * Description: Enforce certain post types to be published with a featured image with certain dimensions if specified.
 * Version: 0.1.3
 * Author: Nashwan Doaqan
 * Author URI: http://nashwan-d.com
 * Text Domain: enforce-featured-image
 * Domain Path: /locales
 *
 * This plugin is based on "Force Featured Image" WP plugin by X-Team.
 */

/**
 * @since 0.1
 */
class Enforce_Featured_Image {

	/**
	 * @var array
	 * @since 0.1
	 * @static
	 */
	protected static $post_types_args = array();

	/**
	 * The constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_i18n' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'transition_post_status', array( $this, 'check_featured_image' ), 10, 3 );
	}

	/**
	 * Loads the translation files.
	 *
	 * @action plugins_loaded
	 * @access public
	 * @return void
	 * @since 0.1
	 */
	public function load_i18n() {
		// Load the translation of the plugin.
		load_plugin_textdomain( 'enforce-featured-image', false, dirname( plugin_basename( __FILE__ ) ) . '/locales' );
	}

	/**
	 * @action admin_notices
	 * @return string|void
	 * @since 0.1
	 */
	public function admin_notices() {
		global $post;

		$current_screen = get_current_screen();
		$msg_code = filter_input( INPUT_GET, 'enforce-featured-image', FILTER_SANITIZE_STRING );

		if ( empty( $msg_code ) && ! is_null( $post ) && 'post' === $current_screen->base ) {
			$msg_code = $this->check_post_featured_image_condition( $post );
		}

		if ( empty( $msg_code ) ) {
			return;
		}

		switch ( $msg_code ) {
			case 'wrong-size':
				$enforce_args = static::get_post_type_enforce_args( $post->post_type );
				if ( $enforce_args['min_width'] && $enforce_args['min_height'] ) {
					$dimensions = sprintf( '<strong>%spx &times; %spx</strong>', $enforce_args['min_width'], $enforce_args['min_height'] );
					$msg = sprintf( __( "This post featured image doesn't respect the required image dimensions. Please add an image with dimensions %s at least.", 'enforce-featured-image' ), $dimensions );
				} elseif ( $enforce_args['min_width'] ) {
					$msg = sprintf( __( "This post featured image doesn't respect the required image dimensions. Please add an image with width of %s at least.", 'enforce-featured-image' ), sprintf( '<strong>%spx</strong>', $enforce_args['min_width'] ) );
				} elseif ( $enforce_args['min_height'] ) {
					$msg = sprintf( __( "This post featured image doesn't respect the required image dimensions. Please add an image with height of %s at least.", 'enforce-featured-image' ), sprintf( '<strong>%spx</strong>', $enforce_args['min_height'] ) );
				}
				break;
			case 'no-image':
				$msg = __( "This post doesn't have a featured image. Please add an image before publishing.", 'enforce-featured-image' );
				break;
			default:
				return;
		}

		?>
			<div class="error">
				<p><?php echo $msg; //xss ok ?></p>
			</div>
		<?php
	}

	/**
	 * @action transition_post_status
	 * @return void
	 * @since 0.1
	 */
	public function check_featured_image( $new_status, $old_status, $post ) {
		if ( 'publish' === $new_status ) {
			if ( static::is_enforced_on_post_type( $post->post_type ) ) {
				$post_thumbnail_id = get_post_thumbnail_id( $post );
				$enforce_args = static::get_post_type_enforce_args( $post->post_type );
				if ( empty( $post_thumbnail_id ) || ! $this->is_image_respect_size( $post_thumbnail_id, $post->post_type ) ) {
					if ( 'publish' !== $old_status || $enforce_args['force_on_published_posts'] ) {
						$post->post_status = 'draft';
						wp_update_post( $post );
					}
				}
			}
		} elseif ( ! in_array( $new_status, array( 'trash', 'auto-draft' ), true ) ) {
			if ( static::is_enforced_on_post_type( $post->post_type ) ) {
				add_filter( 'redirect_post_location', array( $this, 'filter_redirect_post_location' ), 99, 2 );
			}
		}
	}

	/**
	 * Add "enforce-featured-image" query argument so we can display a custom message if the user hasn't set any featured image.
	 *
	 * @param $location
	 * @param $post_id
	 * @return string
	 * @since 0.1
	 */
	public function filter_redirect_post_location( $location, $post_id ) {
		$invalidity = $this->check_post_featured_image_condition( $post_id );

		if ( ! empty( $invalidity ) ) {
			$url_query_args = array(
				'enforce-featured-image' => $invalidity,
			);

			if ( 'draft' === get_post_status( $post_id ) ) {
				$url_query_args['message'] = 10;
			}

			$location = add_query_arg( $url_query_args, $location );
		}

		return $location;
	}

	/**
	 * Check post featured image condition.
	 *
	 * @param WP_Post|int $post
	 * @return string
	 * @since 0.1.3
	 */
	private function check_post_featured_image_condition( $post ) {
		$condition = '';
		$post = get_post( $post );

		if ( empty( $post ) ) {
			return $condition;
		}

		$post_type = get_post_type( $post );

		if ( ! static::is_enforced_on_post_type( $post_type ) ) {
			return $condition;
		}

		// Get the featured image associated with this post
		$featured_image_id = get_post_thumbnail_id( $post );

		// Check if featured image is present
		if ( empty( $featured_image_id ) ) {
			$condition = 'no-image';
		} elseif ( ! $this->is_image_respect_size( $featured_image_id, $post_type ) ) {
			$condition = 'wrong-size';
		}

		return $condition;
	}

	/**
	 * Check if the an image match the enforcement options.
	 *
	 * @param $image_id
	 * @param $post_type
	 * @access private
	 * @return bool
	 * @since 0.1
	 */
	private function is_image_respect_size( $image_id, $post_type ){
		$image_id = (int) $image_id;
		$post_type = (string) $post_type;

		if ( ! $image_id || ! $post_type ) {
			return false;
		}

		if ( ! static::is_enforced_on_post_type( $post_type ) ) {
			return false;
		}

		$image_meta = wp_get_attachment_metadata( $image_id );

		if ( ! $image_meta || ! is_array( $image_meta ) ) {
			return false;
		}

		$enforce_args = static::get_post_type_enforce_args( $post_type );

		// Check if width is set or if height is set and larger than or equal the size in the enforcement options.
		$valid_width = ( ! $enforce_args['min_width'] ) || (int) $image_meta['width'] >= (int) $enforce_args['min_width'];
		$valid_height = ( ! $enforce_args['min_height'] ) || (int) $image_meta['height'] >= (int) $enforce_args['min_height'];

		if ( $valid_width && $valid_height ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a post-type is forced to have a featured image.
	 *
	 * @param string $post_type
	 * @return bool
	 * @since 0.1
	 * @static
	 */
	public static function is_enforced_on_post_type( $post_type ) {
		return array_key_exists( $post_type, static::$post_types_args );
	}

	/**
	 * Get post-type enforcement arguments.
	 *
	 * @return array|bool
	 * @since 0.1
	 * @static
	 */
	public static function get_post_type_enforce_args( $post_type ) {
		if ( ! static::is_enforced_on_post_type( $post_type ) ) {
			return false;
		}

		return static::$post_types_args[ $post_type ];
	}

	/**
	 * Enforce a featured image on a post-type.
	 *
	 * @return bool
	 * @since 0.1
	 * @static
	 */
	public static function enforce_on_post_type( $post_type, array $args = array() ) {
		$post_type = (string) $post_type;

		if ( empty( $post_type ) ) {
			return false;
		}

		$args = array_merge( array(
			'min_width'                => 0,
			'min_height'               => 0,
			'force_on_published_posts' => false,
		), $args );

		$args['min_width'] = (int) $args['min_width'];
		$args['min_height'] = (int) $args['min_height'];
		$args['force_on_published_posts'] = (bool) $args['force_on_published_posts'];

		static::$post_types_args[ $post_type ] = $args;
		return true;
	}

}

$enforce_featured_image = new Enforce_Featured_Image();
