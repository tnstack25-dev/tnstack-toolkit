<?php
/**
 * Hide plugin updates for selected third-party plugins.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'site_transient_update_plugins',
	static function ( $value ) {
		$plugins_to_block = array(
			'advanced-custom-fields-pro/acf.php',
			'button-contact-vr/button-contact-vr.php',
		);

		if ( isset( $value ) && is_object( $value ) ) {
			foreach ( $plugins_to_block as $plugin ) {
				if ( isset( $value->response[ $plugin ] ) ) {
					unset( $value->response[ $plugin ] );
				}
			}
		}

		return $value;
	}
);