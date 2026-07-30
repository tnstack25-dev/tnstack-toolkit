<?php
/**
 * Per-module settings storage helpers.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $slug Module slug.
 */
function tnstack_module_option_key( $slug ) {
	return 'tnstack_mod_' . sanitize_key( str_replace( '-', '_', $slug ) );
}

/**
 * @param string               $slug     Module slug.
 * @param array<string, mixed> $defaults Default settings.
 * @return array<string, mixed>
 */
function tnstack_module_get_settings( $slug, $defaults = array() ) {
	if ( ! isset( $GLOBALS['tnstack_module_settings_cache'] ) || ! is_array( $GLOBALS['tnstack_module_settings_cache'] ) ) {
		$GLOBALS['tnstack_module_settings_cache'] = array();
	}

	if ( isset( $GLOBALS['tnstack_module_settings_cache'][ $slug ] ) ) {
		return $GLOBALS['tnstack_module_settings_cache'][ $slug ];
	}

	$stored = get_option( tnstack_module_option_key( $slug ), array() );
	$stored = is_array( $stored ) ? $stored : array();

	$GLOBALS['tnstack_module_settings_cache'][ $slug ] = wp_parse_args( $stored, $defaults );

	return $GLOBALS['tnstack_module_settings_cache'][ $slug ];
}

/**
 * Clear cached module settings.
 *
 * @param string $slug Optional module slug. Empty clears all modules.
 */
function tnstack_module_flush_settings_cache( $slug = '' ) {
	if ( '' === $slug ) {
		$GLOBALS['tnstack_module_settings_cache'] = array();
		return;
	}

	if ( isset( $GLOBALS['tnstack_module_settings_cache'][ $slug ] ) ) {
		unset( $GLOBALS['tnstack_module_settings_cache'][ $slug ] );
	}
}

/**
 * @param string               $slug     Module slug.
 * @param array<string, mixed> $settings Settings payload.
 * @param array<string, mixed> $defaults Default settings.
 * @return array<string, mixed>
 */
function tnstack_module_update_settings( $slug, $settings, $defaults = array() ) {
	$merged = wp_parse_args( $settings, $defaults );
	update_option( tnstack_module_option_key( $slug ), $merged, false );
	tnstack_module_flush_settings_cache( $slug );
	$GLOBALS['tnstack_module_settings_cache'][ $slug ] = $merged;
	return $merged;
}
