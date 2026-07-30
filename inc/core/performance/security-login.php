<?php
/**
 * Login hardening: CAPTCHA, honeypot, brute-force protection.
 *
 * @package TNStackCore
 */

defined('ABSPATH') || exit;

add_action('login_enqueue_scripts', 'tnstack_core_security_login_assets');
add_action('login_form', 'tnstack_core_security_render_login_extras');
add_filter('authenticate', 'tnstack_core_security_validate_login', 20, 3);

/**
 * Enqueue CAPTCHA assets on wp-login.php.
 */
function tnstack_core_security_login_assets()
{
	wp_register_style('tnstack-core-login-security', false, array(), '1.0.0');
	wp_enqueue_style('tnstack-core-login-security');
	wp_add_inline_style(
		'tnstack-core-login-security',
		'.fcp-login-captcha{display:flex;justify-content:center;margin:16px 0;}'
		. '.fcp-math-captcha{margin:12px 0;}'
		. '.fcp-math-captcha label{display:block;margin-bottom:6px;font-weight:600;}'
		. '.fcp-math-captcha input[type="number"]{width:100%;max-width:100%;}'
	);

	if (!tnstack_core_security_captcha_ready()) {
		return;
	}

	$provider = tnstack_core_optimization_settings()['security']['captcha_provider'];

	if ('math' === $provider) {
		return;
	}

	if ('recaptcha_v2' === $provider) {
		wp_enqueue_script(
			'tnstack-core-recaptcha',
			'https://www.google.com/recaptcha/api.js',
			array(),
			null,
			true
		);
		return;
	}

	wp_enqueue_script(
		'tnstack-core-turnstile',
		'https://challenges.cloudflare.com/turnstile/v0/api.js',
		array(),
		null,
		true
	);
}

/**
 * Render honeypot and CAPTCHA widget on the login form.
 */
function tnstack_core_security_render_login_extras()
{
	if (tnstack_core_opt_enabled('security', 'login_honeypot')) {
		echo '<input type="text" name="fcp_login_honeypot" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;height:0;width:0;opacity:0;" />';
	}

	if (!tnstack_core_security_captcha_ready()) {
		return;
	}

	$provider = tnstack_core_optimization_settings()['security']['captcha_provider'];

	if ('math' === $provider) {
		tnstack_core_security_render_math_captcha();
		return;
	}

	$site_key = tnstack_core_optimization_settings()['security']['captcha_site_key'];

	echo '<div class="fcp-login-captcha" style="margin:12px 0;">';

	if ('recaptcha_v2' === $provider) {
		echo '<div class="g-recaptcha" data-sitekey="' . esc_attr($site_key) . '"></div>';
	} else {
		echo '<div class="cf-turnstile" data-sitekey="' . esc_attr($site_key) . '" data-theme="light"></div>';
	}

	echo '</div>';
}

/**
 * Render a simple math challenge on the login form.
 */
function tnstack_core_security_render_math_captcha()
{
	$challenge = tnstack_core_security_create_math_challenge();

	if (empty($challenge['token']) || empty($challenge['question'])) {
		return;
	}

	echo '<div class="fcp-math-captcha">';
	echo '<label for="fcp_math_captcha_answer">';
	echo esc_html(
		sprintf(
			/* translators: %s: math expression, e.g. 7 + 3 */
			__('Xác minh: %s = ?', 'tnstack-core'),
			$challenge['question']
		)
	);
	echo '</label>';
	echo '<input type="hidden" name="fcp_math_captcha_token" value="' . esc_attr($challenge['token']) . '" />';
	echo '<input type="number" name="fcp_math_captcha_answer" id="fcp_math_captcha_answer" class="input" value="" required autocomplete="off" inputmode="numeric" min="0" step="1" />';
	echo '</div>';
}

/**
 * @return array{token: string, question: string}
 */
function tnstack_core_security_create_math_challenge()
{
	$a = wp_rand(1, 12);
	$b = wp_rand(1, 12);
	$op = wp_rand(0, 1) ? '+' : '-';

	if ('-' === $op && $b > $a) {
		$swap = $a;
		$a = $b;
		$b = $swap;
	}

	$answer = '+' === $op ? ($a + $b) : ($a - $b);
	$token = wp_generate_password(32, false, false);
	$question = $a . ' ' . $op . ' ' . $b;

	set_transient(
		tnstack_core_security_math_transient_key($token),
		array(
			'answer' => (int) $answer,
			'ip' => tnstack_core_security_client_ip(),
		),
		10 * MINUTE_IN_SECONDS
	);

	return array(
		'token' => $token,
		'question' => $question,
	);
}

/**
 * @param string $token Challenge token.
 * @return string
 */
function tnstack_core_security_math_transient_key($token)
{
	return 'tnstack_math_' . md5((string) $token);
}

/**
 * Validate honeypot, IP lockout, account lockout, and CAPTCHA.
 *
 * @param WP_User|WP_Error|null $user     User or error.
 * @param string                $username Username.
 * @param string                $password Password.
 * @return WP_User|WP_Error|null
 */
function tnstack_core_security_validate_login($user, $username, $password)
{
	if (tnstack_core_opt_enabled('security', 'login_honeypot') && !empty($_POST['fcp_login_honeypot'])) {
		return new WP_Error('invalid_login', tnstack_core_security_login_error_message());
	}

	if (tnstack_core_opt_enabled('security', 'ip_login_rate_limit')) {
		$ip_block = tnstack_core_security_ip_lockout_check();
		if (is_wp_error($ip_block)) {
			return $ip_block;
		}
	}

	if (tnstack_core_opt_enabled('security', 'login_rate_limit') && !empty($username)) {
		$account_block = tnstack_core_security_account_lockout_check($username);
		if (is_wp_error($account_block)) {
			return $account_block;
		}
	}

	if (tnstack_core_security_captcha_ready() && !empty($_POST['log'])) {
		$captcha_error = tnstack_core_security_verify_login_captcha();
		if (is_wp_error($captcha_error)) {
			tnstack_core_security_register_failed_login($username);
			return $captcha_error;
		}
	}

	if (is_wp_error($user) && !empty($username)) {
		tnstack_core_security_register_failed_login($username);
	} elseif ($user instanceof WP_User) {
		tnstack_core_security_clear_failed_login($username);
	}

	return $user;
}

/**
 * @return true|WP_Error
 */
function tnstack_core_security_ip_lockout_check()
{
	$settings = tnstack_core_optimization_settings()['security'];
	$key = 'fcp_ip_login_' . md5(tnstack_core_security_client_ip());
	$attempts = (int) get_transient($key);

	if ($attempts >= (int) $settings['ip_login_max_attempts']) {
		return new WP_Error(
			'ip_locked',
			sprintf(
				__('IP của bạn bị tạm khóa do quá nhiều lần đăng nhập thất bại. Thử lại sau %d phút.', 'tnstack-core'),
				(int) $settings['ip_lockout_minutes']
			)
		);
	}

	return true;
}

/**
 * @param string $username Username.
 * @return true|WP_Error
 */
function tnstack_core_security_account_lockout_check($username)
{
	$settings = tnstack_core_optimization_settings()['security'];
	$key = 'fcp_login_' . md5(strtolower($username) . '|' . tnstack_core_security_client_ip());
	$attempts = (int) get_transient($key);

	if ($attempts >= (int) $settings['login_max_attempts']) {
		return new WP_Error(
			'too_many_attempts',
			sprintf(
				__('Quá nhiều lần đăng nhập sai. Vui lòng thử lại sau %d phút.', 'tnstack-core'),
				(int) $settings['login_lockout_minutes']
			)
		);
	}

	return true;
}

/**
 * @param string $username Username.
 */
function tnstack_core_security_register_failed_login($username)
{
	$settings = tnstack_core_optimization_settings()['security'];

	if (tnstack_core_opt_enabled('security', 'login_rate_limit') && '' !== $username) {
		$key = 'fcp_login_' . md5(strtolower($username) . '|' . tnstack_core_security_client_ip());
		$attempts = (int) get_transient($key);
		set_transient($key, $attempts + 1, (int) $settings['login_lockout_minutes'] * MINUTE_IN_SECONDS);
	}

	if (tnstack_core_opt_enabled('security', 'ip_login_rate_limit')) {
		$ip_key = 'fcp_ip_login_' . md5(tnstack_core_security_client_ip());
		$ip_attempts = (int) get_transient($ip_key);
		set_transient($ip_key, $ip_attempts + 1, (int) $settings['ip_lockout_minutes'] * MINUTE_IN_SECONDS);
	}
}

/**
 * @param string $username Username.
 */
function tnstack_core_security_clear_failed_login($username)
{
	if ('' === $username) {
		return;
	}

	delete_transient('fcp_login_' . md5(strtolower($username) . '|' . tnstack_core_security_client_ip()));
	delete_transient('fcp_ip_login_' . md5(tnstack_core_security_client_ip()));
}

/**
 * @return true|WP_Error
 */
function tnstack_core_security_verify_login_captcha()
{
	$settings = tnstack_core_optimization_settings()['security'];
	$provider = $settings['captcha_provider'];

	if ('math' === $provider) {
		return tnstack_core_security_verify_math_captcha();
	}

	$secret = $settings['captcha_secret_key'];
	$token = '';

	if ('recaptcha_v2' === $provider) {
		$token = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'])) : '';
		$endpoint = 'https://www.google.com/recaptcha/api/siteverify';
	} else {
		$token = isset($_POST['cf-turnstile-response']) ? sanitize_text_field(wp_unslash($_POST['cf-turnstile-response'])) : '';
		$endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
	}

	if ('' === $token) {
		return new WP_Error('captcha_missing', __('Vui lòng xác minh CAPTCHA trước khi đăng nhập.', 'tnstack-core'));
	}

	$response = wp_remote_post(
		$endpoint,
		array(
			'timeout' => 12,
			'body' => array(
				'secret' => $secret,
				'response' => $token,
				'remoteip' => tnstack_core_security_client_ip(),
			),
		)
	);

	if (is_wp_error($response)) {
		return new WP_Error('captcha_unreachable', __('Không thể xác minh CAPTCHA. Vui lòng thử lại.', 'tnstack-core'));
	}

	$payload = json_decode(wp_remote_retrieve_body($response), true);

	if (empty($payload['success'])) {
		return new WP_Error('captcha_invalid', __('Xác minh CAPTCHA không thành công. Vui lòng thử lại.', 'tnstack-core'));
	}

	return true;
}

/**
 * @return true|WP_Error
 */
function tnstack_core_security_verify_math_captcha()
{
	$token = isset($_POST['fcp_math_captcha_token']) ? sanitize_text_field(wp_unslash($_POST['fcp_math_captcha_token'])) : '';

	if ('' === $token) {
		return new WP_Error('captcha_missing', __('Vui lòng nhập kết quả phép tính xác minh.', 'tnstack-core'));
	}

	$transient_key = tnstack_core_security_math_transient_key($token);
	$stored = get_transient($transient_key);
	delete_transient($transient_key);

	if (!is_array($stored) || !isset($stored['answer'])) {
		return new WP_Error('captcha_expired', __('Phiên xác minh đã hết hạn. Vui lòng tải lại trang đăng nhập.', 'tnstack-core'));
	}

	if (!empty($stored['ip']) && $stored['ip'] !== tnstack_core_security_client_ip()) {
		return new WP_Error('captcha_invalid', __('Xác minh không hợp lệ. Vui lòng thử lại.', 'tnstack-core'));
	}

	if (!isset($_POST['fcp_math_captcha_answer']) || '' === wp_unslash($_POST['fcp_math_captcha_answer'])) {
		return new WP_Error('captcha_missing', __('Vui lòng nhập kết quả phép tính xác minh.', 'tnstack-core'));
	}

	$submitted = (int) wp_unslash($_POST['fcp_math_captcha_answer']);

	if ((int) $stored['answer'] !== $submitted) {
		return new WP_Error('captcha_invalid', __('Kết quả phép tính không đúng. Vui lòng thử lại.', 'tnstack-core'));
	}

	return true;
}

/**
 * @return string
 */
function tnstack_core_security_login_error_message()
{
	if (tnstack_core_opt_enabled('security', 'hide_login_errors')) {
		return __('Thông tin đăng nhập không chính xác.', 'tnstack-core');
	}

	return __('Yêu cầu đăng nhập không hợp lệ.', 'tnstack-core');
}