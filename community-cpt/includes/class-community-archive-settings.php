<?php
/**
 * Community Archive Settings Class.
 *
 * Handles the archive page settings.
 *
 * @package Community_CPT
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Community_Archive_Settings
 *
 * Adds an admin page for configuring the community archive page
 * including title, top content, and bottom content.
 */
class Community_Archive_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add submenu page under Communities menu.
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'edit.php?post_type=community',
			__( 'Archive Page Settings', 'community-cpt' ),
			__( 'Archive Page', 'community-cpt' ),
			'manage_options',
			'community-archive-settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		// Register individual options for archive page.
		register_setting( 'community_archive_settings_group', 'community_cpt_archive_title', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => __( 'Communities', 'community-cpt' ),
		) );

		register_setting( 'community_archive_settings_group', 'community_cpt_archive_top_content', array(
			'type'              => 'string',
			'sanitize_callback' => 'wp_kses_post',
			'default'           => '',
		) );

		register_setting( 'community_archive_settings_group', 'community_cpt_archive_bottom_content', array(
			'type'              => 'string',
			'sanitize_callback' => 'wp_kses_post',
			'default'           => '',
		) );

		register_setting( 'community_archive_settings_group', 'community_cpt_archive_columns', array(
			'type'              => 'integer',
			'sanitize_callback' => array( $this, 'sanitize_columns' ),
			'default'           => 3,
		) );

		register_setting( 'community_archive_settings_group', 'community_cpt_archive_show_search', array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => true,
		) );
	}

	/**
	 * Sanitize columns value.
	 *
	 * @param mixed $value Input value.
	 * @return int Sanitized value.
	 */
	public function sanitize_columns( $value ) {
		$value = absint( $value );
		return in_array( $value, array( 2, 3, 4 ), true ) ? $value : 3;
	}

	/**
	 * Render the settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Show save message.
		if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) {
			add_settings_error(
				'community_archive_messages',
				'community_archive_message',
				__( 'Settings saved.', 'community-cpt' ),
				'updated'
			);
		}

		$archive_title       = get_option( 'community_cpt_archive_title', __( 'Communities', 'community-cpt' ) );
		$archive_top         = get_option( 'community_cpt_archive_top_content', '' );
		$archive_bottom      = get_option( 'community_cpt_archive_bottom_content', '' );
		$archive_columns     = get_option( 'community_cpt_archive_columns', 3 );
		$archive_show_search = get_option( 'community_cpt_archive_show_search', true );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Archive Page Settings', 'community-cpt' ); ?></h1>

			<p class="description">
				<?php
				printf(
					/* translators: %s: Archive URL */
					esc_html__( 'Configure the community archive page displayed at: %s', 'community-cpt' ),
					'<a href="' . esc_url( get_post_type_archive_link( 'community' ) ) . '" target="_blank"><code>' . esc_html( get_post_type_archive_link( 'community' ) ) . '</code></a>'
				);
				?>
			</p>

			<?php settings_errors( 'community_archive_messages' ); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'community_archive_settings_group' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="community_cpt_archive_title"><?php esc_html_e( 'Page Title', 'community-cpt' ); ?></label>
						</th>
						<td>
							<input type="text"
								id="community_cpt_archive_title"
								name="community_cpt_archive_title"
								value="<?php echo esc_attr( $archive_title ); ?>"
								class="regular-text">
							<p class="description"><?php esc_html_e( 'The main heading displayed on the archive page.', 'community-cpt' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="community_cpt_archive_columns"><?php esc_html_e( 'Grid Columns', 'community-cpt' ); ?></label>
						</th>
						<td>
							<select id="community_cpt_archive_columns" name="community_cpt_archive_columns">
								<option value="2" <?php selected( $archive_columns, 2 ); ?>>2</option>
								<option value="3" <?php selected( $archive_columns, 3 ); ?>>3</option>
								<option value="4" <?php selected( $archive_columns, 4 ); ?>>4</option>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Show Search', 'community-cpt' ); ?></th>
						<td>
							<label>
								<input type="checkbox"
									name="community_cpt_archive_show_search"
									value="1"
									<?php checked( $archive_show_search ); ?>>
								<?php esc_html_e( 'Display search input on archive page', 'community-cpt' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="community_cpt_archive_top_content"><?php esc_html_e( 'Top Content', 'community-cpt' ); ?></label>
						</th>
						<td>
							<?php
							wp_editor(
								$archive_top,
								'community_cpt_archive_top_content',
								array(
									'textarea_name' => 'community_cpt_archive_top_content',
									'textarea_rows' => 8,
									'media_buttons' => true,
									'teeny'         => false,
									'quicktags'     => true,
								)
							);
							?>
							<p class="description"><?php esc_html_e( 'Content displayed above the community grid.', 'community-cpt' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="community_cpt_archive_bottom_content"><?php esc_html_e( 'Bottom Content', 'community-cpt' ); ?></label>
						</th>
						<td>
							<?php
							wp_editor(
								$archive_bottom,
								'community_cpt_archive_bottom_content',
								array(
									'textarea_name' => 'community_cpt_archive_bottom_content',
									'textarea_rows' => 8,
									'media_buttons' => true,
									'teeny'         => false,
									'quicktags'     => true,
								)
							);
							?>
							<p class="description"><?php esc_html_e( 'Content displayed below the community grid.', 'community-cpt' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
