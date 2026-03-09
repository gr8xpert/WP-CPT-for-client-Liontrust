<?php
/**
 * Related Grid Template Partial.
 *
 * Displays the related communities section.
 *
 * @package Community_CPT
 *
 * @var string $title         Related section title.
 * @var array  $related_posts Array of WP_Post objects.
 * @var array  $atts          Shortcode attributes.
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="community-related-wrapper">
	<h2 class="community-related-title"><?php echo esc_html( $title ); ?></h2>
	<div class="community-related-grid community-grid-cols-<?php echo esc_attr( $atts['columns'] ); ?>">
		<?php foreach ( $related_posts as $related_post ) : ?>
			<?php
			$image_url = get_the_post_thumbnail_url( $related_post->ID, 'medium_large' );
			if ( ! $image_url ) {
				$image_url = Community_Shortcodes::get_placeholder_image();
			}
			$card_title = get_the_title( $related_post );
			$permalink  = get_permalink( $related_post );

			// Start output buffering for filter.
			ob_start();
			?>
			<article class="community-related-card">
				<a href="<?php echo esc_url( $permalink ); ?>" class="community-related-card-link">
					<div class="community-related-card-image-wrap">
						<img src="<?php echo esc_url( $image_url ); ?>"
							alt="<?php echo esc_attr( $card_title ); ?>"
							loading="lazy"
							width="400"
							height="250">
					</div>
					<div class="community-related-card-content">
						<h3 class="community-related-card-title"><?php echo esc_html( $card_title ); ?></h3>
						<span class="community-related-card-btn"><?php esc_html_e( 'Explore', 'community-cpt' ); ?></span>
					</div>
				</a>
			</article>
			<?php
			$card_html = ob_get_clean();

			/**
			 * Filter the related card HTML.
			 *
			 * @param string  $card_html The card HTML.
			 * @param WP_Post $post      The related post object.
			 */
			$card_html = apply_filters( 'community_related_card_html', $card_html, $related_post );

			echo $card_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in template.
			?>
		<?php endforeach; ?>
	</div>
</section>
