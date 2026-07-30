<?php
/**
 * FAQ Accordion module.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

require_once tnstack_core_path( 'inc/modules/faq-accordion/shortcode.php' );
require_once tnstack_core_path( 'inc/modules/faq-accordion/builder.php' );

add_action( 'wp_enqueue_scripts', 'tnstack_faq_assets' );
add_action( 'ux_builder_enqueue_scripts', 'tnstack_faq_assets' );

function tnstack_faq_assets() {
	if ( ! tnstack_faq_should_load() ) {
		return;
	}

	$css = tnstack_core_path( 'assets/css/faq-accordion.css' );
	$js  = tnstack_core_path( 'assets/js/faq-accordion.js' );

	wp_enqueue_style( 'tnstack-faq', tnstack_core_uri( 'assets/css/faq-accordion.css' ), array(), tnstack_core_asset_version( $css ) );
	wp_enqueue_script( 'tnstack-faq', tnstack_core_uri( 'assets/js/faq-accordion.js' ), array(), tnstack_core_asset_version( $js ), true );
}

function tnstack_faq_should_load() {
	if (
		( function_exists( 'ux_builder_is_editor' ) && ux_builder_is_editor() )
		|| ( function_exists( 'ux_builder_is_iframe' ) && ux_builder_is_iframe() )
	) {
		return true;
	}
	return tnstack_core_content_has( array( 'ttk_faq', 'ttk_faq_item' ), null );
}
