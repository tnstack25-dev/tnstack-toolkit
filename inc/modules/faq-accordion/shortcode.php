<?php
/**
 * FAQ accordion shortcodes.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'ttk_faq', 'tnstack_faq_shortcode' );
add_shortcode( 'ttk_faq_item', 'tnstack_faq_item_shortcode' );

/**
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 */
function tnstack_faq_shortcode( $atts, $content = '' ) {
	$atts = shortcode_atts( array( 'class' => '' ), $atts, 'ttk_faq' );

	wp_enqueue_style( 'tnstack-faq' );

	$class = 'tnstack-faq' . ( $atts['class'] ? ' ' . esc_attr( $atts['class'] ) : '' );

	return '<div class="' . $class . '" itemscope itemtype="https://schema.org/FAQPage">' . do_shortcode( $content ) . '</div>';
}

/**
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 */
function tnstack_faq_item_shortcode( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'question' => '',
			'answer'   => '',
		),
		$atts,
		'ttk_faq_item'
	);

	$question = $atts['question'] ?: '';
	$answer   = $atts['answer'] ?: $content;

	if ( ! $question ) {
		return '';
	}

	wp_enqueue_script( 'tnstack-faq' );

	return '<details class="tnstack-faq__item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">'
		. '<summary class="tnstack-faq__q" itemprop="name">' . esc_html( $question ) . '</summary>'
		. '<div class="tnstack-faq__a" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer"><div itemprop="text">' . wp_kses_post( wpautop( $answer ) ) . '</div></div>'
		. '</details>';
}