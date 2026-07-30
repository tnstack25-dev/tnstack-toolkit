<?php
/**
 * Shared theme helpers.
 *
 * @package TNStackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string|string[] $needles Shortcode tags or content fragments.
 * @param WP_Post|null    $post    Post object.
 */
function tnstack_core_content_has( $needles, $post = null ) {
	if ( null === $post ) {
		global $post;
	}

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	$content = (string) $post->post_content;
	$needles = (array) $needles;

	foreach ( $needles as $needle ) {
		if ( function_exists( 'has_shortcode' ) && has_shortcode( $content, $needle ) ) {
			return true;
		}

		if ( false !== strpos( $content, $needle ) ) {
			return true;
		}
	}

	return false;
}

/**
 * @param string $path Absolute file path.
 */
function tnstack_core_asset_version( $path ) {
	return file_exists( $path ) ? (string) filemtime( $path ) : TNSTACK_CORE_VERSION;
}

