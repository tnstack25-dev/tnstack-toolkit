<?php

require_once tnstack_core_path( '/inc/modules/background-gradient/helpers.php' );

add_filter( 'ux_builder_shortcode_data_section', 'tnstack_core_gradient_section_options' );
add_filter( 'ux_builder_shortcode_data_row', 'tnstack_core_gradient_row_options' );
add_filter( 'ux_builder_shortcode_data_col', 'tnstack_core_gradient_col_options' );
add_filter( 'ux_builder_shortcode_data_text', 'tnstack_core_gradient_text_options' );
add_filter( 'ux_builder_shortcode_data_title', 'tnstack_core_gradient_title_options' );
add_filter( 'ux_builder_shortcode_data_pricing_grid_row', 'tnstack_core_gradient_pricing_grid_row_options' );
add_filter( 'ux_builder_shortcode_data_pricing_grid_cell', 'tnstack_core_gradient_pricing_grid_cell_options' );

function tnstack_core_gradient_section_options( $data ) {
	$data = tnstack_core_gradient_inject_into_group(
		$data,
		'background_options',
		'bg_color',
		tnstack_core_gradient_builder_options( 'bg', __( 'Background', 'flatsome' ), '.section-bg' )
	);

	if ( empty( $data['options']['layout_options']['options'] ) ) {
		return $data;
	}

	$data['options']['layout_options']['options'] = tnstack_core_gradient_inject_options_after(
		$data['options']['layout_options']['options'],
		'dark',
		tnstack_core_gradient_builder_options( 'text', __( 'Text', 'flatsome' ), '.section-content', 'text' )
	);

	return $data;
}

function tnstack_core_gradient_row_options( $data ) {
	if ( empty( $data['options'] ) ) {
		return $data;
	}

	$data['options'] = tnstack_core_gradient_inject_options_after(
		$data['options'],
		'col_bg',
		tnstack_core_gradient_builder_options( 'col_bg', __( 'Column Background', 'flatsome' ), '> .col > .col-inner' )
	);

	$data['options'] = tnstack_core_gradient_inject_options_after(
		$data['options'],
		'col_bg_radius',
		tnstack_core_gradient_builder_options( 'text', __( 'Text', 'flatsome' ), '> .col > .col-inner', 'text' )
	);

	return $data;
}

function tnstack_core_gradient_col_options( $data ) {
	if ( empty( $data['options'] ) ) {
		return $data;
	}

	$data['options'] = tnstack_core_gradient_inject_options_after(
		$data['options'],
		'bg_color',
		tnstack_core_gradient_builder_options( 'bg', __( 'Background', 'flatsome' ), '> .col-inner' )
	);

	$data['options'] = tnstack_core_gradient_inject_options_after(
		$data['options'],
		'bg_radius',
		tnstack_core_gradient_builder_options( 'text', __( 'Text', 'flatsome' ), '> .col-inner', 'text' )
	);

	return $data;
}

function tnstack_core_gradient_text_options( $data ) {
	return tnstack_core_gradient_inject_into_group(
		$data,
		'typography_options',
		'text_color',
		tnstack_core_gradient_builder_options( 'text', __( 'Text', 'flatsome' ), '> *', 'text' )
	);
}

function tnstack_core_gradient_title_options( $data ) {
	if ( empty( $data['options'] ) ) {
		return $data;
	}

	$data['options'] = tnstack_core_gradient_inject_options_after(
		$data['options'],
		'color',
		tnstack_core_gradient_builder_options( 'color', __( 'Title', 'flatsome' ), '.section-title-main', 'text' )
	);

	return $data;
}

function tnstack_core_gradient_pricing_grid_row_options( $data ) {
	if ( empty( $data['options'] ) ) {
		return $data;
	}

	$data['options'] = tnstack_core_gradient_inject_options_after(
		$data['options'],
		'bg_color',
		tnstack_core_gradient_builder_options( 'bg', __( 'Row Background', 'flatsome' ) )
	);

	$data['options'] = tnstack_core_gradient_inject_options_after(
		$data['options'],
		'text_color',
		tnstack_core_gradient_builder_options( 'text', __( 'Row Text', 'flatsome' ), '', 'text' )
	);

	return $data;
}

function tnstack_core_gradient_pricing_grid_cell_options( $data ) {
	if ( empty( $data['options'] ) ) {
		return $data;
	}

	$data['options'] = tnstack_core_gradient_inject_options_after(
		$data['options'],
		'bg_color',
		tnstack_core_gradient_builder_options( 'bg', __( 'Cell Background', 'flatsome' ) )
	);

	$data['options'] = tnstack_core_gradient_inject_options_after(
		$data['options'],
		'text_color',
		tnstack_core_gradient_builder_options( 'text', __( 'Cell Text', 'flatsome' ), '', 'text' )
	);

	return $data;
}