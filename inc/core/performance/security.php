<?php
/**
 * Production-grade WordPress hardening.
 *
 * @package TNStackCore
 */

defined( 'ABSPATH' ) || exit;

require_once tnstack_core_path( '/inc/core/performance/security-login.php' );

if ( did_action( 'plugins_loaded' ) ) {
	tnstack_core_security_early_blocks();
} else {
	add_action( 'plugins_loaded', 'tnstack_core_security_early_blocks', 0 );
}
add_action( 'init', 'tnstack_core_security_boot', 1 );

/**
 * Block high-risk endpoints before most WordPress logic runs.
 */
function tnstack_core_security_early_blocks() {
	if ( tnstack_core_opt_enabled( 'security', 'block_xmlrpc' ) && tnstack_core_security_is_xmlrpc_request() ) {
		tnstack_core_security_deny_request( 403 );
	}

	if ( tnstack_core_opt_enabled( 'security', 'block_sensitive_files' ) ) {
		tnstack_core_security_block_sensitive_files();
	}

	if ( tnstack_core_opt_enabled( 'security', 'block_suspicious_requests' ) ) {
		tnstack_core_security_block_suspicious_requests();
	}
}

/**
 * Register security hooks based on settings.
 */
function tnstack_core_security_boot() {
	if ( tnstack_core_opt_enabled( 'security', 'remove_generator' ) ) {
		add_filter( 'the_generator', '__return_empty_string' );
		remove_action( 'wp_head', 'wp_generator' );
	}

	if ( tnstack_core_opt_enabled( 'security', 'block_author_scan' ) ) {
		add_action( 'template_redirect', 'tnstack_core_security_block_author_scan', 1 );
	}

	if ( tnstack_core_opt_enabled( 'security', 'disable_rest_users' ) ) {
		add_filter( 'rest_endpoints', 'tnstack_core_security_restrict_rest_users' );
	}

	if ( tnstack_core_opt_enabled( 'security', 'restrict_rest_api' ) ) {
		add_filter( 'rest_authentication_errors', 'tnstack_core_security_restrict_rest_api' );
	}

	if ( tnstack_core_opt_enabled( 'security', 'security_headers' ) || tnstack_core_opt_enabled( 'security', 'hsts_header' ) ) {
		add_action( 'send_headers', 'tnstack_core_security_send_headers' );
	}

	if ( tnstack_core_opt_enabled( 'security', 'hide_login_errors' ) ) {
		add_filter( 'login_errors', 'tnstack_core_security_hide_login_errors' );
	}

	if ( tnstack_core_opt_enabled( 'security', 'disable_file_editor' ) ) {
		add_filter( 'file_mod_allowed', 'tnstack_core_security_disable_file_mods', 10, 2 );
		add_filter( 'map_meta_cap', 'tnstack_core_security_block_file_caps', 10, 4 );
	}

	if ( tnstack_core_opt_enabled( 'security', 'disable_application_passwords' ) ) {
		add_filter( 'wp_is_application_passwords_available', '__return_false' );
	}

	if ( tnstack_core_opt_enabled( 'security', 'disable_pingbacks' ) ) {
		add_filter( 'xmlrpc_methods', 'tnstack_core_security_disable_pingbacks' );
	}

	if ( tnstack_core_opt_enabled( 'security', 'force_ssl_admin' ) && tnstack_core_security_should_force_ssl() ) {
		add_action( 'admin_init', 'tnstack_core_security_force_ssl_admin', 1 );
	}

	if ( tnstack_core_opt_enabled( 'security', 'secure_session_cookies' ) && is_ssl() ) {
		add_filter( 'secure_auth_cookie', '__return_true' );
		add_filter( 'secure_logged_in_cookie', '__return_true' );
	}

	add_filter( 'wp_headers', 'tnstack_core_security_remove_sensitive_headers' );
}

/**
 * @return bool
 */
function tnstack_core_security_should_force_ssl() {
	if ( defined( 'WP_ENVIRONMENT_TYPE' ) && 'local' === WP_ENVIRONMENT_TYPE ) {
		return false;
	}

	return true;
}

/**
 * @return bool
 */
function tnstack_core_security_is_xmlrpc_request() {
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		return true;
	}

	$uri = isset( $_SERVER['REQUEST_URI'] ) ? strtolower( (string) wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

	return false !== strpos( $uri, 'xmlrpc.php' );
}

/**
 * @param int $status HTTP status code.
 */
function tnstack_core_security_deny_request( $status = 403 ) {
	if ( ! headers_sent() ) {
		status_header( $status );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=UTF-8' );
	}

	exit;
}

/**
 * Block direct access to common leak files.
 */
function tnstack_core_security_block_sensitive_files() {
	$path = strtolower( (string) wp_parse_url( tnstack_core_security_request_uri(), PHP_URL_PATH ) );
	$blocked = array(
		'/readme.html',
		'/license.txt',
		'/wp-config.php',
		'/wp-config-sample.php',
		'/.env',
		'/debug.log',
		'/composer.json',
		'/composer.lock',
	);

	foreach ( $blocked as $file ) {
		if ( $path === $file || substr( $path, -strlen( $file ) ) === $file ) {
			tnstack_core_security_deny_request( 404 );
		}
	}
}

/**
 * Block common injection and probing patterns.
 */
function tnstack_core_security_block_suspicious_requests() {
	$haystack = strtolower( tnstack_core_security_request_uri() . ' ' . tnstack_core_security_query_string() );
	$patterns = array(
		'/\bunion[\s\/\*]+select\b/',
		'/\bselect[\s\/\*].*\bfrom\b/',
		'/<script\b/',
		'/javascript:/',
		'/base64_decode\s*\(/',
		'/eval\s*\(/',
		'/\.{2}\//',
		'/wp-config\.php/',
		'/\.env\b/',
		'/php:\/\/input/',
		'/shell_exec\s*\(/',
	);

	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $haystack ) ) {
			tnstack_core_security_deny_request( 403 );
		}
	}
}

/**
 * @return string
 */
function tnstack_core_security_request_uri() {
	return isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
}

/**
 * @return string
 */
function tnstack_core_security_query_string() {
	return isset( $_SERVER['QUERY_STRING'] ) ? (string) wp_unslash( $_SERVER['QUERY_STRING'] ) : '';
}

/**
 * Block author enumeration via query string.
 */
function tnstack_core_security_block_author_scan() {
	if ( is_admin() || ! isset( $_GET['author'] ) ) {
		return;
	}

	wp_safe_redirect( home_url( '/' ), 301 );
	exit;
}

/**
 * @param array<string, mixed> $endpoints REST endpoints.
 * @return array<string, mixed>
 */
function tnstack_core_security_restrict_rest_users( $endpoints ) {
	if ( current_user_can( 'list_users' ) ) {
		return $endpoints;
	}

	unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );

	return $endpoints;
}

/**
 * @param WP_Error|null|true $result Authentication result.
 * @return WP_Error|null|true
 */
function tnstack_core_security_restrict_rest_api( $result ) {
	if ( ! empty( $result ) || is_user_logged_in() ) {
		return $result;
	}

	$route  = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '';
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';

	if ( '' === $route && ! empty( $_GET['rest_route'] ) ) {
		$route = '/' . ltrim( (string) wp_unslash( $_GET['rest_route'] ), '/' );
	}

	$allowed_get_prefixes = array(
		'/wp/v2/posts',
		'/wp/v2/pages',
		'/wp/v2/categories',
		'/wp/v2/tags',
		'/wp/v2/search',
		'/oembed/',
	);

	if ( tnstack_core_opt_enabled( 'security', 'allow_woocommerce_rest' ) && class_exists( 'WooCommerce' ) ) {
		$allowed_get_prefixes[] = '/wc/store/';
		$allowed_get_prefixes[] = '/wc/v3/';
	}

	if ( 'GET' === $method || 'HEAD' === $method ) {
		foreach ( $allowed_get_prefixes as $prefix ) {
			if ( 0 === strpos( $route, $prefix ) ) {
				return $result;
			}
		}
	}

	return new WP_Error(
		'rest_forbidden',
		__( 'REST API bị hạn chế cho khách chưa đăng nhập.', 'tnstack-core' ),
		array( 'status' => 401 )
	);
}

/**
 * Send HTTP security headers.
 */
function tnstack_core_security_send_headers() {
	if ( headers_sent() ) {
		return;
	}

	if ( tnstack_core_opt_enabled( 'security', 'security_headers' ) ) {
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );
		header( 'X-XSS-Protection: 0' );
	}

	if ( tnstack_core_opt_enabled( 'security', 'hsts_header' ) && is_ssl() && tnstack_core_security_should_force_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
	}
}

/**
 * @param array<string, string> $headers Response headers.
 * @return array<string, string>
 */
function tnstack_core_security_remove_sensitive_headers( $headers ) {
	unset( $headers['X-Powered-By'] );

	return $headers;
}

/**
 * Force HTTPS for admin requests.
 */
function tnstack_core_security_force_ssl_admin() {
	if ( is_ssl() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	wp_safe_redirect( 'https://' . tnstack_core_security_current_host() . tnstack_core_security_request_uri(), 301 );
	exit;
}

/**
 * @return string
 */
function tnstack_core_security_current_host() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : wp_parse_url( home_url(), PHP_URL_HOST );
	return is_string( $host ) ? $host : '';
}

/**
 * @param string $error Login error message.
 * @return string
 */
function tnstack_core_security_hide_login_errors( $error ) {
	return __( 'Thông tin đăng nhập không chính xác.', 'tnstack-core' );
}

/**
 * @param bool   $allowed Whether file modifications are allowed.
 * @param string $context Modification context.
 * @return bool
 */
function tnstack_core_security_disable_file_mods( $allowed, $context ) {
	if ( in_array( $context, array( 'edit_themes', 'edit_plugins' ), true ) ) {
		return false;
	}

	return $allowed;
}

/**
 * @param array<string> $caps    Required capabilities.
 * @param string        $cap     Capability name.
 * @param int           $user_id User ID.
 * @param array<mixed>  $args    Extra arguments.
 * @return array<string>
 */
function tnstack_core_security_block_file_caps( $caps, $cap, $user_id, $args ) {
	if ( in_array( $cap, array( 'edit_plugins', 'edit_themes' ), true ) ) {
		return array( 'do_not_allow' );
	}

	return $caps;
}

/**
 * @param array<string, callable> $methods XML-RPC methods.
 * @return array<string, callable>
 */
function tnstack_core_security_disable_pingbacks( $methods ) {
	unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );

	return $methods;
}

/**
 * @return string
 */
function tnstack_core_security_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : 'unknown';
}
