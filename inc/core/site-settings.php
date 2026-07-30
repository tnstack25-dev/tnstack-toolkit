<?php
/**
 * Site-persisted module settings.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

const TNSTACK_TOOLKIT_SETTINGS_OPTION = 'tnstack_toolkit_module_settings';

/**
 * @return array<string, mixed>
 */
function tnstack_toolkit_flush_admin_settings_cache() {
	$GLOBALS['tnstack_toolkit_admin_settings_cache'] = null;
}

function tnstack_toolkit_admin_settings() {
	if ( isset( $GLOBALS['tnstack_toolkit_admin_settings_cache'] ) && null !== $GLOBALS['tnstack_toolkit_admin_settings_cache'] ) {
		return $GLOBALS['tnstack_toolkit_admin_settings_cache'];
	}

	$stored = get_option( TNSTACK_TOOLKIT_SETTINGS_OPTION, array() );
	$stored = is_array( $stored ) ? $stored : array();

	$defaults = tnstack_core_default_config();

	$settings = array(
		'profile'  => isset( $stored['profile'] ) ? sanitize_key( (string) $stored['profile'] ) : $defaults['profile'],
		'modules'  => wp_parse_args( isset( $stored['modules'] ) && is_array( $stored['modules'] ) ? $stored['modules'] : array(), $defaults['modules'] ),
		'project'  => wp_parse_args( isset( $stored['project'] ) && is_array( $stored['project'] ) ? $stored['project'] : array(), $defaults['project'] ),
		'features' => wp_parse_args( isset( $stored['features'] ) && is_array( $stored['features'] ) ? $stored['features'] : array(), $defaults['features'] ),
	);

	foreach ( $settings['modules'] as $key => $value ) {
		$settings['modules'][ $key ] = (bool) $value;
	}

	foreach ( $settings['project'] as $key => $value ) {
		$settings['project'][ $key ] = (bool) $value;
	}

	foreach ( $settings['features'] as $key => $value ) {
		$settings['features'][ $key ] = (bool) $value;
	}

	$GLOBALS['tnstack_toolkit_admin_settings_cache'] = $settings;

	return $settings;
}

/**
 * @param array<string, mixed> $input Raw form input.
 * @return array<string, mixed>
 */
function tnstack_toolkit_sanitize_admin_settings( $input ) {
	$defaults = tnstack_core_default_config();

	$sanitized = array(
		'profile'  => isset( $input['profile'] ) ? sanitize_key( (string) $input['profile'] ) : $defaults['profile'],
		'modules'  => array(),
		'project'  => array(),
		'features' => array(),
	);

	foreach ( $defaults['modules'] as $slug => $default ) {
		$sanitized['modules'][ $slug ] = ! empty( $input['modules'][ $slug ] );
	}

	foreach ( $defaults['project'] as $slug => $default ) {
		$sanitized['project'][ $slug ] = ! empty( $input['project'][ $slug ] );
	}

	foreach ( $defaults['features'] as $slug => $default ) {
		$sanitized['features'][ $slug ] = ! empty( $input['features'][ $slug ] );
	}

	$profiles = tnstack_toolkit_get_profiles();
	if ( ! isset( $profiles[ $sanitized['profile'] ] ) ) {
		$sanitized['profile'] = 'standard';
	}

	return $sanitized;
}

/**
 * @param array<string, mixed> $settings Settings payload.
 */
function tnstack_toolkit_update_admin_settings( $settings ) {
	$updated = tnstack_toolkit_sanitize_admin_settings( $settings );
	update_option( TNSTACK_TOOLKIT_SETTINGS_OPTION, $updated, false );

	tnstack_toolkit_flush_admin_settings_cache();

	if ( function_exists( 'tnstack_core_flush_config_cache' ) ) {
		tnstack_core_flush_config_cache();
	}

	if ( empty( $updated['modules']['performance'] ) ) {
		$cache_file = function_exists( 'tnstack_core_path' ) ? tnstack_core_path( 'inc/core/performance/page-cache.php' ) : '';

		if ( $cache_file && is_readable( $cache_file ) ) {
			require_once $cache_file;
		}

		if ( class_exists( 'Template_Performance_Cache', false ) ) {
			Template_Performance_Cache::update_settings( array( 'enable_page_cache' => 0 ) );
		}
	}

	if ( ! empty( $updated['modules']['slim-catalog'] ) && ! get_option( 'slim_catalog_settings' ) ) {
		$loaded = class_exists( 'TNStack_Module_Manager', false )
			? TNStack_Module_Manager::load_admin( 'slim-catalog' )
			: false;

		if ( $loaded && class_exists( 'Slim_Catalog', false ) ) {
			Slim_Catalog::activate();
		}
	}

	flush_rewrite_rules( false );
}

/**
 * @return bool
 */
function tnstack_toolkit_has_admin_settings() {
	$stored = get_option( TNSTACK_TOOLKIT_SETTINGS_OPTION, null );

	return is_array( $stored ) && ! empty( $stored );
}
