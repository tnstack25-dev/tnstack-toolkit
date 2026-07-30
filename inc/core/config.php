<?php
/**
 * Default site configuration and config loader.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<string, mixed>
 */
function tnstack_core_default_config() {
	return array(
		'profile'  => 'standard',
		'modules'  => class_exists( 'TNStack_Module_Manifest', false )
			? TNStack_Module_Manifest::module_defaults()
			: array(),
		'project'  => class_exists( 'TNStack_Module_Manifest', false )
			? TNStack_Module_Manifest::project_defaults()
			: array(),
		'features' => array(
			'woocommerce' => true,
			'comments'    => false,
		),
	);
}

/**
 * @return array<string, mixed>
 */
function tnstack_core_flush_config_cache() {
	$GLOBALS['tnstack_core_config_cache'] = null;
}

function tnstack_core_config() {
	if ( isset( $GLOBALS['tnstack_core_config_cache'] ) && null !== $GLOBALS['tnstack_core_config_cache'] ) {
		return $GLOBALS['tnstack_core_config_cache'];
	}

	$defaults = tnstack_core_default_config();
	$project  = array();

	$project_file = tnstack_core_path( 'project/config.php' );

	if ( is_readable( $project_file ) ) {
		$loaded = include $project_file;
		if ( is_array( $loaded ) ) {
			$project = $loaded;
		}
	}

	$config = array(
		'profile'  => isset( $project['profile'] ) ? sanitize_key( (string) $project['profile'] ) : $defaults['profile'],
		'modules'  => tnstack_core_normalize_boolean_map( isset( $project['modules'] ) && is_array( $project['modules'] ) ? $project['modules'] : array(), $defaults['modules'] ),
		'project'  => tnstack_core_normalize_boolean_map( isset( $project['project'] ) && is_array( $project['project'] ) ? $project['project'] : array(), $defaults['project'] ),
		'features' => tnstack_core_normalize_boolean_map( isset( $project['features'] ) && is_array( $project['features'] ) ? $project['features'] : array(), $defaults['features'] ),
	);

	if ( function_exists( 'tnstack_toolkit_has_admin_settings' ) && tnstack_toolkit_has_admin_settings() ) {
		$admin = tnstack_toolkit_admin_settings();
		$config['profile']  = $admin['profile'];
		$config['modules']  = tnstack_core_normalize_boolean_map( $admin['modules'], $defaults['modules'] );
		$config['project']  = tnstack_core_normalize_boolean_map( $admin['project'], $defaults['project'] );
		$config['features'] = tnstack_core_normalize_boolean_map( $admin['features'], $defaults['features'] );
	}

	$config = tnstack_core_apply_profile( $config );
	$filtered = apply_filters( 'tnstack_core_config', $config );
	if ( is_array( $filtered ) ) {
		$config = $filtered;
	}

	$config['modules']  = tnstack_core_normalize_boolean_map( isset( $config['modules'] ) && is_array( $config['modules'] ) ? $config['modules'] : array(), $defaults['modules'] );
	$config['project']  = tnstack_core_normalize_boolean_map( isset( $config['project'] ) && is_array( $config['project'] ) ? $config['project'] : array(), $defaults['project'] );
	$config['features'] = tnstack_core_normalize_boolean_map( isset( $config['features'] ) && is_array( $config['features'] ) ? $config['features'] : array(), $defaults['features'] );

	$GLOBALS['tnstack_core_config_cache'] = $config;

	return $config;
}

/**
 * Normalize a map of feature flags from PHP, database or JSON values.
 *
 * @param array<string, mixed> $values   Raw values.
 * @param array<string, bool>  $defaults Default values.
 * @return array<string, bool>
 */
function tnstack_core_normalize_boolean_map( $values, $defaults = array() ) {
	$normalized = wp_parse_args( is_array( $values ) ? $values : array(), $defaults );

	foreach ( $normalized as $key => $value ) {
		if ( is_bool( $value ) ) {
			continue;
		}

		$filtered = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
		$normalized[ $key ] = null === $filtered ? ! empty( $value ) : $filtered;
	}

	return $normalized;
}

/**
 * Apply built-in profile presets over module/feature defaults.
 *
 * @param array<string, mixed> $config Config array.
 * @return array<string, mixed>
 */
function tnstack_core_apply_profile( $config ) {
	$profiles = array(
		'blog' => array(
			'modules' => array(
				'pricing-grid-table' => false,
				'slide-row'          => false,
				'slim-catalog'       => false,
			),
			'features' => array(
				'woocommerce' => false,
			),
		),
		'landing' => array(
			'modules' => array(
				'disable-comments' => true,
			),
			'features' => array(
				'woocommerce' => false,
				'comments'    => false,
			),
		),
		'woocommerce' => array(
			'features' => array(
				'woocommerce' => true,
			),
		),
		'corporate' => array(
			'modules' => array(
				'pricing-grid-table' => true,
				'slide-row'          => false,
			),
		),
	);

	$profile = $config['profile'] ?? 'standard';

	if ( ! isset( $profiles[ $profile ] ) ) {
		return $config;
	}

	$preset = $profiles[ $profile ];

	if ( ! empty( $preset['modules'] ) && is_array( $preset['modules'] ) ) {
		foreach ( $preset['modules'] as $module => $enabled ) {
			$config['modules'][ $module ] = (bool) $enabled;
		}
	}

	if ( ! empty( $preset['features'] ) && is_array( $preset['features'] ) ) {
		foreach ( $preset['features'] as $feature => $enabled ) {
			$config['features'][ $feature ] = (bool) $enabled;
		}
	}

	return $config;
}

/**
 * @param string $module Module slug.
 * @return bool
 */
function tnstack_core_module_enabled( $module ) {
	$config = tnstack_core_config();

	return ! empty( $config['modules'][ $module ] );
}

/**
 * @param string $feature Feature slug.
 * @return bool
 */
function tnstack_core_feature_enabled( $feature ) {
	$config = tnstack_core_config();

	return ! empty( $config['features'][ $feature ] );
}

/**
 * @param string $relative Relative path from plugin root.
 * @return string
 */
function tnstack_core_path( $relative = '' ) {
	$base = trailingslashit( TNSTACK_TOOLKIT_PATH );

	return $relative ? $base . ltrim( $relative, '/' ) : $base;
}

/**
 * @param string $relative Relative path from plugin root.
 * @return string
 */
function tnstack_core_uri( $relative = '' ) {
	$base = trailingslashit( TNSTACK_TOOLKIT_URI );

	return $relative ? $base . ltrim( $relative, '/' ) : $base;
}
