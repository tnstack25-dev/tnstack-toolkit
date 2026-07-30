<?php

require_once tnstack_core_path( '/inc/modules/slide-row/helpers.php' );

function tnstack_core_ux_slide_row( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'_id'                => 'slide-row-' . wp_rand(),
			'label'              => '',
			'class'              => '',
			'visibility'         => '',
			'style'              => '',
			'v_align'            => '',
			'h_align'            => '',
			'columns'            => '1',
			'columns__md'        => '',
			'columns__sm'        => '',
			'timer'              => '6000',
			'bullets'            => 'true',
			'bullet_style'       => '',
			'auto_slide'         => 'false',
			'auto_height'        => 'false',
			'slide_align'        => 'left',
			'slider_style'       => 'normal',
			'slide_width'        => '',
			'slide_width__md'    => '',
			'slide_width__sm'    => '',
			'arrows'             => 'true',
			'pause_hover'        => 'true',
			'hide_nav'           => '',
			'nav_style'          => 'circle',
			'nav_color'          => 'light',
			'nav_size'           => 'large',
			'nav_pos'            => '',
			'infinitive'         => 'true',
			'freescroll'         => 'false',
			'draggable'          => 'true',
			'selectedattraction' => '0.1',
			'friction'           => '0.6',
			'threshold'          => '5',
		),
		$atts,
		'ux_slide_row'
	);

	if ( 'hidden' === $atts['visibility'] ) {
		return '';
	}

	$classes         = tnstack_core_slide_row_classes( $atts );
	$flickity_options = tnstack_core_slide_row_flickity_options( $atts );

	$args = array(
		'slide_width' => array(
			'selector'  => '.flickity-slider > .col',
			'property'  => 'max-width',
			'important' => true,
		),
	);

	ob_start();
	?>
	<div
		class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
		id="<?php echo esc_attr( $atts['_id'] ); ?>"
		data-flickity-options='<?php echo wp_json_encode( $flickity_options ); ?>'>
		<?php echo do_shortcode( $content ); ?>
	</div>
	<?php
	if ( function_exists( 'ux_builder_element_style_tag' ) ) {
		echo ux_builder_element_style_tag( $atts['_id'], $args, $atts );
	}
	?>
	<?php
	return ob_get_clean();
}

add_shortcode( 'ux_slide_row', 'tnstack_core_ux_slide_row' );
add_action( 'init', 'tnstack_core_slide_row_register_nested_shortcodes', 15 );
