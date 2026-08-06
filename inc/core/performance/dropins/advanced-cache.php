<?php
/**
 * TNSTACK_TOOLKIT_ADVANCED_CACHE
 *
 * Serves TNStack HTML cache before WordPress connects to the database.
 */

defined( 'ABSPATH' ) || exit;

if (
	'cli' === PHP_SAPI ||
	( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) ||
	( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE )
) {
	return;
}

if ( ! is_readable( WP_CONTENT_DIR . '/plugins/tnstack-toolkit/tnstack-toolkit.php' ) ) {
	return;
}

$tnstack_config_file = WP_CONTENT_DIR . '/cache/tnstack-page-cache-config.json';
if ( ! is_readable( $tnstack_config_file ) ) {
	return;
}

$tnstack_config = json_decode( (string) file_get_contents( $tnstack_config_file ), true );
if ( ! is_array( $tnstack_config ) || empty( $tnstack_config['enabled'] ) ) {
	return;
}

if ( 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
	return;
}

$tnstack_cookie_names = array_keys( $_COOKIE );
foreach ( $tnstack_cookie_names as $tnstack_cookie_name ) {
	if (
		0 === strpos( $tnstack_cookie_name, 'wordpress_logged_in_' ) ||
		0 === strpos( $tnstack_cookie_name, 'wp_woocommerce_session_' ) ||
		0 === strpos( $tnstack_cookie_name, 'wp-postpass_' ) ||
		0 === strpos( $tnstack_cookie_name, 'comment_author_' )
	) {
		return;
	}
}
if ( ! empty( $_COOKIE['woocommerce_items_in_cart'] ) ) {
	return;
}

$tnstack_raw_uri = (string) ( $_SERVER['REQUEST_URI'] ?? '/' );
$tnstack_parts   = parse_url( $tnstack_raw_uri );
if ( false === $tnstack_parts ) {
	return;
}

$tnstack_path = isset( $tnstack_parts['path'] ) && '' !== $tnstack_parts['path'] ? $tnstack_parts['path'] : '/';
if ( preg_match( '~/(?:wp-admin|wp-login\.php|wp-json|xmlrpc\.php)(?:/|$)~i', $tnstack_path ) ) {
	return;
}
if ( preg_match( '~/(?:cart|checkout|my-account)(?:/|$)~i', $tnstack_path ) ) {
	return;
}

$tnstack_query     = $tnstack_parts['query'] ?? '';
$tnstack_cacheable = array();
if ( '' !== $tnstack_query ) {
	parse_str( $tnstack_query, $tnstack_params );
	if ( ! is_array( $tnstack_params ) ) {
		return;
	}

	$tnstack_tracking = array( 'fbclid', 'gclid', 'gbraid', 'wbraid', 'msclkid', 'mc_cid', 'mc_eid', '_ga', '_gl', 'ref', 'referrer', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_id' );
	$tnstack_dynamic  = array( 's', 'add-to-cart', 'add_to_cart', 'removed_item', 'undo_item', 'update_cart', 'apply_coupon', 'remove_coupon', 'orderby', 'order', 'min_price', 'max_price', 'rating_filter', 'stock_status', 'on_sale', 'featured', 'product_tag', 'product_cat', 'filter_', 'lang', 'currency', 'wc-ajax', 'wc-api', 'preview', 'preview_id', 'preview_nonce', 'doing_wp_cron', 'no_cache' );

	foreach ( $tnstack_params as $tnstack_key => $tnstack_value ) {
		$tnstack_key = (string) $tnstack_key;
		if ( in_array( $tnstack_key, $tnstack_tracking, true ) || 0 === strpos( $tnstack_key, 'utm_' ) ) {
			continue;
		}
		if (
			in_array( $tnstack_key, $tnstack_dynamic, true ) ||
			0 === strpos( $tnstack_key, 'filter_' ) ||
			0 === strpos( $tnstack_key, 'query_type_' ) ||
			0 === strpos( $tnstack_key, 'attribute_' ) ||
			0 === strpos( $tnstack_key, 'pa_' )
		) {
			return;
		}
		if ( in_array( $tnstack_key, array( 'paged', 'page' ), true ) ) {
			$tnstack_page = max( 1, abs( (int) $tnstack_value ) );
			if ( $tnstack_page > 1 ) {
				$tnstack_cacheable[ $tnstack_key ] = (string) $tnstack_page;
			}
		}
	}
}

$tnstack_uri = $tnstack_path;
if ( ! empty( $tnstack_cacheable ) ) {
	ksort( $tnstack_cacheable );
	$tnstack_uri .= '?' . http_build_query( $tnstack_cacheable, '', '&', PHP_QUERY_RFC3986 );
}

$tnstack_host = strtolower( trim( (string) ( $_SERVER['HTTP_HOST'] ?? '' ) ) );
if ( '' === $tnstack_host || ! preg_match( '/^[a-z0-9.-]+(?::[0-9]+)?$/i', $tnstack_host ) ) {
	return;
}

$tnstack_hash = md5( $tnstack_host . $tnstack_uri );
$tnstack_root = WP_CONTENT_DIR . '/cache/html';
$tnstack_file = $tnstack_root . '/' . substr( $tnstack_hash, 0, 2 ) . '/' . $tnstack_hash . '.html';
if ( ! is_readable( $tnstack_file ) ) {
	$tnstack_legacy = $tnstack_root . '/' . $tnstack_hash . '.html';
	$tnstack_file   = is_readable( $tnstack_legacy ) ? $tnstack_legacy : $tnstack_file;
}

$tnstack_ttl = max( 300, min( 604800, abs( (int) ( $tnstack_config['ttl'] ?? 604800 ) ) ) );
if ( ! is_readable( $tnstack_file ) || ( time() - (int) filemtime( $tnstack_file ) ) >= $tnstack_ttl ) {
	return;
}

if ( ! headers_sent() ) {
	header( 'Content-Type: text/html; charset=UTF-8' );
	header( 'X-Page-Cache: HIT' );
	header( 'X-Page-Cache-Engine: advanced-cache' );
	header( 'Cache-Control: public, max-age=' . $tnstack_ttl . ', stale-while-revalidate=300' );
}

readfile( $tnstack_file );
exit;
