<?php
/**
 * Plugin Name: Community CPT
 * Description: Hierarchical community/location CPT with grid listings, Ajax search, Divi integration, and related posts.
 * Version: 1.0.0
 * Author: RealtySoft BV
 * Text Domain: community-cpt
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'COMMUNITY_CPT_VERSION', '1.0.0' );
define( 'COMMUNITY_CPT_PATH', plugin_dir_path( __FILE__ ) );
define( 'COMMUNITY_CPT_URL', plugin_dir_url( __FILE__ ) );
define( 'COMMUNITY_CPT_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load all class files from includes directory.
 */
require_once COMMUNITY_CPT_PATH . 'includes/class-community-cpt.php';
require_once COMMUNITY_CPT_PATH . 'includes/class-community-meta.php';
require_once COMMUNITY_CPT_PATH . 'includes/class-community-shortcodes.php';
require_once COMMUNITY_CPT_PATH . 'includes/class-community-ajax.php';
require_once COMMUNITY_CPT_PATH . 'includes/class-community-breadcrumb.php';
require_once COMMUNITY_CPT_PATH . 'includes/class-community-related.php';
require_once COMMUNITY_CPT_PATH . 'includes/class-community-settings.php';
require_once COMMUNITY_CPT_PATH . 'includes/class-community-divi.php';
require_once COMMUNITY_CPT_PATH . 'includes/class-community-admin-columns.php';
require_once COMMUNITY_CPT_PATH . 'includes/class-community-duplicate.php';
require_once COMMUNITY_CPT_PATH . 'includes/class-community-import-export.php';
require_once COMMUNITY_CPT_PATH . 'includes/class-community-shortcode-wizard.php';
require_once COMMUNITY_CPT_PATH . 'includes/class-community-archive-settings.php';

/**
 * Initialize all plugin classes on plugins_loaded hook.
 */
function community_cpt_init() {
	new Community_CPT();
	new Community_Meta();
	new Community_Shortcodes();
	new Community_Ajax();
	new Community_Breadcrumb();
	new Community_Related();
	new Community_Settings();
	new Community_Divi();
	new Community_Admin_Columns();
	new Community_Duplicate();
	new Community_Import_Export();
	new Community_Shortcode_Wizard();
	new Community_Archive_Settings();
}
add_action( 'plugins_loaded', 'community_cpt_init', 10 );

/**
 * Load plugin text domain for translations.
 */
function community_cpt_load_textdomain() {
	load_plugin_textdomain( 'community-cpt', false, dirname( COMMUNITY_CPT_BASENAME ) . '/languages' );
}
add_action( 'init', 'community_cpt_load_textdomain' );

/**
 * Flush rewrite rules on plugin activation.
 */
function community_cpt_activate() {
	// Register the CPT first so rules are generated.
	$cpt = new Community_CPT();
	$cpt->register_post_type();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'community_cpt_activate' );

/**
 * Flush rewrite rules on plugin deactivation.
 */
function community_cpt_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'community_cpt_deactivate' );

/**
 * Enqueue frontend assets conditionally.
 */
function community_cpt_enqueue_frontend_assets() {
	if ( ! community_cpt_should_load_assets() ) {
		return;
	}

	$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	// Check if minified version exists, fallback to non-minified.
	$css_file = file_exists( COMMUNITY_CPT_PATH . 'assets/css/community-grid' . $suffix . '.css' )
		? 'community-grid' . $suffix . '.css'
		: 'community-grid.css';

	$js_file = file_exists( COMMUNITY_CPT_PATH . 'assets/js/community-grid' . $suffix . '.js' )
		? 'community-grid' . $suffix . '.js'
		: 'community-grid.js';

	wp_enqueue_style(
		'community-grid',
		COMMUNITY_CPT_URL . 'assets/css/' . $css_file,
		array(),
		COMMUNITY_CPT_VERSION
	);

	wp_enqueue_script(
		'community-grid',
		COMMUNITY_CPT_URL . 'assets/js/' . $js_file,
		array(),
		COMMUNITY_CPT_VERSION,
		true
	);

	wp_localize_script( 'community-grid', 'communityGrid', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'community_grid_nonce' ),
		'i18n'    => array(
			'searching' => __( 'Searching...', 'community-cpt' ),
			'noResults' => __( 'No communities found.', 'community-cpt' ),
			'loading'   => __( 'Loading...', 'community-cpt' ),
			'error'     => __( 'Something went wrong. Please try again.', 'community-cpt' ),
		),
	) );
}
add_action( 'wp_enqueue_scripts', 'community_cpt_enqueue_frontend_assets' );

/**
 * Enqueue admin assets only on community post edit screens and settings page.
 */
function community_cpt_enqueue_admin_assets( $hook ) {
	global $post_type;

	$is_community_screen  = ( 'community' === $post_type && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) );
	$is_settings_page     = ( 'community_page_community-cpt-settings' === $hook );
	$is_import_export     = ( 'community_page_community-import-export' === $hook );
	$is_archive_settings  = ( 'community_page_community-archive-settings' === $hook );

	if ( ! $is_community_screen && ! $is_settings_page && ! $is_import_export && ! $is_archive_settings ) {
		return;
	}

	wp_enqueue_style(
		'community-admin',
		COMMUNITY_CPT_URL . 'assets/css/community-admin.css',
		array(),
		COMMUNITY_CPT_VERSION
	);

	// Select2 for related posts selector.
	wp_enqueue_style(
		'select2',
		'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
		array(),
		'4.1.0-rc.0'
	);

	wp_enqueue_script(
		'select2',
		'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
		array( 'jquery' ),
		'4.1.0-rc.0',
		true
	);

	// jQuery UI Sortable for related posts ordering.
	wp_enqueue_script( 'jquery-ui-sortable' );

	wp_enqueue_script(
		'community-admin',
		COMMUNITY_CPT_URL . 'assets/js/community-admin.js',
		array( 'jquery', 'select2', 'jquery-ui-sortable' ),
		COMMUNITY_CPT_VERSION,
		true
	);

	wp_localize_script( 'community-admin', 'communityAdmin', array(
		'nonce'  => wp_create_nonce( 'community_admin_nonce' ),
		'postId' => get_the_ID(),
	) );
}
add_action( 'admin_enqueue_scripts', 'community_cpt_enqueue_admin_assets' );

/**
 * Check if frontend assets should be loaded.
 *
 * @return bool True if assets should load.
 */
function community_cpt_should_load_assets() {
	if ( is_singular( 'community' ) ) {
		return true;
	}

	// Load on archive page.
	if ( is_post_type_archive( 'community' ) ) {
		return true;
	}

	global $post;
	if ( $post && (
		has_shortcode( $post->post_content, 'community_grid' ) ||
		has_shortcode( $post->post_content, 'community_related' ) ||
		has_shortcode( $post->post_content, 'community_breadcrumb' )
	) ) {
		return true;
	}

	return false;
}

/**
 * Retrieve a plugin setting value with default fallback.
 *
 * @param string $key     The setting key.
 * @param mixed  $default Optional. Default value if setting not found.
 * @return mixed The setting value.
 */
function community_cpt_get_setting( $key, $default = null ) {
	$settings = get_option( 'community_cpt_settings', array() );
	$defaults = array(
		'pagination_mode'  => 'all',
		'per_page'         => 20,
		'default_columns'  => 3,
		'show_search'      => true,
		'related_count'    => 8,
		'excerpt_length'   => 120,
		'card_style'       => 'default',
		'lazy_load_images' => true,
		'cache_duration'   => 3600,
	);

	if ( null === $default ) {
		$default = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	}

	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
}

/**
 * Check if a community post has published children.
 *
 * @param int $post_id The post ID to check.
 * @return bool True if the post has children.
 */
function community_post_has_children( $post_id ) {
	$cache_key = 'community_has_children_' . $post_id;
	$cached    = get_transient( $cache_key );

	if ( false !== $cached ) {
		return 'yes' === $cached;
	}

	$children = get_children( array(
		'post_parent' => $post_id,
		'post_type'   => 'community',
		'post_status' => 'publish',
		'numberposts' => 1,
	) );

	$has_children = ! empty( $children );
	set_transient( $cache_key, $has_children ? 'yes' : 'no', HOUR_IN_SECONDS );

	return $has_children;
}
