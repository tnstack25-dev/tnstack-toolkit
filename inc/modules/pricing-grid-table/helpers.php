<?php

function tnstack_core_pricing_grid_gradient_enabled( $atts, $prefix ) {
	if ( ! isset( $atts[ $prefix . '_gradient' ] ) ) {
		return false;
	}

	return in_array( $atts[ $prefix . '_gradient' ], array( 'true', '1', 1, true ), true );
}

function tnstack_core_pricing_grid_gradient_value( $atts, $prefix ) {
	if ( ! tnstack_core_pricing_grid_gradient_enabled( $atts, $prefix ) ) {
		return '';
	}

	$from = rawurldecode( (string) ( $atts[ $prefix . '_gradient_from' ] ?? '' ) );
	$to   = rawurldecode( (string) ( $atts[ $prefix . '_gradient_to' ] ?? '' ) );
	$from = trim( html_entity_decode( $from, ENT_QUOTES, 'UTF-8' ) );
	$to   = trim( html_entity_decode( $to, ENT_QUOTES, 'UTF-8' ) );

	if ( '' === $from || '' === $to ) {
		return '';
	}

	$angle = isset( $atts[ $prefix . '_gradient_angle' ] ) ? (int) $atts[ $prefix . '_gradient_angle' ] : 135;

	return sprintf( 'linear-gradient(%ddeg, %s, %s)', $angle, $from, $to );
}

function tnstack_core_pricing_grid_gradient_bg_style( $gradient ) {
	return '' === $gradient ? '' : 'background:' . $gradient . ' !important;';
}

function tnstack_core_pricing_grid_gradient_text_style( $gradient ) {
	return '' === $gradient
		? ''
		: 'background:' . $gradient . ' !important;-webkit-background-clip:text !important;-webkit-text-fill-color:transparent !important;background-clip:text !important;color:transparent !important;';
}

function tnstack_core_pricing_grid_template( $path ) {
	ob_start();
	include tnstack_core_path( 'inc/modules/pricing-grid-table/templates/' . $path );
	return ob_get_clean();
}

function tnstack_core_pricing_grid_column_color_options() {
	$options = array();

	for ( $i = 1; $i <= 6; $i++ ) {
		$options[ 'col_' . $i . '_bg' ] = array(
			'type'       => 'colorpicker',
			'heading'    => sprintf( __( 'Column %d Background', 'flatsome' ), $i ),
			'conditions' => 'columns >= "' . $i . '"',
			'format'     => 'rgb',
			'alpha'      => true,
			'position'   => 'bottom right',
			'default'    => '',
			'on_change'  => array(
				'recompile' => true,
			),
		);

		$options[ 'col_' . $i . '_text' ] = array(
			'type'       => 'colorpicker',
			'heading'    => sprintf( __( 'Column %d Text', 'flatsome' ), $i ),
			'conditions' => 'columns >= "' . $i . '"',
			'format'     => 'rgb',
			'alpha'      => true,
			'position'   => 'bottom right',
			'default'    => '',
			'on_change'  => array(
				'recompile' => true,
			),
		);
	}

	return $options;
}

function tnstack_core_pricing_grid_get_column_colors( $atts, $columns ) {
	$bg   = array();
	$text = array();

	for ( $i = 0; $i < $columns; $i++ ) {
		$key = $i + 1;
		$bg[]   = isset( $atts[ 'col_' . $key . '_bg' ] ) ? tnstack_core_pricing_grid_normalize_color( $atts[ 'col_' . $key . '_bg' ] ) : '';
		$text[] = isset( $atts[ 'col_' . $key . '_text' ] ) ? tnstack_core_pricing_grid_normalize_color( $atts[ 'col_' . $key . '_text' ] ) : '';
	}

	return array( $bg, $text );
}

function tnstack_core_pricing_grid_normalize_color( $color ) {
	$color = trim( html_entity_decode( (string) $color, ENT_QUOTES, 'UTF-8' ) );

	return $color;
}

function tnstack_core_pricing_grid_resolve_style( $atts, $prefix, $solid, $type = 'bg' ) {
	$gradient = tnstack_core_pricing_grid_gradient_value( $atts, $prefix );

	if ( '' !== $gradient ) {
		return 'bg' === $type
			? tnstack_core_pricing_grid_gradient_bg_style( $gradient )
			: tnstack_core_pricing_grid_gradient_text_style( $gradient );
	}

	$solid = tnstack_core_pricing_grid_normalize_color( $solid );

	if ( '' === $solid ) {
		return '';
	}

	return 'bg' === $type
		? 'background-color:' . esc_attr( $solid ) . ';'
		: 'color:' . esc_attr( $solid ) . ';';
}

function tnstack_core_pricing_grid_resolve_cell_bg_style( $row_atts, $cell_atts, $grid_atts, $col_index, $is_header ) {
	$cell_style = tnstack_core_pricing_grid_resolve_style(
		$cell_atts,
		'bg',
		$cell_atts['bg_color'] ?? '',
		'bg'
	);

	if ( '' !== $cell_style ) {
		return $cell_style;
	}

	if ( $is_header ) {
		$col_key    = $col_index + 1;
		$col_prefix = 'col_' . $col_key . '_bg';
		$col_style  = tnstack_core_pricing_grid_resolve_style(
			$grid_atts,
			$col_prefix,
			$grid_atts[ $col_prefix ] ?? '',
			'bg'
		);

		if ( '' !== $col_style ) {
			return $col_style;
		}
	}

	return tnstack_core_pricing_grid_resolve_style(
		$row_atts,
		'bg',
		$row_atts['bg_color'] ?? '',
		'bg'
	);
}

function tnstack_core_pricing_grid_resolve_cell_text_style( $row_atts, $cell_atts, $grid_atts, $col_index, $is_header ) {
	$cell_style = tnstack_core_pricing_grid_resolve_style(
		$cell_atts,
		'text',
		$cell_atts['text_color'] ?? '',
		'text'
	);

	if ( '' !== $cell_style ) {
		return $cell_style;
	}

	if ( $is_header ) {
		$col_key    = $col_index + 1;
		$col_prefix = 'col_' . $col_key . '_text';
		$col_style  = tnstack_core_pricing_grid_resolve_style(
			$grid_atts,
			$col_prefix,
			$grid_atts[ $col_prefix ] ?? '',
			'text'
		);

		if ( '' !== $col_style ) {
			return $col_style;
		}
	}

	return tnstack_core_pricing_grid_resolve_style(
		$row_atts,
		'text',
		$row_atts['text_color'] ?? '',
		'text'
	);
}

function tnstack_core_pricing_grid_cell_inline_style( $row_atts, $cell_atts, $grid_atts, $col_index, $is_header ) {
	$wrapper_style = tnstack_core_pricing_grid_resolve_cell_bg_style(
		$row_atts,
		$cell_atts,
		$grid_atts,
		$col_index,
		$is_header
	);

	$text_style = tnstack_core_pricing_grid_resolve_cell_text_style(
		$row_atts,
		$cell_atts,
		$grid_atts,
		$col_index,
		$is_header
	);

	return array(
		'wrapper' => $wrapper_style ? ' style="' . tnstack_core_pricing_grid_safe_style_attr( $wrapper_style ) . '"' : '',
		'text'    => $text_style ? ' style="' . tnstack_core_pricing_grid_safe_style_attr( $text_style ) . '"' : '',
	);
}

function tnstack_core_pricing_grid_safe_style_attr( $css ) {
	return esc_attr( preg_replace( '/\s+/', ' ', trim( (string) $css ) ) );
}

function tnstack_core_pricing_grid_register_nested_shortcodes() {
	global $shortcode_tags;

	$tags = array( 'pricing_grid_row', 'pricing_grid_cell' );

	foreach ( $tags as $tag ) {
		if ( empty( $shortcode_tags[ $tag ] ) ) {
			continue;
		}

		$callback = $shortcode_tags[ $tag ];
		$shortcode_tags[ "{$tag}_inner" ] = $callback;

		for ( $i = 1; $i <= 8; $i++ ) {
			$shortcode_tags[ "{$tag}_inner_{$i}" ] = $callback;
		}
	}
}

function tnstack_core_pricing_grid_uses_legacy_format( $content, $atts ) {
	if ( ! empty( $atts['cell_content'] ) && false === strpos( $content, '[pricing_grid_row' ) ) {
		return true;
	}

	return false;
}
