<?php

add_action( 'ux_builder_setup', 'tnstack_core_register_slide_row_builder', 20 );
add_filter( 'ux_builder_shortcode_data_col', 'tnstack_core_slide_row_allow_col_parent' );

function tnstack_core_slide_row_allow_col_parent( $data ) {
	if ( empty( $data['require'] ) || ! is_array( $data['require'] ) ) {
		return $data;
	}

	$data['require'][] = 'ux_slide_row';

	return $data;
}

function tnstack_core_register_slide_row_builder() {
	if ( ! function_exists( 'add_ux_builder_shortcode' ) ) {
		return;
	}

	$thumbnail        = get_template_directory_uri() . '/inc/builder/shortcodes/thumbnails/row.svg';
	$advanced_file    = get_template_directory() . '/inc/builder/shortcodes/commons/advanced.php';
	$advanced_options = is_readable( $advanced_file ) ? require $advanced_file : array();

	add_ux_builder_shortcode(
		'ux_slide_row',
		array(
			'type'      => 'container',
			'name'      => __( 'Slide Row' ),
			'category'  => __( 'Layout' ),
			'thumbnail' => $thumbnail,
			'template'  => tnstack_core_slide_row_template( 'ux_slide_row.html' ),
			'wrap'      => false,
			'nested'    => true,
			'info'      => '{{ label || "Slide Row" }}',
			'message'   => __( 'Add columns here', 'flatsome' ),
			'allow'     => array( 'col' ),
			'toolbar'   => array(
				'show_children_selector' => true,
				'show_on_child_active'     => true,
			),
			'children'  => array(
				'draggable'     => true,
				'addable_spots' => array( 'center' ),
			),
			'presets'   => array(
				array(
					'name'    => __( 'Single Column' ),
					'content' => '[ux_slide_row columns="1" label="' . esc_attr__( 'Single Column', 'flatsome' ) . '"][col span="12" span__sm="12"][/col][col span="12" span__sm="12"][/col][col span="12" span__sm="12"][/col][/ux_slide_row]',
				),
				array(
					'name'    => __( '3 Columns' ),
					'content' => '[ux_slide_row columns="3" label="' . esc_attr__( '3 Columns', 'flatsome' ) . '"][col span="4" span__sm="12"][/col][col span="4" span__sm="12"][/col][col span="4" span__sm="12"][/col][/ux_slide_row]',
				),
				array(
					'name'    => __( '2 Columns' ),
					'content' => '[ux_slide_row columns="2" label="' . esc_attr__( '2 Columns', 'flatsome' ) . '"][col span="6" span__sm="12"][/col][col span="6" span__sm="12"][/col][/ux_slide_row]',
				),
				array(
					'name'    => __( '4 Columns' ),
					'content' => '[ux_slide_row columns="4" label="' . esc_attr__( '4 Columns', 'flatsome' ) . '"][col span="3" span__sm="6"][/col][col span="3" span__sm="6"][/col][col span="3" span__sm="6"][/col][col span="3" span__sm="6"][/col][/ux_slide_row]',
				),
			),
			'options'   => array(
				'label'          => array(
					'type'        => 'textfield',
					'heading'     => __( 'Label', 'flatsome' ),
					'placeholder' => __( 'Enter admin label...', 'flatsome' ),
				),
				'layout_options' => array(
					'type'    => 'group',
					'heading' => __( 'Layout' ),
					'options' => array(
						'columns' => array(
							'type'        => 'slider',
							'heading'     => __( 'Columns Per Slide' ),
							'description' => __( 'Set to 1 to scroll one column at a time.', 'flatsome' ),
							'default'     => '1',
							'min'         => 1,
							'max'         => 6,
							'step'        => 1,
							'responsive'  => true,
						),
						'style'   => array(
							'type'    => 'radio-buttons',
							'heading' => __( 'Column Spacing', 'flatsome' ),
							'default' => '',
							'options' => array(
								''         => array( 'title' => __( 'Normal', 'flatsome' ) ),
								'small'    => array( 'title' => __( 'Small', 'flatsome' ) ),
								'large'    => array( 'title' => __( 'Large', 'flatsome' ) ),
								'collapse' => array( 'title' => __( 'Collapse', 'flatsome' ) ),
							),
						),
						'v_align' => array(
							'type'    => 'radio-buttons',
							'heading' => __( 'Align Vertical', 'flatsome' ),
							'default' => '',
							'options' => array(
								''       => array( 'title' => __( 'Top', 'flatsome' ) ),
								'equal'  => array( 'title' => __( 'Equal', 'flatsome' ) ),
								'middle' => array( 'title' => __( 'Middle', 'flatsome' ) ),
								'bottom' => array( 'title' => __( 'Bottom', 'flatsome' ) ),
							),
						),
						'h_align' => array(
							'type'    => 'radio-buttons',
							'heading' => __( 'Align Horizontal', 'flatsome' ),
							'default' => '',
							'options' => array(
								''       => array( 'title' => __( 'Left', 'flatsome' ) ),
								'center' => array( 'title' => __( 'Center', 'flatsome' ) ),
								'right'  => array( 'title' => __( 'Right', 'flatsome' ) ),
							),
						),
						'slider_style' => array(
							'type'    => 'select',
							'heading' => __( 'Slider Style', 'flatsome' ),
							'default' => 'normal',
							'options' => array(
								'normal'    => __( 'Default', 'flatsome' ),
								'container' => __( 'Container', 'flatsome' ),
								'focus'     => __( 'Focus', 'flatsome' ),
								'shadow'    => __( 'Shadow', 'flatsome' ),
							),
						),
						'slide_width' => array(
							'type'       => 'scrubfield',
							'responsive' => true,
							'heading'    => __( 'Slide Width', 'flatsome' ),
							'placeholder' => __( 'Width in px', 'flatsome' ),
							'default'    => '',
							'min'        => '0',
						),
						'slide_align' => array(
							'type'    => 'select',
							'heading' => __( 'Slide Align', 'flatsome' ),
							'default' => 'left',
							'options' => array(
								'left'   => __( 'Left', 'flatsome' ),
								'center' => __( 'Center', 'flatsome' ),
								'right'  => __( 'Right', 'flatsome' ),
							),
						),
						'infinitive' => array(
							'type'    => 'radio-buttons',
							'heading' => __( 'Infinite Loop', 'flatsome' ),
							'default' => 'true',
							'options' => array(
								'false' => array( 'title' => __( 'Off', 'flatsome' ) ),
								'true'  => array( 'title' => __( 'On', 'flatsome' ) ),
							),
						),
						'freescroll' => array(
							'type'    => 'radio-buttons',
							'heading' => __( 'Free Scroll', 'flatsome' ),
							'default' => 'false',
							'options' => array(
								'false' => array( 'title' => __( 'Off', 'flatsome' ) ),
								'true'  => array( 'title' => __( 'On', 'flatsome' ) ),
							),
						),
						'draggable' => array(
							'type'    => 'radio-buttons',
							'heading' => __( 'Draggable', 'flatsome' ),
							'default' => 'true',
							'options' => array(
								'false' => array( 'title' => __( 'Off', 'flatsome' ) ),
								'true'  => array( 'title' => __( 'On', 'flatsome' ) ),
							),
						),
					),
				),
				'nav_options'    => array(
					'type'    => 'group',
					'heading' => __( 'Navigation' ),
					'options' => array(
						'hide_nav' => array(
							'type'    => 'radio-buttons',
							'heading' => __( 'Always Visible', 'flatsome' ),
							'default' => '',
							'options' => array(
								''     => array( 'title' => __( 'Off', 'flatsome' ) ),
								'true' => array( 'title' => __( 'On', 'flatsome' ) ),
							),
						),
						'nav_pos' => array(
							'type'    => 'select',
							'heading' => __( 'Position', 'flatsome' ),
							'default' => '',
							'options' => array(
								''        => __( 'Inside', 'flatsome' ),
								'outside' => __( 'Outside', 'flatsome' ),
							),
						),
						'nav_size' => array(
							'type'    => 'select',
							'heading' => __( 'Size', 'flatsome' ),
							'default' => 'large',
							'options' => array(
								'large'  => __( 'Large', 'flatsome' ),
								'normal' => __( 'Normal', 'flatsome' ),
							),
						),
						'arrows' => array(
							'type'    => 'radio-buttons',
							'heading' => __( 'Arrows', 'flatsome' ),
							'default' => 'true',
							'options' => array(
								'false' => array( 'title' => __( 'Off', 'flatsome' ) ),
								'true'  => array( 'title' => __( 'On', 'flatsome' ) ),
							),
						),
						'nav_style' => array(
							'type'    => 'select',
							'heading' => __( 'Arrow Style', 'flatsome' ),
							'default' => 'circle',
							'options' => array(
								'circle' => __( 'Circle', 'flatsome' ),
								'simple' => __( 'Simple', 'flatsome' ),
								'reveal' => __( 'Reveal', 'flatsome' ),
							),
						),
						'nav_color' => array(
							'type'    => 'radio-buttons',
							'heading' => __( 'Arrow Color', 'flatsome' ),
							'default' => 'light',
							'options' => array(
								'dark'  => array( 'title' => __( 'Dark', 'flatsome' ) ),
								'light' => array( 'title' => __( 'Light', 'flatsome' ) ),
							),
						),
						'bullets' => array(
							'type'    => 'radio-buttons',
							'heading' => __( 'Bullets', 'flatsome' ),
							'default' => 'true',
							'options' => array(
								'false' => array( 'title' => __( 'Off', 'flatsome' ) ),
								'true'  => array( 'title' => __( 'On', 'flatsome' ) ),
							),
						),
						'bullet_style' => array(
							'type'    => 'select',
							'heading' => __( 'Bullet Style', 'flatsome' ),
							'default' => 'circle',
							'options' => array(
								'circle'        => __( 'Circle', 'flatsome' ),
								'dashes'        => __( 'Dashes', 'flatsome' ),
								'dashes-spaced' => __( 'Dashes (Spaced)', 'flatsome' ),
								'simple'        => __( 'Simple', 'flatsome' ),
								'square'        => __( 'Square', 'flatsome' ),
							),
						),
					),
				),
				'slide_options'  => array(
					'type'    => 'group',
					'heading' => __( 'Auto Slide' ),
					'options' => array(
						'auto_slide' => array(
							'type'    => 'radio-buttons',
							'heading' => __( 'Auto Slide', 'flatsome' ),
							'default' => 'false',
							'options' => array(
								'false' => array( 'title' => __( 'Off', 'flatsome' ) ),
								'true'  => array( 'title' => __( 'On', 'flatsome' ) ),
							),
						),
						'timer' => array(
							'type'    => 'textfield',
							'heading' => __( 'Timer (ms)', 'flatsome' ),
							'default' => '6000',
						),
						'pause_hover' => array(
							'type'    => 'radio-buttons',
							'heading' => __( 'Pause on Hover', 'flatsome' ),
							'default' => 'true',
							'options' => array(
								'false' => array( 'title' => __( 'Off', 'flatsome' ) ),
								'true'  => array( 'title' => __( 'On', 'flatsome' ) ),
							),
						),
					),
				),
				'advanced_options' => $advanced_options,
			),
		)
	);

	tnstack_core_slide_row_register_nested_shortcodes();
}
