<?php
/**
 * Template helpers.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<string, string>
 */
function slim_catalog_default_settings() {
	return array(
		'currency_symbol'   => '$',
		'currency_position' => 'before',
		'color_mode'        => 'light',
		'archive_slug'      => 'san-pham',
		'single_slug'       => 'san-pham',
		'cta_label'         => __( 'Contact Us Now', 'slim-catalog' ),
		'hotline'           => '',
		'zalo'              => '',
		'email'             => '',
		'address'           => '',
	);
}

/**
 * @param string $badge Badge slug.
 * @return string
 */
function slim_catalog_badge_label( $badge ) {
	$labels = array(
		'new'      => __( 'Mới', 'slim-catalog' ),
		'sale'     => __( 'Giảm giá', 'slim-catalog' ),
		'hot'      => __( 'Hot', 'slim-catalog' ),
		'featured' => __( 'Nổi bật', 'slim-catalog' ),
	);

	return $labels[ $badge ] ?? $badge;
}

function slim_catalog_get_settings() {
	$defaults = slim_catalog_default_settings();

	$cached = get_transient( 'slim_catalog_settings_cache' );

	if ( is_array( $cached ) ) {
		return wp_parse_args( $cached, $defaults );
	}

	$settings = wp_parse_args( get_option( 'slim_catalog_settings', array() ), $defaults );
	$settings = is_array( $settings ) ? $settings : $defaults;

	if ( ! empty( $settings['cta_label'] ) && class_exists( 'Slim_Catalog_I18n', false ) ) {
		$settings['cta_label'] = Slim_Catalog_I18n::translate_string( $settings['cta_label'] );
	}

	set_transient( 'slim_catalog_settings_cache', $settings, HOUR_IN_SECONDS );

	return $settings;
}

/**
 * @param string $phone Raw phone number.
 */
function slim_catalog_format_phone_link( $phone ) {
	$phone = preg_replace( '/[^\d+]/', '', (string) $phone );

	if ( '' === $phone ) {
		return '';
	}

	return 'tel:' . $phone;
}

/**
 * Format a phone number for display while keeping the tel: value untouched.
 *
 * Vietnamese mobile numbers are displayed as 0123.456.789.
 *
 * @param string $phone Raw phone number.
 * @return string
 */
function slim_catalog_format_phone_display( $phone ) {
	$raw      = trim( (string) $phone );
	$has_plus = 0 === strpos( $raw, '+' );
	$digits   = preg_replace( '/\D/', '', $raw );

	if ( '' === $digits ) {
		return $raw;
	}

	if ( 10 === strlen( $digits ) && '0' === $digits[0] ) {
		return substr( $digits, 0, 4 ) . '.' . substr( $digits, 4, 3 ) . '.' . substr( $digits, 7, 3 );
	}

	if ( $has_plus && 11 === strlen( $digits ) && 0 === strpos( $digits, '84' ) ) {
		return '+84.' . substr( $digits, 2, 3 ) . '.' . substr( $digits, 5, 3 ) . '.' . substr( $digits, 8, 3 );
	}

	return $raw;
}

/**
 * Convert the saved address textarea into a clean list.
 *
 * @param string $addresses One address per line.
 * @return string[]
 */
function slim_catalog_parse_addresses( $addresses ) {
	$items = preg_split( '/\r\n|\r|\n/', (string) $addresses );
	$items = array_map( 'trim', is_array( $items ) ? $items : array() );
	$items = array_filter(
		$items,
		static function ( $address ) {
			return '' !== $address;
		}
	);

	return array_values( array_unique( $items ) );
}

/**
 * @param string $zalo Zalo phone number or URL.
 */
function slim_catalog_format_zalo_link( $zalo ) {
	$zalo = trim( (string) $zalo );

	if ( '' === $zalo ) {
		return '';
	}

	if ( preg_match( '#^https?://#i', $zalo ) ) {
		return esc_url( $zalo );
	}

	$digits = preg_replace( '/\D/', '', $zalo );

	if ( '' === $digits ) {
		return '';
	}

	return 'https://zalo.me/' . rawurlencode( $digits );
}

/**
 * @return string
 */
function slim_catalog_get_contact_url() {
	$settings = slim_catalog_get_settings();

	return slim_catalog_format_phone_link( $settings['hotline'] );
}

/**
 * @return array<string, string|string[]>
 */
function slim_catalog_get_contact_details() {
	$settings = slim_catalog_get_settings();

	return array(
		'hotline' => trim( (string) $settings['hotline'] ),
		'hotline_display' => slim_catalog_format_phone_display( $settings['hotline'] ),
		'hotline_url' => slim_catalog_format_phone_link( $settings['hotline'] ),
		'zalo' => trim( (string) $settings['zalo'] ),
		'zalo_url' => slim_catalog_format_zalo_link( $settings['zalo'] ),
		'email' => sanitize_email( $settings['email'] ),
		'address' => trim( (string) $settings['address'] ),
		'addresses' => slim_catalog_parse_addresses( $settings['address'] ),
	);
}

/**
 * @param float|null $regular Regular price.
 * @param float|null $sale    Sale price.
 */
function slim_catalog_format_price( $regular, $sale = null ) {
	$settings = slim_catalog_get_settings();
	$symbol   = $settings['currency_symbol'];
	$active   = null !== $sale ? $sale : $regular;

	if ( null === $active || '' === $active ) {
		return '';
	}

	$formatted = number_format_i18n( (float) $active, 2 );

	if ( 'after' === $settings['currency_position'] ) {
		return $formatted . $symbol;
	}

	return $symbol . $formatted;
}

/**
 * @param float|null $regular Regular price.
 * @param float|null $sale    Sale price.
 */
function slim_catalog_format_price_html( $regular, $sale = null ) {
	if ( null === $regular && null === $sale ) {
		return '';
	}

	$on_sale = null !== $sale && null !== $regular && $sale < $regular;

	if ( $on_sale ) {
		return sprintf(
			'<span class="sc-price sc-price--sale"><ins>%s</ins> <del>%s</del></span>',
			esc_html( slim_catalog_format_price( $regular, $sale ) ),
			esc_html( slim_catalog_format_price( $regular ) )
		);
	}

	return sprintf(
		'<span class="sc-price">%s</span>',
		esc_html( slim_catalog_format_price( $regular ) )
	);
}

/**
 * Primary CTA links to the configured hotline.
 *
 * @param Slim_Catalog_Product|null $product Optional product object (unused, kept for backward compatibility).
 * @return string
 */
function slim_catalog_get_cta_url( $product = null ) {
	$url = slim_catalog_get_contact_url();

	return $url ? $url : '#';
}

/**
 * @param array<string, mixed> $args Query args.
 * @return WP_Query
 */
function slim_catalog_get_products_query( $args = array() ) {
	$defaults = array(
		'post_type'      => Slim_Catalog_Post_Types::POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'paged'          => max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) ),
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$query_args = wp_parse_args( $args, $defaults );

	if ( ! empty( $args['category'] ) ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => Slim_Catalog_Post_Types::TAXONOMY,
				'field'    => is_numeric( $args['category'] ) ? 'term_id' : 'slug',
				'terms'    => $args['category'],
			),
		);
	}

	if ( ! empty( $args['featured'] ) ) {
		$query_args['meta_query'] = array(
			array(
				'key'   => '_slim_featured',
				'value' => '1',
			),
		);
	}

	if ( ! empty( $args['ids'] ) ) {
		$query_args['post__in'] = array_map( 'intval', (array) $args['ids'] );
		$query_args['orderby']  = 'post__in';
	}

	if ( ! empty( $args['post__not_in'] ) ) {
		$query_args['post__not_in'] = array_map( 'intval', (array) $args['post__not_in'] );
	}

	return new WP_Query( $query_args );
}

/**
 * @param string               $slug Template slug.
 * @param array<string, mixed> $args Template arguments.
 */
function slim_catalog_get_template( $slug, $args = array() ) {
	$template = SLIM_CATALOG_PATH . 'templates/' . $slug . '.php';

	if ( ! file_exists( $template ) ) {
		return;
	}

	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	}

	include $template;
}

/**
 * @param Slim_Catalog_Product $product Product object.
 * @param array<string, mixed> $args    Card args.
 */
/**
 * @param string $path Template path relative to templates/builder/.
 * @return string
 */
function slim_catalog_builder_template( $path ) {
	return SLIM_CATALOG_PATH . 'templates/builder/' . $path;
}

function slim_catalog_product_card( $product, $args = array() ) {
	slim_catalog_get_template(
		'product-card',
		array_merge(
			array(
				'product' => $product,
			),
			$args
		)
	);
}
