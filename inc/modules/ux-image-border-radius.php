<?php
/**
 * UX Builder Image border radius control.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'ux_builder_shortcode_data_ux_image', 'tnstack_core_ux_image_border_radius_option' );

function tnstack_core_ux_image_border_radius_option( $data ) {
	$border_radius_option = array(
		'border_radius' => array(
			'type'       => 'slider',
			'heading'    => __( 'Border Radius' ),
			'default'    => '0',
			'unit'       => 'px',
			'max'        => '100',
			'min'        => '0',
			'responsive' => true,
			'on_change'  => array(
				'selector' => '.img-inner',
				'style'    => 'border-radius: {{ value }}px; overflow: hidden',
			),
		),
	);

	$options     = $data['options'];
	$new_options = array();

	foreach ( $options as $key => $option ) {
		$new_options[ $key ] = $option;

		if ( 'depth_hover' === $key ) {
			$new_options = array_merge( $new_options, $border_radius_option );
		}
	}

	$data['options'] = $new_options;

	return $data;
}

add_action( 'init', 'tnstack_core_register_ux_image_border_radius', 20 );

function tnstack_core_register_ux_image_border_radius() {
	$dependencies = array(
		'flatsome_get_image',
		'flatsome_parse_target_rel',
		'flatsome_position_classes',
		'get_shortcode_inline_css',
		'ux_builder_element_style_tag',
	);

	foreach ( $dependencies as $dependency ) {
		if ( ! function_exists( $dependency ) ) {
			return;
		}
	}

	remove_shortcode( 'ux_image' );
	add_shortcode( 'ux_image', 'tnstack_core_ux_image' );
}

function tnstack_core_ux_image_has_border_radius( $atts ) {
	foreach ( $atts as $key => $value ) {
		if ( 0 !== strpos( $key, 'border_radius' ) ) {
			continue;
		}

		if ( '' !== strval( $value ) && floatval( $value ) > 0 ) {
			return true;
		}
	}

	return false;
}

function tnstack_core_ux_image( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'_id'                 => 'image_' . rand(),
		'class'               => '',
		'visibility'          => '',
		'id'                  => '',
		'org_img'             => '',
		'caption'             => '',
		'animate'             => '',
		'animate_delay'       => '',
		'lightbox'            => '',
		'lightbox_image_size' => 'large',
		'lightbox_caption'    => '',
		'image_title'         => '',
		'height'              => '',
		'image_overlay'       => '',
		'image_hover'         => '',
		'image_hover_alt'     => '',
		'image_size'          => 'large',
		'icon'                => '',
		'width'               => '',
		'margin'              => '',
		'border_radius'       => '',
		'position_x'          => '',
		'position_x__sm'      => '',
		'position_x__md'      => '',
		'position_y'          => '',
		'position_y__sm'      => '',
		'position_y__md'      => '',
		'depth'               => '',
		'parallax'            => '',
		'depth_hover'         => '',
		'link'                => '',
		'target'              => '_self',
		'rel'                 => '',
	), $atts ) );

	if ( empty( $id ) ) {
		return '<div class="uxb-no-content uxb-image">Upload Image...</div>';
	}

	if ( ! array_key_exists( 'width', $atts ) ) {
		$atts['width'] = '100';
	}

	$classes = array();
	if ( $class ) {
		$classes[] = $class;
	}
	if ( $visibility ) {
		$classes[] = $visibility;
	}

	$classes_inner = array( 'img-inner' );
	$image_meta    = wp_prepare_attachment_for_js( $id );
	$image_title   = filter_var( $image_title, FILTER_VALIDATE_BOOLEAN );
	$link_atts     = array(
		'target' => $target,
		'rel'    => array( $rel ),
	);

	if ( is_numeric( $id ) ) {
		if ( ! $org_img ) {
			$org_img = wp_get_attachment_image_src( $id, $lightbox_image_size );
			$org_img = $org_img ? $org_img[0] : '';
		}
		if ( $caption && 'true' === $caption ) {
			$caption = is_array( $image_meta ) ? $image_meta['caption'] : '';
		}
	} else {
		if ( ! $org_img ) {
			$org_img = $id;
		}
	}

	$link_start = '';
	$link_end   = '';
	$link_class = '';

	if ( $link ) {
		if ( strpos( $link, 'watch?v=' ) !== false ) {
			$icon       = 'icon-play';
			$link_class = 'open-video';
			if ( ! $image_overlay ) {
				$image_overlay = 'rgba(0,0,0,.2)';
			}
		}
		$link_start = '<a class="' . esc_attr( $link_class ) . '" href="' . esc_url( $link ) . '"' . flatsome_parse_target_rel( $link_atts ) . '>';
		$link_end   = '</a>';
	} elseif ( $lightbox ) {
		$title      = $lightbox_caption ? $image_meta['caption'] : '';
		$link_start = '<a class="image-lightbox lightbox-gallery" title="' . esc_attr( $title ) . '" href="' . esc_url( $org_img ) . '">';
		$link_end   = '</a>';
	}

	if ( function_exists( 'ux_builder_is_active' ) && ux_builder_is_active() ) {
		// Positions are set by the onChange handler in the builder.
	} else {
		$classes[] = flatsome_position_classes( 'x', $position_x, $position_x__sm, $position_x__md );
		$classes[] = flatsome_position_classes( 'y', $position_y, $position_y__sm, $position_y__md );
	}

	if ( $image_hover ) {
		$classes_inner[] = 'image-' . $image_hover;
	}
	if ( $image_hover_alt ) {
		$classes_inner[] = 'image-' . $image_hover_alt;
	}
	if ( $height ) {
		$classes_inner[] = 'image-cover';
	}
	if ( $depth ) {
		$classes_inner[] = 'box-shadow-' . $depth;
	}
	if ( $depth_hover ) {
		$classes_inner[] = 'box-shadow-' . $depth_hover . '-hover';
	}
	if ( tnstack_core_ux_image_has_border_radius( $atts ) ) {
		$classes_inner[] = 'has-border-radius';
	}

	if ( $parallax ) {
		$parallax = 'data-parallax-fade="true" data-parallax="' . esc_attr( $parallax ) . '"';
	}

	$css_image_height = array(
		array( 'attribute' => 'padding-top', 'value' => $height ),
		array( 'attribute' => 'margin', 'value' => $margin ),
	);

	$classes       = implode( ' ', $classes );
	$classes_inner = implode( ' ', $classes_inner );

	ob_start();
	?>
	<div class="img has-hover <?php echo esc_attr( $classes ); ?>" id="<?php echo esc_attr( $_id ); ?>">
		<?php echo $link_start; ?>
		<?php if ( $parallax ) echo '<div ' . $parallax . '>'; ?>
		<?php if ( $animate ) echo '<div data-animate="' . esc_attr( $animate ) . '">'; ?>
		<div class="<?php echo esc_attr( $classes_inner ); ?> dark" <?php echo get_shortcode_inline_css( $css_image_height ); ?>>
			<?php echo flatsome_get_image( $id, $image_size, $caption, false, $image_title ); ?>
			<?php if ( $image_overlay ) { ?>
				<div class="overlay" style="background-color: <?php echo esc_attr( $image_overlay ); ?>"></div>
			<?php } ?>
			<?php if ( $icon ) { ?>
				<div class="absolute no-click x50 y50 md-x50 md-y50 lg-x50 lg-y50 text-shadow-2">
					<div class="overlay-icon">
						<i class="icon-play"></i>
					</div>
				</div>
			<?php } ?>

			<?php if ( $caption ) { ?>
				<div class="caption"><?php echo wp_kses_post( $caption ); ?></div>
			<?php } ?>
		</div>
		<?php if ( $animate ) echo '</div>'; ?>
		<?php if ( $parallax ) echo '</div>'; ?>
		<?php echo $link_end; ?>
		<?php
		$args = array(
			'width' => array(
				'selector' => '',
				'property' => 'width',
				'unit'     => '%',
			),
			'border_radius' => array(
				'selector' => '.img-inner',
				'property' => 'border-radius',
				'unit'     => 'px',
			),
		);
		echo ux_builder_element_style_tag( $_id, $args, $atts );
		?>
	</div>
	<?php
	$content = ob_get_contents();
	ob_end_clean();

	return $content;
}
