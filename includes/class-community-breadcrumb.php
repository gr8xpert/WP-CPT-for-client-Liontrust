<?php
/**
 * Community Breadcrumb Class.
 *
 * Handles the [community_breadcrumb] shortcode.
 *
 * @package Community_CPT
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Community_Breadcrumb
 *
 * Registers and renders the breadcrumb shortcode with
 * Schema.org BreadcrumbList markup.
 */
class Community_Breadcrumb {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'community_breadcrumb', array( $this, 'render_breadcrumb' ) );
	}

	/**
	 * Render the breadcrumb shortcode.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string Breadcrumb HTML output.
	 */
	public function render_breadcrumb( $atts = array() ) {
		if ( ! is_singular( 'community' ) ) {
			return '';
		}

		$post_id = get_the_ID();
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return '';
		}

		// Build ancestors array.
		$ancestors = $this->get_ancestors( $post );

		// Build items array.
		$items = array();

		// Home item.
		$items[] = array(
			'title' => __( 'Home', 'community-cpt' ),
			'url'   => home_url( '/' ),
		);

		// Ancestor items (reversed to get root-first order).
		foreach ( array_reverse( $ancestors ) as $ancestor ) {
			$items[] = array(
				'title' => get_the_title( $ancestor ),
				'url'   => get_permalink( $ancestor ),
			);
		}

		// Current item (no link).
		$items[] = array(
			'title'   => get_the_title( $post_id ),
			'url'     => '',
			'current' => true,
		);

		/**
		 * Filter the breadcrumb items array.
		 *
		 * @param array $items   Breadcrumb items.
		 * @param int   $post_id Current post ID.
		 */
		$items = apply_filters( 'community_breadcrumb_items', $items, $post_id );

		/**
		 * Filter the breadcrumb separator.
		 *
		 * @param string $separator The separator HTML.
		 */
		$separator = apply_filters( 'community_breadcrumb_separator', ' &rsaquo; ' );

		// Render output.
		ob_start();
		$this->render_markup( $items, $separator );
		return ob_get_clean();
	}

	/**
	 * Get all ancestors of a post.
	 *
	 * @param WP_Post $post The post object.
	 * @return array Array of ancestor post IDs (immediate parent first).
	 */
	private function get_ancestors( $post ) {
		$ancestors = array();
		$parent_id = $post->post_parent;

		while ( $parent_id ) {
			$ancestors[] = $parent_id;
			$parent      = get_post( $parent_id );
			$parent_id   = $parent ? $parent->post_parent : 0;
		}

		return $ancestors;
	}

	/**
	 * Render the breadcrumb HTML markup.
	 *
	 * @param array  $items     Breadcrumb items.
	 * @param string $separator Separator HTML.
	 */
	private function render_markup( $items, $separator ) {
		?>
		<nav class="community-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'community-cpt' ); ?>">
			<ol itemscope itemtype="https://schema.org/BreadcrumbList">
				<?php
				$position = 0;
				$total    = count( $items );

				foreach ( $items as $index => $item ) :
					$position++;
					$is_last    = ( $index === $total - 1 );
					$is_current = ! empty( $item['current'] );
					?>
					<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
						<?php if ( ! empty( $item['url'] ) ) : ?>
							<a itemprop="item" href="<?php echo esc_url( $item['url'] ); ?>">
								<span itemprop="name"><?php echo esc_html( $item['title'] ); ?></span>
							</a>
						<?php else : ?>
							<span itemprop="name" <?php echo $is_current ? 'aria-current="page"' : ''; ?>>
								<?php echo esc_html( $item['title'] ); ?>
							</span>
						<?php endif; ?>
						<meta itemprop="position" content="<?php echo esc_attr( $position ); ?>">
					</li>
					<?php if ( ! $is_last ) : ?>
						<li class="community-breadcrumb-separator" aria-hidden="true">
							<?php echo wp_kses_post( $separator ); ?>
						</li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ol>
		</nav>
		<?php
	}
}
