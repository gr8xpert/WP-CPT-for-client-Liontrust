<?php
/**
 * Community Meta Class.
 *
 * Handles meta boxes and custom fields for community posts.
 *
 * @package Community_CPT
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Community_Meta
 *
 * Registers and manages meta boxes and custom fields
 * for the community post type.
 */
class Community_Meta {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_community', array( $this, 'save_meta' ), 10, 2 );
	}

	/**
	 * Register meta boxes for community posts.
	 */
	public function add_meta_boxes() {
		global $post;

		// Only show listing content meta box for posts that have children (listing pages).
		$has_children = $post && $post->ID ? community_post_has_children( $post->ID ) : false;

		if ( $has_children ) {
			add_meta_box(
				'community_listing_content',
				__( 'Listing Page Content', 'community-cpt' ),
				array( $this, 'render_listing_content_meta_box' ),
				'community',
				'normal',
				'low'
			);
		}

		add_meta_box(
			'community_settings',
			__( 'Community Settings', 'community-cpt' ),
			array( $this, 'render_settings_meta_box' ),
			'community',
			'normal',
			'low'
		);

		add_meta_box(
			'community_related',
			__( 'Related Communities', 'community-cpt' ),
			array( $this, 'render_related_meta_box' ),
			'community',
			'side',
			'default'
		);
	}

	/**
	 * Render the Community Settings meta box.
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function render_settings_meta_box( $post ) {
		wp_nonce_field( 'community_meta_nonce', 'community_meta_nonce' );

		$grid_excerpt = get_post_meta( $post->ID, '_community_grid_excerpt', true );
		?>
		<div class="community-meta-field">
			<label for="community_grid_excerpt">
				<strong><?php esc_html_e( 'Grid Card Excerpt', 'community-cpt' ); ?></strong>
			</label>
			<p class="description">
				<?php esc_html_e( 'Short description shown on grid cards when this community appears in a parent listing. Falls back to WP excerpt if empty.', 'community-cpt' ); ?>
			</p>
			<textarea
				id="community_grid_excerpt"
				name="community_grid_excerpt"
				rows="3"
				style="width: 100%;"
			><?php echo esc_textarea( $grid_excerpt ); ?></textarea>
		</div>
		<?php
	}

	/**
	 * Render the Listing Page Content meta box.
	 * Only shown for posts that have children (listing pages).
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function render_listing_content_meta_box( $post ) {
		$top_content    = get_post_meta( $post->ID, '_community_top_content', true );
		$bottom_content = get_post_meta( $post->ID, '_community_bottom_content', true );
		?>
		<p class="description" style="margin-bottom: 15px;">
			<?php esc_html_e( 'This community has child pages, so it displays as a listing page with a grid. Add content above or below the grid here.', 'community-cpt' ); ?>
		</p>

		<div class="community-meta-field">
			<label for="community_top_content">
				<strong><?php esc_html_e( 'Content Above Grid', 'community-cpt' ); ?></strong>
			</label>
			<?php
			wp_editor(
				$top_content,
				'community_top_content',
				array(
					'textarea_name' => 'community_top_content',
					'textarea_rows' => 6,
					'media_buttons' => true,
					'teeny'         => false,
					'quicktags'     => true,
				)
			);
			?>
		</div>

		<div class="community-meta-field" style="margin-top: 20px;">
			<label for="community_bottom_content">
				<strong><?php esc_html_e( 'Content Below Grid', 'community-cpt' ); ?></strong>
			</label>
			<?php
			wp_editor(
				$bottom_content,
				'community_bottom_content',
				array(
					'textarea_name' => 'community_bottom_content',
					'textarea_rows' => 6,
					'media_buttons' => true,
					'teeny'         => false,
					'quicktags'     => true,
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * Render the Related Communities meta box.
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function render_related_meta_box( $post ) {
		$related_posts = get_post_meta( $post->ID, '_community_related_posts', true );
		$related_title = get_post_meta( $post->ID, '_community_related_title', true );

		if ( ! is_array( $related_posts ) ) {
			$related_posts = array();
		}

		// Get currently selected posts for display.
		$selected_posts = array();
		if ( ! empty( $related_posts ) ) {
			$selected_posts = get_posts( array(
				'post_type'      => 'community',
				'post__in'       => $related_posts,
				'orderby'        => 'post__in',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			) );
		}
		?>
		<div class="community-meta-field">
			<label for="community_related_title">
				<strong><?php esc_html_e( 'Related Section Title', 'community-cpt' ); ?></strong>
			</label>
			<p class="description">
				<?php esc_html_e( 'Custom heading for the related section. Default: "Communities near {post_title}"', 'community-cpt' ); ?>
			</p>
			<input
				type="text"
				id="community_related_title"
				name="community_related_title"
				value="<?php echo esc_attr( $related_title ); ?>"
				style="width: 100%;"
			>
		</div>

		<div class="community-meta-field" style="margin-top: 15px;">
			<label for="community_related_posts">
				<strong><?php esc_html_e( 'Related Communities', 'community-cpt' ); ?></strong>
			</label>
			<p class="description">
				<?php esc_html_e( 'Leave empty to automatically show sibling communities.', 'community-cpt' ); ?>
			</p>
			<select
				id="community_related_posts"
				name="community_related_posts[]"
				multiple="multiple"
				style="width: 100%;"
			>
				<?php foreach ( $selected_posts as $selected_post ) : ?>
					<?php
					$parent_name = '';
					if ( $selected_post->post_parent ) {
						$parent = get_post( $selected_post->post_parent );
						if ( $parent ) {
							$parent_name = ' (' . $parent->post_title . ')';
						}
					}
					?>
					<option value="<?php echo esc_attr( $selected_post->ID ); ?>" selected="selected">
						<?php echo esc_html( $selected_post->post_title . $parent_name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p style="margin-top: 8px;">
				<button type="button" id="community-clear-related" class="button button-small">
					<?php esc_html_e( 'Clear All', 'community-cpt' ); ?>
				</button>
			</p>
		</div>
		<?php
	}

	/**
	 * Save meta field values.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post object.
	 */
	public function save_meta( $post_id, $post ) {
		// Verify nonce.
		if ( ! isset( $_POST['community_meta_nonce'] ) ||
			 ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['community_meta_nonce'] ) ), 'community_meta_nonce' ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save grid excerpt.
		if ( isset( $_POST['community_grid_excerpt'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
			$grid_excerpt = sanitize_text_field( wp_unslash( $_POST['community_grid_excerpt'] ) );
			if ( ! empty( $grid_excerpt ) ) {
				update_post_meta( $post_id, '_community_grid_excerpt', $grid_excerpt );
			} else {
				delete_post_meta( $post_id, '_community_grid_excerpt' );
			}
		}

		// Save top content.
		if ( isset( $_POST['community_top_content'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
			$top_content = wp_kses_post( wp_unslash( $_POST['community_top_content'] ) );
			if ( ! empty( $top_content ) ) {
				update_post_meta( $post_id, '_community_top_content', $top_content );
			} else {
				delete_post_meta( $post_id, '_community_top_content' );
			}
		}

		// Save bottom content.
		if ( isset( $_POST['community_bottom_content'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
			$bottom_content = wp_kses_post( wp_unslash( $_POST['community_bottom_content'] ) );
			if ( ! empty( $bottom_content ) ) {
				update_post_meta( $post_id, '_community_bottom_content', $bottom_content );
			} else {
				delete_post_meta( $post_id, '_community_bottom_content' );
			}
		}

		// Save related title.
		if ( isset( $_POST['community_related_title'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
			$related_title = sanitize_text_field( wp_unslash( $_POST['community_related_title'] ) );
			if ( ! empty( $related_title ) ) {
				update_post_meta( $post_id, '_community_related_title', $related_title );
			} else {
				delete_post_meta( $post_id, '_community_related_title' );
			}
		}

		// Save related posts.
		if ( isset( $_POST['community_related_posts'] ) && is_array( $_POST['community_related_posts'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
			$related_posts = array_map( 'absint', wp_unslash( $_POST['community_related_posts'] ) );
			$related_posts = array_filter( $related_posts );
			if ( ! empty( $related_posts ) ) {
				update_post_meta( $post_id, '_community_related_posts', $related_posts );
			} else {
				delete_post_meta( $post_id, '_community_related_posts' );
			}
		} else {
			delete_post_meta( $post_id, '_community_related_posts' );
		}
	}
}
