<?php

function tnstack_core_slide_row_template( $path ) {
	ob_start();
	include tnstack_core_path( 'inc/modules/slide-row/templates/' . $path );
	return ob_get_clean();
}

function tnstack_core_slide_row_register_nested_shortcodes() {
	global $shortcode_tags;

	$tag = 'ux_slide_row';

	if ( empty( $shortcode_tags[ $tag ] ) ) {
		return;
	}

	$callback = $shortcode_tags[ $tag ];
	$shortcode_tags[ "{$tag}_inner" ] = $callback;

	for ( $i = 1; $i <= 8; $i++ ) {
		$shortcode_tags[ "{$tag}_inner_{$i}" ] = $callback;
	}
}

function tnstack_core_slide_row_classes( $atts ) {
	$classes = array( 'row', 'slider', 'slide-row', 'row-slider' );

	if ( ! empty( $atts['style'] ) ) {
		$classes[] = 'row-' . $atts['style'];
	}

	if ( ! empty( $atts['v_align'] ) ) {
		$classes[] = 'align-' . $atts['v_align'];
	}

	if ( ! empty( $atts['h_align'] ) ) {
		$classes[] = 'align-' . $atts['h_align'];
	}

	$columns = max( 1, min( 6, (int) $atts['columns'] ) );
	$classes[] = 'large-columns-' . $columns;

	$columns_md = '' !== $atts['columns__md'] ? (int) $atts['columns__md'] : 0;
	if ( $columns_md > 0 ) {
		$classes[] = 'medium-columns-' . max( 1, min( 6, $columns_md ) );
	} elseif ( $columns > 3 ) {
		$classes[] = 'medium-columns-3';
	} else {
		$classes[] = 'medium-columns-' . $columns;
	}

	$columns_sm = '' !== $atts['columns__sm'] ? (int) $atts['columns__sm'] : 0;
	if ( $columns_sm > 0 ) {
		$classes[] = 'small-columns-' . max( 1, min( 6, $columns_sm ) );
	} elseif ( $columns > 2 ) {
		$classes[] = 'small-columns-2';
	} else {
		$classes[] = 'small-columns-' . $columns;
	}

	$nav_style = ! empty( $atts['nav_style'] ) ? $atts['nav_style'] : 'circle';
	$nav_color = ! empty( $atts['nav_color'] ) ? $atts['nav_color'] : 'light';
	$nav_size  = ! empty( $atts['nav_size'] ) ? $atts['nav_size'] : 'large';

	$classes[] = 'slider-nav-' . $nav_style;
	$classes[] = 'slider-nav-' . $nav_color;
	$classes[] = 'slider-nav-' . $nav_size;

	if ( ! empty( $atts['nav_pos'] ) ) {
		$classes[] = 'slider-nav-' . $atts['nav_pos'];
	}

	if ( ! empty( $atts['bullet_style'] ) ) {
		$classes[] = 'slider-nav-dots-' . $atts['bullet_style'];
	}

	if ( ! empty( $atts['slider_style'] ) && 'normal' !== $atts['slider_style'] ) {
		$classes[] = 'slider-style-' . $atts['slider_style'];
	}

	if ( ! empty( $atts['hide_nav'] ) && 'true' === $atts['hide_nav'] ) {
		$classes[] = 'slider-show-nav';
	}

	if ( ! empty( $atts['class'] ) ) {
		$classes[] = $atts['class'];
	}

	if ( ! empty( $atts['visibility'] ) ) {
		$classes[] = $atts['visibility'];
	}

	return $classes;
}

function tnstack_core_slide_row_flickity_options( $atts ) {
	$auto_slide = false;

	if ( 'true' === $atts['auto_slide'] ) {
		$auto_slide = (int) ( ! empty( $atts['timer'] ) ? $atts['timer'] : 6000 );
	}

	$columns = max( 1, min( 6, (int) $atts['columns'] ) );

	return array(
		'imagesLoaded'           => true,
		'groupCells'             => $columns,
		'dragThreshold'          => (int) $atts['threshold'],
		'cellAlign'              => $atts['slide_align'],
		'wrapAround'             => 'true' === $atts['infinitive'],
		'prevNextButtons'        => 'false' !== $atts['arrows'],
		'percentPosition'        => true,
		'pageDots'               => 'false' !== $atts['bullets'],
		'rightToLeft'            => is_rtl(),
		'autoPlay'               => $auto_slide,
		'pauseAutoPlayOnHover'   => 'true' === $atts['pause_hover'],
		'contain'                => true,
		'adaptiveHeight'         => 'true' === $atts['auto_height'],
		'freeScroll'             => 'true' === $atts['freescroll'],
		'draggable'              => 'false' !== $atts['draggable'],
		'selectedAttraction'     => (float) $atts['selectedattraction'],
		'friction'               => (float) $atts['friction'],
	);
}