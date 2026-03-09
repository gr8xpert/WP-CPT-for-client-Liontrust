<?php
/**
 * Community Shortcodes Class.
 *
 * Handles the [community_grid] shortcode for displaying child communities.
 *
 * @package Community_CPT
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Community_Shortcodes
 *
 * Registers and renders the community grid shortcode.
 */
class Community_Shortcodes {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'community_grid', array( $this, 'render_grid' ) );
	}

	/**
	 * Render the community grid shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Grid HTML output.
	 */
	public function render_grid( $atts ) {
		$defaults = array(
			'parent_id'   => get_the_ID(),
			'columns'     => community_cpt_get_setting( 'default_columns', 3 ),
			'per_page'    => community_cpt_get_setting( 'pagination_mode' ) === 'all' ? 0 : community_cpt_get_setting( 'per_page', 20 ),
			'show_search' => community_cpt_get_setting( 'show_search', true ) ? 'true' : 'false',
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
			'style'       => community_cpt_get_setting( 'card_style', 'default' ),
		);

		$atts = shortcode_atts( $defaults, $atts, 'community_grid' );

		// Sanitize and validate attributes.
		$atts['parent_id']   = absint( $atts['parent_id'] );
		$atts['columns']     = $this->validate_columns( absint( $atts['columns'] ) );
		$atts['per_page']    = absint( $atts['per_page'] );
		$atts['show_search'] = filter_var( $atts['show_search'], FILTER_VALIDATE_BOOLEAN );
		$atts['orderby']     = $this->validate_orderby( $atts['orderby'] );
		$atts['order']       = $this->validate_order( $atts['order'] );
		$atts['style']       = in_array( $atts['style'], array( 'default', 'compact' ), true ) ? $atts['style'] : 'default';

		// Build query args.
		$paged      = get_query_var( 'paged', 1 );
		$query_args = array(
			'post_type'      => 'community',
			'post_parent'    => $atts['parent_id'],
			'post_status'    => 'publish',
			'orderby'        => $atts['orderby'],
			'order'          => $atts['order'],
			'posts_per_page' => ( 0 === $atts['per_page'] ) ? -1 : $atts['per_page'],
		);

		if ( $atts['per_page'] > 0 ) {
			$query_args['paged'] = $paged;
		}

		/**
		 * Filter the grid query arguments.
		 *
		 * @param array $query_args WP_Query arguments.
		 * @param array $atts       Shortcode attributes.
		 */
		$query_args = apply_filters( 'community_grid_query_args', $query_args, $atts );

		$query = new WP_Query( $query_args );

		/**
		 * Fires before the grid output.
		 *
		 * @param array    $atts  Shortcode attributes.
		 * @param WP_Query $query The query object.
		 */
		do_action( 'community_before_grid', $atts, $query );

		// Generate output using template partials.
		ob_start();
		$this->render_grid_wrapper( $query, $atts );
		$output = ob_get_clean();

		/**
		 * Fires after the grid output.
		 *
		 * @param array    $atts  Shortcode attributes.
		 * @param WP_Query $query The query object.
		 */
		do_action( 'community_after_grid', $atts, $query );

		wp_reset_postdata();

		return $output;
	}

	/**
	 * Render the grid wrapper template.
	 *
	 * @param WP_Query $query The query object.
	 * @param array    $atts  Shortcode attributes.
	 */
	private function render_grid_wrapper( $query, $atts ) {
		$settings = array(
			'lazy_load_images' => community_cpt_get_setting( 'lazy_load_images', true ),
			'excerpt_length'   => community_cpt_get_setting( 'excerpt_length', 120 ),
		);

		include COMMUNITY_CPT_PATH . 'templates/partials/grid-wrapper.php';
	}

	/**
	 * Validate columns attribute.
	 *
	 * @param int $columns The columns value.
	 * @return int Valid columns value (2, 3, or 4).
	 */
	private function validate_columns( $columns ) {
		$allowed = array( 2, 3, 4 );
		return in_array( $columns, $allowed, true ) ? $columns : 3;
	}

	/**
	 * Validate orderby attribute.
	 *
	 * @param string $orderby The orderby value.
	 * @return string Valid orderby value.
	 */
	private function validate_orderby( $orderby ) {
		$allowed = array( 'menu_order', 'title', 'date' );
		return in_array( $orderby, $allowed, true ) ? $orderby : 'menu_order';
	}

	/**
	 * Validate order attribute.
	 *
	 * @param string $order The order value.
	 * @return string Valid order value (ASC or DESC).
	 */
	private function validate_order( $order ) {
		$order = strtoupper( $order );
		return in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';
	}

	/**
	 * Get the excerpt for a grid card.
	 *
	 * Checks custom meta first, then WP excerpt, then content.
	 *
	 * @param WP_Post $post   The post object.
	 * @param int     $length Character limit for excerpt.
	 * @return string The truncated excerpt.
	 */
	public static function get_card_excerpt( $post, $length = 120 ) {
		// Check custom grid excerpt meta first.
		$excerpt = get_post_meta( $post->ID, '_community_grid_excerpt', true );

		// Fall back to WP excerpt.
		if ( empty( $excerpt ) ) {
			$excerpt = $post->post_excerpt;
		}

		// Fall back to content.
		if ( empty( $excerpt ) ) {
			$excerpt = wp_strip_all_tags( $post->post_content );
			$excerpt = wp_trim_words( $excerpt, 20, '' );
		}

		/**
		 * Filter the excerpt character length.
		 *
		 * @param int $length  Character limit.
		 * @param int $post_id Post ID.
		 */
		$length = apply_filters( 'community_card_excerpt_length', $length, $post->ID );

		// Truncate to character limit.
		if ( strlen( $excerpt ) > $length ) {
			$excerpt = substr( $excerpt, 0, $length );
			$excerpt = substr( $excerpt, 0, strrpos( $excerpt, ' ' ) );
			$excerpt .= '...';
		}

		return $excerpt;
	}

	/**
	 * Get the featured image URL for a grid card.
	 *
	 * Returns a placeholder if no featured image is set.
	 *
	 * @param WP_Post $post The post object.
	 * @param string  $size Image size name.
	 * @return string Image URL.
	 */
	public static function get_card_image_url( $post, $size = 'medium_large' ) {
		$image_url = get_the_post_thumbnail_url( $post->ID, $size );

		if ( ! $image_url ) {
			// Generate a placeholder URL.
			$image_url = self::get_placeholder_image();
		}

		return $image_url;
	}

	/**
	 * Get the lazy load placeholder image.
	 *
	 * @return string SVG data URI placeholder.
	 */
	public static function get_lazy_placeholder() {
		/**
		 * Filter the lazy load placeholder image.
		 *
		 * @param string $placeholder_url The placeholder image URL or data URI.
		 */
		return apply_filters(
			'community_lazy_placeholder',
			"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='250' viewBox='0 0 400 250'%3E%3Crect fill='%23f0f0f0' width='400' height='250'/%3E%3C/svg%3E"
		);
	}

	/**
	 * Get a placeholder image for posts without featured images.
	 *
	 * @return string SVG data URI placeholder.
	 */
	public static function get_placeholder_image() {
		return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='250' viewBox='0 0 400 250'%3E%3Crect fill='%23e0e0e0' width='400' height='250'/%3E%3Cpath fill='%23bbb' d='M175 90h50v70h-50z'/%3E%3Ccircle fill='%23bbb' cx='200' cy='75' r='20'/%3E%3C/svg%3E";
	}
}
