<?php
/**
 * Pagination Template Partial.
 *
 * Displays pagination buttons for the community grid.
 *
 * @package Community_CPT
 *
 * @var int $current_page  Current page number.
 * @var int $max_num_pages Total number of pages.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Generate smart pagination array.
 *
 * Shows first 2 pages, last 2 pages, and pages around current.
 *
 * @param int $current Current page.
 * @param int $total   Total pages.
 * @return array Pagination items (numbers or 'ellipsis').
 */
function community_get_pagination_items( $current, $total ) {
	$items = array();

	if ( $total <= 7 ) {
		// Show all pages.
		for ( $i = 1; $i <= $total; $i++ ) {
			$items[] = $i;
		}
	} else {
		// Smart pagination with ellipsis.
		$items[] = 1;

		if ( $current > 4 ) {
			$items[] = 'ellipsis';
		}

		// Pages around current.
		$start = max( 2, $current - 1 );
		$end   = min( $total - 1, $current + 1 );

		// Adjust if near start.
		if ( $current <= 4 ) {
			$start = 2;
			$end   = 5;
		}

		// Adjust if near end.
		if ( $current >= $total - 3 ) {
			$start = $total - 4;
			$end   = $total - 1;
		}

		for ( $i = $start; $i <= $end; $i++ ) {
			if ( $i > 1 && $i < $total ) {
				$items[] = $i;
			}
		}

		if ( $current < $total - 3 ) {
			$items[] = 'ellipsis';
		}

		$items[] = $total;
	}

	return array_unique( $items );
}

$pagination_items = community_get_pagination_items( $current_page, $max_num_pages );
$prev_page        = max( 1, $current_page - 1 );
$next_page        = min( $max_num_pages, $current_page + 1 );
?>
<nav class="community-pagination"
	aria-label="<?php esc_attr_e( 'Community pagination', 'community-cpt' ); ?>"
	data-total-pages="<?php echo esc_attr( $max_num_pages ); ?>"
	data-current-page="<?php echo esc_attr( $current_page ); ?>">

	<button class="community-page-btn community-page-prev"
		data-page="<?php echo esc_attr( $prev_page ); ?>"
		aria-label="<?php esc_attr_e( 'Previous page', 'community-cpt' ); ?>"
		<?php disabled( $current_page, 1 ); ?>>
		&lsaquo; <?php esc_html_e( 'Previous', 'community-cpt' ); ?>
	</button>

	<?php foreach ( $pagination_items as $item ) : ?>
		<?php if ( 'ellipsis' === $item ) : ?>
			<span class="community-page-ellipsis">&hellip;</span>
		<?php else : ?>
			<button class="community-page-btn community-page-num <?php echo $item === $current_page ? 'active' : ''; ?>"
				data-page="<?php echo esc_attr( $item ); ?>"
				<?php if ( $item === $current_page ) : ?>aria-current="page"<?php endif; ?>>
				<?php echo esc_html( $item ); ?>
			</button>
		<?php endif; ?>
	<?php endforeach; ?>

	<button class="community-page-btn community-page-next"
		data-page="<?php echo esc_attr( $next_page ); ?>"
		aria-label="<?php esc_attr_e( 'Next page', 'community-cpt' ); ?>"
		<?php disabled( $current_page, $max_num_pages ); ?>>
		<?php esc_html_e( 'Next', 'community-cpt' ); ?> &rsaquo;
	</button>
</nav>
