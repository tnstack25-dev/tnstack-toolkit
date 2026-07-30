<?php

add_action( 'ux_builder_setup', 'tnstack_core_register_pricing_grid_builder', 20 );

function tnstack_core_register_pricing_grid_builder() {
	if ( ! function_exists( 'add_ux_builder_shortcode' ) ) {
		return;
	}

	require_once tnstack_core_path( '/inc/modules/pricing-grid-table/helpers.php' );

	$thumbnail    = get_template_directory_uri() . '/inc/builder/shortcodes/thumbnails/price_table.svg';
	$advanced_file = get_template_directory() . '/inc/builder/shortcodes/commons/advanced.php';
	$advanced_options = is_readable( $advanced_file ) ? require $advanced_file : array();

	$default_rows = '[pricing_grid_row header="true" bg_color="rgb(99, 102, 241)" text_color="rgb(255, 255, 255)"]'
		. '[pricing_grid_cell text="Basic"][pricing_grid_cell text="Pro"][pricing_grid_cell text="Enterprise"]'
		. '[/pricing_grid_row]'
		. '[pricing_grid_row bg_color="rgb(255, 255, 255)" text_color="rgb(31, 41, 55)"]'
		. '[pricing_grid_cell text="$9/mo"][pricing_grid_cell text="$29/mo"][pricing_grid_cell text="$99/mo"]'
		. '[/pricing_grid_row]'
		. '[pricing_grid_row bg_color="rgb(248, 250, 252)" text_color="rgb(55, 65, 81)"]'
		. '[pricing_grid_cell text="5 Projects"][pricing_grid_cell text="25 Projects"][pricing_grid_cell text="Unlimited"]'
		. '[/pricing_grid_row]'
		. '[pricing_grid_row bg_color="rgb(255, 255, 255)" text_color="rgb(55, 65, 81)"]'
		. '[pricing_grid_cell text="Email Support"][pricing_grid_cell text="Priority Support"][pricing_grid_cell text="Dedicated Manager"]'
		. '[/pricing_grid_row]';

	add_ux_builder_shortcode(
		'ux_pricing_grid',
		array(
			'type'      => 'container',
			'name'      => __( 'Pricing Grid Table' ),
			'category'  => __( 'Content' ),
			'thumbnail' => $thumbnail,
			'template'  => tnstack_core_pricing_grid_template( 'pricing_grid.html' ),
			'wrap'      => false,
			'info'      => '{{ columns }} columns',
			'allow'     => array( 'pricing_grid_row' ),
			'children'  => array(
				'draggable'     => true,
				'addable_spots' => array( 'center' ),
			),
			'toolbar'   => array(
				'show_children_selector' => true,
				'show_on_child_active'     => true,
			),
			'presets'   => array(
				array(
					'name'    => __( '3 Plans' ),
					'content' => '[ux_pricing_grid columns="3" col_1_bg="rgb(99, 102, 241)" col_2_bg="rgb(139, 92, 246)" col_3_bg="rgb(236, 72, 153)" col_1_text="rgb(255, 255, 255)" col_2_text="rgb(255, 255, 255)" col_3_text="rgb(255, 255, 255)"]' . $default_rows . '[/ux_pricing_grid]',
				),
				array(
					'name'    => __( '2 Plans' ),
					'content' => '[ux_pricing_grid columns="2" col_1_bg="rgb(99, 102, 241)" col_2_bg="rgb(139, 92, 246)" col_1_text="rgb(255, 255, 255)" col_2_text="rgb(255, 255, 255)"]'
						. '[pricing_grid_row header="true" bg_color="rgb(99, 102, 241)" text_color="rgb(255, 255, 255)"]'
						. '[pricing_grid_cell text="Starter"][pricing_grid_cell text="Business"]'
						. '[/pricing_grid_row]'
						. '[pricing_grid_row bg_color="rgb(255, 255, 255)" text_color="rgb(31, 41, 55)"]'
						. '[pricing_grid_cell text="$19/mo"][pricing_grid_cell text="$79/mo"]'
						. '[/pricing_grid_row]'
						. '[pricing_grid_row bg_color="rgb(248, 250, 252)" text_color="rgb(55, 65, 81)"]'
						. '[pricing_grid_cell text="10 GB Storage"][pricing_grid_cell text="200 GB Storage"]'
						. '[/pricing_grid_row]'
						. '[/ux_pricing_grid]',
				),
			),
			'options'   => array(
				'structure_options' => array(
					'type'    => 'group',
					'heading' => __( 'Structure' ),
					'options' => array(
						'columns' => array(
							'type'        => 'slider',
							'heading'     => __( 'Columns' ),
							'description' => __( 'Add one cell per column inside each row.', 'flatsome' ),
							'default'     => '3',
							'min'         => 1,
							'max'         => 6,
							'step'        => 1,
							'responsive'  => true,
						),
					),
				),
				'column_colors'     => array(
					'type'    => 'group',
					'heading' => __( 'Header Column Colors' ),
					'options' => tnstack_core_pricing_grid_column_color_options(),
				),
				'layout_options'    => array(
					'type'    => 'group',
					'heading' => __( 'Layout' ),
					'options' => array(
						'cell_padding'  => array(
							'type'    => 'slider',
							'heading' => __( 'Cell Padding' ),
							'default' => '16',
							'unit'    => 'px',
							'min'     => 0,
							'max'     => 60,
						),
						'gap'           => array(
							'type'    => 'slider',
							'heading' => __( 'Gap' ),
							'default' => '0',
							'unit'    => 'px',
							'min'     => 0,
							'max'     => 40,
						),
						'border_radius' => array(
							'type'    => 'slider',
							'heading' => __( 'Border Radius' ),
							'default' => '8',
							'unit'    => 'px',
							'min'     => 0,
							'max'     => 40,
						),
						'mobile_layout' => array(
							'type'    => 'radio-buttons',
							'heading' => __( 'Mobile Layout' ),
							'default' => 'scroll',
							'options' => array(
								'scroll' => array( 'title' => __( 'Scroll', 'flatsome' ) ),
								'stack'  => array( 'title' => __( 'Stack', 'flatsome' ) ),
							),
						),
					),
				),
				'advanced_options'  => $advanced_options,
			),
		)
	);

	add_ux_builder_shortcode(
		'pricing_grid_row',
		array(
			'type'     => 'container',
			'name'     => __( 'Table Row' ),
			'info'     => '{{ header == "true" ? "Header row" : "Row" }}',
			'require'  => array( 'ux_pricing_grid' ),
			'hidden'   => true,
			'nested'   => true,
			'wrap'     => false,
			'template' => tnstack_core_pricing_grid_template( 'pricing_grid_row.html' ),
			'allow'    => array( 'pricing_grid_cell' ),
			'children' => array(
				'draggable'     => true,
				'addable_spots' => array( 'center' ),
			),
			'options'  => array(
				'header'     => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Header Row' ),
					'default' => '',
					'options' => array(
						''     => array( 'title' => __( 'No', 'flatsome' ) ),
						'true' => array( 'title' => __( 'Yes', 'flatsome' ) ),
					),
				),
				'bg_color'   => array(
					'type'       => 'colorpicker',
					'heading'    => __( 'Row Background' ),
					'format'     => 'rgb',
					'alpha'      => true,
					'position'   => 'bottom right',
					'default'    => '',
					'on_change'  => array(
						'selector' => '.pricing-grid-table__cell',
						'style'    => 'background-color: {{ value }}',
					),
				),
				'text_color' => array(
					'type'       => 'colorpicker',
					'heading'    => __( 'Row Text Color' ),
					'format'     => 'rgb',
					'alpha'      => true,
					'position'   => 'bottom right',
					'default'    => '',
					'on_change'  => array(
						'selector' => '.pricing-grid-table__cell-text',
						'style'    => 'color: {{ value }}',
					),
				),
			),
		)
	);

	add_ux_builder_shortcode(
		'pricing_grid_cell',
		array(
			'name'        => __( 'Table Cell' ),
			'info'        => '{{ text }}',
			'require'     => array( 'pricing_grid_row' ),
			'hidden'      => true,
			'nested'      => true,
			'wrap'        => false,
			'template'    => tnstack_core_pricing_grid_template( 'pricing_grid_cell.html' ),
			'options'     => array(
				'text'       => array(
					'type'        => 'textfield',
					'heading'     => __( 'Cell Text' ),
					'default'     => __( 'Cell', 'flatsome' ),
					'auto_focus'  => true,
					'on_change'   => array(
						'selector' => '.pricing-grid-table__cell-text',
						'content'  => '{{ value }}',
					),
				),
				'bg_color'   => array(
					'type'       => 'colorpicker',
					'heading'    => __( 'Cell Background' ),
					'format'     => 'rgb',
					'alpha'      => true,
					'position'   => 'bottom right',
					'default'    => '',
					'on_change'  => array(
						'selector' => '',
						'style'    => 'background-color: {{ value }}',
					),
				),
				'text_color' => array(
					'type'       => 'colorpicker',
					'heading'    => __( 'Cell Text Color' ),
					'format'     => 'rgb',
					'alpha'      => true,
					'position'   => 'bottom right',
					'default'    => '',
					'on_change'  => array(
						'selector' => '.pricing-grid-table__cell-text',
						'style'    => 'color: {{ value }}',
					),
				),
			),
		)
	);

	tnstack_core_pricing_grid_register_nested_shortcodes();
}
