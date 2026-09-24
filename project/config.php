<?php
/**
 * Active project configuration.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

return array(
	'profile' => 'standard',

	'modules' => array(
		'custom-admin-dashboard' => true,
		'custom-login-interface' => true,
		'login-authentication' => true,
		'popup-form'           => true,
		'disable-comments'     => true,
		'pricing-grid-table'   => true,
		'faq-accordion'        => true,
		'countdown-timer'      => true,
		'slim-catalog'         => true,
	),

	'project' => array(
		'disable-update-plugin' => true,
		'bypasss-acf'           => false,
	),

	'features' => array(
		'woocommerce' => true,
		'comments'    => false,
	),
);
