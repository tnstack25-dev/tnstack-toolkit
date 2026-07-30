<?php
/**
 * Lazy load videos and iframes.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'the_content', 'tnstack_lazy_media_content', 20 );
add_action( 'wp_enqueue_scripts', 'tnstack_lazy_media_assets' );

function tnstack_lazy_media_content( $content ) {
	if ( is_admin() || false === strpos( $content, '<iframe' ) && false === strpos( $content, '<video' ) ) {
		return $content;
	}

	$content = preg_replace_callback(
		'/<iframe([^>]+)><\/iframe>/i',
		function ( $m ) {
			if ( false !== stripos( $m[0], 'loading=' ) ) {
				return $m[0];
			}
			return '<iframe' . $m[1] . ' loading="lazy" class="tnstack-lazy-iframe"></iframe>';
		},
		$content
	);

	$content = preg_replace_callback(
		'/<video([^>]*)>/i',
		function ( $m ) {
			if ( false !== stripos( $m[0], 'preload=' ) ) {
				return $m[0];
			}
			return '<video' . $m[1] . ' preload="none" class="tnstack-lazy-video">';
		},
		$content
	);

	return $content;
}

function tnstack_lazy_media_assets() {
	wp_add_inline_style( 'tnstack-core-main', '.tnstack-lazy-iframe,.tnstack-lazy-video{max-width:100%;}' );
}