<?php
/**
 * Simple cookie consent banner.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_footer', 'tnstack_cookie_consent_render', 5 );
function tnstack_cookie_defaults() {
	return array(
		'message' => __( 'Trang web sử dụng cookie để cải thiện trải nghiệm. Bằng việc tiếp tục, bạn đồng ý với chính sách cookie.', 'tnstack-toolkit' ),
		'policy_url' => '',
		'button'  => __( 'Đồng ý', 'tnstack-toolkit' ),
	);
}

function tnstack_cookie_settings() {
	return tnstack_module_get_settings( 'cookie-consent', tnstack_cookie_defaults() );
}

function tnstack_cookie_render_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_POST['tnstack_cookie_save'] ) ) {
		check_admin_referer( 'tnstack_cookie' );
		tnstack_module_update_settings(
			'cookie-consent',
			array(
				'message'    => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
				'policy_url' => esc_url_raw( wp_unslash( $_POST['policy_url'] ?? '' ) ),
				'button'     => sanitize_text_field( wp_unslash( $_POST['button'] ?? '' ) ),
			),
			tnstack_cookie_defaults()
		);
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Đã lưu.', 'tnstack-toolkit' ) . '</p></div>';
	}
	$s = tnstack_cookie_settings();
	?>
	<div class="wrap"><h1><?php esc_html_e( 'Cookie Consent', 'tnstack-toolkit' ); ?></h1>
	<form method="post"><?php wp_nonce_field( 'tnstack_cookie' ); ?>
	<table class="form-table">
		<tr><th><?php esc_html_e( 'Nội dung', 'tnstack-toolkit' ); ?></th><td><textarea name="message" rows="3" class="large-text"><?php echo esc_textarea( $s['message'] ); ?></textarea></td></tr>
		<tr><th><?php esc_html_e( 'Policy URL', 'tnstack-toolkit' ); ?></th><td><input name="policy_url" class="large-text" value="<?php echo esc_attr( $s['policy_url'] ); ?>" /></td></tr>
		<tr><th><?php esc_html_e( 'Nút', 'tnstack-toolkit' ); ?></th><td><input name="button" class="regular-text" value="<?php echo esc_attr( $s['button'] ); ?>" /></td></tr>
	</table><?php submit_button( __( 'Lưu', 'tnstack-toolkit' ), 'primary', 'tnstack_cookie_save' ); ?></form></div>
	<?php
}

function tnstack_cookie_consent_render() {
	if ( is_admin() ) {
		return;
	}

	$s = tnstack_cookie_settings();
	?>
	<div id="tnstack-cookie" class="tnstack-cookie" hidden>
		<p class="tnstack-cookie__text"><?php echo esc_html( $s['message'] ); ?>
			<?php if ( $s['policy_url'] ) : ?>
				<a href="<?php echo esc_url( $s['policy_url'] ); ?>"><?php esc_html_e( 'Chi tiết', 'tnstack-toolkit' ); ?></a>
			<?php endif; ?>
		</p>
		<button type="button" class="tnstack-cookie__btn" id="tnstack-cookie-accept"><?php echo esc_html( $s['button'] ); ?></button>
	</div>
	<style>.tnstack-cookie{position:fixed;bottom:0;left:0;right:0;z-index:99999;display:flex;align-items:center;justify-content:center;gap:16px;flex-wrap:wrap;padding:16px 24px;background:#1e293b;color:#f8fafc;font-size:14px;box-shadow:0 -4px 20px rgba(0,0,0,.15)}.tnstack-cookie__text{margin:0}.tnstack-cookie__text a{color:#93c5fd}.tnstack-cookie__btn{padding:10px 24px;border:none;border-radius:8px;background:#4f46e5;color:#fff;font-weight:600;cursor:pointer}</style>
	<script>(function(){var k='tnstack_cookie_ok',b=document.getElementById('tnstack-cookie'),a=document.getElementById('tnstack-cookie-accept');if(!b)return;if(localStorage.getItem(k)){return;}b.hidden=false;a.addEventListener('click',function(){localStorage.setItem(k,'1');b.remove();});})();</script>
	<?php
}