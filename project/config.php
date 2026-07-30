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
		'performance'            => true,
		'webp-converter'         => true,
		'disable-comments'     => true,
		'center-image'         => true,
		'ux-image-border-radius' => true,
		'background-gradient'  => true,
		'pricing-grid-table'   => true,
		'slide-row'            => true,
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