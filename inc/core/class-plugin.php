<?php
/**
 * Main plugin bootstrap.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_Plugin {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Whether the plugin hooks were registered.
	 *
	 * @var bool
	 */
	private $registered = false;

	/**
	 * Whether the shared foundation was loaded.
	 *
	 * @var bool
	 */
	private $foundation_loaded = false;

	/**
	 * Whether the plugin services were booted.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * @return TNStack_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Register the plugin with the WordPress lifecycle.
	 */
	public function register() {
		if ( $this->registered ) {
			return;
		}

		$this->registered = true;
		add_action( 'plugins_loaded', array( $this, 'boot' ), 5 );
	}

	/**
	 * Boot shared services after all plugin files are available.
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;
		$this->load_foundation();
		TNStack_GitHub_Updater::boot();
		add_action( 'init', array( $this, 'load_textdomain' ), -10000 );
		add_action( 'init', array( $this, 'boot_modules' ), -9000 );
		add_action( 'init', array( 'TNStack_Plugin_Lifecycle', 'maybe_upgrade' ), -800 );
		add_action( 'init', array( 'TNStack_Plugin_Lifecycle', 'maybe_flush_rewrite_rules' ), 999 );

		if ( is_admin() ) {
			$this->boot_admin();
			add_action( 'admin_notices', array( $this, 'render_module_error_notice' ) );
		}
	}

	/**
	 * Load shared core files.
	 */
	public function load_foundation() {
		if ( $this->foundation_loaded ) {
			return;
		}

		$this->foundation_loaded = true;
		$base = trailingslashit( TNSTACK_TOOLKIT_PATH ) . 'inc/';

		require_once $base . 'contracts/interface-module.php';
		require_once $base . 'core/class-module-manifest.php';
		require_once $base . 'core/config.php';
		require_once $base . 'core/helpers.php';
		require_once $base . 'core/module-settings.php';
		require_once $base . 'core/site-settings.php';
		require_once $base . 'core/class-module-manager.php';
		require_once $base . 'core/class-github-updater.php';
		require_once $base . 'core/assets.php';
	}

	/**
	 * Boot enabled modules early in init so their normal init callbacks still run.
	 */
	public function boot_modules() {
		TNStack_Module_Manager::load();
	}

	/**
	 * Boot WordPress admin integration.
	 */
	private function boot_admin() {
		require_once tnstack_core_path( 'inc/admin/menu.php' );
		require_once tnstack_core_path( 'inc/admin/module-pages.php' );
		require_once tnstack_core_path( 'inc/admin/export-import.php' );

		TNStack_Toolkit_Admin_Menu::boot();
		TNStack_Toolkit_Export_Import::boot();
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'tnstack-toolkit',
			false,
			dirname( plugin_basename( TNSTACK_TOOLKIT_FILE ) ) . '/languages'
		);
	}

	/**
	 * Show isolated module errors to administrators.
	 */
	public function render_module_error_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$errors = TNStack_Module_Manager::errors();
		if ( empty( $errors ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'TNStack Toolkit đã cô lập một module bị lỗi:', 'tnstack-toolkit' ) . '</strong></p><ul>';
		foreach ( $errors as $slug => $error ) {
			echo '<li><code>' . esc_html( $slug ) . '</code>: ' . esc_html( $error['message'] ?? __( 'Không thể khởi động module.', 'tnstack-toolkit' ) ) . '</li>';
		}
		echo '</ul><p>' . esc_html__( 'Các module khác vẫn tiếp tục hoạt động. Chi tiết kỹ thuật đã được ghi vào error log.', 'tnstack-toolkit' ) . '</p></div>';
	}
}
