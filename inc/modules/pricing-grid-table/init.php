<?php
/**
 * Pricing Grid Table module bootstrap.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

require_once tnstack_core_path( 'inc/modules/pricing-grid-table/helpers.php' );
require_once tnstack_core_path( 'inc/modules/pricing-grid-table/shortcode.php' );
require_once tnstack_core_path( 'inc/modules/pricing-grid-table/builder.php' );

add_action( 'wp_enqueue_scripts', 'tnstack_core_enqueue_pricing_grid_styles' );
add_action( 'ux_builder_enqueue_scripts', 'tnstack_core_enqueue_pricing_grid_styles' );

function tnstack_core_enqueue_pricing_grid_styles() {
	if ( ! tnstack_core_should_load_pricing_grid_assets() ) {
		return;
	}

	$path = tnstack_core_path( '/assets/css/pricing-grid-table.css' );

	wp_enqueue_style(
		'tnstack-core-pricing-grid',
		tnstack_core_uri( 'assets/css/pricing-grid-table.css' ),
		array(),
		tnstack_core_asset_version( $path )
	);
}

/**
 * @return bool
 */
function tnstack_core_should_load_pricing_grid_assets() {
	if ( function_exists( 'ux_builder_is_editor' ) && ux_builder_is_editor() ) {
		return true;
	}

	if ( function_exists( 'ux_builder_is_iframe' ) && ux_builder_is_iframe() ) {
		return true;
	}

	return tnstack_core_content_has(
		array( 'pricing_grid', 'pricing_grid_row', 'pricing_grid_cell' ),
		null
	);
}