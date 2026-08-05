<?php
/**
 * Loads enabled modules from the manifest.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_Module_Manager {

	const ERROR_OPTION = 'tnstack_toolkit_module_errors';

	/**
	 * @var array<int, string>
	 */
	private static $loaded = array();

	/**
	 * @var bool
	 */
	private static $booted = false;

	/**
	 * Errors raised during the current request.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static $errors = array();

	/**
	 * Boot selected enabled modules before init.
	 *
	 * This is primarily used by the performance module so full-page cache hits
	 * can return before themes and most init callbacks are loaded.
	 *
	 * @param string[] $slugs Module slugs.
	 */
	public static function load_early( $slugs ) {
		$config = tnstack_core_config();

		foreach ( array_unique( array_map( 'sanitize_key', (array) $slugs ) ) as $slug ) {
			$definition = TNStack_Module_Manifest::get( $slug );

			if ( ! $definition || ! self::is_enabled( $slug, $definition, $config ) ) {
				continue;
			}

			self::boot( $slug, $definition['boot'] );
		}
	}

	/**
	 * Boot all enabled modules and project extensions.
	 */
	public static function load() {
		if ( self::$booted ) {
			return;
		}

		self::$booted = true;

		$config = tnstack_core_config();

		foreach ( TNStack_Module_Manifest::definitions() as $slug => $definition ) {
			if ( ! self::is_enabled( $slug, $definition, $config ) ) {
				continue;
			}

			self::boot( $slug, $definition['boot'] );
		}

		$project_init = tnstack_core_path( 'project/init.php' );
		if ( is_readable( $project_init ) ) {
			self::boot( 'project-init', 'project/init.php' );
		}

		do_action( 'tnstack_core_modules_loaded' );
	}

	/**
	 * @param string               $slug       Module slug.
	 * @param array<string, mixed> $definition Manifest entry.
	 * @param array<string, mixed> $config     Resolved config.
	 * @return bool
	 */
	private static function is_enabled( $slug, $definition, $config ) {
		$key = $definition['config_key'];

		if ( TNStack_Module_Manifest::CONFIG_MODULES === $key ) {
			return ! empty( $config['modules'][ $slug ] );
		}

		if ( TNStack_Module_Manifest::CONFIG_PROJECT === $key ) {
			return ! empty( $config['project'][ $slug ] );
		}

		return false;
	}

	/**
	 * @param string $slug Module slug.
	 * @param string $boot Relative boot file.
	 */
	private static function boot( $slug, $boot ) {
		if ( in_array( $slug, self::$loaded, true ) ) {
			return true;
		}

		$path = self::resolve_boot_path( $boot );

		if ( ! $path ) {
			self::record_error( $slug, __( 'Tệp khởi động không tồn tại hoặc nằm ngoài thư mục plugin.', 'tnstack-toolkit' ) );
			return false;
		}

		try {
			require_once $path;
			self::$loaded[] = $slug;
			self::clear_error( $slug );
			do_action( 'tnstack_toolkit_module_loaded', $slug, $path );
			return true;
		} catch ( Throwable $error ) {
			self::record_error( $slug, $error->getMessage(), $error );
			do_action( 'tnstack_toolkit_module_failed', $slug, $error );
			return false;
		}
	}

	/**
	 * Load module boot file for admin settings rendering.
	 *
	 * @param string $slug Module slug.
	 */
	public static function load_admin( $slug ) {
		$definition = TNStack_Module_Manifest::get( $slug );

		if ( ! $definition || empty( $definition['boot'] ) ) {
			return false;
		}

		return self::boot( $slug, $definition['boot'] );
	}

	/**
	 * Return module errors that still apply to enabled modules.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function errors() {
		$stored = get_option( self::ERROR_OPTION, array() );
		$errors = is_array( $stored ) ? array_merge( $stored, self::$errors ) : self::$errors;
		$config = tnstack_core_config();

		foreach ( $errors as $slug => $error ) {
			$definition = TNStack_Module_Manifest::get( $slug );
			if ( ! $definition ) {
				continue;
			}

			if ( ! self::is_enabled( $slug, $definition, $config ) ) {
				unset( $errors[ $slug ] );
			}
		}

		return $errors;
	}

	/**
	 * Resolve and validate a module path inside the plugin directory.
	 *
	 * @param string $boot Relative boot file.
	 * @return string|false
	 */
	private static function resolve_boot_path( $boot ) {
		if ( ! is_string( $boot ) || '' === trim( $boot ) || false !== strpos( $boot, "\0" ) ) {
			return false;
		}

		$path = realpath( tnstack_core_path( $boot ) );
		$root = realpath( TNSTACK_TOOLKIT_PATH );

		if ( false === $path || false === $root || ! is_readable( $path ) ) {
			return false;
		}

		$path_normalized = wp_normalize_path( $path );
		$root_normalized = trailingslashit( wp_normalize_path( $root ) );

		if ( 0 !== strpos( $path_normalized, $root_normalized ) ) {
			return false;
		}

		return $path;
	}

	/**
	 * Persist a recoverable module error.
	 *
	 * @param string         $slug      Module slug.
	 * @param string         $message   Public admin message.
	 * @param Throwable|null $exception Caught exception.
	 */
	private static function record_error( $slug, $message, $exception = null ) {
		$entry = array(
			'message' => sanitize_text_field( (string) $message ),
			'time'    => time(),
		);

		self::$errors[ $slug ] = $entry;

		$stored = get_option( self::ERROR_OPTION, array() );
		$stored = is_array( $stored ) ? $stored : array();
		$stored[ $slug ] = $entry;
		update_option( self::ERROR_OPTION, $stored, false );

		if ( $exception ) {
			error_log(
				sprintf(
					'TNStack Toolkit module "%1$s" failed: %2$s in %3$s:%4$d',
					$slug,
					$exception->getMessage(),
					$exception->getFile(),
					$exception->getLine()
				)
			);
		}
	}

	/**
	 * Clear a previously recorded module error after a successful boot.
	 *
	 * @param string $slug Module slug.
	 */
	private static function clear_error( $slug ) {
		unset( self::$errors[ $slug ] );

		$stored = get_option( self::ERROR_OPTION, array() );
		if ( ! is_array( $stored ) || ! isset( $stored[ $slug ] ) ) {
			return;
		}

		unset( $stored[ $slug ] );
		if ( $stored ) {
			update_option( self::ERROR_OPTION, $stored, false );
		} else {
			delete_option( self::ERROR_OPTION );
		}
	}
}
