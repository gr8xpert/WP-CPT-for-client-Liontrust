<?php
/**
 * Community Settings Class.
 *
 * Handles the plugin settings page.
 *
 * @package Community_CPT
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Community_Settings
 *
 * Registers and renders the settings page under Settings menu.
 */
class Community_Settings {

	/**
	 * Option name for settings.
	 *
	 * @var string
	 */
	private $option_name = 'community_cpt_settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_community_flush_permalinks', array( $this, 'ajax_flush_permalinks' ) );
		add_action( 'wp_ajax_community_clear_cache', array( $this, 'ajax_clear_cache' ) );
	}

	/**
	 * Add settings page under Communities menu.
	 */
	public function add_settings_page() {
		add_submenu_page(
			'edit.php?post_type=community',
			__( 'Community Settings', 'community-cpt' ),
			__( 'Settings', 'community-cpt' ),
			'manage_options',
			'community-cpt-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings, sections, and fields.
	 */
	public function register_settings() {
		register_setting(
			'community_cpt_settings_group',
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);

		// Section: Grid Display.
		add_settings_section(
			'community_grid_section',
			__( 'Grid Display', 'community-cpt' ),
			array( $this, 'render_grid_section_description' ),
			'community-cpt-settings'
		);

		add_settings_field(
			'pagination_mode',
			__( 'Pagination Mode', 'community-cpt' ),
			array( $this, 'render_pagination_mode_field' ),
			'community-cpt-settings',
			'community_grid_section'
		);

		add_settings_field(
			'per_page',
			__( 'Posts Per Page', 'community-cpt' ),
			array( $this, 'render_per_page_field' ),
			'community-cpt-settings',
			'community_grid_section'
		);

		add_settings_field(
			'default_columns',
			__( 'Default Grid Columns', 'community-cpt' ),
			array( $this, 'render_columns_field' ),
			'community-cpt-settings',
			'community_grid_section'
		);

		add_settings_field(
			'card_style',
			__( 'Card Style', 'community-cpt' ),
			array( $this, 'render_card_style_field' ),
			'community-cpt-settings',
			'community_grid_section'
		);

		add_settings_field(
			'excerpt_length',
			__( 'Card Excerpt Length', 'community-cpt' ),
			array( $this, 'render_excerpt_length_field' ),
			'community-cpt-settings',
			'community_grid_section'
		);

		// Section: Search.
		add_settings_section(
			'community_search_section',
			__( 'Search', 'community-cpt' ),
			null,
			'community-cpt-settings'
		);

		add_settings_field(
			'show_search',
			__( 'Show Search by Default', 'community-cpt' ),
			array( $this, 'render_show_search_field' ),
			'community-cpt-settings',
			'community_search_section'
		);

		// Section: Related Posts.
		add_settings_section(
			'community_related_section',
			__( 'Related Posts', 'community-cpt' ),
			null,
			'community-cpt-settings'
		);

		add_settings_field(
			'related_count',
			__( 'Related Posts Count', 'community-cpt' ),
			array( $this, 'render_related_count_field' ),
			'community-cpt-settings',
			'community_related_section'
		);

		// Section: Performance.
		add_settings_section(
			'community_performance_section',
			__( 'Performance', 'community-cpt' ),
			null,
			'community-cpt-settings'
		);

		add_settings_field(
			'lazy_load_images',
			__( 'Enable Lazy Loading Images', 'community-cpt' ),
			array( $this, 'render_lazy_load_field' ),
			'community-cpt-settings',
			'community_performance_section'
		);

		add_settings_field(
			'cache_duration',
			__( 'Cache Duration', 'community-cpt' ),
			array( $this, 'render_cache_duration_field' ),
			'community-cpt-settings',
			'community_performance_section'
		);

		// Section: Tools.
		add_settings_section(
			'community_tools_section',
			__( 'Tools', 'community-cpt' ),
			null,
			'community-cpt-settings'
		);

		add_settings_field(
			'tools_buttons',
			__( 'Maintenance', 'community-cpt' ),
			array( $this, 'render_tools_buttons' ),
			'community-cpt-settings',
			'community_tools_section'
		);
	}

	/**
	 * Sanitize settings on save.
	 *
	 * @param array $input Raw input values.
	 * @return array Sanitized values.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		$sanitized['pagination_mode'] = isset( $input['pagination_mode'] ) && in_array( $input['pagination_mode'], array( 'all', 'paginated' ), true )
			? $input['pagination_mode']
			: 'all';

		$sanitized['per_page'] = isset( $input['per_page'] )
			? max( 1, min( 100, absint( $input['per_page'] ) ) )
			: 20;

		$sanitized['default_columns'] = isset( $input['default_columns'] ) && in_array( absint( $input['default_columns'] ), array( 2, 3, 4 ), true )
			? absint( $input['default_columns'] )
			: 3;

		$sanitized['card_style'] = isset( $input['card_style'] ) && in_array( $input['card_style'], array( 'default', 'compact' ), true )
			? $input['card_style']
			: 'default';

		$sanitized['excerpt_length'] = isset( $input['excerpt_length'] )
			? max( 50, min( 300, absint( $input['excerpt_length'] ) ) )
			: 120;

		$sanitized['show_search'] = ! empty( $input['show_search'] );

		$sanitized['related_count'] = isset( $input['related_count'] )
			? max( 1, min( 12, absint( $input['related_count'] ) ) )
			: 8;

		$sanitized['lazy_load_images'] = ! empty( $input['lazy_load_images'] );

		$cache_options = array( 0, 3600, 21600, 43200, 86400 );
		$sanitized['cache_duration'] = isset( $input['cache_duration'] ) && in_array( absint( $input['cache_duration'] ), $cache_options, true )
			? absint( $input['cache_duration'] )
			: 3600;

		return $sanitized;
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap community-settings-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'community_cpt_settings_group' );
				do_settings_sections( 'community-cpt-settings' );
				submit_button();
				?>
			</form>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Show/hide per_page based on pagination mode.
			function togglePerPage() {
				var mode = $('input[name="community_cpt_settings[pagination_mode]"]:checked').val();
				$('#per_page_row').toggle(mode === 'paginated');
			}
			togglePerPage();
			$('input[name="community_cpt_settings[pagination_mode]"]').on('change', togglePerPage);

			// Flush permalinks button.
			$('#community-flush-permalinks').on('click', function(e) {
				e.preventDefault();
				var $btn = $(this);
				$btn.prop('disabled', true).text('<?php esc_html_e( 'Flushing...', 'community-cpt' ); ?>');

				$.post(ajaxurl, {
					action: 'community_flush_permalinks',
					nonce: '<?php echo esc_js( wp_create_nonce( 'community_flush_nonce' ) ); ?>'
				}, function(response) {
					if (response.success) {
						$btn.text('<?php esc_html_e( 'Done!', 'community-cpt' ); ?>');
					} else {
						$btn.text('<?php esc_html_e( 'Error', 'community-cpt' ); ?>');
					}
					setTimeout(function() {
						$btn.prop('disabled', false).text('<?php esc_html_e( 'Flush Permalinks', 'community-cpt' ); ?>');
					}, 2000);
				});
			});

			// Clear cache button.
			$('#community-clear-cache').on('click', function(e) {
				e.preventDefault();
				var $btn = $(this);
				$btn.prop('disabled', true).text('<?php esc_html_e( 'Clearing...', 'community-cpt' ); ?>');

				$.post(ajaxurl, {
					action: 'community_clear_cache',
					nonce: '<?php echo esc_js( wp_create_nonce( 'community_cache_nonce' ) ); ?>'
				}, function(response) {
					if (response.success) {
						$btn.text('<?php esc_html_e( 'Done!', 'community-cpt' ); ?>');
					} else {
						$btn.text('<?php esc_html_e( 'Error', 'community-cpt' ); ?>');
					}
					setTimeout(function() {
						$btn.prop('disabled', false).text('<?php esc_html_e( 'Clear All Cache', 'community-cpt' ); ?>');
					}, 2000);
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Render grid section description.
	 */
	public function render_grid_section_description() {
		echo '<p>' . esc_html__( 'Configure how the community grid displays posts.', 'community-cpt' ) . '</p>';
	}

	/**
	 * Render pagination mode field.
	 */
	public function render_pagination_mode_field() {
		$value = community_cpt_get_setting( 'pagination_mode', 'all' );
		?>
		<fieldset>
			<label>
				<input type="radio" name="community_cpt_settings[pagination_mode]" value="all" <?php checked( $value, 'all' ); ?>>
				<?php esc_html_e( 'Show all (no pagination)', 'community-cpt' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="community_cpt_settings[pagination_mode]" value="paginated" <?php checked( $value, 'paginated' ); ?>>
				<?php esc_html_e( 'Paginated', 'community-cpt' ); ?>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Render per page field.
	 */
	public function render_per_page_field() {
		$value = community_cpt_get_setting( 'per_page', 20 );
		?>
		<tr id="per_page_row">
			<td colspan="2" style="padding-left: 0;">
				<input type="number" name="community_cpt_settings[per_page]" value="<?php echo esc_attr( $value ); ?>" min="1" max="100" class="small-text">
				<p class="description"><?php esc_html_e( 'Number of posts to show per page (1-100).', 'community-cpt' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render columns field.
	 */
	public function render_columns_field() {
		$value = community_cpt_get_setting( 'default_columns', 3 );
		?>
		<select name="community_cpt_settings[default_columns]">
			<option value="2" <?php selected( $value, 2 ); ?>>2</option>
			<option value="3" <?php selected( $value, 3 ); ?>>3</option>
			<option value="4" <?php selected( $value, 4 ); ?>>4</option>
		</select>
		<?php
	}

	/**
	 * Render card style field.
	 */
	public function render_card_style_field() {
		$value = community_cpt_get_setting( 'card_style', 'default' );
		?>
		<select name="community_cpt_settings[card_style]">
			<option value="default" <?php selected( $value, 'default' ); ?>><?php esc_html_e( 'Default (image + title + excerpt)', 'community-cpt' ); ?></option>
			<option value="compact" <?php selected( $value, 'compact' ); ?>><?php esc_html_e( 'Compact (image + title only)', 'community-cpt' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Render excerpt length field.
	 */
	public function render_excerpt_length_field() {
		$value = community_cpt_get_setting( 'excerpt_length', 120 );
		?>
		<input type="number" name="community_cpt_settings[excerpt_length]" value="<?php echo esc_attr( $value ); ?>" min="50" max="300" class="small-text">
		<p class="description"><?php esc_html_e( 'Maximum characters for card excerpts (50-300).', 'community-cpt' ); ?></p>
		<?php
	}

	/**
	 * Render show search field.
	 */
	public function render_show_search_field() {
		$value = community_cpt_get_setting( 'show_search', true );
		?>
		<label>
			<input type="checkbox" name="community_cpt_settings[show_search]" value="1" <?php checked( $value ); ?>>
			<?php esc_html_e( 'Display search input above the grid by default', 'community-cpt' ); ?>
		</label>
		<?php
	}

	/**
	 * Render related count field.
	 */
	public function render_related_count_field() {
		$value = community_cpt_get_setting( 'related_count', 8 );
		?>
		<input type="number" name="community_cpt_settings[related_count]" value="<?php echo esc_attr( $value ); ?>" min="1" max="12" class="small-text">
		<p class="description"><?php esc_html_e( 'Number of related communities to display (1-12). Default 8 for 4x2 grid.', 'community-cpt' ); ?></p>
		<?php
	}

	/**
	 * Render lazy load field.
	 */
	public function render_lazy_load_field() {
		$value = community_cpt_get_setting( 'lazy_load_images', true );
		?>
		<label>
			<input type="checkbox" name="community_cpt_settings[lazy_load_images]" value="1" <?php checked( $value ); ?>>
			<?php esc_html_e( 'Use Intersection Observer to lazy load grid images', 'community-cpt' ); ?>
		</label>
		<?php
	}

	/**
	 * Render cache duration field.
	 */
	public function render_cache_duration_field() {
		$value = community_cpt_get_setting( 'cache_duration', 3600 );
		?>
		<select name="community_cpt_settings[cache_duration]">
			<option value="3600" <?php selected( $value, 3600 ); ?>><?php esc_html_e( '1 hour', 'community-cpt' ); ?></option>
			<option value="21600" <?php selected( $value, 21600 ); ?>><?php esc_html_e( '6 hours', 'community-cpt' ); ?></option>
			<option value="43200" <?php selected( $value, 43200 ); ?>><?php esc_html_e( '12 hours', 'community-cpt' ); ?></option>
			<option value="86400" <?php selected( $value, 86400 ); ?>><?php esc_html_e( '24 hours', 'community-cpt' ); ?></option>
			<option value="0" <?php selected( $value, 0 ); ?>><?php esc_html_e( 'No cache', 'community-cpt' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Render tools buttons.
	 */
	public function render_tools_buttons() {
		?>
		<button type="button" id="community-flush-permalinks" class="button">
			<?php esc_html_e( 'Flush Permalinks', 'community-cpt' ); ?>
		</button>
		<button type="button" id="community-clear-cache" class="button">
			<?php esc_html_e( 'Clear All Cache', 'community-cpt' ); ?>
		</button>
		<p class="description"><?php esc_html_e( 'Use these buttons to refresh permalinks or clear cached grid data.', 'community-cpt' ); ?></p>
		<?php
	}

	/**
	 * Ajax handler for flushing permalinks.
	 */
	public function ajax_flush_permalinks() {
		if ( ! isset( $_POST['nonce'] ) ||
			 ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'community_flush_nonce' ) ) {
			wp_send_json_error();
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		flush_rewrite_rules();
		wp_send_json_success();
	}

	/**
	 * Ajax handler for clearing cache.
	 */
	public function ajax_clear_cache() {
		if ( ! isset( $_POST['nonce'] ) ||
			 ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'community_cache_nonce' ) ) {
			wp_send_json_error();
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		global $wpdb;

		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_community_grid_%'
			OR option_name LIKE '_transient_timeout_community_grid_%'
			OR option_name LIKE '_transient_community_has_children_%'
			OR option_name LIKE '_transient_timeout_community_has_children_%'"
		);

		/**
		 * Fires after community cache is cleared.
		 */
		do_action( 'community_cache_cleared' );

		wp_send_json_success();
	}
}
