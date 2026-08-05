<?php
/**
 * Lightweight application firewall for WordPress requests.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_Core_WAF {

	const MAX_LOG_BYTES = 1048576;

	/**
	 * Inspect the current request and block high-confidence attack patterns.
	 */
	public static function inspect_request() {
		if ( ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}

		$method = strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) );
		$uri    = (string) wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' );
		$path   = (string) wp_parse_url( $uri, PHP_URL_PATH );

		if ( self::is_allowlisted( $path ) ) {
			return;
		}

		$raw_query = (string) wp_unslash( $_SERVER['QUERY_STRING'] ?? '' );
		$target    = strtolower( substr( $uri . ' ' . $raw_query, 0, 16384 ) );
		$decoded   = strtolower( rawurldecode( rawurldecode( $target ) ) );
		$rule      = self::match_rule( $method, $target . ' ' . $decoded );

		if ( ! $rule ) {
			return;
		}

		self::log_event( $rule, $method, $path, array_keys( $_GET ) );

		$settings = tnstack_core_optimization_settings()['security'];
		if ( 'monitor' === ( $settings['waf_mode'] ?? 'block' ) ) {
			return;
		}

		if ( ! headers_sent() ) {
			status_header( 403 );
			nocache_headers();
			header( 'Content-Type: text/plain; charset=UTF-8' );
			header( 'X-TNStack-WAF: blocked' );
		}

		exit( 'Request blocked.' );
	}

	/**
	 * @param string $method HTTP method.
	 * @param string $input  Normalized request input.
	 * @return string|false
	 */
	private static function match_rule( $method, $input ) {
		if ( in_array( $method, array( 'TRACE', 'TRACK', 'CONNECT' ), true ) ) {
			return 'protocol-method';
		}

		$rules = array(
			'path-traversal' => '/(?:\.\.\/|\.\.\\\\|%00)/i',
			'sql-injection'  => '/(?:\bunion(?:\s|\/\*.*?\*\/)+select\b|\b(?:sleep|benchmark)\s*\(|\binformation_schema\b)/i',
			'xss-injection'  => '/(?:<\s*script\b|javascript\s*:|on(?:error|load|mouseover)\s*=)/i',
			'php-injection'  => '/(?:php:\/\/(?:input|filter)|(?:base64_decode|gzinflate|shell_exec|passthru|proc_open)\s*\()/i',
			'sensitive-probe' => '#(?:/\.git(?:/|$)|/\.svn(?:/|$)|/vendor/phpunit/|/wp-config\.php|/\.env(?:[/?\s.]|$)|/debug\.log)#i',
		);

		foreach ( $rules as $id => $pattern ) {
			if ( preg_match( $pattern, $input ) ) {
				return $id;
			}
		}

		return false;
	}

	/**
	 * @param string $path Request path.
	 * @return bool
	 */
	private static function is_allowlisted( $path ) {
		$settings = tnstack_core_optimization_settings()['security'];
		$raw      = (string) ( $settings['waf_allowlist'] ?? '' );
		$entries  = preg_split( '/[\r\n,]+/', $raw );

		foreach ( array_filter( array_map( 'trim', (array) $entries ) ) as $prefix ) {
			$prefix = '/' . ltrim( $prefix, '/' );
			if ( 0 === strpos( $path, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string   $rule       Matched rule ID.
	 * @param string   $method     HTTP method.
	 * @param string   $path       Request path.
	 * @param string[] $query_keys Query parameter names only.
	 */
	private static function log_event( $rule, $method, $path, $query_keys ) {
		$file = self::log_file();
		if ( ! $file ) {
			return;
		}

		if ( is_file( $file ) && filesize( $file ) >= self::MAX_LOG_BYTES ) {
			$archive = dirname( $file ) . '/waf-events.1.php';
			if ( is_file( $archive ) ) {
				wp_delete_file( $archive );
			}
			rename( $file, $archive );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $file, "<?php exit; ?>\n", LOCK_EX );
		}

		$event = array(
			'time'       => time(),
			'rule'       => sanitize_key( $rule ),
			'method'     => sanitize_key( $method ),
			'path'       => substr( sanitize_text_field( $path ), 0, 300 ),
			'query_keys' => array_slice( array_map( 'sanitize_key', $query_keys ), 0, 20 ),
			'ip'         => tnstack_core_security_client_ip(),
		);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $file, wp_json_encode( $event ) . PHP_EOL, FILE_APPEND | LOCK_EX );
	}

	/**
	 * @return string|false
	 */
	private static function log_file() {
		$dir = WP_CONTENT_DIR . '/tnstack-security';
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		$deny_file = $dir . '/.htaccess';
		if ( ! is_file( $deny_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $deny_file, "Require all denied\nDeny from all\n", LOCK_EX );
		}

		$index_file = $dir . '/index.php';
		if ( ! is_file( $index_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $index_file, "<?php\n// Silence is golden.\n", LOCK_EX );
		}

		$file = $dir . '/waf-events.php';
		if ( ! is_file( $file ) ) {
			// A PHP guard protects logs even when the web server ignores .htaccess.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $file, "<?php exit; ?>\n", LOCK_EX );
		}

		return $file;
	}

	/**
	 * Return recent WAF events without exposing query values.
	 *
	 * @param int $limit Maximum events.
	 * @return array<int, array<string, mixed>>
	 */
	public static function recent_events( $limit = 20 ) {
		$file = WP_CONTENT_DIR . '/tnstack-security/waf-events.php';
		if ( ! is_readable( $file ) ) {
			return array();
		}

		$lines  = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		$events = array();
		foreach ( array_slice( is_array( $lines ) ? $lines : array(), -absint( $limit ) ) as $line ) {
			$event = json_decode( $line, true );
			if ( is_array( $event ) ) {
				$events[] = $event;
			}
		}

		return array_reverse( $events );
	}
}
