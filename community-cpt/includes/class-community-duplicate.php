<?php
/**
 * Community Duplicate Class.
 *
 * Handles duplicating community posts.
 *
 * @package Community_CPT
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Community_Duplicate
 *
 * Adds a "Duplicate" action link to community posts
 * and handles the duplication process.
 */
class Community_Duplicate {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'post_row_actions', array( $this, 'add_duplicate_link' ), 10, 2 );
		add_action( 'admin_action_duplicate_community', array( $this, 'handle_duplicate' ) );
	}

	/**
	 * Add duplicate link to post row actions.
	 *
	 * @param array   $actions Existing actions.
	 * @param WP_Post $post    Current post object.
	 * @return array Modified actions.
	 */
	public function add_duplicate_link( $actions, $post ) {
		if ( 'community' !== $post->post_type ) {
			return $actions;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return $actions;
		}

		$nonce = wp_create_nonce( 'duplicate_community_' . $post->ID );
		$url   = admin_url( 'admin.php?action=duplicate_community&post=' . $post->ID . '&_wpnonce=' . $nonce );

		$actions['duplicate'] = sprintf(
			'<a href="%s" title="%s">%s</a>',
			esc_url( $url ),
			esc_attr__( 'Duplicate this community', 'community-cpt' ),
			esc_html__( 'Duplicate', 'community-cpt' )
		);

		return $actions;
	}

	/**
	 * Handle the duplicate action.
	 */
	public function handle_duplicate() {
		// Verify post ID.
		if ( ! isset( $_GET['post'] ) ) {
			wp_die( esc_html__( 'No post to duplicate.', 'community-cpt' ) );
		}

		$post_id = absint( $_GET['post'] );

		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) ||
			 ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'duplicate_community_' . $post_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'community-cpt' ) );
		}

		// Verify capability.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to duplicate posts.', 'community-cpt' ) );
		}

		// Get the original post.
		$original = get_post( $post_id );

		if ( ! $original || 'community' !== $original->post_type ) {
			wp_die( esc_html__( 'Invalid community post.', 'community-cpt' ) );
		}

		// Create duplicate post data.
		$new_post_data = array(
			'post_title'   => $original->post_title . ' (' . __( 'Copy', 'community-cpt' ) . ')',
			'post_type'    => 'community',
			'post_status'  => 'draft',
			'post_parent'  => $original->post_parent,
			'post_content' => $original->post_content,
			'post_excerpt' => $original->post_excerpt,
			'menu_order'   => $original->menu_order + 1,
			'post_author'  => get_current_user_id(),
		);

		// Insert the new post.
		$new_post_id = wp_insert_post( $new_post_data );

		if ( is_wp_error( $new_post_id ) ) {
			wp_die( esc_html__( 'Failed to create duplicate post.', 'community-cpt' ) );
		}

		// Copy post meta.
		$this->copy_post_meta( $post_id, $new_post_id );

		// Copy featured image.
		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id ) {
			set_post_thumbnail( $new_post_id, $thumbnail_id );
		}

		/**
		 * Fires after a community post is duplicated.
		 *
		 * @param int $new_post_id      The new post ID.
		 * @param int $original_post_id The original post ID.
		 */
		do_action( 'community_post_duplicated', $new_post_id, $post_id );

		// Redirect to the new post's edit screen.
		wp_safe_redirect( get_edit_post_link( $new_post_id, 'url' ) );
		exit;
	}

	/**
	 * Copy all meta from one post to another.
	 *
	 * @param int $source_id      Source post ID.
	 * @param int $destination_id Destination post ID.
	 */
	private function copy_post_meta( $source_id, $destination_id ) {
		$all_meta = get_post_meta( $source_id );

		if ( ! is_array( $all_meta ) ) {
			return;
		}

		// Meta keys to skip.
		$skip_keys = array(
			'_edit_lock',
			'_edit_last',
			'_wp_old_slug',
			'_wp_old_date',
			'_thumbnail_id', // Handled separately.
		);

		foreach ( $all_meta as $meta_key => $meta_values ) {
			// Skip internal WP meta keys.
			if ( in_array( $meta_key, $skip_keys, true ) ) {
				continue;
			}

			// Skip keys starting with _edit_ or _wp_ (except allowed ones).
			if ( preg_match( '/^_edit_/', $meta_key ) || preg_match( '/^_wp_(?!.*attached)/', $meta_key ) ) {
				continue;
			}

			foreach ( $meta_values as $meta_value ) {
				add_post_meta( $destination_id, $meta_key, maybe_unserialize( $meta_value ) );
			}
		}
	}
}
