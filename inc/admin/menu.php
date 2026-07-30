<?php
/**
 * Central admin menu for TNStack Toolkit.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

require_once tnstack_core_path( 'inc/admin/registry.php' );
require_once tnstack_core_path( 'inc/admin/dashboard.php' );

final class TNStack_Toolkit_Admin_Menu {

	/**
	 * Boot admin menu and subpages.
	 */
	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
		TNStack_Toolkit_Features_Dashboard::boot();
	}

	/**
	 * Register top-level menu and submenus.
	 */
	public static function register_menus() {
		add_menu_page(
			__( 'TNStack Toolkit', 'tnstack-toolkit' ),
			__( 'TNStack Toolkit', 'tnstack-toolkit' ),
			'manage_options',
			TNStack_Toolkit_Features_Dashboard::PAGE_SLUG,
			array( 'TNStack_Toolkit_Features_Dashboard', 'render_page' ),
			'dashicons-admin-tools',
			57
		);

		if ( tnstack_core_module_enabled( 'performance' ) && class_exists( 'TNStack_Core_Performance_Dashboard' ) ) {
			add_submenu_page(
				TNStack_Toolkit_Features_Dashboard::PAGE_SLUG,
				__( 'Tối ưu & Bảo mật', 'tnstack-toolkit' ),
				__( 'Tối ưu & Bảo mật', 'tnstack-toolkit' ),
				'manage_options',
				TNStack_Core_Performance_Dashboard::PAGE_SLUG,
				array( 'TNStack_Core_Performance_Dashboard', 'render_page' )
			);
		}
	}
}