<?php
/**
 * Honeypot spam protection for Contact Form 7.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPCF7' ) ) {
	return;
}

add_filter( 'wpcf7_form_elements', 'tnstack_cf7_add_honeypot' );
add_filter( 'wpcf7_validate', 'tnstack_cf7_validate_honeypot', 10, 2 );

function tnstack_cf7_add_honeypot( $form ) {
	$honeypot = '<span class="tnstack-hp" aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;height:0;width:0;overflow:hidden;">'
		. '<input type="text" name="tnstack_hp_field" value="" tabindex="-1" autocomplete="off" />'
		. '</span>';

	return $form . $honeypot;
}

/**
 * @param WPCF7_Validation $result Validation result.
 * @param array            $tags   Form tags.
 */
function tnstack_cf7_validate_honeypot( $result, $tags ) {
	if ( ! empty( $_POST['tnstack_hp_field'] ) ) {
		$result->invalidate( array_shift( $tags ), __( 'Spam detected.', 'tnstack-toolkit' ) );
	}

	return $result;
}