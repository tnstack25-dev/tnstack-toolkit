<?php
/**
 * Countdown timer shortcode.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'ttk_countdown', 'tnstack_countdown_shortcode' );

/**
 * @param array $atts Shortcode attributes.
 */
function tnstack_countdown_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'date'    => '',
			'label'   => '',
			'class'   => '',
		),
		$atts,
		'ttk_countdown'
	);

	if ( ! $atts['date'] ) {
		return '';
	}

	$timestamp = strtotime( $atts['date'] );
	if ( ! $timestamp ) {
		return '';
	}

	wp_enqueue_style( 'tnstack-countdown' );
	wp_enqueue_script( 'tnstack-countdown' );

	$id    = 'tnstack-cd-' . wp_unique_id();
	$class = 'tnstack-countdown' . ( $atts['class'] ? ' ' . esc_attr( $atts['class'] ) : '' );

	ob_start();
	?>
	<div class="<?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $id ); ?>" data-deadline="<?php echo esc_attr( (string) $timestamp ); ?>">
		<?php if ( $atts['label'] ) : ?>
			<p class="tnstack-countdown__label"><?php echo esc_html( $atts['label'] ); ?></p>
		<?php endif; ?>
		<div class="tnstack-countdown__grid">
			<div class="tnstack-countdown__unit"><span class="tnstack-countdown__num" data-unit="days">00</span><span class="tnstack-countdown__lbl"><?php esc_html_e( 'Ngày', 'tnstack-toolkit' ); ?></span></div>
			<div class="tnstack-countdown__unit"><span class="tnstack-countdown__num" data-unit="hours">00</span><span class="tnstack-countdown__lbl"><?php esc_html_e( 'Giờ', 'tnstack-toolkit' ); ?></span></div>
			<div class="tnstack-countdown__unit"><span class="tnstack-countdown__num" data-unit="minutes">00</span><span class="tnstack-countdown__lbl"><?php esc_html_e( 'Phút', 'tnstack-toolkit' ); ?></span></div>
			<div class="tnstack-countdown__unit"><span class="tnstack-countdown__num" data-unit="seconds">00</span><span class="tnstack-countdown__lbl"><?php esc_html_e( 'Giây', 'tnstack-toolkit' ); ?></span></div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}