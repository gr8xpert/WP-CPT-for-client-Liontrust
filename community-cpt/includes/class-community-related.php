<?php
/**
 * Community Related Posts Class.
 *
 * Handles the [community_related] shortcode.
 *
 * @package Community_CPT
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Community_Related
 *
 * Registers and renders the related communities shortcode
 * with automatic sibling/cousin fallback logic.
 */
class Community_Related {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'community_related', array( $this, 'render_related' ) );
	}

	/**
	 * Render the related communities shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Related posts HTML output.
	 */
	public function render_related( $atts = array() ) {
		if ( ! is_singular( 'community' ) ) {
			return '';
		}

		$post_id = get_the_ID();

		$defaults = array(
			'title'   => '',
			'limit'   => community_cpt_get_setting( 'related_count', 8 ),
			'columns' => 4,
		);

		$atts = shortcode_atts( $defaults, $atts, 'community_related' );

		// Sanitize attributes.
		$atts['limit']   = max( 1, min( 12, absint( $atts['limit'] ) ) );
		$atts['columns'] = in_array( absint( $atts['columns'] ), array( 2, 3, 4 ), true ) ? absint( $atts['columns'] ) : 4;

		// Get related posts.
		$related_posts = $this->get_related_posts( $post_id, $atts['limit'] );

		if ( empty( $related_posts ) ) {
			return '';
		}

		// Get title.
		$title = $this->get_related_title( $post_id, $atts['title'] );

		/**
		 * Fires before the related section.
		 *
		 * @param int   $post_id       Current post ID.
		 * @param array $related_posts Related post objects.
		 */
		do_action( 'community_before_related', $post_id, $related_posts );

		// Render output.
		ob_start();
		include COMMUNITY_CPT_PATH . 'templates/partials/related-grid.php';
		$output = ob_get_clean();

		/**
		 * Fires after the related section.
		 *
		 * @param int   $post_id       Current post ID.
		 * @param array $related_posts Related post objects.
		 */
		do_action( 'community_after_related', $post_id, $related_posts );

		return $output;
	}

	/**
	 * Get related posts for a community.
	 *
	 * Priority order:
	 * 1. Manually selected posts from meta
	 * 2. Sibling posts (same parent)
	 * 3. Cousin posts (same grandparent)
	 *
	 * @param int $post_id Current post ID.
	 * @param int $limit   Maximum posts to return.
	 * @return array Array of WP_Post objects.
	 */
	private function get_related_posts( $post_id, $limit ) {
		// Step 1: Check for manually selected posts.
		$manual_ids = get_post_meta( $post_id, '_community_related_posts', true );

		if ( ! empty( $manual_ids ) && is_array( $manual_ids ) ) {
			$query_args = array(
				'post_type'      => 'community',
				'post__in'       => $manual_ids,
				'orderby'        => 'post__in',
				'posts_per_page' => $limit,
				'post_status'    => 'publish',
			);

			/**
			 * Filter the related posts query arguments.
			 *
			 * @param array $query_args WP_Query arguments.
			 * @param int   $post_id    Current post ID.
			 */
			$query_args = apply_filters( 'community_related_query_args', $query_args, $post_id );

			$query = new WP_Query( $query_args );

			if ( $query->have_posts() ) {
				return $query->posts;
			}
		}

		// Step 2: Get sibling posts.
		$parent_id = wp_get_post_parent_id( $post_id );

		if ( $parent_id ) {
			$siblings = $this->get_siblings( $post_id, $parent_id, $limit );

			if ( ! empty( $siblings ) ) {
				return $siblings;
			}
		}

		// Step 3: Get cousin posts (posts with same grandparent).
		$grandparent_id = $parent_id ? wp_get_post_parent_id( $parent_id ) : 0;

		if ( $grandparent_id ) {
			$cousins = $this->get_cousins( $post_id, $grandparent_id, $limit );

			if ( ! empty( $cousins ) ) {
				return $cousins;
			}
		}

		// Step 4: Fallback to random communities.
		return $this->get_random_communities( $post_id, $limit );
	}

	/**
	 * Get sibling posts (same parent, excluding current).
	 *
	 * @param int $post_id   Current post ID.
	 * @param int $parent_id Parent post ID.
	 * @param int $limit     Maximum posts to return.
	 * @return array Array of WP_Post objects.
	 */
	private function get_siblings( $post_id, $parent_id, $limit ) {
		$query_args = array(
			'post_type'      => 'community',
			'post_parent'    => $parent_id,
			'post__not_in'   => array( $post_id ),
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		);

		/**
		 * Filter the related posts query arguments.
		 *
		 * @param array $query_args WP_Query arguments.
		 * @param int   $post_id    Current post ID.
		 */
		$query_args = apply_filters( 'community_related_query_args', $query_args, $post_id );

		$query = new WP_Query( $query_args );

		return $query->posts;
	}

	/**
	 * Get cousin posts (posts whose parent shares the same grandparent).
	 *
	 * @param int $post_id        Current post ID.
	 * @param int $grandparent_id Grandparent post ID.
	 * @param int $limit          Maximum posts to return.
	 * @return array Array of WP_Post objects.
	 */
	private function get_cousins( $post_id, $grandparent_id, $limit ) {
		// First, get all parents under this grandparent.
		$parent_query = new WP_Query( array(
			'post_type'      => 'community',
			'post_parent'    => $grandparent_id,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		) );

		$parent_ids = $parent_query->posts;

		if ( empty( $parent_ids ) ) {
			return array();
		}

		// Get children of all parents (cousins), excluding current post.
		$query_args = array(
			'post_type'      => 'community',
			'post_parent__in' => $parent_ids,
			'post__not_in'   => array( $post_id ),
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		);

		/**
		 * Filter the related posts query arguments.
		 *
		 * @param array $query_args WP_Query arguments.
		 * @param int   $post_id    Current post ID.
		 */
		$query_args = apply_filters( 'community_related_query_args', $query_args, $post_id );

		$query = new WP_Query( $query_args );

		return $query->posts;
	}

	/**
	 * Get random community posts as fallback.
	 *
	 * @param int $post_id Current post ID to exclude.
	 * @param int $limit   Maximum posts to return.
	 * @return array Array of WP_Post objects.
	 */
	private function get_random_communities( $post_id, $limit ) {
		$query_args = array(
			'post_type'      => 'community',
			'post__not_in'   => array( $post_id ),
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'orderby'        => 'rand',
		);

		/**
		 * Filter the related posts query arguments.
		 *
		 * @param array $query_args WP_Query arguments.
		 * @param int   $post_id    Current post ID.
		 */
		$query_args = apply_filters( 'community_related_query_args', $query_args, $post_id );

		$query = new WP_Query( $query_args );

		return $query->posts;
	}

	/**
	 * Get the related section title.
	 *
	 * @param int    $post_id Current post ID.
	 * @param string $custom  Custom title from shortcode attribute.
	 * @return string The section title.
	 */
	private function get_related_title( $post_id, $custom = '' ) {
		// Use shortcode attribute if provided.
		if ( ! empty( $custom ) ) {
			return esc_html( $custom );
		}

		// Check meta field.
		$meta_title = get_post_meta( $post_id, '_community_related_title', true );

		if ( ! empty( $meta_title ) ) {
			return esc_html( $meta_title );
		}

		// Default title.
		$post_title = get_the_title( $post_id );
		/* translators: %s: Post title (location name) */
		return esc_html( sprintf( __( 'Communities near %s', 'community-cpt' ), $post_title ) );
	}
}
