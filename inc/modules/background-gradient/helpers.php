<?php

function tnstack_core_gradient_is_enabled( $atts, $prefix ) {
	if ( ! isset( $atts[ $prefix . '_gradient' ] ) ) {
		return false;
	}

	$value = $atts[ $prefix . '_gradient' ];

	return in_array( $value, array( 'true', '1', 1, true ), true );
}

function tnstack_core_gradient_normalize_color( $color ) {
	$color = rawurldecode( (string) $color );
	$color = html_entity_decode( $color, ENT_QUOTES, 'UTF-8' );

	return trim( $color );
}

function tnstack_core_gradient_value( $atts, $prefix ) {
	if ( ! tnstack_core_gradient_is_enabled( $atts, $prefix ) ) {
		return '';
	}

	$from = tnstack_core_gradient_normalize_color( $atts[ $prefix . '_gradient_from' ] ?? '' );
	$to   = tnstack_core_gradient_normalize_color( $atts[ $prefix . '_gradient_to' ] ?? '' );

	if ( '' === $from || '' === $to ) {
		return '';
	}

	$angle = isset( $atts[ $prefix . '_gradient_angle' ] ) ? (int) $atts[ $prefix . '_gradient_angle' ] : 135;

	return sprintf( 'linear-gradient(%ddeg, %s, %s)', $angle, $from, $to );
}

function tnstack_core_gradient_bg_style( $gradient ) {
	if ( '' === $gradient ) {
		return '';
	}

	return 'background:' . $gradient . ' !important;';
}

function tnstack_core_gradient_text_style( $gradient ) {
	if ( '' === $gradient ) {
		return '';
	}

	return 'background:' . $gradient . ' !important;-webkit-background-clip:text !important;-webkit-text-fill-color:transparent !important;background-clip:text !important;color:transparent !important;';
}

function tnstack_core_gradient_text_content_selector( $base_selector ) {
	$text_targets = ':where(h1,h2,h3,h4,h5,h6,p,a,span,li,blockquote,small,strong,em,label,td,th)';

	$base_selector = trim( (string) $base_selector );

	if ( '' === $base_selector ) {
		return $text_targets;
	}

	return $base_selector . ' ' . $text_targets;
}

function tnstack_core_gradient_builder_options( $prefix, $heading_label, $preview_selector = '', $type = 'bg' ) {
	$recompile = array( 'recompile' => true );

	if ( 'text' === $type ) {
		$preview_style = 'background: linear-gradient({{ ' . $prefix . '_gradient_angle }}deg, {{ ' . $prefix . '_gradient_from }}, {{ ' . $prefix . '_gradient_to }});-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;color:transparent';
	} else {
		$preview_style = 'background: linear-gradient({{ ' . $prefix . '_gradient_angle }}deg, {{ ' . $prefix . '_gradient_from }}, {{ ' . $prefix . '_gradient_to }})';
	}

	$preview = '' !== $preview_selector
		? array(
			'selector' => $preview_selector,
			'style'    => $preview_style,
		)
		: null;

	return array(
		$prefix . '_gradient' => array(
			'type'    => 'radio-buttons',
			'heading' => sprintf( __( '%s Gradient', 'flatsome' ), $heading_label ),
			'default' => '',
			'options' => array(
				''     => array( 'title' => __( 'Off', 'flatsome' ) ),
				'true' => array( 'title' => __( 'On', 'flatsome' ) ),
			),
			'on_change' => $preview ? array( $preview, $recompile ) : $recompile,
		),
		$prefix . '_gradient_from' => array(
			'type'       => 'colorpicker',
			'heading'    => sprintf( __( '%s Gradient From', 'flatsome' ), $heading_label ),
			'conditions' => $prefix . '_gradient === "true"',
			'format'     => 'rgb',
			'alpha'      => true,
			'position'   => 'bottom right',
			'default'    => '',
			'on_change'  => $preview ? array( $preview, $recompile ) : $recompile,
		),
		$prefix . '_gradient_to' => array(
			'type'       => 'colorpicker',
			'heading'    => sprintf( __( '%s Gradient To', 'flatsome' ), $heading_label ),
			'conditions' => $prefix . '_gradient === "true"',
			'format'     => 'rgb',
			'alpha'      => true,
			'position'   => 'bottom right',
			'default'    => '',
			'on_change'  => $preview ? array( $preview, $recompile ) : $recompile,
		),
		$prefix . '_gradient_angle' => array(
			'type'       => 'slider',
			'heading'    => sprintf( __( '%s Gradient Angle', 'flatsome' ), $heading_label ),
			'conditions' => $prefix . '_gradient === "true"',
			'default'    => '135',
			'unit'       => 'deg',
			'min'        => 0,
			'max'        => 360,
			'on_change'  => $preview ? array( $preview, $recompile ) : $recompile,
		),
	);
}

function tnstack_core_gradient_inject_options_after( $options, $after_key, $gradient_options ) {
	if ( empty( $options ) || ! is_array( $options ) ) {
		return $options;
	}

	$new_options = array();

	foreach ( $options as $key => $option ) {
		$new_options[ $key ] = $option;

		if ( $key === $after_key ) {
			$new_options = array_merge( $new_options, $gradient_options );
		}
	}

	return $new_options;
}

function tnstack_core_gradient_inject_into_group( $data, $group_key, $after_key, $gradient_options ) {
	if ( empty( $data['options'][ $group_key ]['options'] ) ) {
		return $data;
	}

	$data['options'][ $group_key ]['options'] = tnstack_core_gradient_inject_options_after(
		$data['options'][ $group_key ]['options'],
		$after_key,
		$gradient_options
	);

	return $data;
}

function tnstack_core_gradient_extract_element_id( $output, $attr ) {
	if ( ! empty( $attr['_id'] ) ) {
		return $attr['_id'];
	}

	if ( preg_match( '/\sid=["\']([^"\']+)["\']/', $output, $matches ) ) {
		return $matches[1];
	}

	return '';
}

function tnstack_core_gradient_apply_to_shortcode_output( $tag, $atts, $output ) {
	$configs = tnstack_core_gradient_shortcode_configs();

	if ( empty( $configs[ $tag ] ) ) {
		return $output;
	}

	$id = tnstack_core_gradient_extract_element_id( $output, $atts );

	if ( '' === $id ) {
		$id = ! empty( $atts['_id'] ) ? $atts['_id'] : $tag . '_' . wp_rand();
	}

	$output = tnstack_core_gradient_ensure_element_id( $output, $id );

	$css_rules = array();

	foreach ( $configs[ $tag ] as $rule ) {
		$gradient = tnstack_core_gradient_value( $atts, $rule['prefix'] );

		if ( '' === $gradient ) {
			continue;
		}

		$style_fn = 'text' === $rule['type'] ? 'tnstack_core_gradient_text_style' : 'tnstack_core_gradient_bg_style';
		$css      = call_user_func( $style_fn, $gradient );

		if ( '' === $css ) {
			continue;
		}

		$selector_suffix = $rule['selector'];

		if ( 'col' === $tag && ! empty( $atts['sticky'] ) && false !== strpos( $selector_suffix, '> .col-inner' ) ) {
			$selector_suffix = str_replace( '> .col-inner', '> .is-sticky-column > .is-sticky-column__inner > .col-inner', $selector_suffix );
		}

		$selector = tnstack_core_gradient_build_selector( $id, $selector_suffix );
		$css_rules[ $selector ] = $css;
	}

	if ( empty( $css_rules ) ) {
		return $output;
	}

	return tnstack_core_gradient_inject_css_rules( $output, $css_rules );
}

function tnstack_core_gradient_tag_to_config( $tag ) {
	$groups = array(
		'section' => array( 'section', 'section_inner', 'section_inner_1', 'section_inner_2', 'background' ),
		'row'     => array( 'row', 'row_inner', 'row_inner_1', 'row_inner_2' ),
		'col'     => array( 'col', 'col_inner', 'col_inner_1', 'col_inner_2' ),
	);

	foreach ( $groups as $config_key => $tags ) {
		if ( in_array( $tag, $tags, true ) ) {
			return $config_key;
		}
	}

	return '';
}

function tnstack_core_gradient_shortcode_configs() {
	static $configs = null;

	if ( null !== $configs ) {
		return $configs;
	}

	$configs = array(
		'section' => array(
			array(
				'prefix'   => 'bg',
				'selector' => '',
				'type'     => 'bg',
			),
			array(
				'prefix'   => 'bg',
				'selector' => '.section-bg',
				'type'     => 'bg',
			),
			array(
				'prefix'   => 'text',
				'selector' => tnstack_core_gradient_text_content_selector( '.section-content' ),
				'type'     => 'text',
			),
		),
		'row'     => array(
			array(
				'prefix'   => 'col_bg',
				'selector' => '> .col > .col-inner',
				'type'     => 'bg',
			),
			array(
				'prefix'   => 'text',
				'selector' => tnstack_core_gradient_text_content_selector( '> .col > .col-inner' ),
				'type'     => 'text',
			),
		),
		'col'     => array(
			array(
				'prefix'   => 'bg',
				'selector' => '> .col-inner',
				'type'     => 'bg',
			),
			array(
				'prefix'   => 'text',
				'selector' => tnstack_core_gradient_text_content_selector( '> .col-inner' ),
				'type'     => 'text',
			),
		),
		'ux_text' => array(
			array(
				'prefix'   => 'text',
				'selector' => '',
				'type'     => 'text',
			),
			array(
				'prefix'   => 'text',
				'selector' => '> *',
				'type'     => 'text',
			),
		),
		'title'   => array(
			array(
				'prefix'   => 'color',
				'selector' => '.section-title-main',
				'type'     => 'text',
			),
		),
	);

	return $configs;
}

function tnstack_core_gradient_ensure_element_id( $output, $id ) {
	if ( preg_match( '/\sid=["\']' . preg_quote( $id, '/' ) . '["\']/', $output ) ) {
		return $output;
	}

	return preg_replace(
		'/(<(?:div|section|article|header|footer|span|p|h[1-6])\b)/i',
		'$1 id="' . esc_attr( $id ) . '"',
		$output,
		1
	);
}

function tnstack_core_gradient_build_selector( $id, $selector ) {
	$selector = trim( (string) $selector );

	if ( '' === $selector ) {
		return '#' . $id;
	}

	return '#' . $id . ' ' . $selector;
}

function tnstack_core_gradient_inject_css_rules( $output, $css_rules ) {
	$rules_text = '';

	foreach ( $css_rules as $selector => $declarations ) {
		$rules_text .= $selector . '{' . $declarations . '}';
	}

	if ( '' === $rules_text ) {
		return $output;
	}

	if ( preg_match( '/<style[^>]*>/i', $output ) ) {
		return preg_replace( '/(<style[^>]*>\s*)/i', '$1' . $rules_text . "\n", $output, 1 );
	}

	return $output . "\n<style>\n" . $rules_text . "\n</style>\n";
}

function tnstack_core_gradient_append_style_tag( $output, $id, $selector, $declarations ) {
	if ( '' === $id || '' === $declarations ) {
		return $output;
	}

	return tnstack_core_gradient_inject_css_rules(
		$output,
		array(
			tnstack_core_gradient_build_selector( $id, $selector ) => $declarations,
		)
	);
}