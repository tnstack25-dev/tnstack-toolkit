<?php
/**
 * Theme performance optimizations.
 *
 * @package TNStackCore
 */

defined( 'ABSPATH' ) || exit;

require_once tnstack_core_path( 'inc/core/performance/page-cache.php' );
tnstack_core_page_cache_register_hooks();

require_once tnstack_core_path( 'inc/core/performance/settings.php' );
require_once tnstack_core_path( 'inc/core/performance/admin.php' );
require_once tnstack_core_path( 'inc/core/performance/security.php' );
require_once tnstack_core_path( 'inc/core/performance/dashboard.php' );

add_action( 'init', 'tnstack_core_performance_disable_bloat', 1 );
add_action( 'wp_enqueue_scripts', 'tnstack_core_performance_optimize_assets', 999 );
add_filter( 'script_loader_tag', 'tnstack_core_performance_defer_scripts', 10, 3 );
add_filter( 'style_loader_tag', 'tnstack_core_performance_optimize_styles', 10, 4 );
add_filter( 'wp_resource_hints', 'tnstack_core_performance_resource_hints', 10, 2 );
add_filter( 'wp_get_attachment_image_attributes', 'tnstack_core_performance_image_attrs', 10, 3 );
add_filter( 'heartbeat_settings', 'tnstack_core_performance_heartbeat_settings' );
add_filter( 'wp_revisions_to_keep', 'tnstack_core_performance_limit_revisions', 10, 2 );

/**
 * Remove low-value core features on the frontend.
 */
function tnstack_core_performance_disable_bloat() {
	if ( is_admin() ) {
		return;
	}

	if ( tnstack_core_opt_enabled( 'core', 'disable_emoji' ) ) {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		add_filter( 'emoji_svg_url', '__return_false' );
	}

	if ( tnstack_core_opt_enabled( 'core', 'disable_embeds' ) ) {
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		wp_deregister_script( 'wp-embed' );
	}

	if ( tnstack_core_opt_enabled( 'core', 'disable_head_links' ) ) {
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
	}

	if ( tnstack_core_opt_enabled( 'core', 'disable_xmlrpc' ) ) {
		add_filter( 'xmlrpc_enabled', '__return_false' );
	}

	if ( tnstack_core_opt_enabled( 'core', 'disable_heartbeat_frontend' ) && ! is_user_logged_in() ) {
		wp_deregister_script( 'heartbeat' );
	}
}

/**
 * Dequeue scripts and styles that are not needed on most pages.
 */
function tnstack_core_performance_optimize_assets() {
	if ( is_admin() ) {
		return;
	}

	if ( tnstack_core_opt_enabled( 'core', 'dequeue_dashicons' ) && ! is_user_logged_in() ) {
		wp_dequeue_style( 'dashicons' );
	}

	if ( tnstack_core_opt_enabled( 'core', 'dequeue_block_styles' ) ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'classic-theme-styles' );
		wp_dequeue_style( 'global-styles' );
	}

	if ( tnstack_core_feature_enabled( 'woocommerce' ) && tnstack_core_opt_enabled( 'core', 'dequeue_woocommerce_assets' ) && class_exists( 'WooCommerce' ) && ! tnstack_core_is_woocommerce_view() ) {
		wp_dequeue_style( 'woocommerce-general' );
		wp_dequeue_style( 'woocommerce-layout' );
		wp_dequeue_style( 'woocommerce-smallscreen' );
		wp_dequeue_script( 'woocommerce' );
		wp_dequeue_script( 'wc-cart-fragments' );
		wp_dequeue_script( 'jquery-blockui' );
		wp_dequeue_script( 'js-cookie' );
	}

	if ( tnstack_core_opt_enabled( 'core', 'dequeue_cf7_assets' ) && ! tnstack_core_content_has( 'contact-form-7', null ) ) {
		wp_dequeue_style( 'contact-form-7' );
		wp_dequeue_script( 'contact-form-7' );
		wp_dequeue_script( 'swv' );
	}
}

/**
 * @return bool
 */
function tnstack_core_is_woocommerce_view() {
	if ( ! tnstack_core_feature_enabled( 'woocommerce' ) || ! function_exists( 'is_woocommerce' ) ) {
		return false;
	}

	return is_woocommerce() || is_cart() || is_checkout() || is_account_page();
}

/**
 * Defer non-critical JavaScript.
 *
 * @param string $tag    Script tag.
 * @param string $handle Script handle.
 * @param string $src    Script source.
 */
function tnstack_core_performance_defer_scripts( $tag, $handle, $src ) {
	if ( is_admin() || ! tnstack_core_opt_enabled( 'core', 'defer_scripts' ) ) {
		return $tag;
	}

	$exclude = apply_filters(
		'tnstack_core_defer_script_exclusions',
		array( 'jquery', 'jquery-core', 'jquery-migrate', 'customize-preview' )
	);

	if ( in_array( $handle, $exclude, true ) ) {
		return $tag;
	}

	global $wp_scripts;
	$registered = $wp_scripts instanceof WP_Scripts && isset( $wp_scripts->registered[ $handle ] )
		? $wp_scripts->registered[ $handle ]
		: null;
	$extra = $registered && is_array( $registered->extra ) ? $registered->extra : array();

	// Inline code attached to a handle may depend on immediate execution.
	if ( ! empty( $extra['before'] ) || ! empty( $extra['after'] ) ) {
		return $tag;
	}

	if ( false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, ' async' ) || false !== strpos( $tag, 'type="module"' ) ) {
		return $tag;
	}

	return str_replace( ' src', ' defer src', $tag );
}

/**
 * Load non-critical CSS asynchronously.
 *
 * @param string $html   Link tag.
 * @param string $handle Style handle.
 * @param string $href   Style URL.
 * @param string $media  Media attribute.
 */
function tnstack_core_performance_optimize_styles( $html, $handle, $href, $media ) {
	if ( is_admin() || ! tnstack_core_opt_enabled( 'core', 'async_styles' ) ) {
		return $html;
	}

	$async_handles = array(
		'tnstack-core-pricing-grid',
		'slim-catalog',
		'contact-form-7',
	);

	if ( ! in_array( $handle, $async_handles, true ) ) {
		return $html;
	}

	$html = str_replace( "media='all'", "media='print' onload=\"this.media='all'\"", $html );
	$html = str_replace( 'media="all"', 'media="print" onload="this.media=\'all\'"', $html );

	return $html . '<noscript><link rel="stylesheet" href="' . esc_url( $href ) . '"></noscript>';
}

/**
 * Add connection hints only when Google Fonts is actually enqueued.
 *
 * @param array<int, string|array<string, string>> $urls          Hint URLs.
 * @param string                                   $relation_type Hint relation type.
 * @return array<int, string|array<string, string>>
 */
function tnstack_core_performance_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' !== $relation_type || ! tnstack_core_opt_enabled( 'core', 'resource_hints' ) ) {
		return $urls;
	}

	global $wp_styles;

	if ( ! $wp_styles instanceof WP_Styles ) {
		return $urls;
	}

	$uses_google_fonts = false;
	foreach ( $wp_styles->queue as $handle ) {
		if ( empty( $wp_styles->registered[ $handle ]->src ) ) {
			continue;
		}

		$src = (string) $wp_styles->registered[ $handle ]->src;
		if ( false !== strpos( $src, 'fonts.googleapis.com' ) || false !== strpos( $src, 'fonts.gstatic.com' ) ) {
			$uses_google_fonts = true;
			break;
		}
	}

	if ( ! $uses_google_fonts ) {
		return $urls;
	}

	$origins = array(
		'https://fonts.googleapis.com' => false,
		'https://fonts.gstatic.com'    => true,
	);

	foreach ( $urls as $url ) {
		$href = is_array( $url ) ? ( $url['href'] ?? '' ) : $url;
		if ( array_key_exists( $href, $origins ) ) {
			unset( $origins[ $href ] );
		}
	}

	foreach ( $origins as $href => $crossorigin ) {
		$urls[] = $crossorigin
			? array( 'href' => $href, 'crossorigin' => 'anonymous' )
			: $href;
	}

	return $urls;
}

/**
 * Improve image loading behavior.
 *
 * @param array<string, string> $attr       Image attributes.
 * @param WP_Post               $attachment Attachment post.
 * @param string|array          $size       Image size.
 * @return array<string, string>
 */
function tnstack_core_performance_image_attrs( $attr, $attachment, $size ) {
	if ( is_admin() || ! tnstack_core_opt_enabled( 'core', 'lazy_load_images' ) ) {
		return $attr;
	}

	if ( empty( $attr['decoding'] ) ) {
		$attr['decoding'] = 'async';
	}

	// Modern WordPress applies context-aware lazy loading and LCP priority.
	if ( function_exists( 'wp_get_loading_optimization_attributes' ) ) {
		return $attr;
	}

	static $first_content_image = true;

	if ( $first_content_image ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
		$first_content_image   = false;
	} elseif ( empty( $attr['loading'] ) ) {
		$attr['loading'] = 'lazy';
	}

	return $attr;
}

/**
 * @param array<string, mixed> $settings Heartbeat settings.
 * @return array<string, mixed>
 */
function tnstack_core_performance_heartbeat_settings( $settings ) {
	$interval = (int) tnstack_core_optimization_settings()['core']['heartbeat_interval'];
	$settings['interval'] = is_admin() ? $interval : min( $interval, 60 );
	return $settings;
}

/**
 * @param int     $num  Number of revisions to keep.
 * @param WP_Post $post Post object.
 * @return int
 */
function tnstack_core_performance_limit_revisions( $num, $post ) {
	if ( ! tnstack_core_opt_enabled( 'core', 'limit_post_revisions' ) ) {
		return $num;
	}

	return (int) tnstack_core_optimization_settings()['core']['revisions_to_keep'];
}
