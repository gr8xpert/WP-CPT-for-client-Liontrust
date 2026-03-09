<?php
/**
 * Community Divi Integration Class.
 *
 * Enables Divi Builder support on community post type.
 *
 * @package Community_CPT
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Community_Divi
 *
 * Integrates the community post type with Divi Builder,
 * enabling all Divi modules to be used on community posts.
 */
class Community_Divi {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'et_builder_post_types', array( $this, 'add_post_type' ) );
		add_filter( 'et_builder_module_post_types', array( $this, 'add_module_post_type' ) );

		// Force fullwidth layout for community posts.
		add_filter( 'et_pb_page_layout_meta', array( $this, 'force_fullwidth_layout' ) );
		add_filter( 'et_builder_inner_content_class', array( $this, 'add_fullwidth_class' ) );
		add_action( 'wp', array( $this, 'set_fullwidth_layout' ) );
		add_filter( 'body_class', array( $this, 'add_divi_fullwidth_body_class' ), 20 );

		// Remove post meta on community pages.
		add_filter( 'et_builder_post_meta', array( $this, 'remove_post_meta' ) );
		add_action( 'wp_head', array( $this, 'hide_post_meta_css' ) );
	}

	/**
	 * Add community to Divi Builder supported post types.
	 *
	 * @param array $post_types Array of supported post types.
	 * @return array Modified array with community post type.
	 */
	public function add_post_type( $post_types ) {
		if ( ! in_array( 'community', $post_types, true ) ) {
			$post_types[] = 'community';
		}
		return $post_types;
	}

	/**
	 * Add community to Divi module supported post types.
	 *
	 * Makes all Divi modules available when editing community posts.
	 *
	 * @param array $post_types Array of supported post types.
	 * @return array Modified array with community post type.
	 */
	public function add_module_post_type( $post_types ) {
		if ( ! in_array( 'community', $post_types, true ) ) {
			$post_types[] = 'community';
		}
		return $post_types;
	}

	/**
	 * Force fullwidth layout for community posts.
	 *
	 * @param string $layout The page layout.
	 * @return string Modified layout.
	 */
	public function force_fullwidth_layout( $layout ) {
		if ( is_singular( 'community' ) ) {
			return 'et_full_width_page';
		}
		return $layout;
	}

	/**
	 * Add fullwidth class to inner content.
	 *
	 * @param array $classes Inner content classes.
	 * @return array Modified classes.
	 */
	public function add_fullwidth_class( $classes ) {
		if ( is_singular( 'community' ) ) {
			$classes[] = 'et_full_width_page';
		}
		return $classes;
	}

	/**
	 * Set fullwidth layout on community pages.
	 * Hooks early to override Divi's default layout detection.
	 */
	public function set_fullwidth_layout() {
		if ( is_singular( 'community' ) || is_post_type_archive( 'community' ) ) {
			// Remove sidebar.
			add_filter( 'et_divi_sidebar', '__return_empty_string' );

			// Force fullwidth layout meta.
			add_filter( 'get_post_metadata', array( $this, 'filter_layout_meta' ), 10, 4 );
		}
	}

	/**
	 * Filter post meta to force fullwidth layout.
	 *
	 * @param mixed  $value     Meta value.
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key.
	 * @param bool   $single    Whether to return single value.
	 * @return mixed Filtered value.
	 */
	public function filter_layout_meta( $value, $object_id, $meta_key, $single ) {
		if ( '_et_pb_page_layout' === $meta_key ) {
			return 'et_full_width_page';
		}
		if ( '_et_pb_side_nav' === $meta_key ) {
			return 'off';
		}
		return $value;
	}

	/**
	 * Add Divi fullwidth body classes.
	 *
	 * @param array $classes Body classes.
	 * @return array Modified classes.
	 */
	public function add_divi_fullwidth_body_class( $classes ) {
		if ( is_singular( 'community' ) || is_post_type_archive( 'community' ) ) {
			// Remove sidebar classes.
			$classes = array_diff( $classes, array( 'et_right_sidebar', 'et_left_sidebar' ) );

			// Add fullwidth class.
			if ( ! in_array( 'et_no_sidebar', $classes, true ) ) {
				$classes[] = 'et_no_sidebar';
			}
			if ( ! in_array( 'et_full_width_page', $classes, true ) ) {
				$classes[] = 'et_full_width_page';
			}
		}
		return $classes;
	}

	/**
	 * Remove post meta on community pages.
	 *
	 * @param string $meta The post meta HTML.
	 * @return string Empty string for community pages.
	 */
	public function remove_post_meta( $meta ) {
		if ( is_singular( 'community' ) ) {
			return '';
		}
		return $meta;
	}

	/**
	 * Hide post meta wrapper via CSS on community pages.
	 */
	public function hide_post_meta_css() {
		if ( is_singular( 'community' ) ) {
			?>
			<style>
				.single-community .et_post_meta_wrapper,
				.single-community .post-meta,
				.single-community .entry-meta,
				.single-community p.post-meta {
					display: none !important;
				}
			</style>
			<?php
		}
	}
}
