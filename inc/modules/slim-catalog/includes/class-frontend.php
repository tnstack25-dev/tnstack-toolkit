<?php
/**
 * Frontend assets.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

class Slim_Catalog_Frontend {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'body_class', array( __CLASS__, 'theme_body_class' ) );
	}

	/**
	 * Add the selected catalog color mode wherever catalog assets are used.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function theme_body_class( $classes ) {
		if ( ! self::page_needs_catalog_assets() ) {
			return $classes;
		}

		$settings = slim_catalog_get_settings();
		$mode     = isset( $settings['color_mode'] ) ? sanitize_key( $settings['color_mode'] ) : 'light';

		if ( ! in_array( $mode, array( 'light', 'dark', 'auto' ), true ) ) {
			$mode = 'light';
		}

		$classes[] = 'sc-catalog-theme-' . $mode;

		return array_values( array_unique( $classes ) );
	}

	/**
	 * @param bool $force Force enqueue (UX Builder preview).
	 */
	public static function enqueue_assets( $force = false ) {
		if ( ! $force && ! self::should_enqueue_style() ) {
			return;
		}

		$style_path  = SLIM_CATALOG_PATH . 'assets/css/slim-catalog.css';
		$script_path = SLIM_CATALOG_PATH . 'assets/js/slim-catalog.js';

		wp_enqueue_style(
			'slim-catalog',
			SLIM_CATALOG_URL . 'assets/css/slim-catalog.css',
			array(),
			is_readable( $style_path ) ? (string) filemtime( $style_path ) : SLIM_CATALOG_VERSION
		);

		wp_add_inline_style( 'slim-catalog', self::theme_color_css() );

		if ( $force || self::should_enqueue_script() ) {
			wp_enqueue_script(
				'slim-catalog',
				SLIM_CATALOG_URL . 'assets/js/slim-catalog.js',
				array(),
				is_readable( $script_path ) ? (string) filemtime( $script_path ) : SLIM_CATALOG_VERSION,
				true
			);
		}
	}

	/**
	 * @return bool
	 */
	private static function should_enqueue_style() {
		return self::page_needs_catalog_assets();
	}

	/**
	 * @return bool
	 */
	private static function should_enqueue_script() {
		if ( is_singular( Slim_Catalog_Post_Types::POST_TYPE ) ) {
			return true;
		}

		return self::content_has(
			array(
				'slim_product_detail',
				'ux_slim_product_gallery',
				'ux_slim_product_variations',
			)
		);
	}

	/**
	 * @return bool
	 */
	private static function page_needs_catalog_assets() {
		if ( is_post_type_archive( Slim_Catalog_Post_Types::POST_TYPE ) || is_singular( Slim_Catalog_Post_Types::POST_TYPE ) || is_tax( Slim_Catalog_Post_Types::TAXONOMY ) ) {
			return true;
		}

		return self::content_has(
			array(
				'slim_products',
				'slim_products_all',
				'slim_product',
				'slim_product_detail',
				'slim_product_categories',
				'ux_slim_products',
				'ux_slim_featured_products',
				'ux_slim_latest_products',
				'ux_slim_products_list',
				'ux_slim_products_all',
				'ux_slim_product_categories',
				'ux_slim_product_gallery',
				'ux_slim_product_title',
				'ux_slim_product_price',
				'ux_slim_product_excerpt',
				'ux_slim_product_cta',
				'ux_slim_product_description',
				'ux_slim_product_variations',
				'ux_slim_product_related',
			)
		);
	}

	/**
	 * @param string[] $shortcodes Shortcode tags.
	 * @return bool
	 */
	private static function content_has( $shortcodes ) {
		global $post;

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		$content = (string) $post->post_content;

		foreach ( $shortcodes as $shortcode ) {
			if ( has_shortcode( $content, $shortcode ) || false !== strpos( $content, $shortcode ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Map plugin accent colors to the active theme palette.
	 *
	 * @return string
	 */
	private static function theme_color_css() {
		$primary   = '#446084';
		$secondary = '#d26e4b';

		if ( function_exists( 'get_theme_mod' ) ) {
			if ( get_theme_mod( 'color_primary' ) ) {
				$primary = get_theme_mod( 'color_primary' );
			}

			if ( get_theme_mod( 'color_secondary' ) ) {
				$secondary = get_theme_mod( 'color_secondary' );
			}
		}

		return sprintf(
			':root { --sc-accent: %1$s; --sc-accent-hover: %2$s; }',
			esc_attr( $primary ),
			esc_attr( $secondary )
		);
	}
}
