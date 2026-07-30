<?php
/**
 * Template loader for product archives and singles.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

class Slim_Catalog_Template_Loader {

	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * @param string $template Current template path.
	 * @return string
	 */
	public static function template_include( $template ) {
		if ( is_post_type_archive( Slim_Catalog_Post_Types::POST_TYPE ) || is_tax( Slim_Catalog_Post_Types::TAXONOMY ) ) {
			$plugin_template = SLIM_CATALOG_PATH . 'templates/archive-product.php';

			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		if ( is_singular( Slim_Catalog_Post_Types::POST_TYPE ) ) {
			$plugin_template = SLIM_CATALOG_PATH . 'templates/single-product.php';

			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( $classes ) {
		if ( is_post_type_archive( Slim_Catalog_Post_Types::POST_TYPE ) || is_tax( Slim_Catalog_Post_Types::TAXONOMY ) ) {
			$classes[] = 'slim-catalog-archive';
		}

		if ( is_singular( Slim_Catalog_Post_Types::POST_TYPE ) ) {
			$classes[] = 'slim-catalog-single';
		}

		return $classes;
	}
}