<?php
/**
 * Community Ajax Class.
 *
 * Handles Ajax requests for grid search, pagination, and admin post search.
 *
 * @package Community_CPT
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Community_Ajax
 *
 * Registers and handles Ajax endpoints for the community grid
 * search, pagination, and admin post selection.
 */
class Community_Ajax {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Frontend grid search and pagination.
		add_action( 'wp_ajax_community_grid_search', array( $this, 'handle_search' ) );
		add_action( 'wp_ajax_nopriv_community_grid_search', array( $this, 'handle_search' ) );

		// Admin post search for Select2.
		add_action( 'wp_ajax_community_search_posts', array( $this, 'search_posts_for_select' ) );

		// Cache invalidation hooks.
		add_action( 'save_post_community', array( $this, 'invalidate_cache' ) );
		add_action( 'trashed_post', array( $this, 'invalidate_cache_on_trash' ) );
		add_action( 'untrashed_post', array( $this, 'invalidate_cache_on_trash' ) );
		add_action( 'deleted_post', array( $this, 'invalidate_cache_on_trash' ) );
	}

	/**
	 * Handle grid search and pagination Ajax request.
	 */
	public function handle_search() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) ||
			 ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'community_grid_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'community-cpt' ) ) );
		}

		// Sanitize inputs.
		$parent_id = isset( $_POST['parent_id'] ) ? absint( $_POST['parent_id'] ) : 0;
		$search    = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$page      = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
		$per_page  = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : community_cpt_get_setting( 'per_page', 20 );
		$columns   = isset( $_POST['columns'] ) ? $this->validate_columns( absint( $_POST['columns'] ) ) : 3;
		$orderby   = isset( $_POST['orderby'] ) ? $this->validate_orderby( sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) ) : 'menu_order';
		$order     = isset( $_POST['order'] ) ? $this->validate_order( sanitize_text_field( wp_unslash( $_POST['order'] ) ) ) : 'ASC';
		$style     = isset( $_POST['style'] ) ? sanitize_text_field( wp_unslash( $_POST['style'] ) ) : 'default';
		$style     = in_array( $style, array( 'default', 'compact' ), true ) ? $style : 'default';

		if ( ! $parent_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parent ID.', 'community-cpt' ) ) );
		}

		// Build query args.
		$query_args = array(
			'post_type'      => 'community',
			'post_parent'    => $parent_id,
			'post_status'    => 'publish',
			'orderby'        => $orderby,
			'order'          => $order,
			'posts_per_page' => ( 0 === $per_page ) ? -1 : $per_page,
		);

		if ( $per_page > 0 ) {
			$query_args['paged'] = $page;
		}

		// Add search query if provided.
		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}

		/**
		 * Filter the grid query arguments.
		 *
		 * @param array $query_args WP_Query arguments.
		 * @param array $atts       Shortcode-like attributes.
		 */
		$atts = array(
			'parent_id' => $parent_id,
			'columns'   => $columns,
			'per_page'  => $per_page,
			'orderby'   => $orderby,
			'order'     => $order,
			'style'     => $style,
		);
		$query_args = apply_filters( 'community_grid_query_args', $query_args, $atts );

		// Check cache.
		$cache_key = $this->get_cache_key( $query_args, $search );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && empty( $search ) ) {
			wp_send_json_success( $cached );
		}

		// Run query.
		$query = new WP_Query( $query_args );

		// Generate grid HTML.
		$settings = array(
			'lazy_load_images' => community_cpt_get_setting( 'lazy_load_images', true ),
			'excerpt_length'   => community_cpt_get_setting( 'excerpt_length', 120 ),
		);

		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				include COMMUNITY_CPT_PATH . 'templates/partials/grid-card.php';
			}
		} else {
			/**
			 * Filter the no results HTML.
			 *
			 * @param string $html The no results message HTML.
			 * @param array  $atts Shortcode attributes.
			 */
			$no_results_html = apply_filters(
				'community_grid_no_results_html',
				'<p class="community-grid-no-results">' . esc_html__( 'No communities found.', 'community-cpt' ) . '</p>',
				$atts
			);
			echo wp_kses_post( $no_results_html );
		}
		$grid_html = ob_get_clean();

		// Generate pagination HTML.
		$pagination_html = '';
		if ( $per_page > 0 && $query->max_num_pages > 1 ) {
			$current_page  = $page;
			$max_num_pages = $query->max_num_pages;
			ob_start();
			include COMMUNITY_CPT_PATH . 'templates/partials/pagination.php';
			$pagination_html = ob_get_clean();
		}

		wp_reset_postdata();

		$response = array(
			'html'         => $grid_html,
			'pagination'   => $pagination_html,
			'total_pages'  => $query->max_num_pages,
			'current_page' => $page,
			'total_posts'  => $query->found_posts,
			'found_posts'  => $query->post_count,
		);

		// Cache the response (only if not a search query).
		if ( empty( $search ) ) {
			$cache_duration = community_cpt_get_setting( 'cache_duration', 3600 );

			/**
			 * Filter the cache expiration time.
			 *
			 * @param int $seconds Cache duration in seconds.
			 */
			$cache_duration = apply_filters( 'community_cache_expiration', $cache_duration );

			if ( $cache_duration > 0 ) {
				set_transient( $cache_key, $response, $cache_duration );
			}
		}

		wp_send_json_success( $response );
	}

	/**
	 * Search posts for Select2 dropdown (admin).
	 */
	public function search_posts_for_select() {
		// Verify nonce.
		if ( ! isset( $_GET['nonce'] ) ||
			 ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'community_admin_nonce' ) ) {
			wp_send_json( array() );
		}

		$search  = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$exclude = isset( $_GET['exclude'] ) ? absint( $_GET['exclude'] ) : 0;

		if ( strlen( $search ) < 2 ) {
			wp_send_json( array() );
		}

		$query_args = array(
			'post_type'      => 'community',
			'post_status'    => array( 'publish', 'draft' ),
			's'              => $search,
			'posts_per_page' => 20,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( $exclude ) {
			$query_args['post__not_in'] = array( $exclude );
		}

		$query   = new WP_Query( $query_args );
		$results = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id     = get_the_ID();
				$title       = get_the_title();
				$parent_name = '';

				if ( wp_get_post_parent_id( $post_id ) ) {
					$parent = get_post( wp_get_post_parent_id( $post_id ) );
					if ( $parent ) {
						$parent_name = ' (' . $parent->post_title . ')';
					}
				}

				$results[] = array(
					'id'   => $post_id,
					'text' => $title . $parent_name,
				);
			}
		}

		wp_reset_postdata();
		wp_send_json( $results );
	}

	/**
	 * Invalidate cache when a community post is saved.
	 *
	 * @param int $post_id The post ID.
	 */
	public function invalidate_cache( $post_id = 0 ) {
		$this->clear_all_grid_transients();
		$this->clear_all_children_transients();
	}

	/**
	 * Invalidate cache when a post is trashed/untrashed/deleted.
	 *
	 * @param int $post_id The post ID.
	 */
	public function invalidate_cache_on_trash( $post_id ) {
		$post = get_post( $post_id );
		if ( $post && 'community' === $post->post_type ) {
			$this->invalidate_cache( $post_id );
		}
	}

	/**
	 * Clear all grid transients.
	 */
	private function clear_all_grid_transients() {
		global $wpdb;

		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_community_grid_%'
			OR option_name LIKE '_transient_timeout_community_grid_%'"
		);

		/**
		 * Fires after community cache is cleared.
		 */
		do_action( 'community_cache_cleared' );
	}

	/**
	 * Clear all "has children" transients.
	 */
	private function clear_all_children_transients() {
		global $wpdb;

		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_community_has_children_%'
			OR option_name LIKE '_transient_timeout_community_has_children_%'"
		);
	}

	/**
	 * Generate a cache key for grid queries.
	 *
	 * @param array  $query_args Query arguments.
	 * @param string $search     Search term.
	 * @return string Cache key.
	 */
	private function get_cache_key( $query_args, $search = '' ) {
		$key_parts = array(
			'community_grid',
			$query_args['post_parent'],
			isset( $query_args['paged'] ) ? $query_args['paged'] : 1,
			isset( $query_args['posts_per_page'] ) ? $query_args['posts_per_page'] : -1,
			$query_args['orderby'],
			$query_args['order'],
		);

		if ( ! empty( $search ) ) {
			$key_parts[] = md5( $search );
		}

		return implode( '_', $key_parts );
	}

	/**
	 * Validate columns value.
	 *
	 * @param int $columns The columns value.
	 * @return int Valid columns value.
	 */
	private function validate_columns( $columns ) {
		$allowed = array( 2, 3, 4 );
		return in_array( $columns, $allowed, true ) ? $columns : 3;
	}

	/**
	 * Validate orderby value.
	 *
	 * @param string $orderby The orderby value.
	 * @return string Valid orderby value.
	 */
	private function validate_orderby( $orderby ) {
		$allowed = array( 'menu_order', 'title', 'date' );
		return in_array( $orderby, $allowed, true ) ? $orderby : 'menu_order';
	}

	/**
	 * Validate order value.
	 *
	 * @param string $order The order value.
	 * @return string Valid order value.
	 */
	private function validate_order( $order ) {
		$order = strtoupper( $order );
		return in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';
	}
}
