<?php
/**
 * Community Import/Export Class.
 *
 * Handles CSV import and export of community posts.
 *
 * @package Community_CPT
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Community_Import_Export
 *
 * Adds an import/export page for bulk community management.
 */
class Community_Import_Export {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
		add_action( 'admin_init', array( $this, 'handle_export' ) );
		add_action( 'admin_init', array( $this, 'handle_import' ) );
	}

	/**
	 * Add submenu page under Communities menu.
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'edit.php?post_type=community',
			__( 'Import / Export Communities', 'community-cpt' ),
			__( 'Import / Export', 'community-cpt' ),
			'edit_posts',
			'community-import-export',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the import/export page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$import_results = get_transient( 'community_import_results' );
		if ( $import_results ) {
			delete_transient( 'community_import_results' );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import / Export Communities', 'community-cpt' ); ?></h1>

			<?php if ( $import_results ) : ?>
				<div class="community-import-results">
					<h2><?php esc_html_e( 'Import Results', 'community-cpt' ); ?></h2>
					<p>
						<span class="created"><?php echo esc_html( sprintf( __( 'Created: %d', 'community-cpt' ), $import_results['created'] ) ); ?></span> |
						<span class="updated"><?php echo esc_html( sprintf( __( 'Updated: %d', 'community-cpt' ), $import_results['updated'] ) ); ?></span> |
						<span class="error"><?php echo esc_html( sprintf( __( 'Errors: %d', 'community-cpt' ), $import_results['errors'] ) ); ?></span>
					</p>

					<?php if ( ! empty( $import_results['messages'] ) ) : ?>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Row', 'community-cpt' ); ?></th>
									<th><?php esc_html_e( 'Title', 'community-cpt' ); ?></th>
									<th><?php esc_html_e( 'Action', 'community-cpt' ); ?></th>
									<th><?php esc_html_e( 'Message', 'community-cpt' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $import_results['messages'] as $msg ) : ?>
									<tr class="<?php echo esc_attr( $msg['type'] ); ?>">
										<td><?php echo absint( $msg['row'] ); ?></td>
										<td><?php echo esc_html( $msg['title'] ); ?></td>
										<td><?php echo esc_html( $msg['action'] ); ?></td>
										<td><?php echo esc_html( $msg['message'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="community-import-export-sections">
				<!-- Export Section -->
				<div class="community-settings-section">
					<h2><?php esc_html_e( 'Export Communities', 'community-cpt' ); ?></h2>
					<p><?php esc_html_e( 'Download a CSV file containing all community posts.', 'community-cpt' ); ?></p>
					<form method="post">
						<?php wp_nonce_field( 'community_export_nonce', 'community_export_nonce' ); ?>
						<input type="hidden" name="community_action" value="export">
						<?php submit_button( __( 'Export to CSV', 'community-cpt' ), 'primary', 'submit', false ); ?>
					</form>
				</div>

				<!-- Import Section -->
				<div class="community-settings-section" style="margin-top: 30px;">
					<h2><?php esc_html_e( 'Import Communities', 'community-cpt' ); ?></h2>
					<p><?php esc_html_e( 'Upload a CSV file to import or update community posts.', 'community-cpt' ); ?></p>
					<p class="description">
						<?php esc_html_e( 'CSV must contain at least "title" and "slug" columns. Use the export file as a template.', 'community-cpt' ); ?>
					</p>
					<form method="post" enctype="multipart/form-data">
						<?php wp_nonce_field( 'community_import_nonce', 'community_import_nonce' ); ?>
						<input type="hidden" name="community_action" value="import">
						<p>
							<input type="file" name="community_csv" accept=".csv" required>
						</p>
						<?php submit_button( __( 'Import from CSV', 'community-cpt' ), 'primary', 'submit', false ); ?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle CSV export.
	 */
	public function handle_export() {
		if ( ! isset( $_POST['community_action'] ) || 'export' !== $_POST['community_action'] ) {
			return;
		}

		if ( ! isset( $_POST['community_export_nonce'] ) ||
			 ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['community_export_nonce'] ) ), 'community_export_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Query all community posts.
		$query = new WP_Query( array(
			'post_type'      => 'community',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );

		// Set headers for CSV download.
		$filename = 'communities-export-' . gmdate( 'Y-m-d' ) . '.csv';
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Open output stream.
		$output = fopen( 'php://output', 'w' );

		// Write UTF-8 BOM for Excel compatibility.
		fwrite( $output, "\xEF\xBB\xBF" );

		// Write header row.
		$headers = array(
			'id',
			'title',
			'slug',
			'parent_slug',
			'status',
			'menu_order',
			'excerpt',
			'grid_excerpt',
			'related_title',
			'related_post_ids',
			'featured_image_url',
		);
		fputcsv( $output, $headers );

		// Write data rows.
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post = get_post();

				$parent_slug = '';
				if ( $post->post_parent ) {
					$parent = get_post( $post->post_parent );
					if ( $parent ) {
						$parent_slug = $parent->post_name;
					}
				}

				$related_posts = get_post_meta( $post->ID, '_community_related_posts', true );
				$related_ids   = is_array( $related_posts ) ? implode( '|', $related_posts ) : '';

				$featured_image_url = get_the_post_thumbnail_url( $post->ID, 'full' );

				$row = array(
					$post->ID,
					$post->post_title,
					$post->post_name,
					$parent_slug,
					$post->post_status,
					$post->menu_order,
					$post->post_excerpt,
					get_post_meta( $post->ID, '_community_grid_excerpt', true ),
					get_post_meta( $post->ID, '_community_related_title', true ),
					$related_ids,
					$featured_image_url ? $featured_image_url : '',
				);

				fputcsv( $output, $row );
			}
		}

		wp_reset_postdata();
		fclose( $output );
		exit;
	}

	/**
	 * Handle CSV import.
	 */
	public function handle_import() {
		if ( ! isset( $_POST['community_action'] ) || 'import' !== $_POST['community_action'] ) {
			return;
		}

		if ( ! isset( $_POST['community_import_nonce'] ) ||
			 ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['community_import_nonce'] ) ), 'community_import_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Validate file upload.
		if ( ! isset( $_FILES['community_csv'] ) || $_FILES['community_csv']['error'] !== UPLOAD_ERR_OK ) {
			$this->set_import_error( __( 'File upload failed.', 'community-cpt' ) );
			return;
		}

		$file = $_FILES['community_csv'];

		// Check file extension.
		$file_type = wp_check_filetype( $file['name'] );
		if ( 'csv' !== $file_type['ext'] ) {
			$this->set_import_error( __( 'Invalid file type. Please upload a CSV file.', 'community-cpt' ) );
			return;
		}

		// Additional MIME type validation.
		$allowed_mimes = array( 'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel' );
		$finfo_mime    = '';

		if ( function_exists( 'finfo_open' ) ) {
			$finfo      = finfo_open( FILEINFO_MIME_TYPE );
			$finfo_mime = finfo_file( $finfo, $file['tmp_name'] );
			finfo_close( $finfo );
		} elseif ( function_exists( 'mime_content_type' ) ) {
			$finfo_mime = mime_content_type( $file['tmp_name'] );
		}

		if ( ! empty( $finfo_mime ) && ! in_array( $finfo_mime, $allowed_mimes, true ) ) {
			$this->set_import_error( __( 'Invalid file content. The file does not appear to be a valid CSV.', 'community-cpt' ) );
			return;
		}

		// Check file size (max 5MB).
		if ( $file['size'] > 5 * 1024 * 1024 ) {
			$this->set_import_error( __( 'File too large. Maximum size is 5MB.', 'community-cpt' ) );
			return;
		}

		// Parse CSV.
		$handle = fopen( $file['tmp_name'], 'r' );
		if ( ! $handle ) {
			$this->set_import_error( __( 'Could not read CSV file.', 'community-cpt' ) );
			return;
		}

		// Skip BOM if present.
		$bom = fread( $handle, 3 );
		if ( "\xEF\xBB\xBF" !== $bom ) {
			rewind( $handle );
		}

		// Read and validate header row.
		$headers = fgetcsv( $handle );
		if ( ! $headers || ! in_array( 'title', $headers, true ) || ! in_array( 'slug', $headers, true ) ) {
			fclose( $handle );
			$this->set_import_error( __( 'Invalid CSV format. Must contain "title" and "slug" columns.', 'community-cpt' ) );
			return;
		}

		// Create header index map.
		$header_map = array_flip( $headers );

		// Initialize counters.
		$created  = 0;
		$updated  = 0;
		$errors   = 0;
		$messages = array();
		$row_num  = 1;

		// Process each row.
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$row_num++;

			// Skip empty rows.
			if ( empty( array_filter( $row ) ) ) {
				continue;
			}

			$result = $this->process_import_row( $row, $header_map, $row_num );
			$messages[] = $result;

			switch ( $result['type'] ) {
				case 'created':
					$created++;
					break;
				case 'updated':
					$updated++;
					break;
				default:
					$errors++;
			}
		}

		fclose( $handle );

		// Flush rewrite rules and clear cache.
		flush_rewrite_rules();

		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_community_grid_%'
			OR option_name LIKE '_transient_timeout_community_grid_%'
			OR option_name LIKE '_transient_community_has_children_%'
			OR option_name LIKE '_transient_timeout_community_has_children_%'"
		);

		// Store results for display.
		set_transient( 'community_import_results', array(
			'created'  => $created,
			'updated'  => $updated,
			'errors'   => $errors,
			'messages' => $messages,
		), 60 );

		// Redirect to import page to show results.
		wp_safe_redirect( admin_url( 'edit.php?post_type=community&page=community-import-export' ) );
		exit;
	}

	/**
	 * Process a single import row.
	 *
	 * @param array $row        CSV row data.
	 * @param array $header_map Header index map.
	 * @param int   $row_num    Row number.
	 * @return array Result array.
	 */
	private function process_import_row( $row, $header_map, $row_num ) {
		$get_value = function( $key ) use ( $row, $header_map ) {
			return isset( $header_map[ $key ] ) && isset( $row[ $header_map[ $key ] ] )
				? trim( $row[ $header_map[ $key ] ] )
				: '';
		};

		$title = sanitize_text_field( $get_value( 'title' ) );
		$slug  = sanitize_title( $get_value( 'slug' ) );

		if ( empty( $title ) || empty( $slug ) ) {
			return array(
				'row'     => $row_num,
				'title'   => $title ?: __( '(empty)', 'community-cpt' ),
				'action'  => __( 'Skipped', 'community-cpt' ),
				'message' => __( 'Missing title or slug.', 'community-cpt' ),
				'type'    => 'error',
			);
		}

		// Check if update or create.
		$existing_id = absint( $get_value( 'id' ) );
		$is_update   = false;

		if ( $existing_id ) {
			$existing = get_post( $existing_id );
			if ( $existing && 'community' === $existing->post_type ) {
				$is_update = true;
			}
		}

		// Resolve parent.
		$parent_id   = 0;
		$parent_slug = sanitize_title( $get_value( 'parent_slug' ) );
		$warning     = '';

		if ( ! empty( $parent_slug ) ) {
			$parent = get_page_by_path( $parent_slug, OBJECT, 'community' );
			if ( $parent ) {
				$parent_id = $parent->ID;
			} else {
				$warning = sprintf( __( 'Parent "%s" not found.', 'community-cpt' ), $parent_slug );
			}
		}

		// Prepare post data.
		$post_data = array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_type'    => 'community',
			'post_status'  => sanitize_text_field( $get_value( 'status' ) ) ?: 'draft',
			'post_parent'  => $parent_id,
			'menu_order'   => absint( $get_value( 'menu_order' ) ),
			'post_excerpt' => sanitize_textarea_field( $get_value( 'excerpt' ) ),
		);

		// Validate status.
		$valid_statuses = array( 'publish', 'draft', 'pending', 'private' );
		if ( ! in_array( $post_data['post_status'], $valid_statuses, true ) ) {
			$post_data['post_status'] = 'draft';
		}

		if ( $is_update ) {
			$post_data['ID'] = $existing_id;
			$post_id = wp_update_post( $post_data, true );
		} else {
			$post_id = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $post_id ) ) {
			return array(
				'row'     => $row_num,
				'title'   => $title,
				'action'  => __( 'Error', 'community-cpt' ),
				'message' => $post_id->get_error_message(),
				'type'    => 'error',
			);
		}

		// Update meta fields.
		$grid_excerpt = sanitize_text_field( $get_value( 'grid_excerpt' ) );
		if ( ! empty( $grid_excerpt ) ) {
			update_post_meta( $post_id, '_community_grid_excerpt', $grid_excerpt );
		}

		$related_title = sanitize_text_field( $get_value( 'related_title' ) );
		if ( ! empty( $related_title ) ) {
			update_post_meta( $post_id, '_community_related_title', $related_title );
		}

		$related_ids_str = $get_value( 'related_post_ids' );
		if ( ! empty( $related_ids_str ) ) {
			$related_ids = array_map( 'absint', explode( '|', $related_ids_str ) );
			$related_ids = array_filter( $related_ids );
			if ( ! empty( $related_ids ) ) {
				update_post_meta( $post_id, '_community_related_posts', $related_ids );
			}
		}

		// Handle featured image.
		$image_url = esc_url_raw( $get_value( 'featured_image_url' ) );
		if ( ! empty( $image_url ) && ! get_post_thumbnail_id( $post_id ) ) {
			$this->sideload_featured_image( $post_id, $image_url, $title );
		}

		/**
		 * Fires after a row is imported.
		 *
		 * @param array $row_data Row data.
		 * @param int   $post_id  Post ID.
		 * @param string $action  'created' or 'updated'.
		 */
		do_action( 'community_import_row', $row, $post_id, $is_update ? 'updated' : 'created' );

		$message = $is_update ? __( 'Updated successfully.', 'community-cpt' ) : __( 'Created successfully.', 'community-cpt' );
		if ( $warning ) {
			$message .= ' ' . $warning;
		}

		return array(
			'row'     => $row_num,
			'title'   => $title,
			'action'  => $is_update ? __( 'Updated', 'community-cpt' ) : __( 'Created', 'community-cpt' ),
			'message' => $message,
			'type'    => $warning ? 'warning' : ( $is_update ? 'updated' : 'created' ),
		);
	}

	/**
	 * Sideload and set featured image from URL.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $url     Image URL.
	 * @param string $title   Post title for image description.
	 */
	private function sideload_featured_image( $post_id, $url, $title ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		try {
			$image_id = media_sideload_image( $url, $post_id, $title, 'id' );
			if ( ! is_wp_error( $image_id ) ) {
				set_post_thumbnail( $post_id, $image_id );
			}
		} catch ( Exception $e ) {
			// Silently fail - image download issues should not halt import.
		}
	}

	/**
	 * Set import error message.
	 *
	 * @param string $message Error message.
	 */
	private function set_import_error( $message ) {
		set_transient( 'community_import_results', array(
			'created'  => 0,
			'updated'  => 0,
			'errors'   => 1,
			'messages' => array(
				array(
					'row'     => 0,
					'title'   => '',
					'action'  => __( 'Error', 'community-cpt' ),
					'message' => $message,
					'type'    => 'error',
				),
			),
		), 60 );
	}
}
