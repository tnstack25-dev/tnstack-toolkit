<?php

if (!defined('ABSPATH')) {
	exit(); // Exit if accessed directly.
}

$lic_data = base64_encode(
maybe_serialize([
	'key' => '********',
	'url' => home_url(),
	])
);
update_option('acf_pro_license', $lic_data);
update_option('acf_pro_license_status', [
	'status' => 'active',
	'next_check' => time() * 9,
]);

add_filter( 'pre_http_request', 'custom_acf_request_intercept', 10, 3 );
function custom_acf_request_intercept( $preempt, $parsed_args, $url ) {
	// Intercept ACF activation request.
	if ( strpos( $url, 'https://connect.advancedcustomfields.com/v2/plugins/activate?p=pro' ) !== false ) {
		$response = array(
			'headers'  => array(),
			'body'     => wp_json_encode(
				array(
					'message'        => 'Licence key activated. Updates are now enabled',
					'license'        => '1415b451be1a13c283ba771ea52d38bb',
					'license_status' => array(
						'status'            => 'active',
						'lifetime'          => true,
						'name'              => 'Agency',
						'view_licenses_url' => 'https://www.advancedcustomfields.com/my-account/view-licenses/',
					),
					'status'         => 1,
				)
			),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
		);
		return $response;
	}
}
