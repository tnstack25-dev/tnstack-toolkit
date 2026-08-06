<?php
/**
 * Sitemap-driven page cache preloader.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_Cache_Preloader {

	const TABLE_VERSION = '1.0.0';
	const VERSION_OPTION = 'tnstack_cache_preload_table_version';
	const STATE_OPTION   = 'tnstack_cache_preload_state';
	const LOCK_OPTION    = 'tnstack_cache_preload_lock';
	const CRON_HOOK      = 'tnstack_cache_preload_worker';
	const MAX_ATTEMPTS   = 3;
	const MAX_SITEMAPS   = 1000;
	const LOCK_TTL       = 300;
	const TIME_BUDGET    = 20;

	/** Register hooks. */
	public static function boot() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_install' ), 1 );
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_worker' ) );
		add_action( 'admin_post_tnstack_cache_preload_start', array( __CLASS__, 'handle_start' ) );
		add_action( 'admin_post_tnstack_cache_preload_pause', array( __CLASS__, 'handle_pause' ) );
		add_action( 'admin_post_tnstack_cache_preload_resume', array( __CLASS__, 'handle_resume' ) );
		add_action( 'admin_post_tnstack_cache_preload_cancel', array( __CLASS__, 'handle_cancel' ) );
		add_action( 'template_performance_cache_flushed', array( __CLASS__, 'maybe_start_after_flush' ) );
	}

	/** Create or upgrade the queue table. */
	public static function maybe_install() {
		if ( self::TABLE_VERSION === (string) get_option( self::VERSION_OPTION, '' ) ) {
			return;
		}

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			run_id varchar(36) NOT NULL,
			item_type varchar(12) NOT NULL,
			item_hash char(32) NOT NULL,
			url text NOT NULL,
			status varchar(12) NOT NULL DEFAULT 'pending',
			priority smallint(5) unsigned NOT NULL DEFAULT 100,
			attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
			http_code smallint(5) unsigned NOT NULL DEFAULT 0,
			message varchar(255) NOT NULL DEFAULT '',
			available_at datetime NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY run_item (run_id,item_type,item_hash),
			KEY run_status (run_id,status,priority,id),
			KEY updated_at (updated_at)
		) {$charset};";

		dbDelta( $sql );
		update_option( self::VERSION_OPTION, self::TABLE_VERSION, false );
	}

	/** @return string */
	private static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'tnstack_cache_preload_queue';
	}

	/** @return array<string, mixed> */
	public static function get_state() {
		$state = get_option( self::STATE_OPTION, array() );

		return wp_parse_args(
			is_array( $state ) ? $state : array(),
			array(
				'run_id'       => '',
				'status'       => 'idle',
				'sitemap_url'  => '',
				'site_host'    => '',
				'total_urls'   => 0,
				'processed'    => 0,
				'success'      => 0,
				'skipped'      => 0,
				'failed'       => 0,
				'sitemaps'     => 0,
				'last_url'     => '',
				'last_error'   => '',
				'started_at'   => 0,
				'finished_at'  => 0,
				'next_run'     => 0,
			)
		);
	}

	/** @param array<string, mixed> $state State payload. */
	private static function save_state( $state ) {
		update_option( self::STATE_OPTION, $state, false );
	}

	/** @return array<string, mixed> */
	private static function get_fresh_state() {
		wp_cache_delete( self::STATE_OPTION, 'options' );
		return self::get_state();
	}

	/** @return bool */
	private static function can_manage() {
		return class_exists( 'TNStack_Account_Permissions', false )
			&& current_user_can( TNStack_Account_Permissions::MANAGE_CAP );
	}

	/** Start action. */
	public static function handle_start() {
		self::verify_admin_action( 'start' );
		$result = self::start_run();
		self::redirect( is_wp_error( $result ) ? $result->get_error_code() : 'started' );
	}

	/** Pause action. */
	public static function handle_pause() {
		self::verify_admin_action( 'pause' );
		$state = self::get_state();
		if ( 'running' === $state['status'] ) {
			$state['status']   = 'paused';
			$state['next_run'] = 0;
			self::save_state( $state );
		}
		wp_clear_scheduled_hook( self::CRON_HOOK );
		self::redirect( 'paused' );
	}

	/** Resume action. */
	public static function handle_resume() {
		self::verify_admin_action( 'resume' );
		$state = self::get_state();
		if ( 'paused' === $state['status'] && ! empty( $state['run_id'] ) ) {
			$state['status'] = 'running';
			self::save_state( $state );
			self::schedule_worker( 5 );
		}
		self::redirect( 'resumed' );
	}

	/** Cancel action. */
	public static function handle_cancel() {
		self::verify_admin_action( 'cancel' );
		$state = self::get_state();
		if ( ! empty( $state['run_id'] ) ) {
			self::delete_run_items( $state['run_id'] );
		}
		$state['status']      = 'cancelled';
		$state['finished_at'] = time();
		$state['next_run']    = 0;
		self::save_state( $state );
		wp_clear_scheduled_hook( self::CRON_HOOK );
		self::redirect( 'cancelled' );
	}

	/** @param string $action Action key. */
	private static function verify_admin_action( $action ) {
		if ( ! self::can_manage() ) {
			wp_die( esc_html__( 'Bạn không có quyền quản lý preload cache.', 'tnstack-toolkit' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'tnstack_cache_preload_' . $action );
	}

	/** @param string $notice Notice code. */
	private static function redirect( $notice ) {
		$url = add_query_arg(
			array(
				'page'           => 'tnstack-core-performance',
				'tab'            => 'cache',
				'preload_notice' => sanitize_key( $notice ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Begin a new run using the configured sitemap.
	 *
	 * @param bool $automatic Whether triggered by a full cache flush.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function start_run( $automatic = false ) {
		if ( ! class_exists( 'Template_Performance_Cache', false ) || ! Template_Performance_Cache::is_enabled() ) {
			return new WP_Error( 'cache_disabled', __( 'Cache trang đang tắt.', 'tnstack-toolkit' ) );
		}

		$settings = Template_Performance_Cache::settings();
		if ( empty( $settings['enable_preload'] ) ) {
			return new WP_Error( 'preload_disabled', __( 'Preload cache đang tắt.', 'tnstack-toolkit' ) );
		}

		$sitemap_url = isset( $settings['preload_sitemap_url'] ) ? esc_url_raw( $settings['preload_sitemap_url'] ) : '';
		$site_host   = self::normalized_host( home_url( '/' ) );
		if ( ! self::valid_same_site_url( $sitemap_url, $site_host ) ) {
			return new WP_Error( 'invalid_sitemap', __( 'Sitemap phải là URL HTTP/HTTPS hợp lệ trên cùng website.', 'tnstack-toolkit' ) );
		}

		$current = self::get_state();
		if ( $automatic && in_array( $current['status'], array( 'running', 'paused' ), true ) ) {
			return $current;
		}

		self::maybe_install();
		wp_clear_scheduled_hook( self::CRON_HOOK );
		if ( ! empty( $current['run_id'] ) ) {
			self::delete_run_items( $current['run_id'] );
		}
		self::cleanup_old_items();

		$run_id = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'tnstack-', true );
		$state  = array(
			'run_id'       => $run_id,
			'status'       => 'running',
			'sitemap_url'  => $sitemap_url,
			'site_host'    => $site_host,
			'total_urls'   => 0,
			'processed'    => 0,
			'success'      => 0,
			'skipped'      => 0,
			'failed'       => 0,
			'sitemaps'     => 0,
			'last_url'     => '',
			'last_error'   => '',
			'started_at'   => time(),
			'finished_at'  => 0,
			'next_run'     => 0,
		);

		self::save_state( $state );
		self::insert_items( $run_id, 'sitemap', array( $sitemap_url ), 10, $state );
		self::save_state( $state );
		self::schedule_worker( 5 );

		return $state;
	}

	/** Start automatically after the manual full-cache flush. */
	public static function maybe_start_after_flush() {
		if ( ! class_exists( 'Template_Performance_Cache', false ) ) {
			return;
		}
		$settings = Template_Performance_Cache::settings();
		if ( ! empty( $settings['enable_preload'] ) && ! empty( $settings['preload_auto_after_purge'] ) ) {
			self::start_run( true );
		}
	}

	/** Run a bounded worker batch. */
	public static function run_worker() {
		$state = self::get_state();
		if ( 'running' !== $state['status'] || empty( $state['run_id'] ) ) {
			return;
		}
		if ( ! class_exists( 'Template_Performance_Cache', false ) || ! Template_Performance_Cache::is_enabled() || empty( Template_Performance_Cache::settings()['enable_preload'] ) ) {
			$state['status']   = 'paused';
			$state['next_run'] = 0;
			self::save_state( $state );
			return;
		}

		if ( ! self::acquire_lock() ) {
			self::schedule_worker( 30 );
			return;
		}

		$started = microtime( true );
		try {
			self::recover_stale_items( $state['run_id'] );
			$settings = class_exists( 'Template_Performance_Cache', false ) ? Template_Performance_Cache::settings() : array();
			$batch    = min( 10, max( 1, absint( $settings['preload_batch_size'] ?? 5 ) ) );

			for ( $i = 0; $i < $batch; $i++ ) {
				if ( ( microtime( true ) - $started ) >= self::TIME_BUDGET ) {
					break;
				}
				$item = self::claim_next_item( $state['run_id'] );
				if ( ! $item ) {
					break;
				}
				$state['last_url'] = $item->url;
				self::process_item( $item, $state, $settings );

				$control = self::get_fresh_state();
				if ( $control['run_id'] !== $state['run_id'] ) {
					return;
				}
				if ( 'running' !== $control['status'] ) {
					$state['status']      = $control['status'];
					$state['finished_at'] = $control['finished_at'];
					$state['next_run']    = 0;
					self::save_state( $state );
					return;
				}
				self::save_state( $state );
			}

			$control = self::get_fresh_state();
			if ( $control['run_id'] !== $state['run_id'] || 'running' !== $control['status'] ) {
				return;
			}

			if ( self::has_unfinished_items( $state['run_id'] ) ) {
				self::save_state( $state );
				self::schedule_worker( 30 );
			} else {
				$state['status']      = 'completed';
				$state['finished_at'] = time();
				$state['next_run']    = 0;
				self::save_state( $state );
			}
		} finally {
			self::release_lock();
		}
	}

	/** @param object $item Queue row. @param array<string,mixed> $state State. @param array<string,mixed> $settings Settings. */
	private static function process_item( $item, &$state, $settings ) {
		$result = 'sitemap' === $item->item_type
			? self::process_sitemap( $item, $state, $settings )
			: self::process_url( $item, $settings );

		if ( is_wp_error( $result ) ) {
			$error_data = $result->get_error_data();
			$retryable  = is_array( $error_data ) && ! empty( $error_data['retryable'] );
			if ( $retryable && (int) $item->attempts < self::MAX_ATTEMPTS ) {
				self::retry_item( $item->id, $result->get_error_message() );
				return;
			}
			self::finish_item( $item->id, 'failed', 0, $result->get_error_message() );
			if ( 'url' === $item->item_type ) {
				$state['processed']++;
			}
			$state['failed']++;
			$state['last_error'] = $result->get_error_message();
			return;
		}

		self::finish_item( $item->id, $result['status'], $result['http_code'], $result['message'] );
		if ( 'sitemap' === $item->item_type ) {
			$state['sitemaps']++;
			return;
		}

		$state['processed']++;
		if ( 'done' === $result['status'] ) {
			$state['success']++;
		} else {
			$state['skipped']++;
		}
	}

	/** @return array<string,mixed>|WP_Error */
	private static function process_sitemap( $item, &$state, $settings ) {
		$response = self::remote_get( $item->url, true );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = $response['body'];
		if ( 0 === strncmp( $body, "\x1f\x8b", 2 ) ) {
			if ( ! function_exists( 'gzdecode' ) ) {
				return new WP_Error( 'gzip_unsupported', __( 'Máy chủ không hỗ trợ giải nén sitemap .gz.', 'tnstack-toolkit' ) );
			}
			$decoded = gzdecode( $body, 10 * MB_IN_BYTES );
			if ( false === $decoded || strlen( $decoded ) > 10 * MB_IN_BYTES ) {
				return new WP_Error( 'invalid_gzip', __( 'Sitemap nén không hợp lệ hoặc quá lớn.', 'tnstack-toolkit' ) );
			}
			$body = $decoded;
		}

		$is_index = false !== stripos( $body, '<sitemapindex' );
		$is_urlset = false !== stripos( $body, '<urlset' );
		if ( ! $is_index && ! $is_urlset ) {
			return new WP_Error( 'invalid_sitemap_xml', __( 'Nội dung không phải sitemap index hoặc URL sitemap hợp lệ.', 'tnstack-toolkit' ) );
		}

		if ( ! preg_match_all( '~<loc\b[^>]*>\s*(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?\s*</loc>~is', $body, $matches ) ) {
			return new WP_Error( 'empty_sitemap', __( 'Sitemap không chứa URL nào.', 'tnstack-toolkit' ) );
		}

		$urls = array();
		foreach ( $matches[1] as $value ) {
			$url = esc_url_raw( trim( html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES, 'UTF-8' ) ) );
			if ( self::valid_same_site_url( $url, $state['site_host'] ) ) {
				$urls[] = $url;
			}
		}

		$type     = $is_index ? 'sitemap' : 'url';
		$priority = $is_index ? 10 : 100;
		self::insert_items( $state['run_id'], $type, $urls, $priority, $state, absint( $settings['preload_max_urls'] ?? 5000 ) );

		return array( 'status' => 'done', 'http_code' => 200, 'message' => sprintf( __( 'Đã đọc %d URL.', 'tnstack-toolkit' ), count( $urls ) ) );
	}

	/** @return array<string,mixed>|WP_Error */
	private static function process_url( $item, $settings ) {
		if ( self::should_skip_url( $item->url ) ) {
			return array( 'status' => 'skipped', 'http_code' => 0, 'message' => __( 'URL động hoặc bị loại trừ.', 'tnstack-toolkit' ) );
		}

		if ( ! empty( $settings['preload_only_missing'] ) && Template_Performance_Cache::is_url_cache_fresh( $item->url ) ) {
			return array( 'status' => 'skipped', 'http_code' => 200, 'message' => __( 'Cache vẫn còn mới.', 'tnstack-toolkit' ) );
		}

		$response = self::remote_get( $item->url, false );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$cache_header = strtoupper( (string) wp_remote_retrieve_header( $response['raw'], 'x-page-cache' ) );
		if ( ! in_array( $cache_header, array( 'HIT', 'MISS' ), true ) ) {
			return array( 'status' => 'skipped', 'http_code' => $response['code'], 'message' => __( 'Trang không đủ điều kiện lưu cache.', 'tnstack-toolkit' ) );
		}

		return array( 'status' => 'done', 'http_code' => $response['code'], 'message' => 'X-Page-Cache: ' . $cache_header );
	}

	/** @return array<string,mixed>|WP_Error */
	private static function remote_get( $url, $sitemap ) {
		$args = array(
			'timeout'             => $sitemap ? 15 : 8,
			'redirection'         => 0,
			'reject_unsafe_urls'  => true,
			'limit_response_size' => $sitemap ? 10 * MB_IN_BYTES : 5 * MB_IN_BYTES,
			'headers'             => array(
				'User-Agent'        => 'TNStack-Cache-Preloader/' . TNSTACK_TOOLKIT_VERSION,
				'X-TNStack-Preload' => '1',
				'Accept'            => $sitemap ? 'application/xml,text/xml;q=0.9,*/*;q=0.8' : 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.8',
			),
			'cookies'             => array(),
		);

		$response = wp_safe_remote_get( $url, $args );
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'request_failed',
				$response->get_error_message(),
				array( 'retryable' => true )
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error(
				'http_error',
				sprintf( __( 'HTTP %d khi tải URL.', 'tnstack-toolkit' ), $code ),
				array( 'retryable' => 429 === $code || $code >= 500 )
			);
		}

		return array(
			'raw'  => $response,
			'code' => $code,
			'body' => (string) wp_remote_retrieve_body( $response ),
		);
	}

	/** @return bool */
	private static function should_skip_url( $url ) {
		$parts = wp_parse_url( $url );
		$path  = isset( $parts['path'] ) ? '/' . ltrim( $parts['path'], '/' ) : '/';
		if ( preg_match( '~/(?:wp-admin|wp-login\.php|wp-json|xmlrpc\.php)(?:/|$)~i', $path ) ) {
			return true;
		}
		if ( preg_match( '~/(?:feed|cart|checkout|my-account)(?:/|$)~i', $path ) ) {
			return true;
		}

		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $params );
			foreach ( array_keys( (array) $params ) as $key ) {
				$key = (string) $key;
				if ( 0 === strpos( $key, 'utm_' ) || in_array( $key, array( 'fbclid', 'gclid', 'gbraid', 'wbraid', 'paged', 'page' ), true ) ) {
					continue;
				}
				return true;
			}
		}

		return (bool) apply_filters( 'tnstack_cache_preload_skip_url', false, $url );
	}

	/** @return bool */
	private static function valid_same_site_url( $url, $site_host ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return false;
		}
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return false;
		}
		if ( ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) || ! empty( $parts['user'] ) || ! empty( $parts['pass'] ) ) {
			return false;
		}
		return self::normalized_host( $url ) === $site_host;
	}

	/** @return string */
	private static function normalized_host( $url ) {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return '';
		}

		$host   = strtolower( (string) ( $parts['host'] ?? '' ) );
		$host   = 0 === strpos( $host, 'www.' ) ? substr( $host, 4 ) : $host;
		$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
		$port   = isset( $parts['port'] ) ? absint( $parts['port'] ) : 0;
		if ( $port && ! ( 80 === $port && 'http' === $scheme ) && ! ( 443 === $port && 'https' === $scheme ) ) {
			$host .= ':' . $port;
		}

		return $host;
	}

	/** Insert deduplicated sitemap or page items. */
	private static function insert_items( $run_id, $type, $urls, $priority, &$state, $max_urls = 50000 ) {
		global $wpdb;
		$urls = array_values( array_unique( array_filter( array_map( 'esc_url_raw', (array) $urls ) ) ) );
		if ( 'url' === $type ) {
			$remaining = max( 0, min( 50000, max( 1, $max_urls ) ) - (int) $state['total_urls'] );
			$urls      = array_slice( $urls, 0, $remaining );
		} elseif ( 'sitemap' === $type ) {
			$table     = self::table_name();
			$queued    = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE run_id = %s AND item_type = 'sitemap'",
					$run_id
				)
			);
			$remaining = max( 0, self::MAX_SITEMAPS - $queued );
			$urls      = array_slice( $urls, 0, $remaining );
		}
		if ( empty( $urls ) ) {
			return 0;
		}

		$table = self::table_name();
		$now   = current_time( 'mysql', true );
		$added = 0;

		foreach ( array_chunk( $urls, 100 ) as $chunk ) {
			$placeholders = array();
			$values       = array();
			foreach ( $chunk as $url ) {
				$placeholders[] = '(%s,%s,%s,%s,%d,%s,%s,%s)';
				array_push( $values, $run_id, $type, md5( strtolower( $url ) ), $url, $priority, $now, $now, $now );
			}
			$sql = "INSERT IGNORE INTO {$table} (run_id,item_type,item_hash,url,priority,available_at,created_at,updated_at) VALUES " . implode( ',', $placeholders );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$result = $wpdb->query( $wpdb->prepare( $sql, $values ) );
			if ( false !== $result ) {
				$added += (int) $result;
			}
		}

		if ( 'url' === $type ) {
			$state['total_urls'] += $added;
		}
		return $added;
	}

	/** @return object|null */
	private static function claim_next_item( $run_id ) {
		global $wpdb;
		$table = self::table_name();
		$now   = current_time( 'mysql', true );
		$item  = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE run_id = %s AND status = 'pending' AND available_at <= %s ORDER BY priority ASC, id ASC LIMIT 1",
				$run_id,
				$now
			)
		);
		if ( ! $item ) {
			return null;
		}

		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'processing', attempts = attempts + 1, updated_at = %s WHERE id = %d AND status = 'pending'",
				$now,
				$item->id
			)
		);
		if ( 1 !== (int) $updated ) {
			return null;
		}
		$item->attempts = (int) $item->attempts + 1;
		return $item;
	}

	/** @param int $id Item ID. */
	private static function finish_item( $id, $status, $http_code, $message ) {
		global $wpdb;
		$wpdb->update(
			self::table_name(),
			array(
				'status'     => sanitize_key( $status ),
				'http_code'  => absint( $http_code ),
				'message'    => sanitize_text_field( $message ),
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $id ) ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
	}

	/** @param int $id Item ID. */
	private static function retry_item( $id, $message ) {
		global $wpdb;
		$available = gmdate( 'Y-m-d H:i:s', time() + 60 );
		$wpdb->update(
			self::table_name(),
			array(
				'status'       => 'pending',
				'message'      => sanitize_text_field( $message ),
				'available_at' => $available,
				'updated_at'   => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $id ) ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/** @return bool */
	private static function has_unfinished_items( $run_id ) {
		global $wpdb;
		$table = self::table_name();
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$table} WHERE run_id = %s AND status IN ('pending','processing') LIMIT 1", $run_id ) );
	}

	/** Recover a worker interrupted during processing. */
	private static function recover_stale_items( $run_id ) {
		global $wpdb;
		$table  = self::table_name();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - self::LOCK_TTL );
		$now    = current_time( 'mysql', true );
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = 'pending', available_at = %s WHERE run_id = %s AND status = 'processing' AND updated_at < %s", $now, $run_id, $cutoff ) );
	}

	/** @param string $run_id Run identifier. */
	private static function delete_run_items( $run_id ) {
		global $wpdb;
		$wpdb->delete( self::table_name(), array( 'run_id' => $run_id ), array( '%s' ) );
	}

	/** Remove abandoned queue rows. */
	private static function cleanup_old_items() {
		global $wpdb;
		$table  = self::table_name();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE updated_at < %s", $cutoff ) );
	}

	/** @return bool */
	private static function acquire_lock() {
		$now = time();
		if ( add_option( self::LOCK_OPTION, $now, '', false ) ) {
			return true;
		}
		$locked_at = (int) get_option( self::LOCK_OPTION, 0 );
		if ( $locked_at && ( $now - $locked_at ) < self::LOCK_TTL ) {
			return false;
		}
		delete_option( self::LOCK_OPTION );
		return add_option( self::LOCK_OPTION, $now, '', false );
	}

	private static function release_lock() {
		delete_option( self::LOCK_OPTION );
	}

	/** @param int $delay Seconds before next batch. */
	private static function schedule_worker( $delay = 30 ) {
		if ( wp_next_scheduled( self::CRON_HOOK ) ) {
			return;
		}
		$timestamp = time() + max( 1, absint( $delay ) );
		wp_schedule_single_event( $timestamp, self::CRON_HOOK );
		$state = self::get_state();
		if ( 'running' === $state['status'] ) {
			$state['next_run'] = $timestamp;
			self::save_state( $state );
		}
	}
}
