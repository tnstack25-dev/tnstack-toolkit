<?php
/**
 * Slim Catalog module bootstrap.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SLIM_CATALOG_VERSION' ) ) {
	define( 'SLIM_CATALOG_VERSION', TNSTACK_TOOLKIT_VERSION );
}

if ( ! defined( 'SLIM_CATALOG_FILE' ) ) {
	define( 'SLIM_CATALOG_FILE', tnstack_core_path( 'inc/modules/slim-catalog/init.php' ) );
}

if ( ! defined( 'SLIM_CATALOG_PATH' ) ) {
	define( 'SLIM_CATALOG_PATH', tnstack_core_path( 'inc/modules/slim-catalog/' ) );
}

if ( ! defined( 'SLIM_CATALOG_URL' ) ) {
	define( 'SLIM_CATALOG_URL', tnstack_core_uri( 'inc/modules/slim-catalog/' ) );
}

require_once SLIM_CATALOG_PATH . 'includes/class-slim-catalog.php';

/**
 * @return Slim_Catalog
 */
function slim_catalog() {
	return Slim_Catalog::instance();
}

slim_catalog();
