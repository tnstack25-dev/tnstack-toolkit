<?php
/**
 * Module contract for future object-oriented modules.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Optional interface for modules that boot via a class instance.
 */
interface TNStack_Module_Interface {

	/**
	 * Unique module slug.
	 *
	 * @return string
	 */
	public function slug();

	/**
	 * Register hooks and services.
	 */
	public function boot();
}