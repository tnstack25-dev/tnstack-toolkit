<?php
/**
 * Redirect manager and 404 logging.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_action( 'template_redirect', 'tnstack_redirect_manager_handle', 0 );
add_action( 'template_redirect', 'tnstack_redirect_log_404', 99 );
function tnstack_redirect_defaults() {
	return array(
		'redirects' => array(),
		'log_404'   => 1,
	);
}

function tnstack_redirect_settings() {
	return tnstack_module_get_settings( 'redirect-manager', tnstack_redirect_defaults() );
}

function tnstack_redirect_render_admin() {
	if ( ! current_user_can( TNStack_Account_Permissions::MANAGE_CAP ) ) {
		return;
	}

	if ( isset( $_POST['tnstack_redirect_save'] ) ) {
		check_admin_referer( 'tnstack_redirect' );
		$lines     = isset( $_POST['redirects'] ) ? explode( "\n", wp_unslash( $_POST['redirects'] ) ) : array();
		$redirects = array();

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line || false === strpos( $line, '>' ) ) {
				continue;
			}
			list( $from, $to ) = array_map( 'trim', explode( '>', $line, 2 ) );
			if ( $from && $to ) {
				$redirects[] = array(
					'from' => sanitize_text_field( $from ),
					'to'   => esc_url_raw( $to ),
					'code' => 301,
				);
			}
		}

		tnstack_module_update_settings( 'redirect-manager', array( 'redirects' => $redirects, 'log_404' => ! empty( $_POST['log_404'] ) ? 1 : 0 ), tnstack_redirect_defaults() );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Đã lưu redirects.', 'tnstack-toolkit' ) . '</p></div>';
	}

	$s     = tnstack_redirect_settings();
	$lines = '';
	foreach ( $s['redirects'] as $r ) {
		$lines .= $r['from'] . ' > ' . $r['to'] . "\n";
	}

	$log = get_option( 'tnstack_404_log', array() );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Redirect Manager', 'tnstack-toolkit' ); ?></h1>
		<form method="post"><?php wp_nonce_field( 'tnstack_redirect' ); ?>
		<p><?php esc_html_e( 'Mỗi dòng: /old-path > https://new-url', 'tnstack-toolkit' ); ?></p>
		<textarea name="redirects" rows="12" class="large-text code"><?php echo esc_textarea( trim( $lines ) ); ?></textarea>
		<p><label><input type="checkbox" name="log_404" value="1" <?php checked( $s['log_404'] ); ?> /> <?php esc_html_e( 'Ghi log 404', 'tnstack-toolkit' ); ?></label></p>
		<?php submit_button( __( 'Lưu', 'tnstack-toolkit' ), 'primary', 'tnstack_redirect_save' ); ?>
		</form>
		<?php if ( ! empty( $log ) ) : ?>
		<h2><?php esc_html_e( '404 gần đây', 'tnstack-toolkit' ); ?></h2>
		<table class="widefat"><thead><tr><th>URL</th><th>Count</th></tr></thead><tbody>
		<?php foreach ( array_slice( $log, 0, 20, true ) as $url => $count ) : ?>
			<tr><td><?php echo esc_html( $url ); ?></td><td><?php echo esc_html( (string) $count ); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
		<?php endif; ?>
	</div>
	<?php
}

function tnstack_redirect_manager_handle() {
	$s   = tnstack_redirect_settings();
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = strtok( $uri, '?' );

	foreach ( $s['redirects'] as $rule ) {
		if ( $path === $rule['from'] || trailingslashit( $path ) === trailingslashit( $rule['from'] ) ) {
			wp_safe_redirect( $rule['to'], (int) ( $rule['code'] ?? 301 ) );
			exit;
		}
	}
}

function tnstack_redirect_log_404() {
	if ( ! is_404() ) {
		return;
	}

	$s = tnstack_redirect_settings();
	if ( empty( $s['log_404'] ) ) {
		return;
	}

	$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$log = get_option( 'tnstack_404_log', array() );
	$log = is_array( $log ) ? $log : array();

	if ( ! isset( $log[ $uri ] ) ) {
		$log[ $uri ] = 0;
	}
	$log[ $uri ]++;

	if ( count( $log ) > 100 ) {
		$log = array_slice( $log, -100, null, true );
	}

	update_option( 'tnstack_404_log', $log, false );
}
