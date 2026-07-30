<?php
/**
 * UX Builder registration for FAQ accordion.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_action( 'ux_builder_setup', 'tnstack_faq_register_builder', 25 );

function tnstack_faq_register_builder() {
	if ( ! function_exists( 'add_ux_builder_shortcode' ) ) {
		return;
	}

	add_ux_builder_shortcode(
		'ttk_faq',
		array(
			'type'     => 'container',
			'name'     => __( 'FAQ Accordion', 'tnstack-toolkit' ),
			'category' => __( 'Content', 'flatsome' ),
			'wrap'     => false,
			'nested'   => true,
			'allow'    => array( 'ttk_faq_item' ),
			'presets'  => array(
				array(
					'name'    => __( 'FAQ 2 items', 'tnstack-toolkit' ),
					'content' => '[ttk_faq][ttk_faq_item question="' . esc_attr__( 'Câu hỏi 1?', 'tnstack-toolkit' ) . '" answer="' . esc_attr__( 'Câu trả lời 1.', 'tnstack-toolkit' ) . '"][/ttk_faq_item][ttk_faq_item question="' . esc_attr__( 'Câu hỏi 2?', 'tnstack-toolkit' ) . '" answer="' . esc_attr__( 'Câu trả lời 2.', 'tnstack-toolkit' ) . '"][/ttk_faq_item][/ttk_faq]',
				),
			),
		)
	);

	add_ux_builder_shortcode(
		'ttk_faq_item',
		array(
			'type'     => 'container',
			'name'     => __( 'FAQ Item', 'tnstack-toolkit' ),
			'category' => __( 'Content', 'flatsome' ),
			'wrap'     => false,
			'options'  => array(
				'question' => array(
					'type'    => 'textfield',
					'heading' => __( 'Question', 'tnstack-toolkit' ),
				),
				'answer'   => array(
					'type'    => 'textarea',
					'heading' => __( 'Answer', 'tnstack-toolkit' ),
				),
			),
		)
	);
}