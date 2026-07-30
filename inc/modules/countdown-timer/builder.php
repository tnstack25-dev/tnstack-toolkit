<?php
/**
 * UX Builder registration for countdown timer.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_action( 'ux_builder_setup', 'tnstack_countdown_register_builder', 25 );

function tnstack_countdown_register_builder() {
	if ( ! function_exists( 'add_ux_builder_shortcode' ) ) {
		return;
	}

	add_ux_builder_shortcode(
		'ttk_countdown',
		array(
			'name'     => __( 'Countdown Timer', 'tnstack-toolkit' ),
			'category' => __( 'Content', 'flatsome' ),
			'options'  => array(
				'date'  => array(
					'type'        => 'textfield',
					'heading'     => __( 'Deadline', 'tnstack-toolkit' ),
					'description' => __( 'Format: YYYY-MM-DD HH:MM:SS', 'tnstack-toolkit' ),
					'default'     => gmdate( 'Y-m-d H:i:s', strtotime( '+7 days' ) ),
				),
				'label' => array(
					'type'    => 'textfield',
					'heading' => __( 'Label', 'tnstack-toolkit' ),
					'default' => __( 'Sale ends in', 'tnstack-toolkit' ),
				),
			),
			'presets'  => array(
				array(
					'name'    => __( 'Countdown', 'tnstack-toolkit' ),
					'content' => '[ttk_countdown date="' . esc_attr( gmdate( 'Y-m-d H:i:s', strtotime( '+3 days' ) ) ) . '" label="' . esc_attr__( 'Kết thúc sau', 'tnstack-toolkit' ) . '"]',
				),
			),
		)
	);
}