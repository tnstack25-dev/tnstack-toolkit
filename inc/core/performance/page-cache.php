<?php
/**
 * Full-page HTML cache for anonymous visitors.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Template_Performance_Cache', false ) ) {
	return;
}

final class Template_Performance_Cache {

	const OPTION       = 'template_performance_settings';
	const STATS_OPTION = 'template_performance_cache_stats';
	const QUEUE_KEY    = 'template_performance_purge_queue';
	const TTL          = 604800;
	const VERSION      = '1.0.0';

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'enable_page_cache' => 1,
			'cache_ttl'         => self::TTL,
			'purge_mode'        => 'selective',
			'max_cache_files'   => 5000,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function settings() {
		$settings = get_option( self::OPTION, array() );
		$settings = wp_parse_args( is_array( $settings ) ? $settings : array(), self::defaults() );

		$settings['enable_page_cache'] = ! empty( $settings['enable_page_cache'] ) ? 1 : 0;
		$settings['cache_ttl']         = max( 300, min( WEEK_IN_SECONDS, absint( $settings['cache_ttl'] ) ) );
		$settings['purge_mode']        = in_array( $settings['purge_mode'], array( 'selective', 'full' ), true )
			? $settings['purge_mode']
			: 'selective';
		$settings['max_cache_files']   = max( 500, min( 50000, absint( $settings['max_cache_files'] ) ) );

		return $settings;
	}

	/**
	 * @return bool
	 */
	public static function is_enabled() {
		if ( ! self::performance_module_active() ) {
			return false;
		}

		if ( ! function_exists( 'get_option' ) ) {
			return true;
		}

		return ! empty( self::settings()['enable_page_cache'] );
	}

	/**
	 * @return bool
	 */
	private static function performance_module_active() {
		if ( function_exists( 'tnstack_core_module_enabled' ) ) {
			return tnstack_core_module_enabled( 'performance' );
		}

		if ( ! function_exists( 'get_option' ) ) {
			return true;
		}

		$stored = get_option( 'tnstack_toolkit_module_settings', array() );

		if ( ! is_array( $stored ) || empty( $stored['modules'] ) ) {
			return true;
		}

		return ! empty( $stored['modules']['performance'] );
	}

	/**
	 * @return int
	 */
	public static function get_ttl() {
		if ( ! function_exists( 'get_option' ) ) {
			return self::TTL;
		}

		return (int) self::settings()['cache_ttl'];
	}

	/**
	 * @param array<string, mixed> $settings Settings payload.
	 * @return array<string, mixed>
	 */
	public static function update_settings( $settings ) {
		$current = self::settings();
		$updated = array(
			'enable_page_cache' => ! empty( $settings['enable_page_cache'] ) ? 1 : 0,
			'cache_ttl'         => isset( $settings['cache_ttl'] ) ? max( 300, min( WEEK_IN_SECONDS, absint( $settings['cache_ttl'] ) ) ) : $current['cache_ttl'],
			'purge_mode'        => isset( $settings['purge_mode'] ) && in_array( $settings['purge_mode'], array( 'selective', 'full' ), true )
				? $settings['purge_mode']
				: $current['purge_mode'],
			'max_cache_files'   => isset( $settings['max_cache_files'] )
				? max( 500, min( 50000, absint( $settings['max_cache_files'] ) ) )
				: $current['max_cache_files'],
		);

		update_option( self::OPTION, $updated, false );

		return $updated;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_stats() {
		$meta = self::get_stats_meta();

		if ( empty( $meta['last_scan'] ) || ( time() - (int) $meta['last_scan'] ) > HOUR_IN_SECONDS ) {
			$meta = self::rebuild_stats_meta();
		}

		return array_merge(
			$meta,
			array(
				'enabled'    => self::is_enabled(),
				'ttl'        => self::get_ttl(),
				'purge_mode' => self::settings()['purge_mode'],
				'directory'  => self::cache_dir(),
				'version'    => self::VERSION,
			)
		);
	}

	/**
	 * Attempt to serve a cached page before WordPress fully boots.
	 */
	public static function try_early_serve() {
		if ( ! self::is_enabled() || ! self::can_serve_early() ) {
			return;
		}

		$uri = self::normalize_request_uri();

		if ( null === $uri ) {
			return;
		}

		$file = self::resolve_cache_file( self::request_host(), $uri );

		if ( self::is_fresh( $file ) ) {
			self::send_cached( $file );
		}
	}

	/**
	 * Boot cache storage after WordPress core is available.
	 */
	public static function boot() {
		if ( ! self::is_enabled() || self::should_bypass() ) {
			return;
		}

		$uri = self::normalize_request_uri();

		if ( null === $uri ) {
			return;
		}

		$file = self::resolve_cache_file( self::request_host(), $uri );

		if ( self::is_fresh( $file ) ) {
			self::send_cached( $file );
		}

		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), 0 );
	}

	/**
	 * Early eligibility checks that do not rely on pluggable functions.
	 *
	 * @return bool
	 */
	private static function can_serve_early() {
		if ( 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
			return false;
		}

		if ( self::has_auth_cookie() ) {
			return false;
		}

		if ( ! empty( $_GET['uxb_iframe'] ) || ! empty( $_GET['preview'] ) || ! empty( $_GET['customize_changeset_uuid'] ) ) {
			return false;
		}

		if ( ! empty( $_COOKIE['woocommerce_items_in_cart'] ) ) {
			return false;
		}

		foreach ( array_keys( $_COOKIE ) as $cookie_name ) {
			if ( 0 === strpos( $cookie_name, 'wp_woocommerce_session_' ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @return bool
	 */
	private static function should_bypass() {
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return true;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return true;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}

		if ( ! self::can_serve_early() ) {
			return true;
		}

		if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
			return true;
		}

		if ( did_action( 'wp' ) && function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
			return true;
		}

		if ( null === self::normalize_request_uri() ) {
			return true;
		}

		return (bool) apply_filters( 'template_performance_cache_bypass', false );
	}

	/**
	 * @return bool
	 */
	private static function has_auth_cookie() {
		foreach ( array_keys( $_COOKIE ) as $cookie_name ) {
			if ( 0 === strpos( $cookie_name, 'wordpress_logged_in_' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return string
	 */
	private static function cache_dir() {
		return WP_CONTENT_DIR . '/cache/html';
	}

	/**
	 * @return string
	 */
	private static function request_host() {
		if ( function_exists( 'sanitize_text_field' ) && function_exists( 'wp_unslash' ) ) {
			return strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? 'localhost' ) ) );
		}

		return strtolower( (string) ( $_SERVER['HTTP_HOST'] ?? 'localhost' ) );
	}

	/**
	 * Normalize the request URI for cache lookup/storage.
	 *
	 * Returns null when the request should not be cached.
	 *
	 * @return string|null
	 */
	private static function normalize_request_uri() {
		if ( function_exists( 'wp_unslash' ) ) {
			$uri = wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' );
		} else {
			$uri = (string) ( $_SERVER['REQUEST_URI'] ?? '/' );
		}

		$parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $uri ) : parse_url( $uri );
		$path  = isset( $parts['path'] ) && '' !== $parts['path'] ? $parts['path'] : '/';
		$query = $parts['query'] ?? '';

		if ( '' === $query ) {
			return $path;
		}

		parse_str( $query, $params );

		if ( ! is_array( $params ) || empty( $params ) ) {
			return $path;
		}

		$cacheable = array();

		foreach ( $params as $key => $value ) {
			$key = (string) $key;

			if ( self::is_tracking_param( $key ) ) {
				continue;
			}

			if ( self::is_dynamic_param( $key ) ) {
				return null;
			}

			if ( in_array( $key, array( 'paged', 'page' ), true ) ) {
				$page = max( 1, absint( $value ) );
				if ( $page > 1 ) {
					$cacheable[ $key ] = (string) $page;
				}
			}
		}

		if ( empty( $cacheable ) ) {
			return $path;
		}

		ksort( $cacheable );

		return $path . '?' . http_build_query( $cacheable, '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * @param string $key Query parameter key.
	 * @return bool
	 */
	private static function is_tracking_param( $key ) {
		$tracking = array(
			'fbclid',
			'gclid',
			'gbraid',
			'wbraid',
			'msclkid',
			'mc_cid',
			'mc_eid',
			'_ga',
			'_gl',
			'ref',
			'referrer',
			'utm_source',
			'utm_medium',
			'utm_campaign',
			'utm_term',
			'utm_content',
			'utm_id',
		);

		if ( in_array( $key, $tracking, true ) ) {
			return true;
		}

		return 0 === strpos( $key, 'utm_' );
	}

	/**
	 * @param string $key Query parameter key.
	 * @return bool
	 */
	private static function is_dynamic_param( $key ) {
		$dynamic = array(
			's',
			'add-to-cart',
			'add_to_cart',
			'removed_item',
			'undo_item',
			'update_cart',
			'apply_coupon',
			'remove_coupon',
			'orderby',
			'order',
			'min_price',
			'max_price',
			'rating_filter',
			'stock_status',
			'on_sale',
			'featured',
			'product_tag',
			'product_cat',
			'filter_',
			'lang',
			'currency',
			'wc-ajax',
			'wc-api',
			'preview',
			'preview_id',
			'preview_nonce',
			'doing_wp_cron',
			'no_cache',
		);

		if ( in_array( $key, $dynamic, true ) ) {
			return true;
		}

		if ( 0 === strpos( $key, 'filter_' ) || 0 === strpos( $key, 'query_type_' ) || 0 === strpos( $key, 'attribute_' ) || 0 === strpos( $key, 'pa_' ) ) {
			return true;
		}

		return (bool) apply_filters( 'template_performance_cache_dynamic_param', false, $key );
	}

	/**
	 * @param string $host Request host.
	 * @param string $uri  Normalized request URI.
	 * @return string
	 */
	private static function cache_hash( $host, $uri ) {
		return md5( strtolower( $host ) . $uri );
	}

	/**
	 * @param string $host Request host.
	 * @param string $uri  Normalized request URI.
	 * @return string
	 */
	private static function cache_file_for( $host, $uri ) {
		$hash = self::cache_hash( $host, $uri );
		$dir  = self::cache_dir() . '/' . substr( $hash, 0, 2 );

		return $dir . '/' . $hash . '.html';
	}

	/**
	 * Resolve a cache file path, including legacy flat files.
	 *
	 * @param string $host Request host.
	 * @param string $uri  Normalized request URI.
	 * @return string
	 */
	private static function resolve_cache_file( $host, $uri ) {
		$sharded = self::cache_file_for( $host, $uri );

		if ( is_readable( $sharded ) ) {
			return $sharded;
		}

		$legacy = self::cache_dir() . '/' . self::cache_hash( $host, $uri ) . '.html';

		return is_readable( $legacy ) ? $legacy : $sharded;
	}

	/**
	 * @param string $file Cache file path.
	 * @return bool
	 */
	private static function is_fresh( $file ) {
		return is_readable( $file ) && ( time() - (int) filemtime( $file ) ) < self::get_ttl();
	}

	/**
	 * @param string $file Cache file path.
	 */
	private static function send_cached( $file ) {
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/html; charset=UTF-8' );
			header( 'X-Page-Cache: HIT' );
			header( 'Cache-Control: public, max-age=' . self::get_ttl() . ', stale-while-revalidate=300' );
		}

		readfile( $file );
		exit;
	}

	public static function start_buffer() {
		ob_start( array( __CLASS__, 'store_buffer' ) );
	}

	/**
	 * @param string $html Page HTML.
	 * @return string
	 */
	public static function store_buffer( $html ) {
		if ( self::should_bypass() ) {
			return $html;
		}

		$status = http_response_code();

		if ( 200 !== (int) $status || strlen( $html ) < 500 ) {
			return $html;
		}

		$uri = self::normalize_request_uri();

		if ( null === $uri ) {
			return $html;
		}

		$file = self::cache_file_for( self::request_host(), $uri );
		$dir  = dirname( $file );
		$file_existed = is_file( $file );
		$previous_size = $file_existed ? filesize( $file ) : 0;
		$previous_size = false !== $previous_size ? (int) $previous_size : 0;

		if ( function_exists( 'wp_mkdir_p' ) ) {
			wp_mkdir_p( $dir );
		} elseif ( ! is_dir( $dir ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
			mkdir( $dir, 0755, true );
		}

		self::enforce_cache_limit();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$written = file_put_contents( $file, $html, LOCK_EX );

		if ( false !== $written ) {
			self::adjust_stats_meta(
				$file_existed ? 0 : 1,
				(int) $written - $previous_size,
				(int) filemtime( $file )
			);
		}

		if ( ! headers_sent() ) {
			header( 'X-Page-Cache: MISS' );
			header( 'Cache-Control: public, max-age=' . self::get_ttl() . ', stale-while-revalidate=300' );
		}

		return $html;
	}

	/**
	 * Keep cache size bounded for large catalogs.
	 */
	private static function enforce_cache_limit() {
		$meta  = self::get_stats_meta();
		$limit = (int) self::settings()['max_cache_files'];

		if ( (int) ( $meta['file_count'] ?? 0 ) < $limit ) {
			return;
		}

		$files = self::collect_cache_files();

		if ( count( $files ) < $limit ) {
			self::rebuild_stats_meta();
			return;
		}

		usort(
			$files,
			static function ( $a, $b ) {
				$a_mtime = filemtime( $a );
				$b_mtime = filemtime( $b );

				if ( false === $a_mtime || false === $b_mtime ) {
					return 0;
				}

				return $a_mtime <=> $b_mtime;
			}
		);

		$to_delete = max( 1, (int) floor( $limit * 0.1 ) );

		foreach ( array_slice( $files, 0, $to_delete ) as $file ) {
			self::delete_cache_file_path( $file );
		}
	}

	/**
	 * @return array<int, string>
	 */
	private static function collect_cache_files() {
		$files = array();
		$root  = self::cache_dir();

		if ( ! is_dir( $root ) ) {
			return $files;
		}

		$legacy = glob( $root . '/*.html' );
		if ( is_array( $legacy ) ) {
			$files = array_merge( $files, $legacy );
		}

		$shards = glob( $root . '/*', GLOB_ONLYDIR );
		if ( ! is_array( $shards ) ) {
			return $files;
		}

		foreach ( $shards as $shard ) {
			$shard_files = glob( $shard . '/*.html' );
			if ( is_array( $shard_files ) ) {
				$files = array_merge( $files, $shard_files );
			}
		}

		return $files;
	}

	/**
	 * @param string $file Cache file path.
	 * @return bool
	 */
	private static function delete_cache_file_path( $file ) {
		if ( ! is_file( $file ) ) {
			return false;
		}

		$size  = filesize( $file );
		$size  = false !== $size ? (int) $size : 0;
		$removed = function_exists( 'wp_delete_file' ) ? wp_delete_file( $file ) : unlink( $file );

		if ( $removed ) {
			self::adjust_stats_meta( -1, -$size, 0 );
		}

		return (bool) $removed;
	}

	/**
	 * @param string $host Request host.
	 * @param string $uri  Normalized URI.
	 * @return bool
	 */
	private static function delete_cache_file( $host, $uri ) {
		$paths = array(
			self::cache_file_for( $host, $uri ),
			self::cache_dir() . '/' . self::cache_hash( $host, $uri ) . '.html',
		);

		$deleted = false;

		foreach ( array_unique( $paths ) as $path ) {
			if ( self::delete_cache_file_path( $path ) ) {
				$deleted = true;
			}
		}

		return $deleted;
	}

	/**
	 * @param array<int, string> $uris Relative URIs.
	 * @return int
	 */
	public static function purge_uris( $uris ) {
		$host    = self::request_host();
		$deleted = 0;

		foreach ( array_unique( array_filter( (array) $uris ) ) as $uri ) {
			if ( self::delete_cache_file( $host, $uri ) ) {
				$deleted++;
			}

			for ( $page = 2; $page <= 5; $page++ ) {
				$paged_uri = $uri . ( false === strpos( $uri, '?' ) ? '?' : '&' ) . 'paged=' . $page;
				if ( self::delete_cache_file( $host, $paged_uri ) ) {
					$deleted++;
				}
			}
		}

		return $deleted;
	}

	/**
	 * @param string $url Absolute URL.
	 * @return string
	 */
	private static function url_to_uri( $url ) {
		$parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $url ) : parse_url( $url );
		$path  = $parts['path'] ?? '/';

		if ( empty( $parts['query'] ) ) {
			return $path;
		}

		parse_str( $parts['query'], $params );

		if ( ! is_array( $params ) ) {
			return $path;
		}

		$cacheable = array();

		foreach ( $params as $key => $value ) {
			if ( self::is_tracking_param( (string) $key ) ) {
				continue;
			}

			if ( in_array( $key, array( 'paged', 'page' ), true ) ) {
				$page = max( 1, absint( $value ) );
				if ( $page > 1 ) {
					$cacheable[ $key ] = (string) $page;
				}
			}
		}

		if ( empty( $cacheable ) ) {
			return $path;
		}

		ksort( $cacheable );

		return $path . '?' . http_build_query( $cacheable, '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * @param int $post_id Post ID.
	 * @return array<int, string>
	 */
	private static function collect_post_uris( $post_id ) {
		$post_id = absint( $post_id );
		$uris    = array( '/' );

		if ( ! $post_id || ! function_exists( 'get_post' ) ) {
			return $uris;
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return $uris;
		}

		$permalink = get_permalink( $post );

		if ( $permalink ) {
			$uris[] = self::url_to_uri( $permalink );
		}

		if ( function_exists( 'get_post_type_archive_link' ) ) {
			$archive = get_post_type_archive_link( $post->post_type );
			if ( $archive ) {
				$uris[] = self::url_to_uri( $archive );
			}
		}

		if ( function_exists( 'get_author_posts_url' ) && $post->post_author ) {
			$author = get_author_posts_url( (int) $post->post_author );
			if ( $author ) {
				$uris[] = self::url_to_uri( $author );
			}
		}

		$taxonomies = get_object_taxonomies( $post->post_type, 'objects' );

		foreach ( $taxonomies as $taxonomy ) {
			if ( empty( $taxonomy->public ) ) {
				continue;
			}

			$terms = get_the_terms( $post_id, $taxonomy->name );

			if ( ! is_array( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$link = get_term_link( $term );
				if ( ! is_wp_error( $link ) ) {
					$uris[] = self::url_to_uri( $link );
				}
			}
		}

		if ( 'page' === $post->post_type && function_exists( 'get_option' ) ) {
			$front_page = (int) get_option( 'page_on_front' );
			$posts_page = (int) get_option( 'page_for_posts' );

			if ( $front_page === $post_id || $posts_page === $post_id ) {
				$uris[] = '/';
			}
		}

		if ( 'product' === $post->post_type && function_exists( 'wc_get_page_id' ) ) {
			$shop_id = (int) wc_get_page_id( 'shop' );
			if ( $shop_id > 0 ) {
				$shop_link = get_permalink( $shop_id );
				if ( $shop_link ) {
					$uris[] = self::url_to_uri( $shop_link );
				}
			}
		}

		if ( function_exists( 'apply_filters' ) ) {
			$uris = apply_filters( 'template_performance_cache_post_uris', $uris, $post );
		}

		return array_values( array_unique( array_filter( $uris ) ) );
	}

	/**
	 * @param int $post_id Post ID.
	 */
	public static function purge_post_related( $post_id ) {
		self::purge_uris( self::collect_post_uris( $post_id ) );
	}

	/**
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy name.
	 */
	public static function purge_term_related( $term_id, $tt_id, $taxonomy = '' ) {
		$uris = array( '/' );

		if ( function_exists( 'get_term_link' ) && $taxonomy ) {
			$link = get_term_link( (int) $term_id, $taxonomy );
			if ( ! is_wp_error( $link ) ) {
				$uris[] = self::url_to_uri( $link );
			}
		}

		if ( 'product_cat' === $taxonomy && function_exists( 'wc_get_page_id' ) ) {
			$shop_id = (int) wc_get_page_id( 'shop' );
			if ( $shop_id > 0 ) {
				$shop_link = get_permalink( $shop_id );
				if ( $shop_link ) {
					$uris[] = self::url_to_uri( $shop_link );
				}
			}
		}

		self::purge_uris( $uris );
	}

	/**
	 * @return int Number of deleted cache files.
	 */
	public static function flush_all() {
		$files   = self::collect_cache_files();
		$deleted = 0;

		foreach ( $files as $file ) {
			if ( self::delete_cache_file_path( $file ) ) {
				$deleted++;
			}
		}

		self::reset_stats_meta();

		return $deleted;
	}

	/**
	 * Flush page cache and common WordPress caches.
	 *
	 * @return array<string, int>
	 */
	public static function flush_everything() {
		$results = array(
			'page_cache_files' => self::flush_all(),
		);

		if ( function_exists( 'wp_cache_flush' ) ) {
			$results['object_cache'] = wp_cache_flush() ? 1 : 0;
		}

		delete_transient( 'slim_catalog_settings_cache' );
		delete_transient( self::QUEUE_KEY );

		if ( class_exists( 'WPMA_Plugin' ) ) {
			delete_transient( WPMA_Plugin::INVENTORY_CACHE );
		}

		do_action( 'template_performance_cache_flushed', $results );

		return $results;
	}

	/**
	 * @param int $post_id Post ID.
	 */
	public static function purge_on_save( $post_id ) {
		if ( function_exists( 'wp_is_post_autosave' ) && wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( 'full' === self::settings()['purge_mode'] ) {
			self::flush_all();
			return;
		}

		if ( self::is_bulk_operation() ) {
			self::queue_deferred_purge( $post_id );
			return;
		}

		self::purge_post_related( $post_id );
	}

	/**
	 * @return bool
	 */
	private static function is_bulk_operation() {
		if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
			return true;
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return true;
		}

		if ( ! empty( $_REQUEST['bulk_edit'] ) ) {
			return true;
		}

		if ( ! empty( $_REQUEST['action'] ) ) {
			$action = function_exists( 'sanitize_key' ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : (string) $_REQUEST['action'];
			if ( in_array( $action, array( 'edit', 'trash', 'untrash', 'delete' ), true ) ) {
				return true;
			}
		}

		return (bool) apply_filters( 'template_performance_cache_bulk_operation', false );
	}

	/**
	 * @param int $post_id Post ID.
	 */
	private static function queue_deferred_purge( $post_id ) {
		$queue   = get_transient( self::QUEUE_KEY );
		$queue   = is_array( $queue ) ? $queue : array();
		$queue[] = absint( $post_id );
		$queue   = array_values( array_unique( array_filter( $queue ) ) );

		set_transient( self::QUEUE_KEY, $queue, HOUR_IN_SECONDS );

		if ( ! wp_next_scheduled( 'template_performance_process_purge_queue' ) ) {
			wp_schedule_single_event( time() + 30, 'template_performance_process_purge_queue' );
		}
	}

	/**
	 * Process deferred selective purges after bulk imports/edits.
	 */
	public static function process_purge_queue() {
		$queue = get_transient( self::QUEUE_KEY );

		if ( ! is_array( $queue ) || empty( $queue ) ) {
			return;
		}

		delete_transient( self::QUEUE_KEY );

		$uris = array( '/' );

		foreach ( $queue as $post_id ) {
			$uris = array_merge( $uris, self::collect_post_uris( (int) $post_id ) );
		}

		self::purge_uris( array_unique( $uris ) );
	}

	/**
	 * Remove expired cache files without flushing everything.
	 *
	 * @return int
	 */
	public static function cleanup_expired() {
		$ttl     = self::get_ttl();
		$deleted = 0;
		$now     = time();

		foreach ( self::collect_cache_files() as $file ) {
			$mtime = filemtime( $file );

			if ( false === $mtime || ( $now - (int) $mtime ) >= $ttl ) {
				if ( self::delete_cache_file_path( $file ) ) {
					$deleted++;
				}
			}
		}

		return $deleted;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function get_stats_meta() {
		$meta = get_option( self::STATS_OPTION, array() );

		return wp_parse_args(
			is_array( $meta ) ? $meta : array(),
			array(
				'file_count'   => 0,
				'total_bytes'  => 0,
				'oldest_mtime' => 0,
				'newest_mtime' => 0,
				'last_scan'    => 0,
			)
		);
	}

	/**
	 * @param array<string, mixed> $meta Stats metadata.
	 */
	private static function save_stats_meta( $meta ) {
		update_option( self::STATS_OPTION, $meta, false );
	}

	private static function reset_stats_meta() {
		self::save_stats_meta(
			array(
				'file_count'   => 0,
				'total_bytes'  => 0,
				'oldest_mtime' => 0,
				'newest_mtime' => 0,
				'last_scan'    => time(),
			)
		);
	}

	/**
	 * @param int $file_delta  File count delta.
	 * @param int $bytes_delta Bytes delta.
	 * @param int $mtime       File mtime.
	 */
	private static function adjust_stats_meta( $file_delta, $bytes_delta, $mtime ) {
		$meta = self::get_stats_meta();

		$meta['file_count']  = max( 0, (int) $meta['file_count'] + (int) $file_delta );
		$meta['total_bytes'] = max( 0, (int) $meta['total_bytes'] + (int) $bytes_delta );

		if ( $mtime > 0 ) {
			$meta['oldest_mtime'] = $meta['oldest_mtime'] ? min( (int) $meta['oldest_mtime'], $mtime ) : $mtime;
			$meta['newest_mtime'] = max( (int) $meta['newest_mtime'], $mtime );
		}

		if ( $file_delta < 0 && 0 === (int) $meta['file_count'] ) {
			$meta['oldest_mtime'] = 0;
			$meta['newest_mtime'] = 0;
		}

		$meta['last_scan'] = time();
		self::save_stats_meta( $meta );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function rebuild_stats_meta() {
		$files        = self::collect_cache_files();
		$total_bytes  = 0;
		$oldest_mtime = 0;
		$newest_mtime = 0;

		foreach ( $files as $file ) {
			$size  = filesize( $file );
			$mtime = filemtime( $file );

			if ( false !== $size ) {
				$total_bytes += (int) $size;
			}

			if ( false !== $mtime ) {
				$oldest_mtime = $oldest_mtime ? min( $oldest_mtime, $mtime ) : $mtime;
				$newest_mtime = max( $newest_mtime, $mtime );
			}
		}

		$meta = array(
			'file_count'   => count( $files ),
			'total_bytes'  => $total_bytes,
			'oldest_mtime' => $oldest_mtime,
			'newest_mtime' => $newest_mtime,
			'last_scan'    => time(),
		);

		self::save_stats_meta( $meta );

		return $meta;
	}
}

/**
 * Register page cache hooks (idempotent).
 */
function tnstack_core_page_cache_register_hooks() {
	static $registered = false;

	if ( $registered ) {
		return;
	}

	$registered = true;

	if ( did_action( 'plugins_loaded' ) ) {
		Template_Performance_Cache::boot();
	} else {
		add_action( 'plugins_loaded', array( 'Template_Performance_Cache', 'boot' ), 0 );
	}
	add_action( 'save_post', array( 'Template_Performance_Cache', 'purge_on_save' ) );
	add_action( 'deleted_post', array( 'Template_Performance_Cache', 'purge_post_related' ) );
	add_action( 'edited_term', array( 'Template_Performance_Cache', 'purge_term_related' ), 10, 3 );
	add_action( 'delete_term', array( 'Template_Performance_Cache', 'purge_term_related' ), 10, 3 );
	add_action( 'switch_theme', array( 'Template_Performance_Cache', 'flush_all' ) );
	add_action( 'customize_save_after', array( 'Template_Performance_Cache', 'flush_all' ) );
	add_action( 'template_performance_process_purge_queue', array( 'Template_Performance_Cache', 'process_purge_queue' ) );
	add_action( 'template_performance_cleanup_expired', array( 'Template_Performance_Cache', 'cleanup_expired' ) );

	add_action(
		'init',
		static function () {
			if ( ! wp_next_scheduled( 'template_performance_cleanup_expired' ) ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'template_performance_cleanup_expired' );
			}
		}
	);
}
