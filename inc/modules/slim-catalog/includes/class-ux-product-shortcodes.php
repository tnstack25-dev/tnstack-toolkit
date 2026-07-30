<?php
/**
 * WooCommerce-style UX Builder shortcodes for Slim Catalog.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

class Slim_Catalog_UX_Product_Shortcodes {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ), 15 );
		add_action( 'init', array( __CLASS__, 'register_ux_builder_post_type' ), 11 );
	}

	public static function register_ux_builder_post_type() {
		if ( function_exists( 'add_ux_builder_post_type' ) ) {
			add_ux_builder_post_type( Slim_Catalog_Post_Types::POST_TYPE );
		}
	}

	public static function register_shortcodes() {
		$shop = array(
			'ux_slim_products'           => array( __CLASS__, 'render_products' ),
			'ux_slim_featured_products'  => array( __CLASS__, 'render_featured_products' ),
			'ux_slim_latest_products'    => array( __CLASS__, 'render_latest_products' ),
			'ux_slim_products_list'      => array( __CLASS__, 'render_products_list' ),
			'ux_slim_product_categories' => array( __CLASS__, 'render_product_categories' ),
			'ux_slim_products_all'       => array( 'Slim_Catalog_UX_Builder', 'render_products_all' ),
		);

		$page = array(
			'ux_slim_product_gallery'     => array( __CLASS__, 'render_gallery' ),
			'ux_slim_product_title'       => array( __CLASS__, 'render_title' ),
			'ux_slim_product_price'       => array( __CLASS__, 'render_price' ),
			'ux_slim_product_excerpt'     => array( __CLASS__, 'render_excerpt' ),
			'ux_slim_product_cta'        => array( __CLASS__, 'render_cta' ),
			'ux_slim_product_description' => array( __CLASS__, 'render_description' ),
			'ux_slim_product_variations'  => array( __CLASS__, 'render_variations' ),
			'ux_slim_product_related'     => array( __CLASS__, 'render_related' ),
		);

		foreach ( array_merge( $shop, $page ) as $tag => $callback ) {
			add_shortcode( $tag, $callback );
		}
	}

	/**
	 * @param array<string, mixed>|string $atts Attributes.
	 */
	public static function render_products( $atts ) {
		return Slim_Catalog_Shortcodes::products_repeater(
			Slim_Catalog_UX_Builder::merge_public_atts( $atts, 'ux_slim_products' ),
			'ux_slim_products'
		);
	}

	/**
	 * @param array<string, mixed>|string $atts Attributes.
	 */
	public static function render_featured_products( $atts ) {
		$atts = Slim_Catalog_UX_Builder::merge_public_atts( $atts, 'ux_slim_featured_products' );
		$atts['featured'] = 'true';

		return Slim_Catalog_Shortcodes::products_repeater( $atts, 'ux_slim_featured_products' );
	}

	/**
	 * @param array<string, mixed>|string $atts Attributes.
	 */
	public static function render_latest_products( $atts ) {
		$atts            = Slim_Catalog_UX_Builder::merge_public_atts( $atts, 'ux_slim_latest_products' );
		$atts['orderby'] = 'date';
		$atts['order']   = 'DESC';

		return Slim_Catalog_Shortcodes::products_repeater( $atts, 'ux_slim_latest_products' );
	}

	/**
	 * @param array<string, mixed>|string $atts Attributes.
	 */
	public static function render_products_list( $atts ) {
		$atts = Slim_Catalog_UX_Builder::merge_public_atts( $atts, 'ux_slim_products_list' );

		$atts = shortcode_atts(
			array(
				'title'    => '',
				'ids'      => '',
				'products' => '8',
				'category' => '',
				'cat'      => '',
				'featured' => '',
				'orderby'  => 'date',
				'order'    => 'DESC',
				'offset'   => '',
			),
			$atts,
			'ux_slim_products_list'
		);

		Slim_Catalog_Frontend::enqueue_assets( true );

		$limit    = (int) $atts['products'];
		$category = '' !== $atts['category'] ? $atts['category'] : $atts['cat'];

		$query_args = array(
			'posts_per_page' => max( 1, $limit ),
			'orderby'        => sanitize_key( $atts['orderby'] ),
			'order'          => 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC',
			'offset'         => max( 0, (int) $atts['offset'] ),
		);

		if ( ! empty( $category ) ) {
			$query_args['category'] = sanitize_text_field( $category );
		}

		if ( ! empty( $atts['featured'] ) && in_array( $atts['featured'], array( '1', 'true', 'yes' ), true ) ) {
			$query_args['featured'] = true;
		}

		if ( ! empty( $atts['ids'] ) ) {
			$query_args['ids'] = array_map( 'intval', explode( ',', (string) $atts['ids'] ) );
		}

		$query = slim_catalog_get_products_query( $query_args );

		ob_start();
		?>
		<div class="sc-products-list-wrap">
			<?php if ( $atts['title'] ) : ?>
				<h3 class="sc-products-list__title"><?php echo esc_html( $atts['title'] ); ?></h3>
			<?php endif; ?>
			<ul class="ux-products-list sc-products-list product_list_widget">
				<?php
				if ( $query->have_posts() ) :
					while ( $query->have_posts() ) :
						$query->the_post();
						$product = Slim_Catalog_Product::get( get_the_ID() );

						if ( ! $product ) {
							continue;
						}

						$image_id = $product->get_image_id();
						?>
						<li>
							<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
								<?php
								if ( $image_id ) {
									echo wp_get_attachment_image( $image_id, 'thumbnail', false, array( 'class' => 'sc-products-list__thumb' ) );
								}
								?>
								<span class="product-title"><?php echo esc_html( $product->get_title() ); ?></span>
								<?php if ( $product->get_price_html() ) : ?>
									<span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
								<?php endif; ?>
							</a>
						</li>
						<?php
					endwhile;
					wp_reset_postdata();
				endif;
				?>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param array<string, mixed>|string $atts Attributes.
	 */
	public static function render_product_categories( $atts ) {
		$atts = Slim_Catalog_UX_Builder::merge_public_atts( $atts, 'ux_slim_product_categories' );

		if ( ! function_exists( 'get_flatsome_repeater_start' ) ) {
			return Slim_Catalog_Shortcodes::product_categories( $atts );
		}

		Slim_Catalog_Frontend::enqueue_assets( true );

		$atts = shortcode_atts(
			array(
				'_id'                 => 'sc-cats-' . wp_rand(),
				'title'               => '',
				'class'               => '',
				'visibility'          => '',
				'type'                => 'slider',
				'columns'             => '4',
				'columns__sm'         => '',
				'columns__md'         => '',
				'col_spacing'         => 'small',
				'width'               => '',
				'slider_nav_style'    => 'reveal',
				'slider_nav_position' => '',
				'slider_nav_color'    => '',
				'slider_bullets'      => 'false',
				'auto_slide'          => '',
				'infinitive'          => 'true',
				'depth'               => '',
				'depth_hover'         => '',
				'animate'             => '',
				'style'               => 'badge',
				'text_align'          => 'center',
				'show_count'          => 'true',
				'ids'                 => '',
				'number'              => '',
				'offset'              => '',
				'orderby'             => 'name',
				'order'               => 'ASC',
				'hide_empty'          => '1',
			),
			$atts,
			'ux_slim_product_categories'
		);

		if ( 'hidden' === $atts['visibility'] ) {
			return '';
		}

		$term_args = array(
			'taxonomy'   => Slim_Catalog_Post_Types::TAXONOMY,
			'hide_empty' => in_array( $atts['hide_empty'], array( '1', 'true', 'yes' ), true ),
			'orderby'    => sanitize_key( $atts['orderby'] ),
			'order'      => 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC',
			'offset'     => max( 0, (int) $atts['offset'] ),
		);

		if ( ! empty( $atts['ids'] ) ) {
			$term_args['include'] = array_map( 'intval', explode( ',', (string) $atts['ids'] ) );
			$term_args['orderby'] = 'include';
		}

		$terms = get_terms( $term_args );

		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}

		if ( ! empty( $atts['number'] ) ) {
			$terms = array_slice( $terms, 0, (int) $atts['number'] );
		}

		$repeater = array(
			'id'                  => $atts['_id'],
			'title'               => $atts['title'],
			'class'               => trim( 'sc-catalog-categories ' . $atts['class'] ),
			'visibility'          => $atts['visibility'],
			'type'                => $atts['type'],
			'columns'             => $atts['columns'],
			'columns__sm'         => $atts['columns__sm'],
			'columns__md'         => $atts['columns__md'],
			'row_spacing'         => $atts['col_spacing'],
			'row_width'           => $atts['width'],
			'slider_style'        => $atts['slider_nav_style'],
			'slider_nav_position' => $atts['slider_nav_position'],
			'slider_nav_color'    => $atts['slider_nav_color'],
			'slider_bullets'      => $atts['slider_bullets'],
			'auto_slide'          => $atts['auto_slide'],
			'infinitive'          => $atts['infinitive'],
			'depth'               => $atts['depth'],
			'depth_hover'         => $atts['depth_hover'],
		);

		$animate_attr = ( $atts['animate'] && 'none' !== $atts['animate'] ) ? ' data-animate="' . esc_attr( $atts['animate'] ) . '"' : '';
		$box_class    = 'box box-category has-hover box-' . sanitize_html_class( $atts['style'] );

		ob_start();
		get_flatsome_repeater_start( $repeater );

		foreach ( $terms as $term ) {
			$link  = get_term_link( $term );
			$image = self::get_category_image_url( $term );
			?>
			<div class="product-category col"<?php echo $animate_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<div class="col-inner">
					<a class="<?php echo esc_attr( $box_class ); ?>" href="<?php echo esc_url( $link ); ?>">
						<div class="box-image">
							<div class="image-cover">
								<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $term->name ); ?>" width="300" height="300" loading="lazy" />
							</div>
						</div>
						<div class="box-text text-<?php echo esc_attr( $atts['text_align'] ); ?>">
							<div class="box-text-inner">
								<h5 class="uppercase header-title"><?php echo esc_html( $term->name ); ?></h5>
								<?php if ( in_array( $atts['show_count'], array( '1', 'true', 'yes' ), true ) ) : ?>
									<p class="is-xsmall uppercase count"><?php echo esc_html( (string) $term->count ); ?> <?php echo esc_html( _n( 'Product', 'Products', (int) $term->count, 'slim-catalog' ) ); ?></p>
								<?php endif; ?>
							</div>
						</div>
					</a>
				</div>
			</div>
			<?php
		}

		get_flatsome_repeater_end( $atts['type'] );

		return ob_get_clean();
	}

	/**
	 * @param array<string, mixed>|string $atts Attributes.
	 */
	public static function render_gallery( $atts ) {
		$product = self::resolve_context_product( Slim_Catalog_UX_Builder::merge_public_atts( $atts, 'ux_slim_product_gallery' ) );

		if ( ! $product ) {
			return '';
		}

		Slim_Catalog_Frontend::enqueue_assets( true );

		ob_start();
		slim_catalog_get_template(
			'ux-builder/gallery',
			array( 'product' => $product )
		);
		return ob_get_clean();
	}

	/**
	 * @param array<string, mixed>|string $atts Attributes.
	 */
	public static function render_title( $atts ) {
		$atts    = shortcode_atts(
			array(
				'size'      => '',
				'divider'   => 'true',
				'uppercase' => 'false',
				'product_id' => '',
			),
			Slim_Catalog_UX_Builder::merge_public_atts( $atts, 'ux_slim_product_title' ),
			'ux_slim_product_title'
		);
		$product = self::resolve_context_product( $atts );

		if ( ! $product ) {
			return '';
		}

		$classes = array( 'sc-product-title', 'product-title-container' );
		if ( $atts['size'] ) {
			$classes[] = 'is-' . sanitize_html_class( $atts['size'] );
		}
		if ( in_array( $atts['uppercase'], array( '1', 'true', 'yes' ), true ) ) {
			$classes[] = 'is-uppercase';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<?php if ( in_array( $atts['divider'], array( '1', 'true', 'yes' ), true ) ) : ?>
				<div class="is-divider small"></div>
			<?php endif; ?>
			<h1 class="product-title entry-title"><?php echo esc_html( $product->get_title() ); ?></h1>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param array<string, mixed>|string $atts Attributes.
	 */
	public static function render_price( $atts ) {
		$atts    = shortcode_atts(
			array(
				'size'       => '',
				'product_id' => '',
			),
			Slim_Catalog_UX_Builder::merge_public_atts( $atts, 'ux_slim_product_price' ),
			'ux_slim_product_price'
		);
		$product = self::resolve_context_product( $atts );

		if ( ! $product || ! $product->get_price_html() ) {
			return '';
		}

		$class = $atts['size'] ? ' is-' . sanitize_html_class( $atts['size'] ) : '';

		return '<div class="product-price-container sc-product-price' . esc_attr( $class ) . '"><div class="price-wrapper">' . wp_kses_post( $product->get_price_html() ) . '</div></div>';
	}

	/**
	 * @param array<string, mixed>|string $atts Attributes.
	 */
	public static function render_excerpt( $atts ) {
		$product = self::resolve_context_product( Slim_Catalog_UX_Builder::merge_public_atts( $atts, 'ux_slim_product_excerpt' ) );

		if ( ! $product || ! $product->get_excerpt() ) {
			return '';
		}

		return '<div class="sc-product-excerpt product-short-description">' . wp_kses_post( wpautop( $product->get_excerpt() ) ) . '</div>';
	}

	/**
	 * @param array<string, mixed>|string $atts Attributes.
	 */
	public static function render_cta( $atts ) {
		$atts     = shortcode_atts(
			array(
				'size'       => '',
				'product_id' => '',
			),
			Slim_Catalog_UX_Builder::merge_public_atts( $atts, 'ux_slim_product_cta' ),
			'ux_slim_product_cta'
		);
		$product  = self::resolve_context_product( $atts );
		$settings = slim_catalog_get_settings();

		if ( ! $product ) {
			return '';
		}

		$class = $atts['size'] ? ' sc-button--' . sanitize_html_class( $atts['size'] ) : '';

		return '<div class="sc-product-cta product-contact-container"><a class="sc-button button primary' . esc_attr( $class ) . '" href="' . esc_url( slim_catalog_get_cta_url( $product ) ) . '">' . esc_html( $settings['cta_label'] ) . '</a></div>';
	}

	/**
	 * @param array<string, mixed>|string $atts Attributes.
	 */
	public static function render_description( $atts ) {
		$product = self::resolve_context_product( Slim_Catalog_UX_Builder::merge_public_atts( $atts, 'ux_slim_product_description' ) );

		if ( ! $product || ! $product->get_content() ) {
			return '';
		}

		ob_start();
		?>
		<div class="sc-product-description product-description">
			<h2><?php esc_html_e( 'Description', 'slim-catalog' ); ?></h2>
			<?php echo wp_kses_post( $product->get_content() ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param array<string, mixed>|string $atts Attributes.
	 */
	public static function render_variations( $atts ) {
		$product = self::resolve_context_product( Slim_Catalog_UX_Builder::merge_public_atts( $atts, 'ux_slim_product_variations' ) );

		if ( ! $product || ! $product->is_variable() ) {
			return '';
		}

		Slim_Catalog_Frontend::enqueue_assets( true );

		ob_start();
		slim_catalog_get_template(
			'product-variations',
			array( 'product' => $product )
		);
		return ob_get_clean();
	}

	/**
	 * @param array<string, mixed>|string $atts Attributes.
	 */
	public static function render_related( $atts ) {
		$atts = shortcode_atts(
			array(
				'products'   => '4',
				'columns'    => '4',
				'type'       => 'slider',
				'title'      => __( 'Related Products', 'slim-catalog' ),
				'product_id' => '',
			),
			Slim_Catalog_UX_Builder::merge_public_atts( $atts, 'ux_slim_product_related' ),
			'ux_slim_product_related'
		);

		$product = self::resolve_context_product( $atts );

		if ( ! $product ) {
			return '';
		}

		$categories = wp_list_pluck( $product->get_categories(), 'term_id' );
		$query_args = array(
			'posts_per_page' => max( 1, (int) $atts['products'] ),
			'post__not_in'   => array( $product->get_id() ),
		);

		if ( ! empty( $categories ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => Slim_Catalog_Post_Types::TAXONOMY,
					'field'    => 'term_id',
					'terms'    => $categories,
				),
			);
		}

		$related_query = slim_catalog_get_products_query( $query_args );

		if ( ! $related_query->have_posts() ) {
			return '';
		}

		$ids = wp_list_pluck( $related_query->posts, 'ID' );
		wp_reset_postdata();

		return Slim_Catalog_Shortcodes::products_repeater(
			array_merge(
				$atts,
				array(
					'ids'      => implode( ',', $ids ),
					'products' => count( $ids ),
				)
			),
			'ux_slim_product_related'
		);
	}

	/**
	 * @param WP_Term $term Category term.
	 * @return string
	 */
	private static function get_category_image_url( $term ) {
		$thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );

		if ( $thumbnail_id ) {
			$url = wp_get_attachment_image_url( $thumbnail_id, 'medium' );
			if ( $url ) {
				return $url;
			}
		}

		$products = slim_catalog_get_products_query(
			array(
				'posts_per_page' => 1,
				'category'       => $term->slug,
			)
		);

		if ( $products->have_posts() ) {
			$products->the_post();
			$product = Slim_Catalog_Product::get( get_the_ID() );
			wp_reset_postdata();

			if ( $product && $product->get_image_id() ) {
				$url = wp_get_attachment_image_url( $product->get_image_id(), 'medium' );
				if ( $url ) {
					return $url;
				}
			}
		}

		return includes_url( 'images/media/default.png' );
	}

	/**
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return Slim_Catalog_Product|null
	 */
	public static function resolve_context_product( $atts = array() ) {
		if ( ! empty( $atts['product_id'] ) ) {
			return Slim_Catalog_Product::get( (int) $atts['product_id'] );
		}

		if ( is_singular( Slim_Catalog_Post_Types::POST_TYPE ) ) {
			return Slim_Catalog_Product::get( get_the_ID() );
		}

		if ( defined( 'UX_BUILDER_AJAX_REQUEST' ) && UX_BUILDER_AJAX_REQUEST ) {
			$preview = slim_catalog_get_products_query( array( 'posts_per_page' => 1 ) );

			if ( $preview->have_posts() ) {
				$preview->the_post();
				$product = Slim_Catalog_Product::get( get_the_ID() );
				wp_reset_postdata();

				return $product;
			}
		}

		return null;
	}
}