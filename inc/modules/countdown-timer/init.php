<?php
/**
 * Countdown timer module.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

require_once tnstack_core_path( 'inc/modules/countdown-timer/shortcode.php' );

add_action( 'wp_enqueue_scripts', 'tnstack_countdown_assets' );

function tnstack_countdown_assets() {
	if ( ! tnstack_countdown_should_load() ) {
		return;
	}

	$css = tnstack_core_path( 'assets/css/countdown-timer.css' );
	$js  = tnstack_core_path( 'assets/js/countdown-timer.js' );

	wp_enqueue_style( 'tnstack-countdown', tnstack_core_uri( 'assets/css/countdown-timer.css' ), array(), tnstack_core_asset_version( $css ) );
	wp_enqueue_script( 'tnstack-countdown', tnstack_core_uri( 'assets/js/countdown-timer.js' ), array(), tnstack_core_asset_version( $js ), true );
}

function tnstack_countdown_should_load() {
	return tnstack_core_content_has( 'ttk_countdown', null );
}
