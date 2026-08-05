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

	wp_register_style(
		'tnstack-core-main',
		false,
		array(),
		TNSTACK_CORE_VERSION
	);
	wp_enqueue_style( 'tnstack-core-main' );

	// The base stylesheet is tiny; inline it to avoid a separate HTTP request.
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$css = file_get_contents( $css_path );
	if ( false !== $css && '' !== trim( $css ) ) {
		wp_add_inline_style( 'tnstack-core-main', $css );
	}

	do_action( 'tnstack_core_enqueue_assets' );
}
