<?php
/**
 * Main plugin bootstrap.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

final class Slim_Catalog {

	/**
	 * @var Slim_Catalog|null
	 */
	private static $instance = null;

	/**
	 * @return Slim_Catalog
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->includes();
		$this->init();
	}

	private function includes() {
		require_once SLIM_CATALOG_PATH . 'includes/class-post-types.php';
		require_once SLIM_CATALOG_PATH . 'includes/class-product-variations.php';
		require_once SLIM_CATALOG_PATH . 'includes/class-product.php';
		require_once SLIM_CATALOG_PATH . 'includes/class-product-meta.php';
		require_once SLIM_CATALOG_PATH . 'includes/class-product-variations-meta.php';
		require_once SLIM_CATALOG_PATH . 'includes/class-admin.php';
		require_once SLIM_CATALOG_PATH . 'includes/class-frontend.php';
		require_once SLIM_CATALOG_PATH . 'includes/class-shortcodes.php';
		require_once SLIM_CATALOG_PATH . 'includes/class-template-loader.php';
		require_once SLIM_CATALOG_PATH . 'includes/class-i18n.php';
		require_once SLIM_CATALOG_PATH . 'includes/class-ux-product-shortcodes.php';
		require_once SLIM_CATALOG_PATH . 'includes/class-ux-builder.php';
		require_once SLIM_CATALOG_PATH . 'includes/template-functions.php';
	}

	private function init() {
		Slim_Catalog_I18n::init();
		Slim_Catalog_Post_Types::init();
		Slim_Catalog_Product_Meta::init();
		Slim_Catalog_Product_Variations_Meta::init();
		Slim_Catalog_Admin::init();
		Slim_Catalog_Frontend::init();
		Slim_Catalog_Shortcodes::init();
		Slim_Catalog_Template_Loader::init();
		Slim_Catalog_UX_Builder::init();
	}

	/**
	 * Plugin activation.
	 */
	public static function activate() {
		require_once SLIM_CATALOG_PATH . 'includes/class-i18n.php';
		require_once SLIM_CATALOG_PATH . 'includes/template-functions.php';
		require_once SLIM_CATALOG_PATH . 'includes/class-post-types.php';

		if ( false === get_option( 'slim_catalog_settings', false ) ) {
			update_option(
				'slim_catalog_settings',
				slim_catalog_default_settings()
			);
		}

		delete_transient( 'slim_catalog_settings_cache' );
		Slim_Catalog_Post_Types::register();
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
