<?php
/**
 * Registers product post type and taxonomy.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

class Slim_Catalog_Post_Types {

	const POST_TYPE = 'slim_product';
	const TAXONOMY  = 'slim_product_cat';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_migrate_slugs' ), 1 );
		add_action( 'init', array( __CLASS__, 'register' ), 5 );
	}

	/**
	 * Migrate legacy English slugs to Vietnamese defaults.
	 */
	public static function maybe_migrate_slugs() {
		if ( get_option( 'slim_catalog_slug_migrated_v1' ) ) {
			return;
		}

		$settings = get_option( 'slim_catalog_settings', array() );

		if ( ! is_array( $settings ) ) {
			update_option( 'slim_catalog_slug_migrated_v1', 1 );
			return;
		}

		$changed = false;

		if ( in_array( $settings['archive_slug'] ?? '', array( 'products', 'product' ), true ) ) {
			$settings['archive_slug'] = 'san-pham';
			$changed                  = true;
		}

		if ( 'product' === ( $settings['single_slug'] ?? '' ) ) {
			$settings['single_slug'] = 'san-pham';
			$changed                 = true;
		}

		if ( $changed ) {
			update_option( 'slim_catalog_settings', $settings );
			delete_transient( 'slim_catalog_settings_cache' );
			flush_rewrite_rules();
		}

		update_option( 'slim_catalog_slug_migrated_v1', 1 );
	}

	public static function register() {
		$settings    = slim_catalog_get_settings();
		$archive_slug = $settings['archive_slug'];
		$single_slug  = $settings['single_slug'];

		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Product Categories', 'slim-catalog' ),
					'singular_name' => __( 'Product Category', 'slim-catalog' ),
					'search_items'  => __( 'Search Categories', 'slim-catalog' ),
					'all_items'     => __( 'All Categories', 'slim-catalog' ),
					'edit_item'     => __( 'Edit Category', 'slim-catalog' ),
					'update_item'   => __( 'Update Category', 'slim-catalog' ),
					'add_new_item'  => __( 'Add New Category', 'slim-catalog' ),
					'new_item_name' => __( 'New Category Name', 'slim-catalog' ),
					'menu_name'     => __( 'Categories', 'slim-catalog' ),
				),
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array(
					'slug'         => $archive_slug . '/category',
					'with_front'   => false,
					'hierarchical' => true,
				),
				'show_in_rest'      => true,
			)
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Products', 'slim-catalog' ),
					'singular_name'      => __( 'Product', 'slim-catalog' ),
					'add_new'            => __( 'Add New', 'slim-catalog' ),
					'add_new_item'       => __( 'Add New Product', 'slim-catalog' ),
					'edit_item'          => __( 'Edit Product', 'slim-catalog' ),
					'new_item'           => __( 'New Product', 'slim-catalog' ),
					'view_item'          => __( 'View Product', 'slim-catalog' ),
					'search_items'       => __( 'Search Products', 'slim-catalog' ),
					'not_found'          => __( 'No products found', 'slim-catalog' ),
					'not_found_in_trash' => __( 'No products found in trash', 'slim-catalog' ),
					'menu_name'          => __( 'Slim Catalog', 'slim-catalog' ),
				),
				'public'              => true,
				'has_archive'         => $archive_slug,
				'rewrite'             => array(
					'slug'       => $single_slug,
					'with_front' => false,
				),
				'menu_icon'           => 'dashicons-store',
				'menu_position'       => 26,
				'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
				'show_in_rest'        => true,
				'exclude_from_search' => false,
			)
		);
	}
}