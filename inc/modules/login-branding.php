<?php
/**
 * Customize wp-login appearance.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_action( 'login_enqueue_scripts', 'tnstack_login_branding_styles' );
add_filter( 'login_headerurl', 'tnstack_login_header_url' );
add_filter( 'login_headertext', 'tnstack_login_header_text' );
function tnstack_login_defaults() {
	return array(
		'logo_url' => '',
		'bg_color' => '#f0f2f5',
		'accent'   => '#4f46e5',
		'hide_wp'  => 1,
	);
}

function tnstack_login_settings() {
	return tnstack_module_get_settings( 'login-branding', tnstack_login_defaults() );
}

function tnstack_login_render_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['tnstack_login_save'] ) ) {
		check_admin_referer( 'tnstack_login' );
		tnstack_module_update_settings(
			'login-branding',
			array(
				'logo_url' => esc_url_raw( wp_unslash( $_POST['logo_url'] ?? '' ) ),
				'bg_color' => sanitize_hex_color( wp_unslash( $_POST['bg_color'] ?? '#f0f2f5' ) ) ?: '#f0f2f5',
				'accent'   => sanitize_hex_color( wp_unslash( $_POST['accent'] ?? '#4f46e5' ) ) ?: '#4f46e5',
				'hide_wp'  => ! empty( $_POST['hide_wp'] ) ? 1 : 0,
			),
			tnstack_login_defaults()
		);
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Đã lưu.', 'tnstack-toolkit' ) . '</p></div>';
	}

	$s = tnstack_login_settings();
	?>
	<div class="wrap"><h1><?php esc_html_e( 'Login Branding', 'tnstack-toolkit' ); ?></h1>
	<form method="post"><?php wp_nonce_field( 'tnstack_login' ); ?>
	<table class="form-table">
		<tr><th><?php esc_html_e( 'Logo URL', 'tnstack-toolkit' ); ?></th><td><input name="logo_url" class="large-text" value="<?php echo esc_attr( $s['logo_url'] ); ?>" /></td></tr>
		<tr><th><?php esc_html_e( 'Background', 'tnstack-toolkit' ); ?></th><td><input name="bg_color" type="color" value="<?php echo esc_attr( $s['bg_color'] ); ?>" /></td></tr>
		<tr><th><?php esc_html_e( 'Accent', 'tnstack-toolkit' ); ?></th><td><input name="accent" type="color" value="<?php echo esc_attr( $s['accent'] ); ?>" /></td></tr>
		<tr><th><?php esc_html_e( 'Ẩn logo WordPress', 'tnstack-toolkit' ); ?></th><td><label><input type="checkbox" name="hide_wp" value="1" <?php checked( $s['hide_wp'] ); ?> /> <?php esc_html_e( 'Bật', 'tnstack-toolkit' ); ?></label></td></tr>
	</table><?php submit_button( __( 'Lưu', 'tnstack-toolkit' ), 'primary', 'tnstack_login_save' ); ?></form></div>
	<?php
}

function tnstack_login_branding_styles() {
	$s = tnstack_login_settings();
	$logo = $s['logo_url'] ? esc_url( $s['logo_url'] ) : '';
	?>
	<style>
		body.login{background:<?php echo esc_attr( $s['bg_color'] ); ?>}
		.login form{border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08)}
		.wp-core-ui .button-primary{background:<?php echo esc_attr( $s['accent'] ); ?>;border-color:<?php echo esc_attr( $s['accent'] ); ?>}
		<?php if ( $logo ) : ?>
		.login h1 a{background-image:url(<?php echo esc_url( $logo ); ?>);background-size:contain;width:200px;height:80px}
		<?php endif; ?>
		<?php if ( $s['hide_wp'] ) : ?>
		.login #backtoblog,.login #nav{display:none}
		<?php endif; ?>
	</style>
	<?php
}

function tnstack_login_header_url() {
	return home_url( '/' );
}

function tnstack_login_header_text() {
	return get_bloginfo( 'name' );
}