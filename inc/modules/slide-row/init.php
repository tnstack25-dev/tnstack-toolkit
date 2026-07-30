<?php
/**
 * Slide Row module bootstrap.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

require_once tnstack_core_path( 'inc/modules/slide-row/helpers.php' );
require_once tnstack_core_path( 'inc/modules/slide-row/shortcode.php' );
require_once tnstack_core_path( 'inc/modules/slide-row/builder.php' );

add_action( 'wp_enqueue_scripts', 'tnstack_core_enqueue_slide_row_styles' );
add_action( 'ux_builder_enqueue_scripts', 'tnstack_core_enqueue_slide_row_styles' );

function tnstack_core_enqueue_slide_row_styles() {
	if ( ! tnstack_core_should_load_slide_row_assets() ) {
		return;
	}

	$path = tnstack_core_path( '/assets/css/slide-row.css' );

	wp_enqueue_style(
		'tnstack-core-slide-row',
		tnstack_core_uri( 'assets/css/slide-row.css' ),
		array(),
		tnstack_core_asset_version( $path )
	);
}

/**
 * @return bool
 */
function tnstack_core_should_load_slide_row_assets() {
	if ( function_exists( 'ux_builder_is_editor' ) && ux_builder_is_editor() ) {
		return true;
	}

	if ( function_exists( 'ux_builder_is_iframe' ) && ux_builder_is_iframe() ) {
		return true;
	}

	return tnstack_core_content_has(
		array( 'ux_slide_row', 'slide_row', 'ux_slide_row_item' ),
		null
	);
}