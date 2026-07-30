<?php
/**
 * WordPress admin performance optimizations.
 *
 * @package TNStackCore
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', 'tnstack_core_admin_disable_bloat', 1 );
add_action( 'admin_init', 'tnstack_core_admin_skip_update_checks', 0 );
add_action( 'admin_enqueue_scripts', 'tnstack_core_admin_disable_heartbeat', 99 );
add_action( 'admin_enqueue_scripts', 'tnstack_core_admin_dequeue_assets', 999 );
add_action( 'wp_dashboard_setup', 'tnstack_core_admin_remove_dashboard_widgets', 99 );

/**
 * Remove low-value admin features.
 */
function tnstack_core_admin_disable_bloat() {
	if ( ! tnstack_core_opt_enabled( 'admin', 'disable_emoji' ) ) {
		return;
	}

	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
	add_filter( 'emoji_svg_url', '__return_false' );
}

/**
 * Avoid remote update checks on routine admin screens.
 */
function tnstack_core_admin_skip_update_checks() {
	if ( ! tnstack_core_opt_enabled( 'admin', 'skip_update_checks' ) || ! is_admin() || ! current_user_can( 'update_core' ) ) {
		return;
	}

	$pagenow = $GLOBALS['pagenow'] ?? '';

	$update_pages = array(
		'plugins.php',
		'plugin-install.php',
		'themes.php',
		'theme-install.php',
		'update-core.php',
		'update.php',
	);

	if ( in_array( $pagenow, $update_pages, true ) ) {
		return;
	}

	remove_action( 'admin_init', '_maybe_update_core' );
	remove_action( 'admin_init', '_maybe_update_plugins' );
	remove_action( 'admin_init', '_maybe_update_themes' );
}

/**
 * Keep heartbeat only where autosave/session locking matters.
 *
 * @param string $hook Current admin page hook.
 */
function tnstack_core_admin_disable_heartbeat( $hook ) {
	if ( ! tnstack_core_opt_enabled( 'admin', 'disable_heartbeat' ) ) {
		return;
	}

	if ( in_array( $hook, array( 'post.php', 'post-new.php', 'site-health.php' ), true ) ) {
		return;
	}

	wp_deregister_script( 'heartbeat' );
}

/**
 * Dequeue plugin assets outside the screens that need them.
 *
 * @param string $hook Current admin page hook.
 */
function tnstack_core_admin_dequeue_assets( $hook ) {
	if ( ! tnstack_core_opt_enabled( 'admin', 'dequeue_assets' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	wp_dequeue_style( 'wp-pointer' );
	wp_dequeue_script( 'wp-pointer' );

	if ( $screen && 'post' !== $screen->base ) {
		wp_dequeue_style( 'wp-block-editor' );
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_script( 'wp-block-editor' );
		wp_dequeue_script( 'wp-blocks' );
		wp_dequeue_script( 'wp-block-editor' );
	}

	if ( ! tnstack_core_is_woocommerce_admin_screen( $screen, $hook ) ) {
		wp_dequeue_style( 'woocommerce_admin_styles' );
		wp_dequeue_style( 'woocommerce-general' );
		wp_dequeue_script( 'woocommerce_admin' );
		wp_dequeue_script( 'wc-admin-app' );
		wp_dequeue_script( 'wc-settings-deprecation' );
		wp_dequeue_script( 'wc-admin-layout' );
		wp_dequeue_script( 'wc-admin-notices' );
		wp_dequeue_script( 'wc-admin-date' );
		wp_dequeue_script( 'wc-components' );
	}

	if ( ! tnstack_core_is_yoast_admin_screen( $screen, $hook ) ) {
		wp_dequeue_style( 'yoast-seo-adminbar' );
		wp_dequeue_script( 'yoast-seo-adminbar' );
	}

	if ( 'toplevel_page_wpcf7' !== $hook && false === strpos( $hook, 'wpcf7' ) ) {
		wp_dequeue_style( 'contact-form-7' );
		wp_dequeue_style( 'contact-form-7-admin' );
		wp_dequeue_script( 'contact-form-7' );
		wp_dequeue_script( 'swv' );
	}

	if ( false === strpos( $hook, 'itsec' ) && false === strpos( $hook, 'ithemes-security' ) ) {
		wp_dequeue_style( 'itsec-icon-font' );
		wp_dequeue_script( 'itsec-icon-font' );
	}
}

/**
 * @param WP_Screen|null $screen Current screen.
 * @param string         $hook   Current hook.
 * @return bool
 */
function tnstack_core_is_woocommerce_admin_screen( $screen, $hook ) {
	if ( false !== strpos( $hook, 'woocommerce' ) || false !== strpos( $hook, 'wc-' ) ) {
		return true;
	}

	if ( ! $screen ) {
		return false;
	}

	return in_array( $screen->post_type, array( 'product', 'shop_order', 'shop_coupon' ), true )
		|| in_array( $screen->id, array( 'edit-product', 'product', 'woocommerce_page_wc-settings' ), true );
}

/**
 * @param WP_Screen|null $screen Current screen.
 * @param string         $hook   Current hook.
 * @return bool
 */
function tnstack_core_is_yoast_admin_screen( $screen, $hook ) {
	if ( false !== strpos( $hook, 'wpseo' ) || false !== strpos( $hook, 'yoast' ) ) {
		return true;
	}

	if ( ! $screen ) {
		return false;
	}

	return in_array( $screen->base, array( 'post', 'term' ), true );
}

/**
 * Remove dashboard widgets that add queries and remote requests.
 */
function tnstack_core_admin_remove_dashboard_widgets() {
	if ( ! tnstack_core_opt_enabled( 'admin', 'remove_dashboard_widgets' ) ) {
		return;
	}

	remove_action( 'welcome_panel', 'wp_welcome_panel' );
	remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );

	$optional_widgets = array(
		'woocommerce_dashboard_status',
		'woocommerce_dashboard_recent_reviews',
		'yoast_db_widget',
		'wpseo-dashboard-overview',
		'rg_forms_dashboard',
		'itsec-dashboard-widget',
		'itsec-dashboard-widget-admin-bar',
	);

	foreach ( $optional_widgets as $widget_id ) {
		remove_meta_box( $widget_id, 'dashboard', 'normal' );
		remove_meta_box( $widget_id, 'dashboard', 'side' );
	}
}