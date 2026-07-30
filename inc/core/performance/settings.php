<?php
/**
 * Central optimization and security settings.
 *
 * @package TNStackCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array{numeric: string[], string: string[]}
 */
function tnstack_core_optimization_meta_keys() {
	return array(
		'numeric' => array(
			'revisions_to_keep',
			'heartbeat_interval',
			'login_max_attempts',
			'login_lockout_minutes',
			'ip_login_max_attempts',
			'ip_lockout_minutes',
		),
		'string'  => array(
			'captcha_provider',
			'captcha_site_key',
			'captcha_secret_key',
		),
	);
}

/**
 * @return array<string, array<string, mixed>>
 */
function tnstack_core_optimization_defaults() {
	return array(
		'core'     => array(
			'disable_emoji'              => 1,
			'disable_embeds'             => 1,
			'disable_xmlrpc'             => 1,
			'disable_head_links'         => 1,
			'disable_heartbeat_frontend' => 1,
			'defer_scripts'              => 1,
			'async_styles'               => 1,
			'lazy_load_images'           => 1,
			'resource_hints'             => 1,
			'dequeue_block_styles'       => 1,
			'dequeue_dashicons'          => 1,
			'dequeue_woocommerce_assets' => 1,
			'dequeue_cf7_assets'         => 1,
			'limit_post_revisions'       => 1,
			'revisions_to_keep'          => 5,
			'heartbeat_interval'         => 90,
		),
		'admin'    => array(
			'disable_emoji'              => 1,
			'skip_update_checks'         => 1,
			'disable_heartbeat'          => 1,
			'dequeue_assets'             => 1,
			'remove_dashboard_widgets'   => 1,
		),
		'security' => array(
			'remove_generator'              => 1,
			'block_author_scan'             => 1,
			'disable_rest_users'            => 1,
			'restrict_rest_api'             => 1,
			'allow_woocommerce_rest'        => 1,
			'block_xmlrpc'                  => 1,
			'block_suspicious_requests'     => 1,
			'block_sensitive_files'         => 1,
			'security_headers'              => 1,
			'hsts_header'                     => 1,
			'force_ssl_admin'               => 1,
			'secure_session_cookies'        => 1,
			'disable_pingbacks'             => 1,
			'login_rate_limit'              => 1,
			'ip_login_rate_limit'           => 1,
			'login_honeypot'                => 1,
			'login_captcha'                 => 1,
			'login_max_attempts'            => 5,
			'login_lockout_minutes'         => 30,
			'ip_login_max_attempts'         => 25,
			'ip_lockout_minutes'            => 60,
			'hide_login_errors'             => 1,
			'disable_file_editor'           => 1,
			'disable_application_passwords' => 1,
			'captcha_provider'              => 'math',
			'captcha_site_key'              => '',
			'captcha_secret_key'            => '',
		),
	);
}

/**
 * @param array<string, mixed> $group_values Group values.
 * @return array<string, mixed>
 */
function tnstack_core_optimization_normalize_group( $group_values ) {
	$meta = tnstack_core_optimization_meta_keys();

	foreach ( $group_values as $key => $value ) {
		if ( in_array( $key, $meta['string'], true ) ) {
			continue;
		}

		if ( in_array( $key, $meta['numeric'], true ) ) {
			$group_values[ $key ] = absint( $value );
			continue;
		}

		$group_values[ $key ] = ! empty( $value ) ? 1 : 0;
	}

	return $group_values;
}

/**
 * @return array<string, array<string, mixed>>
 */
function tnstack_core_optimization_settings() {
	static $settings = null;

	if ( null !== $settings ) {
		return $settings;
	}

	$stored = get_option( 'tnstack_core_optimization_settings', null );

	if ( null === $stored ) {
		$legacy = get_option( 'flatsome_child_optimization_settings', array() );
		$stored = is_array( $legacy ) && ! empty( $legacy ) ? $legacy : array();

		if ( ! empty( $stored ) ) {
			update_option( 'tnstack_core_optimization_settings', $stored, false );
		}
	}

	$stored   = is_array( $stored ) ? $stored : array();
	$defaults = tnstack_core_optimization_defaults();
	$merged   = array();

	foreach ( $defaults as $group => $group_defaults ) {
		$group_values = isset( $stored[ $group ] ) && is_array( $stored[ $group ] ) ? $stored[ $group ] : array();
		$merged[ $group ] = wp_parse_args( $group_values, $group_defaults );
		$merged[ $group ] = tnstack_core_optimization_normalize_group( $merged[ $group ] );
	}

	$merged['core']['revisions_to_keep']           = min( 20, max( 0, (int) $merged['core']['revisions_to_keep'] ) );
	$merged['core']['heartbeat_interval']          = min( 300, max( 15, (int) $merged['core']['heartbeat_interval'] ) );
	$merged['security']['login_max_attempts']      = min( 20, max( 3, (int) $merged['security']['login_max_attempts'] ) );
	$merged['security']['login_lockout_minutes']    = min( 240, max( 5, (int) $merged['security']['login_lockout_minutes'] ) );
	$merged['security']['ip_login_max_attempts']   = min( 100, max( 5, (int) $merged['security']['ip_login_max_attempts'] ) );
	$merged['security']['ip_lockout_minutes']      = min( 1440, max( 15, (int) $merged['security']['ip_lockout_minutes'] ) );
	$merged['security']['captcha_provider']        = in_array( $merged['security']['captcha_provider'], tnstack_core_security_captcha_providers(), true )
		? $merged['security']['captcha_provider']
		: 'math';
	$merged['security']['captcha_site_key']        = sanitize_text_field( (string) $merged['security']['captcha_site_key'] );
	$merged['security']['captcha_secret_key']      = sanitize_text_field( (string) $merged['security']['captcha_secret_key'] );

	$settings = $merged;

	return $settings;
}

/**
 * @param string $group Settings group.
 * @param string $key   Settings key.
 * @return bool
 */
function tnstack_core_opt_enabled( $group, $key ) {
	$settings = tnstack_core_optimization_settings();

	return ! empty( $settings[ $group ][ $key ] );
}

/**
 * @return string[]
 */
function tnstack_core_security_captcha_providers() {
	return array( 'math', 'turnstile', 'recaptcha_v2' );
}

/**
 * @return bool
 */
function tnstack_core_security_captcha_ready() {
	$settings = tnstack_core_optimization_settings()['security'];

	if ( ! tnstack_core_opt_enabled( 'security', 'login_captcha' ) ) {
		return false;
	}

	if ( 'math' === $settings['captcha_provider'] ) {
		return true;
	}

	return '' !== $settings['captcha_site_key'] && '' !== $settings['captcha_secret_key'];
}

/**
 * @return bool
 */
function tnstack_core_security_captcha_needs_api_keys() {
	$settings = tnstack_core_optimization_settings()['security'];

	return tnstack_core_opt_enabled( 'security', 'login_captcha' )
		&& 'math' !== $settings['captcha_provider'];
}

/**
 * @param array<string, array<string, mixed>> $input Raw settings.
 * @return array<string, array<string, mixed>>
 */
function tnstack_core_optimization_sanitize_settings( $input ) {
	$defaults = tnstack_core_optimization_defaults();
	$meta     = tnstack_core_optimization_meta_keys();
	$sanitized = array();

	foreach ( $defaults as $group => $group_defaults ) {
		$group_input = isset( $input[ $group ] ) && is_array( $input[ $group ] ) ? $input[ $group ] : array();
		$sanitized[ $group ] = array();

		foreach ( $group_defaults as $key => $default_value ) {
			if ( in_array( $key, $meta['numeric'], true ) ) {
				$sanitized[ $group ][ $key ] = isset( $group_input[ $key ] ) ? absint( $group_input[ $key ] ) : $default_value;
				continue;
			}

			if ( in_array( $key, $meta['string'], true ) ) {
				if ( 'captcha_provider' === $key ) {
					$provider = isset( $group_input[ $key ] ) ? sanitize_key( $group_input[ $key ] ) : 'math';
					$sanitized[ $group ][ $key ] = in_array( $provider, tnstack_core_security_captcha_providers(), true ) ? $provider : 'math';
					continue;
				}

				$sanitized[ $group ][ $key ] = isset( $group_input[ $key ] ) ? sanitize_text_field( $group_input[ $key ] ) : '';
				continue;
			}

			$sanitized[ $group ][ $key ] = ! empty( $group_input[ $key ] ) ? 1 : 0;
		}
	}

	$sanitized['core']['revisions_to_keep']         = min( 20, max( 0, (int) $sanitized['core']['revisions_to_keep'] ) );
	$sanitized['core']['heartbeat_interval']        = min( 300, max( 15, (int) $sanitized['core']['heartbeat_interval'] ) );
	$sanitized['security']['login_max_attempts']    = min( 20, max( 3, (int) $sanitized['security']['login_max_attempts'] ) );
	$sanitized['security']['login_lockout_minutes'] = min( 240, max( 5, (int) $sanitized['security']['login_lockout_minutes'] ) );
	$sanitized['security']['ip_login_max_attempts'] = min( 100, max( 5, (int) $sanitized['security']['ip_login_max_attempts'] ) );
	$sanitized['security']['ip_lockout_minutes']    = min( 1440, max( 15, (int) $sanitized['security']['ip_lockout_minutes'] ) );

	return $sanitized;
}

/**
 * @param array<string, array<string, mixed>> $settings Settings payload.
 * @return array<string, array<string, mixed>>
 */
function tnstack_core_optimization_update_settings( $settings ) {
	$updated = tnstack_core_optimization_sanitize_settings( $settings );
	update_option( 'tnstack_core_optimization_settings', $updated, false );
	return $updated;
}

/**
 * @return array<string, array<string, string>>
 */
function tnstack_core_optimization_field_labels() {
	return array(
		'core' => array(
			'disable_emoji'              => __( 'Tắt Emoji WordPress', 'tnstack-toolkit' ),
			'disable_embeds'             => __( 'Tắt oEmbed / wp-embed', 'tnstack-toolkit' ),
			'disable_xmlrpc'             => __( 'Tắt XML-RPC', 'tnstack-toolkit' ),
			'disable_head_links'         => __( 'Xóa RSD, wlwmanifest, shortlink', 'tnstack-toolkit' ),
			'disable_heartbeat_frontend' => __( 'Tắt Heartbeat ngoài frontend', 'tnstack-toolkit' ),
			'defer_scripts'              => __( 'Defer JavaScript (trừ jQuery)', 'tnstack-toolkit' ),
			'async_styles'               => __( 'Tải CSS không quan trọng bất đồng bộ', 'tnstack-toolkit' ),
			'lazy_load_images'           => __( 'Lazy load ảnh + ưu tiên LCP', 'tnstack-toolkit' ),
			'resource_hints'             => __( 'Preconnect Google Fonts', 'tnstack-toolkit' ),
			'dequeue_block_styles'       => __( 'Gỡ CSS Block Editor / Global Styles', 'tnstack-toolkit' ),
			'dequeue_dashicons'          => __( 'Gỡ Dashicons cho khách', 'tnstack-toolkit' ),
			'dequeue_woocommerce_assets' => __( 'Gỡ asset WooCommerce ngoài trang shop', 'tnstack-toolkit' ),
			'dequeue_cf7_assets'         => __( 'Gỡ asset Contact Form 7 khi không dùng', 'tnstack-toolkit' ),
			'limit_post_revisions'       => __( 'Giới hạn số revision bài viết', 'tnstack-toolkit' ),
		),
		'admin' => array(
			'disable_emoji'              => __( 'Tắt Emoji trong admin', 'tnstack-toolkit' ),
			'skip_update_checks'         => __( 'Bỏ kiểm tra cập nhật khi không cần', 'tnstack-toolkit' ),
			'disable_heartbeat'          => __( 'Tắt Heartbeat admin (trừ soạn bài)', 'tnstack-toolkit' ),
			'dequeue_assets'             => __( 'Gỡ asset plugin thừa trong admin', 'tnstack-toolkit' ),
			'remove_dashboard_widgets'   => __( 'Ẩn widget dashboard không cần thiết', 'tnstack-toolkit' ),
		),
		'security' => array(
			'remove_generator'              => __( 'Ẩn thẻ generator WordPress', 'tnstack-toolkit' ),
			'block_author_scan'             => __( 'Chặn quét author (?author=1)', 'tnstack-toolkit' ),
			'disable_rest_users'            => __( 'Chặn REST API liệt kê user', 'tnstack-toolkit' ),
			'restrict_rest_api'             => __( 'Chặn REST API cho khách chưa đăng nhập', 'tnstack-toolkit' ),
			'allow_woocommerce_rest'        => __( 'Cho phép REST WooCommerce (nếu có shop)', 'tnstack-toolkit' ),
			'block_xmlrpc'                  => __( 'Chặn hoàn toàn xmlrpc.php', 'tnstack-toolkit' ),
			'block_suspicious_requests'     => __( 'Chặn request SQLi/XSS phổ biến', 'tnstack-toolkit' ),
			'block_sensitive_files'         => __( 'Chặn truy cập readme, license, .env', 'tnstack-toolkit' ),
			'security_headers'              => __( 'Security headers (X-Frame, nosniff…)', 'tnstack-toolkit' ),
			'hsts_header'                   => __( 'HSTS khi dùng HTTPS', 'tnstack-toolkit' ),
			'force_ssl_admin'               => __( 'Bắt buộc HTTPS cho wp-admin', 'tnstack-toolkit' ),
			'secure_session_cookies'        => __( 'Cookie đăng nhập chỉ gửi qua HTTPS', 'tnstack-toolkit' ),
			'disable_pingbacks'             => __( 'Tắt XML-RPC pingback', 'tnstack-toolkit' ),
			'login_rate_limit'              => __( 'Giới hạn đăng nhập sai theo tài khoản', 'tnstack-toolkit' ),
			'ip_login_rate_limit'           => __( 'Giới hạn đăng nhập sai theo IP', 'tnstack-toolkit' ),
			'login_honeypot'                => __( 'Honeypot chống bot trên form login', 'tnstack-toolkit' ),
			'login_captcha'                 => __( 'CAPTCHA trên trang đăng nhập', 'tnstack-toolkit' ),
			'hide_login_errors'             => __( 'Ẩn thông báo lỗi đăng nhập chi tiết', 'tnstack-toolkit' ),
			'disable_file_editor'           => __( 'Chặn sửa file theme/plugin trong admin', 'tnstack-toolkit' ),
			'disable_application_passwords' => __( 'Tắt Application Passwords', 'tnstack-toolkit' ),
		),
	);
}