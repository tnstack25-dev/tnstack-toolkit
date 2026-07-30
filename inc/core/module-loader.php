<?php
/**
 * Backward-compatible module loader shim.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

require_once tnstack_core_path( 'inc/core/class-module-manager.php' );

/**
 * @deprecated 1.0.0 Use TNStack_Module_Manager::load().
 */
function tnstack_core_load_modules() {
	TNStack_Module_Manager::load();
}