<?php
/**
 * UX Builder element registration.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

class Slim_Catalog_UX_Builder {

	public static function init() {
		Slim_Catalog_UX_Product_Shortcodes::init();
		add_action( 'ux_builder_setup', array( __CLASS__, 'register_elements' ), 20 );
		add_action( 'ux_builder_enqueue_scripts', array( __CLASS__, 'enqueue_builder_assets' ) );
		add_filter( 'ux_builder_preprocess_array_options', array( __CLASS__, 'normalize_builder_options' ), 10, 2 );
	}

	public static function enqueue_builder_assets() {
		Slim_Catalog_Frontend::enqueue_assets( true );
	}

	/**
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 */
	public static function render_products_all( $atts ) {
		return Slim_Catalog_Shortcodes::products_all( self::merge_builder_atts( $atts, 'ux_slim_products_all' ) );
	}

	/**
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @param string                      $tag  Shortcode tag.
	 * @return array<string, mixed>
	 */
	public static function merge_public_atts( $atts, $tag ) {
		return self::merge_builder_atts( $atts, $tag );
	}

	/**
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @param string                      $tag  Shortcode tag.
	 * @return array<string, mixed>
	 */
	private static function merge_builder_atts( $atts, $tag ) {
		$atts = is_array( $atts ) ? $atts : array();

		if ( defined( 'UX_BUILDER_AJAX_REQUEST' ) && UX_BUILDER_AJAX_REQUEST && ! empty( $_POST['ux_builder_shortcode']['options'] ) ) {
			$atts = array_merge( $atts, (array) wp_unslash( $_POST['ux_builder_shortcode']['options'] ) );
		}

		return self::normalize_builder_options( $atts, $tag );
	}

	public static function register_elements() {
		if ( ! function_exists( 'add_ux_builder_shortcode' ) ) {
			return;
		}

		self::register_shop_elements();
		self::register_product_page_elements();
	}

	private static function register_shop_elements() {
		$products_thumb = self::get_thumbnail( 'products.svg' );
		$list_thumb     = self::get_thumbnail( 'products-list.svg' );
		$cats_thumb     = self::get_thumbnail( 'categories.svg' );

		$products_options = self::products_options();

		add_ux_builder_shortcode(
			'ux_slim_products',
			array(
				'name'      => __( 'Products', 'slim-catalog' ),
				'category'  => __( 'Cửa hàng', 'slim-catalog' ),
				'priority'  => 1,
				'thumbnail' => $products_thumb,
				'info'      => '{{ title || "' . esc_attr__( 'Products', 'slim-catalog' ) . '" }}',
				'wrap'      => false,
				'presets'   => self::products_presets( 'ux_slim_products' ),
				'options'   => $products_options,
			)
		);

		add_ux_builder_shortcode(
			'ux_slim_featured_products',
			array(
				'name'      => __( 'Featured Products', 'slim-catalog' ),
				'category'  => __( 'Cửa hàng', 'slim-catalog' ),
				'priority'  => 2,
				'thumbnail' => $products_thumb,
				'info'      => '{{ title || "' . esc_attr__( 'Featured Products', 'slim-catalog' ) . '" }}',
				'wrap'      => false,
				'presets'   => array(
					array(
						'name'    => __( 'Mặc định', 'slim-catalog' ),
						'content' => '[ux_slim_featured_products products="8" columns="4"]',
					),
				),
				'options'   => $products_options,
			)
		);

		add_ux_builder_shortcode(
			'ux_slim_latest_products',
			array(
				'name'      => __( 'Latest Products', 'slim-catalog' ),
				'category'  => __( 'Cửa hàng', 'slim-catalog' ),
				'priority'  => 3,
				'thumbnail' => $products_thumb,
				'info'      => '{{ title || "' . esc_attr__( 'Latest Products', 'slim-catalog' ) . '" }}',
				'wrap'      => false,
				'presets'   => array(
					array(
						'name'    => __( 'Mặc định', 'slim-catalog' ),
						'content' => '[ux_slim_latest_products products="8" columns="4"]',
					),
				),
				'options'   => $products_options,
			)
		);

		add_ux_builder_shortcode(
			'ux_slim_products_list',
			array(
				'name'      => __( 'Products List', 'slim-catalog' ),
				'category'  => __( 'Cửa hàng', 'slim-catalog' ),
				'priority'  => 4,
				'thumbnail' => $list_thumb,
				'info'      => '{{ title || "' . esc_attr__( 'Products List', 'slim-catalog' ) . '" }}',
				'wrap'      => false,
				'presets'   => array(
					array(
						'name'    => __( 'Mặc định', 'slim-catalog' ),
						'content' => '[ux_slim_products_list]',
					),
					array(
						'name'    => __( 'Featured Products', 'slim-catalog' ),
						'content' => '[ux_slim_products_list featured="true"]',
					),
				),
				'options'   => self::products_list_options(),
			)
		);

		add_ux_builder_shortcode(
			'ux_slim_product_categories',
			array(
				'name'      => __( 'Product Categories', 'slim-catalog' ),
				'category'  => __( 'Cửa hàng', 'slim-catalog' ),
				'priority'  => 5,
				'thumbnail' => $cats_thumb,
				'info'      => __( 'Product Categories', 'slim-catalog' ),
				'wrap'      => false,
				'presets'   => array(
					array(
						'name'    => __( 'Mặc định', 'slim-catalog' ),
						'content' => '[ux_slim_product_categories]',
					),
					array(
						'name'    => __( 'Đơn giản', 'slim-catalog' ),
						'content' => '[ux_slim_product_categories type="row" style="normal"]',
					),
					array(
						'name'    => __( 'Slider', 'slim-catalog' ),
						'content' => '[ux_slim_product_categories type="slider" columns="4"]',
					),
				),
				'options'   => self::product_categories_options(),
			)
		);

		add_ux_builder_shortcode(
			'ux_slim_products_all',
			array(
				'name'      => __( 'All Products', 'slim-catalog' ),
				'category'  => __( 'Cửa hàng', 'slim-catalog' ),
				'priority'  => 6,
				'thumbnail' => $products_thumb,
				'info'      => '{{ title || "' . esc_attr__( 'All Products', 'slim-catalog' ) . '" }}',
				'wrap'      => false,
				'presets'   => array(
					array(
						'name'    => __( 'Full Catalog', 'slim-catalog' ),
						'content' => '[ux_slim_products_all columns="3" per_page="12" title="' . esc_attr__( 'All Products', 'slim-catalog' ) . '" show_categories="true" pagination="true"]',
					),
				),
				'options'   => self::products_all_options(),
			)
		);
	}

	private static function register_product_page_elements() {
		$category = __( 'Trang sản phẩm', 'slim-catalog' );
		$sizes    = self::text_sizes();

		$page_elements = array(
			'ux_slim_product_gallery' => array(
				'name'      => __( 'Product Gallery', 'slim-catalog' ),
				'thumbnail' => self::get_thumbnail( 'woo_gallery.svg' ),
				'wrap'      => true,
				'overlay'   => true,
				'priority'  => 9999,
				'options'   => array(),
			),
			'ux_slim_product_title' => array(
				'name'      => __( 'Product Title', 'slim-catalog' ),
				'thumbnail' => self::get_thumbnail( 'woo_title.svg' ),
				'options'   => array(
					'size'      => self::size_option( $sizes ),
					'divider'   => array(
						'type'    => 'checkbox',
						'heading' => __( 'Đường phân cách', 'slim-catalog' ),
						'default' => 'true',
					),
					'uppercase' => array(
						'type'    => 'checkbox',
						'heading' => __( 'Chữ in hoa', 'slim-catalog' ),
						'default' => 'false',
					),
				),
			),
			'ux_slim_product_price' => array(
				'name'      => __( 'Product Price', 'slim-catalog' ),
				'thumbnail' => self::get_thumbnail( 'woo_price.svg' ),
				'wrap'      => false,
				'options'   => array(
					'size' => self::size_option( $sizes ),
				),
			),
			'ux_slim_product_excerpt' => array(
				'name'      => __( 'Product Short Description', 'slim-catalog' ),
				'thumbnail' => self::get_thumbnail( 'woo_short_desc.svg' ),
				'wrap'      => false,
				'options'   => array(),
			),
			'ux_slim_product_cta' => array(
				'name'      => __( 'Product Contact Button', 'slim-catalog' ),
				'thumbnail' => self::get_thumbnail( 'woo_add_to_cart.svg' ),
				'options'   => array(
					'size' => self::size_option( $sizes ),
				),
			),
			'ux_slim_product_description' => array(
				'name'      => __( 'Product Description', 'slim-catalog' ),
				'thumbnail' => self::get_thumbnail( 'woo_short_desc.svg' ),
				'wrap'      => false,
				'options'   => array(),
			),
			'ux_slim_product_variations' => array(
				'name'      => __( 'Product Variations', 'slim-catalog' ),
				'thumbnail' => self::get_thumbnail( 'woo_add_to_cart.svg' ),
				'wrap'      => false,
				'options'   => array(),
			),
			'ux_slim_product_related' => array(
				'name'      => __( 'Related Products', 'slim-catalog' ),
				'thumbnail' => self::get_thumbnail( 'woo_upsells.svg' ),
				'options'   => array(
					'title'    => array(
						'type'    => 'textfield',
						'heading' => __( 'Title', 'slim-catalog' ),
						'default' => __( 'Related Products', 'slim-catalog' ),
					),
					'products' => array(
						'type'    => 'slider',
						'heading' => __( 'Products', 'slim-catalog' ),
						'default' => '4',
						'min'     => 1,
						'max'     => 12,
					),
					'columns'  => array(
						'type'    => 'slider',
						'heading' => __( 'Columns', 'slim-catalog' ),
						'default' => '4',
						'min'     => 1,
						'max'     => 6,
					),
					'type'     => array(
						'type'    => 'select',
						'heading' => __( 'Bố cục', 'slim-catalog' ),
						'default' => 'slider',
						'options' => array(
							'slider' => __( 'Slider', 'slim-catalog' ),
							'row'    => __( 'Hàng ngang', 'slim-catalog' ),
						),
					),
				),
			),
		);

		foreach ( $page_elements as $tag => $config ) {
			$config['category'] = $category;
			add_ux_builder_shortcode( $tag, $config );
		}
	}

	/**
	 * @param string $tag Shortcode tag.
	 * @return array<int, array<string, string>>
	 */
	private static function products_presets( $tag ) {
		return array(
			array(
				'name'    => __( 'Mặc định', 'slim-catalog' ),
				'content' => '[' . $tag . ']',
			),
			array(
				'name'    => __( 'Product Slider', 'slim-catalog' ),
				'content' => '[' . $tag . ' type="slider" columns="4" products="8" title="' . esc_attr__( 'Our Products', 'slim-catalog' ) . '"]',
			),
			array(
				'name'    => __( 'Product Row', 'slim-catalog' ),
				'content' => '[' . $tag . ' type="row" columns="4" products="8"]',
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function products_options() {
		$layout_options        = self::flatsome_builder_options(
			'commons/repeater-options.php',
			array(
				'repeater_columns'     => '4',
				'repeater_type'        => 'slider',
				'repeater_col_spacing' => 'small',
			)
		);
		$layout_options_slider = self::flatsome_builder_options( 'commons/repeater-slider.php' );

		return Slim_Catalog_I18n::localize_builder_options(
			array(
				'title'                 => array(
					'type'    => 'textfield',
					'heading' => __( 'Title', 'slim-catalog' ),
					'default' => __( 'Our Products', 'slim-catalog' ),
				),
				'subtitle'              => array(
					'type'    => 'textfield',
					'heading' => __( 'Subtitle', 'slim-catalog' ),
					'default' => '',
				),
				'layout_options'        => $layout_options,
				'layout_options_slider' => $layout_options_slider,
				'post_options'          => self::products_query_options(),
				'advanced_options'      => self::advanced_options(),
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function products_list_options() {
		return array(
			'title'            => array(
				'type'    => 'textfield',
				'heading' => __( 'Title', 'slim-catalog' ),
				'default' => '',
			),
			'post_options'     => self::products_query_options(),
			'advanced_options' => self::advanced_options(),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function products_query_options() {
		return array(
			'type'    => 'group',
			'heading' => __( 'Products', 'slim-catalog' ),
			'options' => array(
				'ids'      => array(
					'type'       => 'select',
					'heading'    => __( 'Products', 'slim-catalog' ),
					'param_name' => 'ids',
					'full_width' => true,
					'default'    => '',
					'config'     => array(
						'multiple'    => true,
						'placeholder' => __( 'Select...', 'slim-catalog' ),
						'postSelect'  => array(
							'post_type' => array( Slim_Catalog_Post_Types::POST_TYPE ),
						),
					),
				),
				'category' => array(
					'type'       => 'select',
					'heading'    => __( 'Category', 'slim-catalog' ),
					'full_width' => true,
					'conditions' => 'ids == ""',
					'default'    => '',
					'config'     => array(
						'multiple'    => false,
						'placeholder' => __( 'Select...', 'slim-catalog' ),
						'termSelect'  => array(
							'post_type'  => Slim_Catalog_Post_Types::POST_TYPE,
							'taxonomies' => Slim_Catalog_Post_Types::TAXONOMY,
						),
					),
				),
				'products' => array(
					'type'       => 'slider',
					'heading'    => __( 'Total Products', 'slim-catalog' ),
					'conditions' => 'ids == ""',
					'default'    => '8',
					'min'        => 1,
					'max'        => 24,
					'step'       => 1,
				),
				'offset'   => array(
					'type'       => 'textfield',
					'heading'    => __( 'Offset', 'slim-catalog' ),
					'conditions' => 'ids == ""',
					'default'    => '',
				),
				'featured' => array(
					'type'       => 'radio-buttons',
					'heading'    => __( 'Featured Only', 'slim-catalog' ),
					'conditions' => 'ids == ""',
					'default'    => '',
					'options'    => array(
						''     => array( 'title' => __( 'Off', 'slim-catalog' ) ),
						'true' => array( 'title' => __( 'On', 'slim-catalog' ) ),
					),
				),
				'orderby'  => array(
					'type'       => 'select',
					'heading'    => __( 'Order By', 'slim-catalog' ),
					'conditions' => 'ids == ""',
					'default'    => 'date',
					'options'    => array(
						'date'  => __( 'Date', 'slim-catalog' ),
						'title' => __( 'Title', 'slim-catalog' ),
						'rand'  => __( 'Random', 'slim-catalog' ),
					),
				),
				'order'    => array(
					'type'       => 'select',
					'heading'    => __( 'Order', 'slim-catalog' ),
					'conditions' => 'ids == ""',
					'default'    => 'DESC',
					'options'    => array(
						'ASC'  => 'ASC',
						'DESC' => 'DESC',
					),
				),
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function product_categories_options() {
		$layout_options        = self::flatsome_builder_options(
			'commons/repeater-options.php',
			array(
				'repeater_columns'     => '4',
				'repeater_type'        => 'slider',
				'repeater_col_spacing' => 'small',
			)
		);
		$layout_options_slider = self::flatsome_builder_options( 'commons/repeater-slider.php' );
		$box_styles            = self::flatsome_builder_options(
			'commons/box-styles.php',
			array(
				'default_text_align' => 'center',
			)
		);

		$options = array(
			'title'                 => array(
				'type'    => 'textfield',
				'heading' => __( 'Title', 'slim-catalog' ),
				'default' => '',
			),
			'style_options'         => array(
				'type'    => 'group',
				'heading' => __( 'Style', 'slim-catalog' ),
				'options' => array(
					'style' => array(
						'type'    => 'select',
						'heading' => __( 'Style', 'slim-catalog' ),
						'default' => 'badge',
						'options' => self::flatsome_builder_options( 'values/box-layouts.php' ) ?: array(
							'badge'   => __( 'Badge', 'slim-catalog' ),
							'normal'  => __( 'Normal', 'slim-catalog' ),
							'overlay' => __( 'Overlay', 'slim-catalog' ),
						),
					),
				),
			),
			'layout_options'        => $layout_options,
			'layout_options_slider' => $layout_options_slider,
			'cat_meta'              => array(
				'type'    => 'group',
				'heading' => __( 'Categories', 'slim-catalog' ),
				'options' => array(
					'ids'        => array(
						'type'       => 'select',
						'heading'    => __( 'Categories', 'slim-catalog' ),
						'param_name' => 'ids',
						'full_width' => true,
						'default'    => '',
						'config'     => array(
							'multiple'    => true,
							'placeholder' => __( 'Select...', 'slim-catalog' ),
							'termSelect'  => array(
								'post_type'  => Slim_Catalog_Post_Types::POST_TYPE,
								'taxonomies' => Slim_Catalog_Post_Types::TAXONOMY,
							),
						),
					),
					'number'     => array(
						'type'       => 'textfield',
						'heading'    => __( 'Total', 'slim-catalog' ),
						'conditions' => 'ids == ""',
						'default'    => '',
					),
					'offset'     => array(
						'type'       => 'textfield',
						'heading'    => __( 'Offset', 'slim-catalog' ),
						'conditions' => 'ids == ""',
						'default'    => '',
					),
					'orderby'    => array(
						'type'    => 'select',
						'heading' => __( 'Order By', 'slim-catalog' ),
						'default' => 'name',
						'options' => array(
							'name'  => __( 'Name', 'slim-catalog' ),
							'count' => __( 'Count', 'slim-catalog' ),
							'id'    => 'ID',
						),
					),
					'order'      => array(
						'type'    => 'select',
						'heading' => __( 'Order', 'slim-catalog' ),
						'default' => 'ASC',
						'options' => array(
							'ASC'  => 'ASC',
							'DESC' => 'DESC',
						),
					),
					'show_count' => array(
						'type'    => 'checkbox',
						'heading' => __( 'Show Count', 'slim-catalog' ),
						'default' => 'true',
					),
					'hide_empty' => array(
						'type'    => 'checkbox',
						'heading' => __( 'Hide Empty', 'slim-catalog' ),
						'default' => 'true',
					),
				),
			),
			'advanced_options'      => self::advanced_options(),
		);

		if ( is_array( $box_styles ) ) {
			$options = array_merge( $options, $box_styles );
		}

		return Slim_Catalog_I18n::localize_builder_options( $options );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function products_all_options() {
		return Slim_Catalog_I18n::localize_builder_options(
			array(
			'title'            => array(
				'type'    => 'textfield',
				'heading' => __( 'Title', 'slim-catalog' ),
				'default' => __( 'All Products', 'slim-catalog' ),
			),
			'subtitle'         => array(
				'type'    => 'textfield',
				'heading' => __( 'Subtitle', 'slim-catalog' ),
				'default' => '',
			),
			'layout_options'   => array(
				'type'    => 'group',
				'heading' => __( 'Layout', 'slim-catalog' ),
				'options' => array(
					'columns'  => array(
						'type'    => 'slider',
						'heading' => __( 'Columns', 'slim-catalog' ),
						'default' => '3',
						'min'     => 1,
						'max'     => 4,
					),
					'per_page' => array(
						'type'       => 'slider',
						'heading'    => __( 'Per Page', 'slim-catalog' ),
						'default'    => '12',
						'min'        => 4,
						'max'        => 48,
						'conditions' => 'pagination !== "false"',
					),
					'pagination' => array(
						'type'    => 'radio-buttons',
						'heading' => __( 'Pagination', 'slim-catalog' ),
						'default' => 'true',
						'options' => array(
							'true'  => array( 'title' => __( 'On', 'slim-catalog' ) ),
							'false' => array( 'title' => __( 'Off', 'slim-catalog' ) ),
						),
					),
					'show_categories' => array(
						'type'    => 'radio-buttons',
						'heading' => __( 'Category Pills', 'slim-catalog' ),
						'default' => 'true',
						'options' => array(
							'true'  => array( 'title' => __( 'On', 'slim-catalog' ) ),
							'false' => array( 'title' => __( 'Off', 'slim-catalog' ) ),
						),
					),
				),
			),
			'query_options'    => self::products_query_options(),
			'advanced_options' => self::advanced_options(),
			)
		);
	}

	/**
	 * @return array<string, string>
	 */
	private static function text_sizes() {
		return array(
			'xxsmall' => __( 'XX-Small', 'slim-catalog' ),
			'xsmall'  => __( 'X-Small', 'slim-catalog' ),
			'smaller' => __( 'Smaller', 'slim-catalog' ),
			'small'   => __( 'Small', 'slim-catalog' ),
			''        => __( 'Normal', 'slim-catalog' ),
			'large'   => __( 'Large', 'slim-catalog' ),
			'larger'  => __( 'Larger', 'slim-catalog' ),
			'xlarge'  => __( 'X-Large', 'slim-catalog' ),
			'xxlarge' => __( 'XX-Large', 'slim-catalog' ),
		);
	}

	/**
	 * @param array<string, string> $sizes Size options.
	 * @return array<string, mixed>
	 */
	private static function size_option( $sizes ) {
		return array(
			'type'    => 'select',
			'heading' => __( 'Size', 'slim-catalog' ),
			'default' => '',
			'options' => $sizes,
		);
	}

	/**
	 * Normalize UX Builder option values before shortcode rendering.
	 *
	 * @param array<string, mixed> $options Shortcode options.
	 * @param string               $tag     Shortcode tag.
	 * @return array<string, mixed>
	 */
	public static function normalize_builder_options( $options, $tag ) {
		$tags = array(
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
		);

		if ( ! in_array( $tag, $tags, true ) ) {
			return $options;
		}

		if ( ! empty( $options['ids'] ) && is_array( $options['ids'] ) ) {
			$options['ids'] = implode( ',', array_map( 'intval', $options['ids'] ) );
		}

		if ( ! empty( $options['category'] ) && is_numeric( $options['category'] ) ) {
			$term = get_term( (int) $options['category'], Slim_Catalog_Post_Types::TAXONOMY );

			if ( $term && ! is_wp_error( $term ) ) {
				$options['category'] = $term->slug;
			}
		}

		if ( ! empty( $options['cat'] ) && is_numeric( $options['cat'] ) ) {
			$term = get_term( (int) $options['cat'], Slim_Catalog_Post_Types::TAXONOMY );

			if ( $term && ! is_wp_error( $term ) ) {
				$options['cat']      = $term->slug;
				$options['category'] = $term->slug;
			}
		}

		if ( empty( $options['category'] ) && ! empty( $options['cat'] ) ) {
			$options['category'] = $options['cat'];
		}

		if ( empty( $options['limit'] ) && ! empty( $options['products'] ) ) {
			$options['limit'] = $options['products'];
		}

		return $options;
	}

	/**
	 * @param string               $relative_path Path relative to theme builder shortcodes directory.
	 * @param array<string, mixed> $vars          Variables exposed to the required file.
	 * @return array<string, mixed>|false
	 */
	private static function flatsome_builder_options( $relative_path, $vars = array() ) {
		$path = get_template_directory() . '/inc/builder/shortcodes/' . ltrim( $relative_path, '/' );

		if ( ! file_exists( $path ) ) {
			return false;
		}

		foreach ( $vars as $key => $value ) {
			${$key} = $value; // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		$result = require $path;

		return is_array( $result ) ? Slim_Catalog_I18n::localize_builder_options( $result ) : false;
	}

	/**
	 * @return array<string, mixed>|false
	 */
	private static function advanced_options() {
		$path = get_template_directory() . '/inc/builder/shortcodes/commons/advanced.php';

		if ( ! file_exists( $path ) ) {
			return false;
		}

		$result = require $path;

		return is_array( $result ) ? Slim_Catalog_I18n::localize_builder_options( $result ) : false;
	}

	/**
	 * @param string $file Thumbnail filename.
	 */
	private static function get_thumbnail( $file ) {
		$theme_path = get_template_directory() . '/inc/builder/shortcodes/thumbnails/' . $file;

		if ( file_exists( $theme_path ) ) {
			return get_template_directory_uri() . '/inc/builder/shortcodes/thumbnails/' . $file;
		}

		return SLIM_CATALOG_URL . 'assets/images/products.svg';
	}
}