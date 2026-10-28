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
	const LEGACY_GITHUB_OPTION = 'tnstack_github_updater_settings';

	/**
	 * Preserve the effective defaults for sites upgrading from releases that
	 * implicitly enabled features. New installations receive the current off state.
	 */
	public static function migrate_legacy_defaults() {
		$installed = (string) get_option( self::VERSION_OPTION, '' );
		if ( '' === $installed || TNSTACK_TOOLKIT_VERSION === $installed ) {
			return;
		}

		$legacy_settings = array(
			'profile'  => 'standard',
			'modules'  => array(
				'custom-admin-dashboard' => true,
				'custom-login-interface' => true,
				'login-authentication'   => true,
				'cf7-honeypot'           => true,
				'maintenance-mode'       => false,
				'schema-markup'          => true,
				'pricing-grid-table'     => true,
				'faq-accordion'          => true,
				'countdown-timer'        => true,
				'slim-catalog'           => true,
				'popup-form'             => true,
				'floating-contact'       => false,
				'cookie-consent'         => false,
				'disable-comments'       => true,
				'custom-login-url'       => true,
			),
			'project'  => array(
				'disable-update-plugin' => false,
				'bypasss-acf'           => false,
			),
			'features' => array(
				'woocommerce' => true,
				'comments'    => false,
			),
		);
		$stored_settings = get_option( TNSTACK_TOOLKIT_SETTINGS_OPTION, array() );
		$stored_settings = is_array( $stored_settings ) ? $stored_settings : array();
		foreach ( array( 'modules', 'project', 'features' ) as $group ) {
			$stored_group = isset( $stored_settings[ $group ] ) && is_array( $stored_settings[ $group ] ) ? $stored_settings[ $group ] : array();
			$legacy_settings[ $group ] = array_merge( $legacy_settings[ $group ], $stored_group );
		}
		$legacy_settings['profile'] = $stored_settings['profile'] ?? $legacy_settings['profile'];
		update_option( TNSTACK_TOOLKIT_SETTINGS_OPTION, $legacy_settings, false );

		$legacy_optimization = array(
			'core' => array(
				'disable_emoji' => 1, 'disable_embeds' => 1, 'disable_xmlrpc' => 1,
				'disable_head_links' => 1, 'disable_heartbeat_frontend' => 1, 'defer_scripts' => 1,
				'async_styles' => 1, 'lazy_load_images' => 1, 'resource_hints' => 1,
				'dequeue_block_styles' => 1, 'dequeue_dashicons' => 1,
				'dequeue_woocommerce_assets' => 1, 'dequeue_cf7_assets' => 1,
				'limit_post_revisions' => 1, 'revisions_to_keep' => 5, 'heartbeat_interval' => 90,
			),
			'admin' => array(
				'disable_emoji' => 1, 'skip_update_checks' => 1, 'disable_heartbeat' => 1,
				'dequeue_assets' => 1, 'remove_dashboard_widgets' => 1,
			),
			'security' => array(
				'waf_enabled' => 1, 'waf_mode' => 'block', 'waf_allowlist' => '',
				'malware_monitor' => 1, 'malware_email_alert' => 1, 'malware_scan_frequency' => 'daily',
				'malware_max_file_size_mb' => 5, 'remove_generator' => 1, 'block_author_scan' => 1,
				'disable_rest_users' => 1, 'restrict_rest_api' => 1, 'allow_woocommerce_rest' => 1,
				'block_xmlrpc' => 1, 'block_suspicious_requests' => 0, 'block_sensitive_files' => 1,
				'security_headers' => 1, 'hsts_header' => 1, 'force_ssl_admin' => 1,
				'secure_session_cookies' => 1, 'disable_pingbacks' => 1, 'login_rate_limit' => 1,
				'ip_login_rate_limit' => 1, 'login_honeypot' => 1, 'login_captcha' => 1,
				'login_max_attempts' => 5, 'login_lockout_minutes' => 30,
				'ip_login_max_attempts' => 25, 'ip_lockout_minutes' => 60,
				'hide_login_errors' => 1, 'disable_file_editor' => 1,
				'disable_application_passwords' => 1, 'captcha_provider' => 'math',
				'captcha_site_key' => '', 'captcha_secret_key' => '',
			),
		);
		$stored_optimization = get_option( 'tnstack_core_optimization_settings', array() );
		$stored_optimization = is_array( $stored_optimization ) ? $stored_optimization : array();
		update_option( 'tnstack_core_optimization_settings', array_replace_recursive( $legacy_optimization, $stored_optimization ), false );

		$popup_settings = get_option( 'tn_popup_form_settings', array() );
		$popup_settings = is_array( $popup_settings ) ? $popup_settings : array();
		$popup_settings = array_merge( array( 'enabled' => 1, 'store_submissions' => 1 ), $popup_settings );
		update_option( 'tn_popup_form_settings', $popup_settings, false );

		$floating_settings = get_option( tnstack_module_option_key( 'floating-contact' ), array() );
		$floating_settings = is_array( $floating_settings ) ? $floating_settings : array();
		if ( ! array_key_exists( 'enabled', $floating_settings ) ) {
			$floating_settings['enabled'] = 1;
			update_option( tnstack_module_option_key( 'floating-contact' ), $floating_settings, false );
		}

		$catalog_settings = get_option( 'slim_catalog_settings', array() );
		$catalog_settings = is_array( $catalog_settings ) ? $catalog_settings : array();
		if ( ! array_key_exists( 'schema_enabled', $catalog_settings ) ) {
			$catalog_settings['schema_enabled'] = '1';
			update_option( 'slim_catalog_settings', $catalog_settings, false );
		}
	}

	/**
	 * Activate the plugin and initialize enabled subsystems.
	 */
	public static function activate() {
		TNStack_Plugin::instance()->load_foundation();

		update_option( self::VERSION_OPTION, TNSTACK_TOOLKIT_VERSION, false );
		update_option( self::FLUSH_OPTION, 1, false );
		delete_site_option( self::LEGACY_GITHUB_OPTION );

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
		wp_clear_scheduled_hook( 'tnstack_popup_cleanup_leads' );
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
		delete_site_option( self::LEGACY_GITHUB_OPTION );
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
