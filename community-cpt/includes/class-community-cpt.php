<?php
/**
 * Community CPT Registration Class.
 *
 * Handles registration of the hierarchical community custom post type
 * and permalink configuration.
 *
 * @package Community_CPT
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Community_CPT
 *
 * Registers the community custom post type with hierarchical support
 * and configures permalink structure for 3-level nesting.
 */
class Community_CPT {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'the_content', array( $this, 'inject_listing_content' ), 15 );
		add_filter( 'the_content', array( $this, 'inject_related_posts' ), 20 );
		add_filter( 'template_include', array( $this, 'archive_template' ) );
		add_filter( 'body_class', array( $this, 'add_body_classes' ) );
	}

	/**
	 * Register the community custom post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Communities', 'community-cpt' ),
			'singular_name'      => __( 'Community', 'community-cpt' ),
			'add_new'            => __( 'Add New Community', 'community-cpt' ),
			'add_new_item'       => __( 'Add New Community', 'community-cpt' ),
			'edit_item'          => __( 'Edit Community', 'community-cpt' ),
			'new_item'           => __( 'New Community', 'community-cpt' ),
			'view_item'          => __( 'View Community', 'community-cpt' ),
			'search_items'       => __( 'Search Communities', 'community-cpt' ),
			'not_found'          => __( 'No communities found', 'community-cpt' ),
			'not_found_in_trash' => __( 'No communities found in trash', 'community-cpt' ),
			'parent_item_colon'  => __( 'Parent Community:', 'community-cpt' ),
			'all_items'          => __( 'All Communities', 'community-cpt' ),
			'menu_name'          => __( 'Communities', 'community-cpt' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'hierarchical'       => true,
			'has_archive'        => 'community',
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-location-alt',
			'menu_position'      => 20,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'custom-fields' ),
			'rewrite'            => array(
				'slug'         => 'community',
				'with_front'   => false,
				'hierarchical' => true,
			),
			'capability_type'    => 'page',
			'show_in_nav_menus'  => true,
		);

		register_post_type( 'community', $args );
	}

	/**
	 * Inject top and bottom content on listing pages.
	 *
	 * For posts that have children (listing pages), prepend/append
	 * custom content from meta fields.
	 *
	 * @param string $content The post content.
	 * @return string Modified content.
	 */
	public function inject_listing_content( $content ) {
		if ( ! is_singular( 'community' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id = get_the_ID();

		// Only apply to listing pages (posts with children).
		if ( ! community_post_has_children( $post_id ) ) {
			return $content;
		}

		$top_content    = get_post_meta( $post_id, '_community_top_content', true );
		$bottom_content = get_post_meta( $post_id, '_community_bottom_content', true );

		if ( ! empty( $top_content ) ) {
			$content = wp_kses_post( $top_content ) . $content;
		}

		if ( ! empty( $bottom_content ) ) {
			$content .= wp_kses_post( $bottom_content );
		}

		return $content;
	}

	/**
	 * Auto-append related posts on detail pages.
	 *
	 * For posts that have no children (detail pages), automatically
	 * append the related posts shortcode if not already present.
	 *
	 * @param string $content The post content.
	 * @return string Modified content.
	 */
	public function inject_related_posts( $content ) {
		if ( ! is_singular( 'community' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id = get_the_ID();

		// Only apply to detail pages (posts without children).
		if ( community_post_has_children( $post_id ) ) {
			return $content;
		}

		// Check if related shortcode already exists in content.
		if ( has_shortcode( $content, 'community_related' ) ) {
			return $content;
		}

		// Append related posts shortcode.
		$content .= do_shortcode( '[community_related]' );

		return $content;
	}

	/**
	 * Load custom archive template for community post type.
	 *
	 * @param string $template The current template path.
	 * @return string Modified template path.
	 */
	public function archive_template( $template ) {
		if ( is_post_type_archive( 'community' ) ) {
			$custom_template = COMMUNITY_CPT_PATH . 'templates/archive-community.php';
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}
		return $template;
	}

	/**
	 * Add custom body classes for community pages.
	 *
	 * @param array $classes Existing body classes.
	 * @return array Modified body classes.
	 */
	public function add_body_classes( $classes ) {
		if ( is_singular( 'community' ) ) {
			$classes[] = 'community-single-fullwidth';

			$post_id = get_the_ID();
			if ( community_post_has_children( $post_id ) ) {
				$classes[] = 'community-listing-page';
			} else {
				$classes[] = 'community-detail-page';
			}
		}

		if ( is_post_type_archive( 'community' ) ) {
			$classes[] = 'community-archive-fullwidth';
		}

		return $classes;
	}
}
