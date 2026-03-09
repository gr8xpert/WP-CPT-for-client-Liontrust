<?php
/**
 * Community Shortcode Wizard Class.
 *
 * Handles the shortcode wizard modal for Divi/classic editor.
 *
 * @package Community_CPT
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Community_Shortcode_Wizard
 *
 * Adds a shortcode wizard button and modal to help users
 * build community shortcodes visually.
 */
class Community_Shortcode_Wizard {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'media_buttons', array( $this, 'add_wizard_button' ), 15 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_wizard_assets' ) );
		add_action( 'admin_footer', array( $this, 'render_wizard_modal' ) );
	}

	/**
	 * Add wizard button to editor toolbar.
	 *
	 * @param string $editor_id Editor ID.
	 */
	public function add_wizard_button( $editor_id ) {
		global $post_type;

		// Only show on community post type.
		if ( 'community' !== $post_type ) {
			return;
		}
		?>
		<button type="button" class="button community-shortcode-wizard-btn" title="<?php esc_attr_e( 'Insert Community Shortcode', 'community-cpt' ); ?>">
			<span class="dashicons dashicons-screenoptions" style="vertical-align: text-bottom; margin-right: 4px;"></span>
			<?php esc_html_e( 'Community Grid', 'community-cpt' ); ?>
		</button>
		<?php
	}

	/**
	 * Enqueue wizard assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_wizard_assets( $hook ) {
		global $post_type;

		if ( 'community' !== $post_type ) {
			return;
		}

		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_script(
			'community-shortcode-wizard',
			COMMUNITY_CPT_URL . 'assets/js/community-shortcode-wizard.js',
			array( 'jquery', 'select2' ),
			COMMUNITY_CPT_VERSION,
			true
		);

		wp_localize_script( 'community-shortcode-wizard', 'communityWizard', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'community_admin_nonce' ),
			'i18n'    => array(
				'title'           => __( 'Insert Community Shortcode', 'community-cpt' ),
				'insert'          => __( 'Insert Shortcode', 'community-cpt' ),
				'cancel'          => __( 'Cancel', 'community-cpt' ),
				'shortcodeType'   => __( 'Shortcode Type', 'community-cpt' ),
				'parentCommunity' => __( 'Parent Community', 'community-cpt' ),
				'columns'         => __( 'Columns', 'community-cpt' ),
				'postsPerPage'    => __( 'Posts Per Page', 'community-cpt' ),
				'showSearch'      => __( 'Show Search', 'community-cpt' ),
				'orderBy'         => __( 'Order By', 'community-cpt' ),
				'orderDirection'  => __( 'Order Direction', 'community-cpt' ),
				'cardStyle'       => __( 'Card Style', 'community-cpt' ),
				'relatedTitle'    => __( 'Related Section Title', 'community-cpt' ),
				'relatedLimit'    => __( 'Related Posts Limit', 'community-cpt' ),
				'preview'         => __( 'Preview:', 'community-cpt' ),
				'searchPlaceholder' => __( 'Search communities...', 'community-cpt' ),
				'currentPost'     => __( '(Current post)', 'community-cpt' ),
				'allPosts'        => __( '0 = Show all', 'community-cpt' ),
			),
		) );
	}

	/**
	 * Render the wizard modal HTML.
	 */
	public function render_wizard_modal() {
		global $post_type;

		if ( 'community' !== $post_type ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->base, array( 'post', 'post-new' ), true ) ) {
			return;
		}
		?>
		<div id="community-wizard-overlay" class="community-wizard-overlay" style="display: none;" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Insert Community Shortcode', 'community-cpt' ); ?>">
			<div class="community-wizard-modal">
				<div class="community-wizard-header">
					<h2><?php esc_html_e( 'Insert Community Shortcode', 'community-cpt' ); ?></h2>
					<button type="button" class="community-wizard-close" aria-label="<?php esc_attr_e( 'Close', 'community-cpt' ); ?>">&times;</button>
				</div>
				<div class="community-wizard-body">
					<!-- Shortcode Type -->
					<div class="community-wizard-field">
						<label><?php esc_html_e( 'Shortcode Type', 'community-cpt' ); ?></label>
						<div class="community-wizard-radio-group">
							<label>
								<input type="radio" name="shortcode_type" value="community_grid" checked>
								[community_grid]
							</label>
							<label>
								<input type="radio" name="shortcode_type" value="community_related">
								[community_related]
							</label>
							<label>
								<input type="radio" name="shortcode_type" value="community_breadcrumb">
								[community_breadcrumb]
							</label>
						</div>
					</div>

					<!-- Grid Options -->
					<div class="community-wizard-grid-options">
						<div class="community-wizard-field">
							<label for="wizard_parent_id"><?php esc_html_e( 'Parent Community', 'community-cpt' ); ?></label>
							<select id="wizard_parent_id" style="width: 100%;">
								<option value=""><?php esc_html_e( '(Current post)', 'community-cpt' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Leave empty to use current post as parent.', 'community-cpt' ); ?></p>
						</div>

						<div class="community-wizard-field">
							<label for="wizard_columns"><?php esc_html_e( 'Columns', 'community-cpt' ); ?></label>
							<select id="wizard_columns">
								<option value="2">2</option>
								<option value="3" selected>3</option>
								<option value="4">4</option>
							</select>
						</div>

						<div class="community-wizard-field">
							<label for="wizard_per_page"><?php esc_html_e( 'Posts Per Page', 'community-cpt' ); ?></label>
							<input type="number" id="wizard_per_page" value="0" min="0" max="100">
							<p class="description"><?php esc_html_e( '0 = Show all posts (no pagination)', 'community-cpt' ); ?></p>
						</div>

						<div class="community-wizard-field">
							<label>
								<input type="checkbox" id="wizard_show_search" checked>
								<?php esc_html_e( 'Show Search Input', 'community-cpt' ); ?>
							</label>
						</div>

						<div class="community-wizard-field">
							<label for="wizard_orderby"><?php esc_html_e( 'Order By', 'community-cpt' ); ?></label>
							<select id="wizard_orderby">
								<option value="menu_order"><?php esc_html_e( 'Menu Order', 'community-cpt' ); ?></option>
								<option value="title"><?php esc_html_e( 'Title', 'community-cpt' ); ?></option>
								<option value="date"><?php esc_html_e( 'Date', 'community-cpt' ); ?></option>
							</select>
						</div>

						<div class="community-wizard-field">
							<label for="wizard_order"><?php esc_html_e( 'Order Direction', 'community-cpt' ); ?></label>
							<select id="wizard_order">
								<option value="ASC"><?php esc_html_e( 'Ascending', 'community-cpt' ); ?></option>
								<option value="DESC"><?php esc_html_e( 'Descending', 'community-cpt' ); ?></option>
							</select>
						</div>

						<div class="community-wizard-field">
							<label for="wizard_style"><?php esc_html_e( 'Card Style', 'community-cpt' ); ?></label>
							<select id="wizard_style">
								<option value="default"><?php esc_html_e( 'Default', 'community-cpt' ); ?></option>
								<option value="compact"><?php esc_html_e( 'Compact', 'community-cpt' ); ?></option>
							</select>
						</div>
					</div>

					<!-- Related Options -->
					<div class="community-wizard-related-options" style="display: none;">
						<div class="community-wizard-field">
							<label for="wizard_related_title"><?php esc_html_e( 'Section Title', 'community-cpt' ); ?></label>
							<input type="text" id="wizard_related_title" placeholder="<?php esc_attr_e( 'Properties near...', 'community-cpt' ); ?>">
							<p class="description"><?php esc_html_e( 'Leave empty for default title.', 'community-cpt' ); ?></p>
						</div>

						<div class="community-wizard-field">
							<label for="wizard_related_limit"><?php esc_html_e( 'Number of Posts', 'community-cpt' ); ?></label>
							<input type="number" id="wizard_related_limit" value="8" min="1" max="12">
							<p class="description"><?php esc_html_e( 'Default 8 for 4x2 grid layout.', 'community-cpt' ); ?></p>
						</div>

						<div class="community-wizard-field">
							<label for="wizard_related_columns"><?php esc_html_e( 'Columns', 'community-cpt' ); ?></label>
							<select id="wizard_related_columns">
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4" selected>4</option>
							</select>
						</div>
					</div>

					<!-- Preview -->
					<div class="community-wizard-preview">
						<strong><?php esc_html_e( 'Preview:', 'community-cpt' ); ?></strong>
						<code id="wizard_preview">[community_grid]</code>
					</div>
				</div>
				<div class="community-wizard-footer">
					<button type="button" class="button community-wizard-cancel"><?php esc_html_e( 'Cancel', 'community-cpt' ); ?></button>
					<button type="button" class="button button-primary community-wizard-insert"><?php esc_html_e( 'Insert Shortcode', 'community-cpt' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}
}
