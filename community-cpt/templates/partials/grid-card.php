<?php
/**
 * Grid Card Template Partial.
 *
 * Displays a single community card in the grid.
 *
 * @package Community_CPT
 *
 * @var WP_Post $post     The current post object (via the_post()).
 * @var array   $atts     Shortcode attributes.
 * @var array   $settings Plugin settings.
 */

defined( 'ABSPATH' ) || exit;

$current_post  = get_post();
$excerpt       = Community_Shortcodes::get_card_excerpt( $current_post, $settings['excerpt_length'] );
$lazy_load     = $settings['lazy_load_images'];
$image_url     = Community_Shortcodes::get_card_image_url( $current_post );
$placeholder   = Community_Shortcodes::get_lazy_placeholder();
$title         = get_the_title();
$permalink     = get_permalink();

// Data attributes for client-side filtering.
$data_title   = strtolower( $title );
$data_excerpt = strtolower( wp_strip_all_tags( $excerpt ) );

// Start output buffering for filter.
ob_start();
?>
<article class="community-grid-card" data-title="<?php echo esc_attr( $data_title ); ?>" data-excerpt="<?php echo esc_attr( $data_excerpt ); ?>">
	<a href="<?php echo esc_url( $permalink ); ?>" class="community-card-link" aria-label="<?php echo esc_attr( sprintf( __( 'View %s', 'community-cpt' ), $title ) ); ?>">
		<div class="community-card-image-wrap">
			<?php if ( $lazy_load ) : ?>
				<img src="<?php echo esc_url( $placeholder ); ?>"
					data-src="<?php echo esc_url( $image_url ); ?>"
					alt="<?php echo esc_attr( $title ); ?>"
					class="community-card-image community-lazy"
					width="400"
					height="250"
					loading="lazy">
			<?php else : ?>
				<img src="<?php echo esc_url( $image_url ); ?>"
					alt="<?php echo esc_attr( $title ); ?>"
					class="community-card-image"
					width="400"
					height="250"
					loading="lazy">
			<?php endif; ?>
		</div>
		<div class="community-card-content">
			<h3 class="community-card-title"><?php echo esc_html( $title ); ?></h3>
			<?php if ( 'default' === $atts['style'] && ! empty( $excerpt ) ) : ?>
				<p class="community-card-excerpt"><?php echo esc_html( $excerpt ); ?></p>
			<?php endif; ?>
		</div>
	</a>
</article>
<?php
$html = ob_get_clean();

/**
 * Filter the grid card HTML.
 *
 * @param string  $html The card HTML.
 * @param WP_Post $post The post object.
 * @param array   $atts Shortcode attributes.
 */
$html = apply_filters( 'community_grid_card_html', $html, $current_post, $atts );

echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in template.
