<?php
/**
 * Grid Wrapper Template Partial.
 *
 * Displays the community grid container, search input, and pagination.
 *
 * @package Community_CPT
 *
 * @var WP_Query $query    The query object.
 * @var array    $atts     Shortcode attributes.
 * @var array    $settings Plugin settings.
 */

defined( 'ABSPATH' ) || exit;

$nonce = wp_create_nonce( 'community_grid_nonce' );
?>
<div class="community-grid-wrapper"
	data-parent-id="<?php echo esc_attr( $atts['parent_id'] ); ?>"
	data-per-page="<?php echo esc_attr( $atts['per_page'] ); ?>"
	data-columns="<?php echo esc_attr( $atts['columns'] ); ?>"
	data-style="<?php echo esc_attr( $atts['style'] ); ?>"
	data-orderby="<?php echo esc_attr( $atts['orderby'] ); ?>"
	data-order="<?php echo esc_attr( $atts['order'] ); ?>"
	data-nonce="<?php echo esc_attr( $nonce ); ?>">

	<?php if ( $atts['show_search'] ) : ?>
	<div class="community-grid-search">
		<div class="community-search-input-wrap">
			<svg class="community-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<circle cx="11" cy="11" r="8"/>
				<line x1="21" y1="21" x2="16.65" y2="16.65"/>
			</svg>
			<input type="text"
				class="community-search-input"
				placeholder="<?php esc_attr_e( 'Search for a community...', 'community-cpt' ); ?>"
				aria-label="<?php esc_attr_e( 'Search communities', 'community-cpt' ); ?>">
			<button class="community-search-clear" aria-label="<?php esc_attr_e( 'Clear search', 'community-cpt' ); ?>" style="display:none;">&times;</button>
		</div>
	</div>
	<?php endif; ?>

	<div class="community-grid community-grid-cols-<?php echo esc_attr( $atts['columns'] ); ?>">
		<?php if ( $query->have_posts() ) : ?>
			<?php while ( $query->have_posts() ) : $query->the_post(); ?>
				<?php
				include COMMUNITY_CPT_PATH . 'templates/partials/grid-card.php';
				?>
			<?php endwhile; ?>
		<?php else : ?>
			<?php
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
			?>
		<?php endif; ?>
	</div>

	<?php
	// Include pagination if applicable.
	if ( $atts['per_page'] > 0 && $query->max_num_pages > 1 ) {
		$current_page   = max( 1, get_query_var( 'paged', 1 ) );
		$max_num_pages  = $query->max_num_pages;
		include COMMUNITY_CPT_PATH . 'templates/partials/pagination.php';
	}
	?>

	<div class="community-grid-loading" style="display:none;">
		<div class="community-spinner"></div>
	</div>
</div>
