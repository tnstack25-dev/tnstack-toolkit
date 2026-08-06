<?php
/**
 * Installer and configuration manager for the advanced-cache.php drop-in.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_Advanced_Cache {

	const MARKER       = 'TNSTACK_TOOLKIT_ADVANCED_CACHE';
	const ERROR_OPTION = 'tnstack_advanced_cache_error';

	/** Register maintenance hooks. */
	public static function boot() {
		add_action( 'admin_init', array( __CLASS__, 'sync' ), 2 );
	}

	/**
	 * Install/update the drop-in and write its database-free configuration.
	 *
	 * @param array<string, mixed>|null $settings Page-cache settings.
	 * @return true|WP_Error
	 */
	public static function sync( $settings = null ) {
		if ( null === $settings ) {
			if ( ! class_exists( 'Template_Performance_Cache', false ) ) {
				return new WP_Error( 'cache_unavailable', __( 'Không thể đọc cấu hình cache trang.', 'tnstack-toolkit' ) );
			}
			$settings = Template_Performance_Cache::settings();
		}

		$config_result = self::write_config( $settings );
		if ( is_wp_error( $config_result ) ) {
			self::remember_error( $config_result );
			return $config_result;
		}

		if ( empty( $settings['enable_page_cache'] ) ) {
			delete_option( self::ERROR_OPTION );
			return true;
		}

		$dropin_result = self::install_dropin();
		if ( is_wp_error( $dropin_result ) ) {
			self::remember_error( $dropin_result );
			return $dropin_result;
		}

		$wp_cache_result = self::ensure_wp_cache_constant();
		if ( is_wp_error( $wp_cache_result ) ) {
			self::remember_error( $wp_cache_result );
			return $wp_cache_result;
		}

		delete_option( self::ERROR_OPTION );
		return true;
	}

	/** Remove only files owned by TNStack. */
	public static function uninstall() {
		$dropin = self::dropin_path();
		if ( self::is_owned_dropin( $dropin ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $dropin );
		}

		$config = self::config_path();
		if ( is_file( $config ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $config );
		}
		delete_option( self::ERROR_OPTION );
	}

	/** @return array<string, mixed> */
	public static function status() {
		$dropin       = self::dropin_path();
		$dropin_owned = self::is_owned_dropin( $dropin );
		$wp_cache     = self::wp_cache_configured();
		$error        = (string) get_option( self::ERROR_OPTION, '' );
		$config_path  = self::config_path();
		$config        = is_readable( $config_path ) ? json_decode( (string) file_get_contents( $config_path ), true ) : array();
		$config_on     = is_array( $config ) && ! empty( $config['enabled'] );

		return array(
			'active'        => $dropin_owned && $wp_cache && $config_on,
			'dropin_owned'  => $dropin_owned,
			'has_conflict'  => is_file( $dropin ) && ! $dropin_owned,
			'wp_cache'      => $wp_cache,
			'config_exists' => is_readable( $config_path ),
			'config_enabled' => $config_on,
			'error'         => $error,
		);
	}

	/** @return bool */
	private static function wp_cache_configured() {
		if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
			return true;
		}

		$config_path = self::wp_config_path();
		if ( ! $config_path || ! is_readable( $config_path ) ) {
			return false;
		}

		$contents = file_get_contents( $config_path );
		return is_string( $contents ) && (bool) preg_match( '~define\s*\(\s*([\'\"])WP_CACHE\1\s*,\s*(?:true|1)\s*\)\s*;~i', $contents );
	}

	/** @return true|WP_Error */
	private static function install_dropin() {
		$source = __DIR__ . '/dropins/advanced-cache.php';
		$target = self::dropin_path();
		if ( ! is_readable( $source ) ) {
			return new WP_Error( 'dropin_source_missing', __( 'Thiếu mẫu advanced-cache.php trong plugin.', 'tnstack-toolkit' ) );
		}
		if ( is_file( $target ) && ! self::is_owned_dropin( $target ) ) {
			return new WP_Error( 'dropin_conflict', __( 'Đã có advanced-cache.php của plugin khác; TNStack không ghi đè file này.', 'tnstack-toolkit' ) );
		}

		$contents = file_get_contents( $source );
		if ( false === $contents ) {
			return new WP_Error( 'dropin_read_failed', __( 'Không thể đọc mẫu advanced-cache.php.', 'tnstack-toolkit' ) );
		}
		if ( is_file( $target ) && hash_file( 'sha256', $target ) === hash( 'sha256', $contents ) ) {
			return true;
		}

		return self::write_file( $target, $contents, 'dropin_write_failed', __( 'Không thể ghi wp-content/advanced-cache.php.', 'tnstack-toolkit' ) );
	}

	/** @param array<string, mixed> $settings Page-cache settings. @return true|WP_Error */
	private static function write_config( $settings ) {
		$directory = dirname( self::config_path() );
		if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) {
			return new WP_Error( 'config_directory_failed', __( 'Không thể tạo thư mục cấu hình advanced cache.', 'tnstack-toolkit' ) );
		}

		$config = array(
			'version' => 1,
			'enabled' => ! empty( $settings['enable_page_cache'] ),
			'ttl'     => max( 300, min( WEEK_IN_SECONDS, absint( $settings['cache_ttl'] ?? WEEK_IN_SECONDS ) ) ),
		);
		$json = wp_json_encode( $config, JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			return new WP_Error( 'config_encode_failed', __( 'Không thể tạo cấu hình advanced cache.', 'tnstack-toolkit' ) );
		}

		$path = self::config_path();
		if ( is_readable( $path ) && trim( (string) file_get_contents( $path ) ) === $json ) {
			return true;
		}

		return self::write_file( $path, $json, 'config_write_failed', __( 'Không thể ghi cấu hình advanced cache.', 'tnstack-toolkit' ) );
	}

	/** @return true|WP_Error */
	private static function ensure_wp_cache_constant() {
		if ( self::wp_cache_configured() ) {
			return true;
		}

		$config_path = self::wp_config_path();
		if ( ! $config_path || ! is_readable( $config_path ) || ! is_writable( $config_path ) ) {
			return new WP_Error( 'wp_config_unwritable', __( 'Không thể bật WP_CACHE vì wp-config.php không có quyền ghi.', 'tnstack-toolkit' ) );
		}

		$contents = file_get_contents( $config_path );
		if ( false === $contents ) {
			return new WP_Error( 'wp_config_read_failed', __( 'Không thể đọc wp-config.php.', 'tnstack-toolkit' ) );
		}

		$true_pattern = '~define\s*\(\s*([\'\"])WP_CACHE\1\s*,\s*(?:true|1)\s*\)\s*;~i';
		if ( preg_match( $true_pattern, $contents ) ) {
			return true;
		}

		$pattern = '~define\s*\(\s*([\'\"])WP_CACHE\1\s*,\s*(?:true|false|1|0)\s*\)\s*;~i';
		if ( preg_match( $pattern, $contents ) ) {
			$updated = preg_replace( $pattern, "define( 'WP_CACHE', true );", $contents, 1 );
		} else {
			$line    = "define( 'WP_CACHE', true ); // TNStack Toolkit advanced cache" . PHP_EOL;
			$updated = preg_replace( '~(?=/\* That\'s all, stop editing!)~', $line, $contents, 1, $count );
			if ( empty( $count ) ) {
				$updated = preg_replace( '~(?=require_once\s+ABSPATH\s*\.\s*[\'\"]wp-settings\.php[\'\"]\s*;)~', $line, $contents, 1, $count );
			}
			if ( empty( $count ) ) {
				return new WP_Error( 'wp_config_anchor_missing', __( 'Không tìm thấy vị trí an toàn để thêm WP_CACHE vào wp-config.php.', 'tnstack-toolkit' ) );
			}
		}

		if ( ! is_string( $updated ) || $updated === $contents ) {
			return new WP_Error( 'wp_config_update_failed', __( 'Không thể cập nhật WP_CACHE trong wp-config.php.', 'tnstack-toolkit' ) );
		}

		return self::write_file( $config_path, $updated, 'wp_config_write_failed', __( 'Không thể lưu WP_CACHE vào wp-config.php.', 'tnstack-toolkit' ) );
	}

	/** @return string */
	private static function wp_config_path() {
		$candidates = array(
			ABSPATH . 'wp-config.php',
			dirname( untrailingslashit( ABSPATH ) ) . '/wp-config.php',
		);
		foreach ( $candidates as $candidate ) {
			if ( is_file( $candidate ) ) {
				return $candidate;
			}
		}
		return '';
	}

	/** @return string */
	private static function dropin_path() {
		return WP_CONTENT_DIR . '/advanced-cache.php';
	}

	/** @return string */
	private static function config_path() {
		return WP_CONTENT_DIR . '/cache/tnstack-page-cache-config.json';
	}

	/** @param string $path File path. @return bool */
	private static function is_owned_dropin( $path ) {
		if ( ! is_readable( $path ) ) {
			return false;
		}
		$handle = fopen( $path, 'rb' );
		if ( false === $handle ) {
			return false;
		}
		$head = fread( $handle, 512 );
		fclose( $handle );
		return is_string( $head ) && false !== strpos( $head, self::MARKER );
	}

	/** @return true|WP_Error */
	private static function write_file( $path, $contents, $code, $message ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$written = file_put_contents( $path, $contents, LOCK_EX );
		if ( false === $written || strlen( $contents ) !== (int) $written ) {
			return new WP_Error( $code, $message );
		}
		return true;
	}

	/** @param WP_Error $error Installation error. */
	private static function remember_error( $error ) {
		update_option( self::ERROR_OPTION, sanitize_text_field( $error->get_error_message() ), false );
	}
}
