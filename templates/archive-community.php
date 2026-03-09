<?php
/**
 * Archive Template for Community CPT.
 *
 * Displays the community archive page with top-level communities grid.
 *
 * @package Community_CPT
 */

defined( 'ABSPATH' ) || exit;

get_header();

// Get archive settings.
$archive_title      = get_option( 'community_cpt_archive_title', __( 'Communities', 'community-cpt' ) );
$archive_top        = get_option( 'community_cpt_archive_top_content', '' );
$archive_bottom     = get_option( 'community_cpt_archive_bottom_content', '' );
$archive_columns    = get_option( 'community_cpt_archive_columns', 3 );
$archive_show_search = get_option( 'community_cpt_archive_show_search', true );
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main community-archive-main">

		<header class="community-archive-header">
			<h1 class="community-archive-title"><?php echo esc_html( $archive_title ); ?></h1>
		</header>

		<?php if ( ! empty( $archive_top ) ) : ?>
			<div class="community-archive-top-content">
				<?php echo wp_kses_post( $archive_top ); ?>
			</div>
		<?php endif; ?>

		<div class="community-archive-grid-wrapper">
			<?php
			// Display grid of top-level communities (parent = 0).
			echo do_shortcode( sprintf(
				'[community_grid parent_id="0" columns="%d" show_search="%s"]',
				absint( $archive_columns ),
				$archive_show_search ? 'true' : 'false'
			) );
			?>
		</div>

		<?php if ( ! empty( $archive_bottom ) ) : ?>
			<div class="community-archive-bottom-content">
				<?php echo wp_kses_post( $archive_bottom ); ?>
			</div>
		<?php endif; ?>

	</main>
</div>

<?php
get_sidebar();
get_footer();
