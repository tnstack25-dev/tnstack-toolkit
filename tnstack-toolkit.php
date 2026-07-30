<?php
/**
 * Plugin Name: TNStack Toolkit
 * Plugin URI: https://tnstack.com
 * Update URI: https://github.com/tnstack25-dev/tnstack-toolkit
 * Description: Performance, security, Slim Catalog, and modular UX Builder extensions for WordPress (Flatsome).
 * Version: 2.1.0
 * Author: TNStack
 * Text Domain: tnstack-toolkit
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'TNSTACK_TOOLKIT_VERSION' ) ) {
	define( 'TNSTACK_TOOLKIT_VERSION', '2.1.0' );
}

if ( ! defined( 'TNSTACK_TOOLKIT_FILE' ) ) {
	define( 'TNSTACK_TOOLKIT_FILE', __FILE__ );
}

if ( ! defined( 'TNSTACK_TOOLKIT_PATH' ) ) {
	define( 'TNSTACK_TOOLKIT_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'TNSTACK_TOOLKIT_URI' ) ) {
	define( 'TNSTACK_TOOLKIT_URI', plugin_dir_url( __FILE__ ) );
}

// Backward compatibility with theme-era constants.
if ( ! defined( 'TNSTACK_CORE_VERSION' ) ) {
	define( 'TNSTACK_CORE_VERSION', TNSTACK_TOOLKIT_VERSION );
}

require_once TNSTACK_TOOLKIT_PATH . 'inc/bootstrap.php';

register_activation_hook( TNSTACK_TOOLKIT_FILE, array( 'TNStack_Plugin_Lifecycle', 'activate' ) );
register_deactivation_hook( TNSTACK_TOOLKIT_FILE, array( 'TNStack_Plugin_Lifecycle', 'deactivate' ) );
