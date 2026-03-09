<?php
/**
 * Community Admin Columns Class.
 *
 * Handles custom columns in the community post list table.
 *
 * @package Community_CPT
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Community_Admin_Columns
 *
 * Adds custom columns to the community posts admin list table
 * including thumbnail, parent, level, children count, and menu order.
 */
class Community_Admin_Columns {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'manage_community_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_community_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-community_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'handle_sortable_columns' ) );
	}

	/**
	 * Define custom columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_columns( $columns ) {
		$new_columns = array();

		// Add checkbox and thumbnail at start.
		if ( isset( $columns['cb'] ) ) {
			$new_columns['cb'] = $columns['cb'];
		}

		$new_columns['thumbnail'] = __( 'Image', 'community-cpt' );

		// Add title.
		if ( isset( $columns['title'] ) ) {
			$new_columns['title'] = $columns['title'];
		}

		// Add custom columns.
		$new_columns['parent']     = __( 'Parent', 'community-cpt' );
		$new_columns['level']      = __( 'Level', 'community-cpt' );
		$new_columns['children']   = __( 'Children', 'community-cpt' );
		$new_columns['menu_order'] = __( 'Order', 'community-cpt' );

		// Add date.
		if ( isset( $columns['date'] ) ) {
			$new_columns['date'] = $columns['date'];
		}

		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'thumbnail':
				$this->render_thumbnail_column( $post_id );
				break;

			case 'parent':
				$this->render_parent_column( $post_id );
				break;

			case 'level':
				$this->render_level_column( $post_id );
				break;

			case 'children':
				$this->render_children_column( $post_id );
				break;

			case 'menu_order':
				$this->render_menu_order_column( $post_id );
				break;
		}
	}

	/**
	 * Render thumbnail column.
	 *
	 * @param int $post_id Post ID.
	 */
	private function render_thumbnail_column( $post_id ) {
		$thumbnail = get_the_post_thumbnail( $post_id, array( 60, 40 ) );

		if ( $thumbnail ) {
			echo wp_kses_post( $thumbnail );
		} else {
			echo '<span style="display:inline-block;width:60px;height:40px;background:#f0f0f0;"></span>';
		}
	}

	/**
	 * Render parent column.
	 *
	 * @param int $post_id Post ID.
	 */
	private function render_parent_column( $post_id ) {
		$parent_id = wp_get_post_parent_id( $post_id );

		if ( $parent_id ) {
			$parent = get_post( $parent_id );
			if ( $parent ) {
				$edit_link = get_edit_post_link( $parent_id );
				printf(
					'<a href="%s">%s</a>',
					esc_url( $edit_link ),
					esc_html( $parent->post_title )
				);
			} else {
				echo '&mdash;';
			}
		} else {
			echo '&mdash;';
		}
	}

	/**
	 * Render level column.
	 *
	 * @param int $post_id Post ID.
	 */
	private function render_level_column( $post_id ) {
		$level      = $this->get_hierarchy_level( $post_id );
		$level_text = 'L' . $level;

		$class = 'community-level-badge level-' . $level;
		printf(
			'<span class="%s">%s</span>',
			esc_attr( $class ),
			esc_html( $level_text )
		);
	}

	/**
	 * Render children column.
	 *
	 * @param int $post_id Post ID.
	 */
	private function render_children_column( $post_id ) {
		$children = get_children( array(
			'post_parent' => $post_id,
			'post_type'   => 'community',
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
		) );

		$count = count( $children );

		if ( $count > 0 ) {
			$filter_url = add_query_arg( array(
				'post_type'   => 'community',
				'post_parent' => $post_id,
			), admin_url( 'edit.php' ) );

			printf(
				'<a href="%s">%d</a>',
				esc_url( $filter_url ),
				absint( $count )
			);
		} else {
			echo '0';
		}
	}

	/**
	 * Render menu order column.
	 *
	 * @param int $post_id Post ID.
	 */
	private function render_menu_order_column( $post_id ) {
		$post = get_post( $post_id );
		echo absint( $post->menu_order );
	}

	/**
	 * Define sortable columns.
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sortable_columns( $columns ) {
		$columns['parent']     = 'parent';
		$columns['menu_order'] = 'menu_order';
		return $columns;
	}

	/**
	 * Handle sorting by custom columns.
	 *
	 * @param WP_Query $query The query object.
	 */
	public function handle_sortable_columns( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'community' !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'parent' === $orderby ) {
			$query->set( 'orderby', 'parent' );
		}

		if ( 'menu_order' === $orderby ) {
			$query->set( 'orderby', 'menu_order' );
		}
	}

	/**
	 * Calculate hierarchy level for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int Hierarchy level (1, 2, or 3+).
	 */
	private function get_hierarchy_level( $post_id ) {
		$level  = 1;
		$parent = wp_get_post_parent_id( $post_id );

		while ( $parent ) {
			$level++;
			$parent = wp_get_post_parent_id( $parent );
		}

		return $level;
	}
}
