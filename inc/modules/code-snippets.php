<?php
/**
 * Custom CSS/JS and header-footer code snippets.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', 'tnstack_snippets_head', 1 );
add_action( 'wp_footer', 'tnstack_snippets_footer', 99 );
add_action( 'wp_enqueue_scripts', 'tnstack_snippets_assets', 99 );
function tnstack_snippets_defaults() {
	return array(
		'custom_css'    => '',
		'custom_js'     => '',
		'head_code'     => '',
		'footer_code'   => '',
		'disable_admin' => 1,
	);
}

function tnstack_snippets_settings() {
	return tnstack_module_get_settings( 'code-snippets', tnstack_snippets_defaults() );
}

function tnstack_snippets_head() {
	if ( is_admin() ) {
		return;
	}

	$s = tnstack_snippets_settings();

	if ( ! empty( $s['custom_css'] ) ) {
		echo "<style id=\"tnstack-custom-css\">\n" . wp_strip_all_tags( $s['custom_css'] ) . "\n</style>\n";
	}

	if ( ! empty( $s['head_code'] ) ) {
		echo "\n<!-- TNStack head snippets -->\n" . $s['head_code'] . "\n";
	}
}

function tnstack_snippets_footer() {
	if ( is_admin() ) {
		return;
	}

	$s = tnstack_snippets_settings();

	if ( ! empty( $s['footer_code'] ) ) {
		echo "\n<!-- TNStack footer snippets -->\n" . $s['footer_code'] . "\n";
	}
}

function tnstack_snippets_assets() {
	if ( is_admin() ) {
		return;
	}

	$s = tnstack_snippets_settings();

	if ( empty( $s['custom_js'] ) ) {
		return;
	}

	wp_register_script( 'tnstack-custom-js', false, array(), TNSTACK_TOOLKIT_VERSION, true );
	wp_enqueue_script( 'tnstack-custom-js' );
	wp_add_inline_script( 'tnstack-custom-js', $s['custom_js'] );
}

function tnstack_snippets_render_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['tnstack_snippets_save'] ) ) {
		check_admin_referer( 'tnstack_snippets' );
		tnstack_module_update_settings(
			'code-snippets',
			array(
				'custom_css'  => isset( $_POST['custom_css'] ) ? wp_unslash( $_POST['custom_css'] ) : '',
				'custom_js'   => isset( $_POST['custom_js'] ) ? wp_unslash( $_POST['custom_js'] ) : '',
				'head_code'   => isset( $_POST['head_code'] ) ? wp_unslash( $_POST['head_code'] ) : '',
				'footer_code' => isset( $_POST['footer_code'] ) ? wp_unslash( $_POST['footer_code'] ) : '',
			),
			tnstack_snippets_defaults()
		);
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Đã lưu snippets.', 'tnstack-toolkit' ) . '</p></div>';
	}

	$s = tnstack_snippets_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Code Snippets', 'tnstack-toolkit' ); ?></h1>
		<form method="post">
			<?php wp_nonce_field( 'tnstack_snippets' ); ?>
			<table class="form-table">
				<tr><th><?php esc_html_e( 'Custom CSS', 'tnstack-toolkit' ); ?></th><td><textarea name="custom_css" rows="8" class="large-text code"><?php echo esc_textarea( $s['custom_css'] ); ?></textarea></td></tr>
				<tr><th><?php esc_html_e( 'Custom JS', 'tnstack-toolkit' ); ?></th><td><textarea name="custom_js" rows="8" class="large-text code"><?php echo esc_textarea( $s['custom_js'] ); ?></textarea></td></tr>
				<tr><th><?php esc_html_e( 'Head code', 'tnstack-toolkit' ); ?></th><td><textarea name="head_code" rows="6" class="large-text code"><?php echo esc_textarea( $s['head_code'] ); ?></textarea></td></tr>
				<tr><th><?php esc_html_e( 'Footer code', 'tnstack-toolkit' ); ?></th><td><textarea name="footer_code" rows="6" class="large-text code"><?php echo esc_textarea( $s['footer_code'] ); ?></textarea></td></tr>
			</table>
			<?php submit_button( __( 'Lưu', 'tnstack-toolkit' ), 'primary', 'tnstack_snippets_save' ); ?>
		</form>
	</div>
	<?php
}