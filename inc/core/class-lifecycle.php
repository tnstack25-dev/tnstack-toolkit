<?php
/**
 * Plugin activation, deactivation and upgrade routines.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_Plugin_Lifecycle {

	const VERSION_OPTION = 'tnstack_toolkit_version';
	const FLUSH_OPTION   = 'tnstack_toolkit_flush_rewrite_rules';

	/**
	 * Activate the plugin and initialize enabled subsystems.
	 */
	public static function activate() {
		TNStack_Plugin::instance()->load_foundation();

		update_option( self::VERSION_OPTION, TNSTACK_TOOLKIT_VERSION, false );
		update_option( self::FLUSH_OPTION, 1, false );

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$standalone_catalog = is_plugin_active( 'slim-catalog/slim-catalog.php' );
		if ( $standalone_catalog ) {
			deactivate_plugins( 'slim-catalog/slim-catalog.php' );
		}

		/*
		 * A standalone Slim Catalog class remains in memory until the current
		 * activation request ends. Do not load the bundled class in that case;
		 * it will start normally on the next request.
		 */
		if ( tnstack_core_module_enabled( 'slim-catalog' ) && ! $standalone_catalog && ! class_exists( 'Slim_Catalog', false ) ) {
			TNStack_Module_Manager::load_admin( 'slim-catalog' );

			if ( class_exists( 'Slim_Catalog', false ) ) {
				Slim_Catalog::activate();
			}
		}

		flush_rewrite_rules();
		delete_option( self::FLUSH_OPTION );
	}

	/**
	 * Deactivate the plugin.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Run lightweight upgrade bookkeeping once after a version change.
	 */
	public static function maybe_upgrade() {
		$installed = (string) get_option( self::VERSION_OPTION, '' );

		if ( TNSTACK_TOOLKIT_VERSION === $installed ) {
			return;
		}

		update_option( self::VERSION_OPTION, TNSTACK_TOOLKIT_VERSION, false );
		update_option( self::FLUSH_OPTION, 1, false );
		do_action( 'tnstack_toolkit_upgraded', $installed, TNSTACK_TOOLKIT_VERSION );
	}

	/**
	 * Flush rewrite rules once, after all post types have registered.
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( ! get_option( self::FLUSH_OPTION, false ) ) {
			return;
		}

		flush_rewrite_rules( false );
		delete_option( self::FLUSH_OPTION );
	}
}
