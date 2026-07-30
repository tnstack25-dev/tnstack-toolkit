<?php
/**
 * Product shortcodes.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

class Slim_Catalog_Shortcodes {

	public static function init() {
		add_shortcode( 'slim_products', array( __CLASS__, 'products_grid' ) );
		add_shortcode( 'slim_products_all', array( __CLASS__, 'products_all' ) );
		add_shortcode( 'slim_product', array( __CLASS__, 'single_product_card' ) );
		add_shortcode( 'slim_product_detail', array( __CLASS__, 'single_product_detail' ) );
		add_shortcode( 'slim_product_categories', array( __CLASS__, 'product_categories' ) );
	}

	/**
	 * Product list with Flatsome slider/row layout (UX Builder friendly).
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @param string                       $tag  Shortcode tag.
	 */
	public static function products_repeater( $atts, $tag = 'ux_slim_products' ) {
		$atts = self::normalize_grid_atts( $atts );

		$atts = shortcode_atts(
			array(
				'_id'                 => 'slim-products-' . wp_rand(),
				'title'               => '',
				'subtitle'            => '',
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
				'products'            => '8',
				'limit'               => '',
				'category'            => '',
				'cat'                 => '',
				'featured'            => '',
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ids'                 => '',
				'offset'              => '',
			),
			$atts,
			$tag
		);

		if ( 'hidden' === $atts['visibility'] ) {
			return '';
		}

		if ( ! function_exists( 'get_flatsome_repeater_start' ) ) {
			return self::products_grid( $atts );
		}

		Slim_Catalog_Frontend::enqueue_assets( true );

		$limit    = '' !== $atts['limit'] ? (int) $atts['limit'] : (int) $atts['products'];
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
		self::prime_products_cache( $query );

		$repeater = array(
			'id'                  => $atts['_id'],
			'title'               => '',
			'class'               => trim( 'sc-products-repeater ' . $atts['class'] ),
			'visibility'          => $atts['visibility'],
			'type'                => $atts['type'] ? $atts['type'] : 'row',
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

		ob_start();
		?>
		<div class="sc-products-repeater-wrap">
			<?php if ( $atts['title'] || $atts['subtitle'] ) : ?>
				<div class="row sc-products-repeater__header">
					<div class="col large-12">
						<?php if ( $atts['title'] ) : ?>
							<h3 class="section-title"><span><?php echo esc_html( $atts['title'] ); ?></span></h3>
						<?php endif; ?>
						<?php if ( $atts['subtitle'] ) : ?>
							<p class="sc-section__subtitle"><?php echo esc_html( $atts['subtitle'] ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php
			get_flatsome_repeater_start( $repeater );

			if ( $query->have_posts() ) :
				while ( $query->have_posts() ) :
					$query->the_post();
					$product = Slim_Catalog_Product::get( get_the_ID() );

					if ( ! $product ) {
						continue;
					}
					?>
					<div class="col"<?php echo $animate_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<div class="col-inner">
							<?php slim_catalog_product_card( $product ); ?>
						</div>
					</div>
					<?php
				endwhile;
				wp_reset_postdata();
			else :
				?>
				<div class="col">
					<p class="sc-empty"><?php esc_html_e( 'No products found.', 'slim-catalog' ); ?></p>
				</div>
				<?php
			endif;

			get_flatsome_repeater_end( $atts['type'] );
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public static function products_grid( $atts ) {
		$atts = self::normalize_grid_atts( $atts );

		$atts = shortcode_atts(
			array(
				'limit'    => '12',
				'columns'  => '3',
				'category' => '',
				'featured' => '',
				'orderby'  => 'date',
				'order'    => 'DESC',
				'ids'      => '',
				'title'    => '',
				'subtitle' => '',
			),
			$atts,
			'slim_products'
		);

		$query_args = array(
			'posts_per_page' => (int) $atts['limit'],
			'orderby'        => sanitize_key( $atts['orderby'] ),
			'order'          => 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC',
		);

		if ( ! empty( $atts['category'] ) ) {
			$query_args['category'] = sanitize_text_field( $atts['category'] );
		}

		if ( ! empty( $atts['featured'] ) && in_array( $atts['featured'], array( '1', 'true', 'yes' ), true ) ) {
			$query_args['featured'] = true;
		}

		if ( ! empty( $atts['ids'] ) ) {
			$query_args['ids'] = array_map( 'intval', explode( ',', $atts['ids'] ) );
		}

		$query = slim_catalog_get_products_query( $query_args );
		self::prime_products_cache( $query );

		ob_start();
		?>
		<section class="sc-section sc-products container" data-columns="<?php echo esc_attr( (int) $atts['columns'] ); ?>">
			<?php if ( $atts['title'] || $atts['subtitle'] ) : ?>
				<header class="sc-section__header">
					<?php if ( $atts['title'] ) : ?>
						<h2 class="sc-section__title"><?php echo esc_html( $atts['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( $atts['subtitle'] ) : ?>
						<p class="sc-section__subtitle"><?php echo esc_html( $atts['subtitle'] ); ?></p>
					<?php endif; ?>
				</header>
			<?php endif; ?>

			<?php if ( $query->have_posts() ) : ?>
				<div class="sc-grid" style="--sc-columns: <?php echo esc_attr( max( 1, min( 4, (int) $atts['columns'] ) ) ); ?>">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						$product = Slim_Catalog_Product::get( get_the_ID() );

						if ( $product ) {
							slim_catalog_product_card( $product );
						}
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			<?php else : ?>
				<p class="sc-empty"><?php esc_html_e( 'No products found.', 'slim-catalog' ); ?></p>
			<?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Full product catalog with optional pagination.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public static function products_all( $atts ) {
		$atts = self::normalize_grid_atts( $atts );

		$atts = shortcode_atts(
			array(
				'columns'          => '3',
				'category'         => '',
				'featured'         => '',
				'orderby'          => 'date',
				'order'            => 'DESC',
				'title'            => __( 'All Products', 'slim-catalog' ),
				'subtitle'         => '',
				'show_categories'  => 'true',
				'pagination'       => 'true',
				'per_page'         => '12',
			),
			$atts,
			'slim_products_all'
		);

		$show_all_on_page = in_array( $atts['pagination'], array( '0', 'false', 'no' ), true );
		$per_page         = $show_all_on_page ? -1 : max( 1, (int) $atts['per_page'] );

		$query_args = array(
			'posts_per_page' => $per_page,
			'paged'          => $show_all_on_page ? 1 : max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) ),
			'orderby'        => sanitize_key( $atts['orderby'] ),
			'order'          => 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC',
		);

		if ( ! empty( $atts['category'] ) ) {
			$query_args['category'] = sanitize_text_field( $atts['category'] );
		}

		if ( ! empty( $atts['featured'] ) && in_array( $atts['featured'], array( '1', 'true', 'yes' ), true ) ) {
			$query_args['featured'] = true;
		}

		$query = slim_catalog_get_products_query( $query_args );
		self::prime_products_cache( $query );

		ob_start();
		?>
		<section class="sc-section sc-products sc-products--all container" data-columns="<?php echo esc_attr( (int) $atts['columns'] ); ?>">
			<?php if ( $atts['title'] || $atts['subtitle'] ) : ?>
				<header class="sc-section__header">
					<?php if ( $atts['title'] ) : ?>
						<h2 class="sc-section__title"><?php echo esc_html( $atts['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( $atts['subtitle'] ) : ?>
						<p class="sc-section__subtitle"><?php echo esc_html( $atts['subtitle'] ); ?></p>
					<?php endif; ?>
				</header>
			<?php endif; ?>

			<?php if ( in_array( $atts['show_categories'], array( '1', 'true', 'yes' ), true ) ) : ?>
				<?php echo do_shortcode( '[slim_product_categories]' ); ?>
			<?php endif; ?>

			<?php if ( $query->have_posts() ) : ?>
				<div class="sc-grid sc-grid--archive" style="--sc-columns: <?php echo esc_attr( max( 1, min( 4, (int) $atts['columns'] ) ) ); ?>">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						$product = Slim_Catalog_Product::get( get_the_ID() );

						if ( $product ) {
							slim_catalog_product_card( $product );
						}
					endwhile;
					wp_reset_postdata();
					?>
				</div>

				<?php if ( ! $show_all_on_page && $query->max_num_pages > 1 ) : ?>
					<nav class="sc-pagination" aria-label="<?php esc_attr_e( 'Products pagination', 'slim-catalog' ); ?>">
						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'total'     => $query->max_num_pages,
									'current'   => max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) ),
									'mid_size'  => 2,
									'prev_text' => '&larr;',
									'next_text' => '&rarr;',
								)
							)
						);
						?>
					</nav>
				<?php endif; ?>
			<?php else : ?>
				<p class="sc-empty"><?php esc_html_e( 'No products found.', 'slim-catalog' ); ?></p>
			<?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public static function single_product_card( $atts ) {
		$atts = self::normalize_product_atts( $atts );

		$atts = shortcode_atts(
			array(
				'id'    => '',
				'slug'  => '',
				'style' => 'card',
			),
			$atts,
			'slim_product'
		);

		$product = self::resolve_product( $atts );

		if ( ! $product ) {
			return '';
		}

		ob_start();
		slim_catalog_product_card( $product, array( 'style' => $atts['style'] ) );
		return ob_get_clean();
	}

	/**
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public static function single_product_detail( $atts ) {
		$atts = self::normalize_product_atts( $atts );

		$atts = shortcode_atts(
			array(
				'id'   => '',
				'slug' => '',
			),
			$atts,
			'slim_product_detail'
		);

		$product = self::resolve_product( $atts );

		if ( ! $product ) {
			return '';
		}

		ob_start();
		slim_catalog_get_template(
			'product-detail',
			array(
				'product' => $product,
			)
		);
		return ob_get_clean();
	}

	/**
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public static function product_categories( $atts ) {
		$atts = shortcode_atts(
			array(
				'show_all' => 'true',
				'hide_empty' => 'false',
			),
			$atts,
			'slim_product_categories'
		);

		$terms = get_terms(
			array(
				'taxonomy'   => Slim_Catalog_Post_Types::TAXONOMY,
				'hide_empty' => in_array( $atts['hide_empty'], array( '1', 'true', 'yes' ), true ),
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		$settings    = slim_catalog_get_settings();
		$archive_url = get_post_type_archive_link( Slim_Catalog_Post_Types::POST_TYPE );

		ob_start();
		?>
		<nav class="sc-categories" aria-label="<?php esc_attr_e( 'Product categories', 'slim-catalog' ); ?>">
			<?php if ( in_array( $atts['show_all'], array( '1', 'true', 'yes' ), true ) ) : ?>
				<a class="sc-categories__pill" href="<?php echo esc_url( $archive_url ); ?>"><?php esc_html_e( 'All', 'slim-catalog' ); ?></a>
			<?php endif; ?>
			<?php foreach ( $terms as $term ) : ?>
				<a class="sc-categories__pill" href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return Slim_Catalog_Product|null
	 */
	/**
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return array<string, string>
	 */
	private static function normalize_product_atts( $atts ) {
		$atts = is_array( $atts ) ? $atts : array();

		if ( ! empty( $atts['product_id'] ) && empty( $atts['id'] ) ) {
			$atts['id'] = $atts['product_id'];
		}

		return $atts;
	}

	/**
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return array<string, string>
	 */
	private static function normalize_grid_atts( $atts ) {
		$atts = is_array( $atts ) ? $atts : array();

		if ( ! empty( $atts['ids'] ) && is_array( $atts['ids'] ) ) {
			$atts['ids'] = implode( ',', array_map( 'intval', $atts['ids'] ) );
		} elseif ( ! empty( $atts['ids'] ) && 'Array' === $atts['ids'] ) {
			$atts['ids'] = '';
		}

		if ( empty( $atts['category'] ) && ! empty( $atts['cat'] ) ) {
			$atts['category'] = $atts['cat'];
		}

		if ( empty( $atts['limit'] ) && ! empty( $atts['products'] ) ) {
			$atts['limit'] = $atts['products'];
		}

		if ( ! empty( $atts['category'] ) && is_numeric( $atts['category'] ) ) {
			$term = get_term( (int) $atts['category'], Slim_Catalog_Post_Types::TAXONOMY );

			if ( $term && ! is_wp_error( $term ) ) {
				$atts['category'] = $term->slug;
			}
		}

		if ( ! empty( $atts['cat'] ) && is_numeric( $atts['cat'] ) ) {
			$term = get_term( (int) $atts['cat'], Slim_Catalog_Post_Types::TAXONOMY );

			if ( $term && ! is_wp_error( $term ) ) {
				$atts['cat']      = $term->slug;
				$atts['category'] = $term->slug;
			}
		}

		return $atts;
	}

	/**
	 * Prime WordPress object caches before rendering product loops.
	 *
	 * @param WP_Query $query Product query.
	 */
	private static function prime_products_cache( $query ) {
		if ( ! $query instanceof WP_Query || ! $query->posts ) {
			return;
		}

		$post_ids = wp_list_pluck( $query->posts, 'ID' );

		update_meta_cache( 'post', $post_ids );
		update_object_term_cache( $post_ids, Slim_Catalog_Post_Types::POST_TYPE );
		_prime_post_caches( $post_ids, false, true );
	}

	/**
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return Slim_Catalog_Product|null
	 */
	private static function resolve_product( $atts ) {
		if ( ! empty( $atts['id'] ) ) {
			return Slim_Catalog_Product::get( (int) $atts['id'] );
		}

		if ( ! empty( $atts['slug'] ) ) {
			$post = get_page_by_path( sanitize_title( $atts['slug'] ), OBJECT, Slim_Catalog_Post_Types::POST_TYPE );

			return $post ? Slim_Catalog_Product::get( $post ) : null;
		}

		return null;
	}
}