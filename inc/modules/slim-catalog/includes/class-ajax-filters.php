<?php
/** AJAX product filtering shortcode. */
defined( 'ABSPATH' ) || exit;

final class Slim_Catalog_Ajax_Filters {
	public static function init() {
		add_shortcode( 'slim_product_filter', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_ajax_slim_catalog_filter', array( __CLASS__, 'ajax' ) );
		add_action( 'wp_ajax_nopriv_slim_catalog_filter', array( __CLASS__, 'ajax' ) );
	}
	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'columns' => '4', 'per_page' => '12', 'category' => '', 'show_price' => 'true' ), $atts, 'slim_product_filter' );
		Slim_Catalog_Frontend::enqueue_assets( true );
		$terms = get_terms( array( 'taxonomy' => Slim_Catalog_Post_Types::TAXONOMY, 'hide_empty' => true ) );
		ob_start(); ?>
		<section class="sc-ajax-filter" data-sc-filter data-columns="<?php echo esc_attr( max( 1, min( 6, absint( $atts['columns'] ) ) ) ); ?>" data-per-page="<?php echo esc_attr( max( 1, absint( $atts['per_page'] ) ) ); ?>">
		<form class="sc-filter-bar"><label><span>Tìm sản phẩm</span><input type="search" name="search" placeholder="Tên hoặc mã SKU"></label><label><span>Danh mục</span><select name="category"><option value="">Tất cả</option><?php if ( ! is_wp_error( $terms ) ) foreach ( $terms as $term ) : ?><option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $atts['category'], $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option><?php endforeach; ?></select></label><?php if ( 'false' !== $atts['show_price'] ) : ?><label><span>Giá từ</span><input type="number" min="0" name="min_price"></label><label><span>Đến</span><input type="number" min="0" name="max_price"></label><?php endif; ?><label><span>Sắp xếp</span><select name="orderby"><option value="date">Mới nhất</option><option value="title_asc">Tên A–Z</option><option value="price_asc">Giá tăng dần</option><option value="price_desc">Giá giảm dần</option></select></label><button type="submit" class="sc-button">Lọc</button></form>
		<div class="sc-filter-result" aria-live="polite"></div><button type="button" class="sc-button sc-filter-more" data-load-more hidden>Xem thêm</button></section><?php
		return ob_get_clean();
	}
	public static function ajax() {
		check_ajax_referer( 'slim_catalog_filter', 'nonce' );
		$page = max( 1, absint( $_POST['page'] ?? 1 ) ); $per_page = min( 48, max( 1, absint( $_POST['per_page'] ?? 12 ) ) );
		$args = array( 'post_type' => Slim_Catalog_Post_Types::POST_TYPE, 'post_status' => 'publish', 'posts_per_page' => $per_page, 'paged' => $page );
		$search = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) ); if ( $search ) { $title_ids = get_posts( array( 'post_type' => Slim_Catalog_Post_Types::POST_TYPE, 'post_status'=>'publish', 'fields'=>'ids', 'posts_per_page'=>-1, 's'=>$search ) ); $sku_ids = get_posts( array( 'post_type' => Slim_Catalog_Post_Types::POST_TYPE, 'post_status'=>'publish', 'fields' => 'ids', 'posts_per_page' => -1, 'meta_key' => '_slim_sku', 'meta_value' => $search, 'meta_compare' => 'LIKE' ) ); $args['post__in'] = array_unique( array_merge( $title_ids, $sku_ids ) ); if ( ! $args['post__in'] ) $args['post__in'] = array( 0 ); }
		$category = sanitize_title( wp_unslash( $_POST['category'] ?? '' ) ); if ( $category ) $args['tax_query'] = array( array( 'taxonomy' => Slim_Catalog_Post_Types::TAXONOMY, 'field' => 'slug', 'terms' => $category ) );
		$meta = array(); $min = isset( $_POST['min_price'] ) && '' !== $_POST['min_price'] ? (float) $_POST['min_price'] : null; $max = isset( $_POST['max_price'] ) && '' !== $_POST['max_price'] ? (float) $_POST['max_price'] : null; if ( null !== $min ) $meta[] = array( 'key' => '_slim_price', 'value' => $min, 'compare' => '>=', 'type' => 'NUMERIC' ); if ( null !== $max ) $meta[] = array( 'key' => '_slim_price', 'value' => $max, 'compare' => '<=', 'type' => 'NUMERIC' ); if ( $meta ) $args['meta_query'] = $meta;
		$orderby = sanitize_key( wp_unslash( $_POST['orderby'] ?? 'date' ) ); if ( 0 === strpos( $orderby, 'price_' ) ) { $args['meta_key'] = '_slim_price'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'price_asc' === $orderby ? 'ASC' : 'DESC'; } elseif ( 'title_asc' === $orderby ) { $args['orderby'] = 'title'; $args['order'] = 'ASC'; } else { $args['orderby'] = 'date'; $args['order'] = 'DESC'; }
		$query = new WP_Query( $args ); ob_start(); if ( $query->have_posts() ) while ( $query->have_posts() ) { $query->the_post(); $product = Slim_Catalog_Product::get( get_the_ID() ); if ( $product ) slim_catalog_product_card( $product ); } else echo '<p class="sc-empty">Không tìm thấy sản phẩm.</p>'; wp_reset_postdata();
		wp_send_json_success( array( 'html' => ob_get_clean(), 'has_more' => $page < $query->max_num_pages, 'count' => (int) $query->found_posts ) );
	}
}
Slim_Catalog_Ajax_Filters::init();
