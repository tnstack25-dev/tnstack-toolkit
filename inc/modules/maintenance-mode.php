<?php
/**
 * Simple maintenance mode.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_action( 'template_redirect', 'tnstack_maintenance_mode_redirect', 1 );
function tnstack_maintenance_defaults() {
	return array(
		'enabled' => 0,
		'title'   => __( 'Đang bảo trì', 'tnstack-toolkit' ),
		'message' => __( 'Website đang được bảo trì. Vui lòng quay lại sau.', 'tnstack-toolkit' ),
	);
}

function tnstack_maintenance_settings() {
	return tnstack_module_get_settings( 'maintenance-mode', tnstack_maintenance_defaults() );
}

function tnstack_maintenance_render_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['tnstack_maint_save'] ) ) {
		check_admin_referer( 'tnstack_maint' );
		tnstack_module_update_settings(
			'maintenance-mode',
			array(
				'enabled' => ! empty( $_POST['enabled'] ) ? 1 : 0,
				'title'   => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
				'message' => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
			),
			tnstack_maintenance_defaults()
		);
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Đã lưu.', 'tnstack-toolkit' ) . '</p></div>';
	}

	$s = tnstack_maintenance_settings();
	?>
	<div class="wrap"><h1><?php esc_html_e( 'Maintenance Mode', 'tnstack-toolkit' ); ?></h1>
	<form method="post"><?php wp_nonce_field( 'tnstack_maint' ); ?>
	<table class="form-table">
		<tr><th><?php esc_html_e( 'Bật', 'tnstack-toolkit' ); ?></th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( $s['enabled'] ); ?> /> <?php esc_html_e( 'Kích hoạt chế độ bảo trì', 'tnstack-toolkit' ); ?></label></td></tr>
		<tr><th><?php esc_html_e( 'Tiêu đề', 'tnstack-toolkit' ); ?></th><td><input name="title" class="regular-text" value="<?php echo esc_attr( $s['title'] ); ?>" /></td></tr>
		<tr><th><?php esc_html_e( 'Nội dung', 'tnstack-toolkit' ); ?></th><td><textarea name="message" rows="4" class="large-text"><?php echo esc_textarea( $s['message'] ); ?></textarea></td></tr>
	</table><?php submit_button( __( 'Lưu', 'tnstack-toolkit' ), 'primary', 'tnstack_maint_save' ); ?></form></div>
	<?php
}

function tnstack_maintenance_mode_redirect() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$s = tnstack_maintenance_settings();
	if ( empty( $s['enabled'] ) ) {
		return;
	}

	if ( current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_die(
		'<div style="font-family:system-ui;text-align:center;padding:60px 20px"><h1 style="color:#1e293b">' . esc_html( $s['title'] ) . '</h1><p style="color:#64748b;max-width:480px;margin:0 auto">' . esc_html( $s['message'] ) . '</p></div>',
		esc_html( $s['title'] ),
		array( 'response' => 503 )
	);
}