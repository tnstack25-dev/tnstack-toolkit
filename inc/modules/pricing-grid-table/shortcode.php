<?php

require_once tnstack_core_path( '/inc/modules/pricing-grid-table/helpers.php' );

function tnstack_core_pricing_grid_parse_rows( $content ) {
	$rows = array();

	if ( ! preg_match_all( '/\[pricing_grid_row(?:_inner(?:_\d+)?)?([^\]]*)\](.*?)\[\/pricing_grid_row(?:_inner(?:_\d+)?)?\]/s', $content, $matches, PREG_SET_ORDER ) ) {
		return $rows;
	}

	foreach ( $matches as $match ) {
		$row_atts = shortcode_parse_atts( $match[1] );
		$cells    = array();

		if ( preg_match_all( '/\[pricing_grid_cell(?:_inner(?:_\d+)?)?([^\]]*)\]/', $match[2], $cell_matches, PREG_SET_ORDER ) ) {
			foreach ( $cell_matches as $cell_match ) {
				$cell_atts = shortcode_parse_atts( $cell_match[1] );
				$cells[] = array(
					'text' => isset( $cell_atts['text'] ) ? $cell_atts['text'] : '',
					'atts' => $cell_atts,
				);
			}
		}

		$rows[] = array(
			'header' => isset( $row_atts['header'] ) && 'true' === $row_atts['header'],
			'atts'   => $row_atts,
			'cells'  => $cells,
		);
	}

	return $rows;
}

function tnstack_core_pricing_grid_responsive_columns_style( $id, $atts, $default_columns ) {
	if (
		! function_exists( 'get_ux_builder_breakpoints' )
		|| ! function_exists( 'ux_builder_get_responsive_values' )
		|| ! function_exists( 'ux_builder_process_breakpoint_values' )
	) {
		return '';
	}

	$breakpoints = get_ux_builder_breakpoints();
	$values      = ux_builder_get_responsive_values(
		'columns',
		array_merge(
			$atts,
			array(
				'columns' => $default_columns,
			)
		)
	);
	$values          = ux_builder_process_breakpoint_values( $values );
	$styles          = array();
	$breakpoint_keys = array_keys( $breakpoints );

	foreach ( $values as $index => $value ) {
		if ( '' === strval( $value ) ) {
			continue;
		}

		$columns = max( 1, (int) $value );
		$rule    = '#' . $id . ' .pricing-grid-table__row, #' . $id . ' .pricing-grid-table__grid--stack { grid-template-columns: repeat(' . $columns . ', minmax(0, 1fr)); }';

		if ( 0 === (int) $index ) {
			$styles[] = $rule;
			continue;
		}

		$min_width = $breakpoints[ $breakpoint_keys[ $index - 1 ] ]['width'];
		$styles[]  = '@media (min-width: ' . $min_width . 'px) { ' . $rule . ' }';
	}

	$output = trim( implode( "\n", $styles ) );

	if ( '' === $output ) {
		return '';
	}

	return "\n<style>\n" . $output . "\n</style>\n";
}

function tnstack_core_pricing_grid_render_stack_layout( $rows, $columns, $grid_atts, $wrapper_style ) {
	ob_start();
	?>
	<div class="pricing-grid-table__grid pricing-grid-table__grid--stack" style="<?php echo esc_attr( $wrapper_style ); ?>">
		<?php for ( $col = 0; $col < $columns; $col++ ) : ?>
			<div class="pricing-grid-table__column" data-column="<?php echo esc_attr( $col + 1 ); ?>">
				<?php foreach ( $rows as $row ) : ?>
					<?php
					$cell = isset( $row['cells'][ $col ] ) ? $row['cells'][ $col ] : array(
						'text' => '',
						'atts' => array(),
					);

					$cell_class = array(
						'pricing-grid-table__cell',
						'pricing-grid-table__cell--col-' . ( $col + 1 ),
					);

					if ( $row['header'] ) {
						$cell_class[] = 'pricing-grid-table__cell--header';
					}

					$cell_style = tnstack_core_pricing_grid_cell_inline_style(
						$row['atts'],
						$cell['atts'],
						$grid_atts,
						$col,
						$row['header']
					);
					?>
					<div class="<?php echo esc_attr( implode( ' ', $cell_class ) ); ?>"<?php echo $cell_style['wrapper']; ?>>
						<span class="pricing-grid-table__cell-text"<?php echo $cell_style['text']; ?>><?php echo wp_kses_post( $cell['text'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endfor; ?>
	</div>
	<?php
	return ob_get_clean();
}

function tnstack_core_pricing_grid_render_rows_layout( $content ) {
	return '<div class="pricing-grid-table__rows">' . do_shortcode( $content ) . '</div>';
}

function tnstack_core_pricing_grid_cell( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'text'       => '',
			'bg_color'   => '',
			'text_color' => '',
		),
		$atts,
		'pricing_grid_cell'
	);

	$context = isset( $GLOBALS['tnstack_core_pricing_grid'] ) ? $GLOBALS['tnstack_core_pricing_grid'] : null;

	if ( ! $context ) {
		return '';
	}

	$col_index = (int) $context['cell_index'];
	$row_atts  = $context['current_row'];
	$is_header = ! empty( $row_atts['header'] ) && 'true' === $row_atts['header'];

	$cell_style = tnstack_core_pricing_grid_cell_inline_style(
		$row_atts,
		$atts,
		$context['grid_atts'],
		$col_index,
		$is_header
	);

	$cell_class = array( 'pricing-grid-table__cell' );

	if ( $is_header ) {
		$cell_class[] = 'pricing-grid-table__cell--header';
	}

	$text = '' !== $atts['text'] ? $atts['text'] : $content;

	$GLOBALS['tnstack_core_pricing_grid']['cell_index'] = $col_index + 1;

	return '<div class="' . esc_attr( implode( ' ', $cell_class ) ) . '"' . $cell_style['wrapper'] . '>'
		. '<span class="pricing-grid-table__cell-text"' . $cell_style['text'] . '>' . wp_kses_post( $text ) . '</span>'
		. '</div>';
}

function tnstack_core_pricing_grid_row( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'header'     => '',
			'bg_color'   => '',
			'text_color' => '',
		),
		$atts,
		'pricing_grid_row'
	);

	$row_class = array( 'pricing-grid-table__row' );

	if ( 'true' === $atts['header'] ) {
		$row_class[] = 'pricing-grid-table__row--header';
	}

	if ( isset( $GLOBALS['tnstack_core_pricing_grid'] ) ) {
		$GLOBALS['tnstack_core_pricing_grid']['cell_index'] = 0;
		$GLOBALS['tnstack_core_pricing_grid']['current_row'] = $atts;
	}

	return '<div class="' . esc_attr( implode( ' ', $row_class ) ) . '">' . do_shortcode( $content ) . '</div>';
}

function tnstack_core_pricing_grid_legacy_render( $atts ) {
	$cells   = max( 1, min( 60, (int) $atts['cells'] ) );
	$columns = max( 1, min( 6, (int) $atts['columns'] ) );
	$rows    = (int) ceil( $cells / $columns );

	$lines = preg_split( '/\r\n|\r|\n/', trim( (string) $atts['cell_content'] ) );
	$grid  = array();

	for ( $row = 0; $row < $rows; $row++ ) {
		$line  = isset( $lines[ $row ] ) ? $lines[ $row ] : '';
		$cells_in_row = array_map( 'trim', explode( '|', $line ) );

		for ( $col = 0; $col < $columns; $col++ ) {
			$grid[ $row ][ $col ] = isset( $cells_in_row[ $col ] ) ? $cells_in_row[ $col ] : '';
		}
	}

	$row_bg_lines = preg_split( '/\r\n|\r|\n/', (string) $atts['row_bg_colors'] );
	$row_text_lines = preg_split( '/\r\n|\r|\n/', (string) $atts['row_text_colors'] );
	$col_bg_lines = preg_split( '/\r\n|\r|\n/', (string) $atts['col_bg_colors'] );
	$col_text_lines = preg_split( '/\r\n|\r|\n/', (string) $atts['col_text_colors'] );

	$parsed_rows = array();

	for ( $row = 0; $row < $rows; $row++ ) {
		$row_cells = array();

		for ( $col = 0; $col < $columns; $col++ ) {
			$row_cells[] = array(
				'text' => $grid[ $row ][ $col ],
				'atts' => array(),
			);
		}

		$parsed_rows[] = array(
			'header' => 0 === $row,
			'atts'   => array(
				'bg_color'   => isset( $row_bg_lines[ $row ] ) ? trim( $row_bg_lines[ $row ] ) : '',
				'text_color' => isset( $row_text_lines[ $row ] ) ? trim( $row_text_lines[ $row ] ) : '',
			),
			'cells'  => $row_cells,
		);
	}

	$legacy_grid_atts = $atts;

	for ( $col = 0; $col < $columns; $col++ ) {
		$key = $col + 1;
		$legacy_grid_atts[ 'col_' . $key . '_bg' ]   = isset( $col_bg_lines[ $col ] ) ? trim( $col_bg_lines[ $col ] ) : '';
		$legacy_grid_atts[ 'col_' . $key . '_text' ] = isset( $col_text_lines[ $col ] ) ? trim( $col_text_lines[ $col ] ) : '';
	}

	return tnstack_core_pricing_grid_render_stack_layout(
		$parsed_rows,
		$columns,
		$legacy_grid_atts,
		sprintf(
			'--pricing-cols:%d;--pricing-gap:%dpx;--pricing-radius:%dpx;--pricing-padding:%dpx;',
			$columns,
			(int) $atts['gap'],
			(int) $atts['border_radius'],
			(int) $atts['cell_padding']
		)
	);
}

function tnstack_core_ux_pricing_grid( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'_id'             => 'pricing_grid_' . wp_rand(),
			'class'           => '',
			'visibility'      => '',
			'columns'         => '3',
			'cell_padding'    => '16',
			'gap'             => '0',
			'border_radius'   => '8',
			'mobile_layout'   => 'scroll',
			'columns__sm'     => '',
			'columns__md'     => '',
			'cells'           => '',
			'cell_content'    => '',
			'row_bg_colors'   => '',
			'row_text_colors' => '',
			'col_bg_colors'   => '',
			'col_text_colors' => '',
			'col_1_bg'        => '',
			'col_1_text'      => '',
			'col_2_bg'        => '',
			'col_2_text'      => '',
			'col_3_bg'        => '',
			'col_3_text'      => '',
			'col_4_bg'        => '',
			'col_4_text'      => '',
			'col_5_bg'        => '',
			'col_5_text'      => '',
			'col_6_bg'        => '',
			'col_6_text'      => '',
		),
		$atts,
		'ux_pricing_grid'
	);

	if ( 'hidden' === $atts['visibility'] ) {
		return '';
	}

	$columns = max( 1, min( 6, (int) $atts['columns'] ) );

	$classes = array( 'pricing-grid-table' );

	if ( $atts['class'] ) {
		$classes[] = $atts['class'];
	}
	if ( $atts['visibility'] ) {
		$classes[] = $atts['visibility'];
	}
	if ( 'stack' === $atts['mobile_layout'] ) {
		$classes[] = 'pricing-grid-table--stack';
	} else {
		$classes[] = 'pricing-grid-table--scroll';
	}

	$wrapper_style = sprintf(
		'--pricing-cols:%d;--pricing-gap:%dpx;--pricing-radius:%dpx;--pricing-padding:%dpx;',
		$columns,
		(int) $atts['gap'],
		(int) $atts['border_radius'],
		(int) $atts['cell_padding']
	);

	$body_html = '';

	if ( tnstack_core_pricing_grid_uses_legacy_format( $content, $atts ) ) {
		$body_html = tnstack_core_pricing_grid_legacy_render( $atts );
	} else {
		$parsed_rows = tnstack_core_pricing_grid_parse_rows( $content );

		$GLOBALS['tnstack_core_pricing_grid'] = array(
			'columns'     => $columns,
			'grid_atts'   => $atts,
			'cell_index'  => 0,
			'current_row' => array(),
		);

		$rows_html = tnstack_core_pricing_grid_render_rows_layout( $content );
		unset( $GLOBALS['tnstack_core_pricing_grid'] );

		if ( 'stack' === $atts['mobile_layout'] && ! empty( $parsed_rows ) ) {
			$body_html = $rows_html . tnstack_core_pricing_grid_render_stack_layout(
				$parsed_rows,
				$columns,
				$atts,
				$wrapper_style
			);
		} else {
			$body_html = $rows_html;
		}
	}

	ob_start();
	?>
	<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" id="<?php echo esc_attr( $atts['_id'] ); ?>" style="<?php echo esc_attr( $wrapper_style ); ?>">
		<div class="pricing-grid-table__outer">
			<?php echo $body_html; ?>
		</div>
		<?php echo tnstack_core_pricing_grid_responsive_columns_style( $atts['_id'], $atts, $columns ); ?>
	</div>
	<?php

	return ob_get_clean();
}

add_shortcode( 'ux_pricing_grid', 'tnstack_core_ux_pricing_grid' );
add_shortcode( 'pricing_grid_row', 'tnstack_core_pricing_grid_row' );
add_shortcode( 'pricing_grid_cell', 'tnstack_core_pricing_grid_cell' );
add_action( 'init', 'tnstack_core_pricing_grid_register_nested_shortcodes', 15 );
