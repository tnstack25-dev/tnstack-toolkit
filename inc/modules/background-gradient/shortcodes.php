<?php

require_once tnstack_core_path( '/inc/modules/background-gradient/helpers.php' );

add_action( 'init', 'tnstack_core_gradient_register_shortcode_wrappers', 20 );

function tnstack_core_gradient_register_shortcode_wrappers() {
	$handlers = array(
		'section' => array( 'callback' => 'ux_section', 'tags' => array( 'section', 'section_inner', 'section_inner_1', 'section_inner_2', 'background' ) ),
		'row'     => array( 'callback' => 'ux_row', 'tags' => array( 'row', 'row_inner', 'row_inner_1', 'row_inner_2' ) ),
		'col'     => array( 'callback' => 'ux_col', 'tags' => array( 'col', 'col_inner', 'col_inner_1', 'col_inner_2' ) ),
		'ux_text' => array( 'callback' => 'flatsome_render_ux_text_shortcode', 'tags' => array( 'ux_text' ) ),
		'title'   => array( 'callback' => 'title_shortcode', 'tags' => array( 'title' ) ),
	);

	foreach ( $handlers as $config_key => $handler ) {
		if ( ! is_callable( $handler['callback'] ) ) {
			continue;
		}

		$wrapper = tnstack_core_gradient_make_shortcode_wrapper( $handler['callback'], $config_key );

		foreach ( $handler['tags'] as $tag ) {
			if ( ! shortcode_exists( $tag ) ) {
				continue;
			}

			remove_shortcode( $tag );
			add_shortcode( $tag, $wrapper );
		}
	}
}

function tnstack_core_gradient_make_shortcode_wrapper( $callback_name, $config_key ) {
	return function ( $atts, $content = null, $shortcode_tag = '' ) use ( $callback_name, $config_key ) {
		$atts = is_array( $atts ) ? $atts : array();

		$output = call_user_func( $callback_name, $atts, $content, $shortcode_tag );

		return tnstack_core_gradient_apply_to_shortcode_output( $config_key, $atts, $output );
	};
}