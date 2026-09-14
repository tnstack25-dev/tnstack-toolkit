<?php
/** Login-only authentication protection. */

defined( 'ABSPATH' ) || exit;

require_once tnstack_core_path( 'inc/core/performance/settings.php' );

if ( ! function_exists( 'tnstack_core_security_client_ip' ) ) {
	function tnstack_core_security_client_ip() {
		$keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
		foreach ( $keys as $key ) {
			if ( empty( $_SERVER[ $key ] ) ) {
				continue;
			}
			$value = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
			$ip    = trim( explode( ',', $value )[0] );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
		return '0.0.0.0';
	}
}

require_once tnstack_core_path( 'inc/core/performance/security-login.php' );
require_once tnstack_core_path( 'inc/modules/login-history.php' );

add_action( 'admin_init', 'tnstack_login_authentication_register_settings' );
function tnstack_login_authentication_register_settings() {
	register_setting( 'tnstack_login_authentication', 'tnstack_core_optimization_settings', array( 'sanitize_callback' => 'tnstack_core_optimization_sanitize_settings' ) );
}

function tnstack_login_authentication_render_admin() {
	$security = tnstack_core_optimization_settings()['security'];
	$active_count = count( array_filter( array( $security['login_honeypot'], $security['login_rate_limit'], $security['ip_login_rate_limit'], $security['login_captcha'] ) ) );
	?>
	<div class="wrap ttk-auth-wrap">
		<div class="ttk-auth-hero"><div><span class="dashicons dashicons-shield-alt"></span><div><h1>Xác thực đăng nhập</h1><p>Bảo vệ trang đăng nhập bằng nhiều lớp nhẹ, không ảnh hưởng khách truy cập website.</p></div></div><span class="ttk-auth-status"><?php echo esc_html( $active_count ); ?>/4 lớp đang bật</span></div>
		<form action="options.php" method="post">
		<?php settings_fields( 'tnstack_login_authentication' ); ?>
		<div class="ttk-auth-grid">
			<section class="ttk-auth-card"><div class="ttk-auth-card__head"><span class="dashicons dashicons-hidden"></span><div><h2>Honeypot</h2><p>Bẫy vô hình chặn bot tự động mà người dùng không cần thao tác.</p></div><label class="ttk-switch"><input type="checkbox" name="tnstack_core_optimization_settings[security][login_honeypot]" value="1" <?php checked( ! empty( $security['login_honeypot'] ) ); ?>><span></span></label></div></section>
			<section class="ttk-auth-card"><div class="ttk-auth-card__head"><span class="dashicons dashicons-admin-users"></span><div><h2>Giới hạn theo tài khoản</h2><p>Tạm khóa khi một tài khoản bị thử sai mật khẩu quá nhiều lần.</p></div><label class="ttk-switch"><input type="checkbox" name="tnstack_core_optimization_settings[security][login_rate_limit]" value="1" <?php checked( ! empty( $security['login_rate_limit'] ) ); ?>><span></span></label></div><div class="ttk-auth-fields"><label>Số lần thử tối đa<input type="number" min="3" max="20" name="tnstack_core_optimization_settings[security][login_max_attempts]" value="<?php echo esc_attr( $security['login_max_attempts'] ); ?>"></label><label>Thời gian khóa (phút)<input type="number" min="5" max="240" name="tnstack_core_optimization_settings[security][login_lockout_minutes]" value="<?php echo esc_attr( $security['login_lockout_minutes'] ); ?>"></label></div></section>
			<section class="ttk-auth-card"><div class="ttk-auth-card__head"><span class="dashicons dashicons-admin-site-alt3"></span><div><h2>Giới hạn theo IP</h2><p>Giảm tấn công dò mật khẩu từ cùng một địa chỉ mạng.</p></div><label class="ttk-switch"><input type="checkbox" name="tnstack_core_optimization_settings[security][ip_login_rate_limit]" value="1" <?php checked( ! empty( $security['ip_login_rate_limit'] ) ); ?>><span></span></label></div><div class="ttk-auth-fields"><label>Số lần thử tối đa<input type="number" min="5" max="100" name="tnstack_core_optimization_settings[security][ip_login_max_attempts]" value="<?php echo esc_attr( $security['ip_login_max_attempts'] ); ?>"></label><label>Thời gian khóa (phút)<input type="number" min="15" max="1440" name="tnstack_core_optimization_settings[security][ip_lockout_minutes]" value="<?php echo esc_attr( $security['ip_lockout_minutes'] ); ?>"></label></div></section>
			<section class="ttk-auth-card ttk-auth-card--wide"><div class="ttk-auth-card__head"><span class="dashicons dashicons-lock"></span><div><h2>CAPTCHA</h2><p>Xác minh người đăng nhập bằng phép tính, Cloudflare Turnstile hoặc Google reCAPTCHA.</p></div><label class="ttk-switch"><input type="checkbox" name="tnstack_core_optimization_settings[security][login_captcha]" value="1" <?php checked( ! empty( $security['login_captcha'] ) ); ?>><span></span></label></div><div class="ttk-auth-fields ttk-auth-fields--captcha"><label>Phương thức<select data-captcha-provider name="tnstack_core_optimization_settings[security][captcha_provider]"><option value="math" <?php selected( $security['captcha_provider'], 'math' ); ?>>Phép tính đơn giản</option><option value="turnstile" <?php selected( $security['captcha_provider'], 'turnstile' ); ?>>Cloudflare Turnstile</option><option value="recaptcha_v2" <?php selected( $security['captcha_provider'], 'recaptcha_v2' ); ?>>Google reCAPTCHA v2</option></select></label><label data-api-key>Site key<input name="tnstack_core_optimization_settings[security][captcha_site_key]" value="<?php echo esc_attr( $security['captcha_site_key'] ); ?>"></label><label data-api-key>Secret key<input type="password" name="tnstack_core_optimization_settings[security][captcha_secret_key]" value="<?php echo esc_attr( $security['captcha_secret_key'] ); ?>" autocomplete="new-password"></label></div></section>
		</div>
		<div class="ttk-auth-save"><p>Mẹo: CAPTCHA phép tính không cần API key và phù hợp với hầu hết website.</p><?php submit_button( 'Lưu cấu hình xác thực', 'primary', 'submit', false ); ?></div>
		</form>
	</div>
	<style>
	.ttk-auth-wrap{max-width:1120px}.ttk-auth-hero{display:flex;align-items:center;justify-content:space-between;gap:24px;margin:20px 0;padding:26px 30px;border-radius:18px;background:linear-gradient(135deg,#172554,#2563eb);color:#fff;box-shadow:0 14px 35px rgba(37,99,235,.2)}.ttk-auth-hero>div{display:flex;align-items:center;gap:18px}.ttk-auth-hero .dashicons{width:50px;height:50px;font-size:50px}.ttk-auth-hero h1{margin:0 0 6px;color:#fff;font-size:28px}.ttk-auth-hero p{margin:0;color:#dbeafe}.ttk-auth-status{flex:none;padding:8px 13px;border:1px solid rgba(255,255,255,.3);border-radius:999px;background:rgba(255,255,255,.12);font-weight:600}.ttk-auth-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.ttk-auth-card{padding:22px;border:1px solid #dbe3ef;border-radius:16px;background:#fff;box-shadow:0 5px 18px rgba(15,23,42,.05)}.ttk-auth-card--wide{grid-column:1/-1}.ttk-auth-card__head{display:grid;grid-template-columns:auto 1fr auto;align-items:start;gap:14px}.ttk-auth-card__head>.dashicons{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:#eff6ff;color:#2563eb;font-size:22px}.ttk-auth-card h2{margin:1px 0 5px;font-size:17px}.ttk-auth-card p{margin:0;color:#64748b;line-height:1.55}.ttk-auth-fields{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:20px;padding-top:18px;border-top:1px solid #edf1f7}.ttk-auth-fields--captcha{grid-template-columns:1fr 1fr 1fr}.ttk-auth-fields label{font-weight:600;color:#334155}.ttk-auth-fields input,.ttk-auth-fields select{display:block;width:100%;max-width:none;margin-top:7px}.ttk-switch input{display:none}.ttk-switch span{position:relative;display:block;width:44px;height:24px;border-radius:99px;background:#cbd5e1;cursor:pointer;transition:.2s}.ttk-switch span:after{content:'';position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:.2s}.ttk-switch input:checked+span{background:#2563eb}.ttk-switch input:checked+span:after{transform:translateX(20px)}.ttk-auth-save{position:sticky;bottom:0;display:flex;align-items:center;justify-content:space-between;gap:20px;margin-top:20px;padding:16px 20px;border:1px solid #dbe3ef;border-radius:14px;background:rgba(255,255,255,.94);box-shadow:0 -5px 20px rgba(15,23,42,.07);backdrop-filter:blur(8px)}.ttk-auth-save p{margin:0;color:#64748b}@media(max-width:782px){.ttk-auth-hero,.ttk-auth-save{align-items:flex-start;flex-direction:column}.ttk-auth-grid{grid-template-columns:1fr}.ttk-auth-card--wide{grid-column:auto}.ttk-auth-fields,.ttk-auth-fields--captcha{grid-template-columns:1fr}.ttk-auth-status{align-self:flex-start}}
	</style>
	<script>document.addEventListener('DOMContentLoaded',function(){var select=document.querySelector('[data-captcha-provider]');if(!select)return;function toggleKeys(){document.querySelectorAll('[data-api-key]').forEach(function(field){field.style.display=select.value==='math'?'none':'block';});}select.addEventListener('change',toggleKeys);toggleKeys();});</script>
	<?php
}
