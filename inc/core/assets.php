<?php
/**
 * Core frontend assets.
 *
 * @package TNStackCore
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'tnstack_core_enqueue_assets' );

/**
 * Enqueue base theme assets.
 */
function tnstack_core_enqueue_assets() {
	$css_path = tnstack_core_path( 'assets/css/main.css' );

	if ( ! file_exists( $css_path ) ) {
		return;
	}

	wp_enqueue_style(
		'tnstack-core-main',
		tnstack_core_uri( 'assets/css/main.css' ),
		array(),
		tnstack_core_asset_version( $css_path )
	);

	do_action( 'tnstack_core_enqueue_assets' );
}