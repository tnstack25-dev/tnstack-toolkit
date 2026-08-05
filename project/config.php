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
