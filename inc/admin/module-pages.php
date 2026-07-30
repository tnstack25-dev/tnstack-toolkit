<?php
/**
 * Central registry for module settings submenus.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<string, array<string, string>>
 */
function tnstack_toolkit_module_settings_pages() {
	return TNStack_Module_Manifest::settings_pages();
}

/**
 * @param string $slug Module slug.
 * @return string
 */
function tnstack_toolkit_module_settings_url( $slug ) {
	return admin_url( 'admin.php?page=tnstack-mod-' . $slug );
}

/**
 * Ensure admin render callback is loadable.
 *
 * @param string $slug Module slug.
 */
function tnstack_toolkit_load_module_admin( $slug ) {
	TNStack_Module_Manager::load_admin( $slug );
}

/**
 * Register module settings submenus.
 */
function tnstack_toolkit_register_module_pages() {
	$parent = TNStack_Toolkit_Features_Dashboard::PAGE_SLUG;

	foreach ( tnstack_toolkit_module_settings_pages() as $slug => $page ) {
		if ( ! tnstack_core_module_enabled( $slug ) ) {
			continue;
		}

		tnstack_toolkit_load_module_admin( $slug );

		if ( ! is_callable( $page['callback'] ) ) {
			continue;
		}

		add_submenu_page(
			$parent,
			$page['title'],
			$page['title'],
			'manage_options',
			'tnstack-mod-' . $slug,
			$page['callback']
		);
	}
}

add_action( 'admin_menu', 'tnstack_toolkit_register_module_pages', 25 );

/**
 * Enqueue the shared settings UI only on supported toolkit pages.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function tnstack_toolkit_enqueue_module_settings_assets( $hook_suffix ) {
	unset( $hook_suffix );

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( ! in_array( $page, array( 'tnstack-mod-floating-contact', 'tnstack-mod-table-of-contents', 'tnstack-mod-custom-login-url', 'tnstack-mod-smtp-email', 'tnstack-github-updates', 'tnstack-export-import' ), true ) ) {
		return;
	}

	$path = tnstack_core_path( 'assets/css/module-settings-admin.css' );
	wp_enqueue_style(
		'tnstack-module-settings-admin',
		tnstack_core_uri( 'assets/css/module-settings-admin.css' ),
		array(),
		tnstack_core_asset_version( $path )
	);
}

add_action( 'admin_enqueue_scripts', 'tnstack_toolkit_enqueue_module_settings_assets' );

/**
 * @param string $slug Module slug.
 * @return bool
 */
function tnstack_toolkit_module_has_settings( $slug ) {
	return TNStack_Module_Manifest::has_settings_page( $slug );
}
