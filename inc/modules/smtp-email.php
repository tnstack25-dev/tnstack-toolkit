<?php
/**
 * SMTP email configuration.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_action( 'phpmailer_init', 'tnstack_smtp_configure' );

function tnstack_smtp_defaults() {
	return array(
		'enabled'  => 0,
		'host'     => '',
		'port'     => 587,
		'secure'   => 'tls',
		'user'     => '',
		'password' => '',
		'from'     => '',
		'fromname' => '',
	);
}

function tnstack_smtp_settings() {
	return tnstack_module_get_settings( 'smtp-email', tnstack_smtp_defaults() );
}

function tnstack_smtp_render_admin() {
	if ( ! current_user_can( TNStack_Account_Permissions::MANAGE_CAP ) ) {
		return;
	}

	$saved = false;

	if ( isset( $_POST['tnstack_smtp_save'] ) ) {
		check_admin_referer( 'tnstack_smtp' );
		$current = tnstack_smtp_settings();
		$pass    = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';
		$secure  = isset( $_POST['secure'] ) ? sanitize_key( wp_unslash( $_POST['secure'] ) ) : 'tls';
		$secure  = in_array( $secure, array( 'tls', 'ssl', '' ), true ) ? $secure : 'tls';
		tnstack_module_update_settings(
			'smtp-email',
			array(
				'enabled'  => ! empty( $_POST['enabled'] ) ? 1 : 0,
				'host'     => sanitize_text_field( wp_unslash( $_POST['host'] ?? '' ) ),
				'port'     => min( 65535, max( 1, absint( $_POST['port'] ?? 587 ) ) ),
				'secure'   => $secure,
				'user'     => sanitize_text_field( wp_unslash( $_POST['user'] ?? '' ) ),
				'password' => $pass !== '' ? $pass : $current['password'],
				'from'     => sanitize_email( wp_unslash( $_POST['from'] ?? '' ) ),
				'fromname' => sanitize_text_field( wp_unslash( $_POST['fromname'] ?? '' ) ),
			),
			tnstack_smtp_defaults()
		);
		$saved = true;
	}

	$s = tnstack_smtp_settings();
	?>
	<div class="wrap ttk-settings ttk-settings--smtp">
		<header class="ttk-settings__hero">
			<span class="ttk-settings__hero-icon"><span class="dashicons dashicons-email-alt"></span></span>
			<div>
				<span class="ttk-settings__eyebrow"><?php esc_html_e( 'TNStack Toolkit', 'tnstack-toolkit' ); ?></span>
				<h1><?php esc_html_e( 'SMTP Email', 'tnstack-toolkit' ); ?></h1>
				<p><?php esc_html_e( 'Gửi email WordPress qua máy chủ SMTP để tăng độ ổn định và khả năng vào hộp thư.', 'tnstack-toolkit' ); ?></p>
			</div>
		</header>

		<?php if ( $saved ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Đã lưu cấu hình SMTP Email.', 'tnstack-toolkit' ); ?></p></div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'tnstack_smtp' ); ?>

			<section class="ttk-settings__card">
				<div class="ttk-settings__smtp-status">
					<span class="ttk-settings__smtp-status-icon"><span class="dashicons dashicons-email"></span></span>
					<div class="ttk-settings__smtp-status-copy">
						<strong><?php esc_html_e( 'Kích hoạt gửi mail qua SMTP', 'tnstack-toolkit' ); ?></strong>
						<span><?php esc_html_e( 'Khi tắt, WordPress sử dụng phương thức gửi mail mặc định.', 'tnstack-toolkit' ); ?></span>
					</div>
					<label class="ttk-settings__switch">
						<span class="screen-reader-text"><?php esc_html_e( 'Bật SMTP', 'tnstack-toolkit' ); ?></span>
						<input type="checkbox" name="enabled" value="1" <?php checked( $s['enabled'] ); ?>>
						<span class="ttk-settings__switch-track" aria-hidden="true"></span>
					</label>
				</div>
			</section>

			<div class="ttk-settings__smtp-grid">
				<section class="ttk-settings__card">
					<div class="ttk-settings__card-header">
						<h2><?php esc_html_e( 'Máy chủ và xác thực', 'tnstack-toolkit' ); ?></h2>
						<p><?php esc_html_e( 'Thông tin được cung cấp bởi dịch vụ email của bạn.', 'tnstack-toolkit' ); ?></p>
					</div>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="tnstack_smtp_host">Host</label></th>
							<td><input id="tnstack_smtp_host" name="host" type="text" class="regular-text" value="<?php echo esc_attr( $s['host'] ); ?>" placeholder="smtp.example.com"></td>
						</tr>
						<tr>
							<th scope="row"><label for="tnstack_smtp_port">Port</label></th>
							<td><input id="tnstack_smtp_port" name="port" type="number" class="small-text" value="<?php echo esc_attr( (string) $s['port'] ); ?>" min="1" max="65535" inputmode="numeric"></td>
						</tr>
						<tr>
							<th scope="row"><label for="tnstack_smtp_secure"><?php esc_html_e( 'Mã hóa', 'tnstack-toolkit' ); ?></label></th>
							<td>
								<select id="tnstack_smtp_secure" name="secure">
									<option value="tls" <?php selected( $s['secure'], 'tls' ); ?>>TLS</option>
									<option value="ssl" <?php selected( $s['secure'], 'ssl' ); ?>>SSL</option>
									<option value="" <?php selected( $s['secure'], '' ); ?>><?php esc_html_e( 'Không mã hóa', 'tnstack-toolkit' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="tnstack_smtp_user">Username</label></th>
							<td><input id="tnstack_smtp_user" name="user" type="text" class="regular-text" value="<?php echo esc_attr( $s['user'] ); ?>" autocomplete="username"></td>
						</tr>
						<tr>
							<th scope="row"><label for="tnstack_smtp_password">Password</label></th>
							<td>
								<input id="tnstack_smtp_password" name="password" type="password" class="regular-text" placeholder="••••••••" autocomplete="new-password">
								<p class="description"><?php esc_html_e( 'Để trống để giữ nguyên mật khẩu đang lưu.', 'tnstack-toolkit' ); ?></p>
							</td>
						</tr>
					</table>
				</section>

				<section class="ttk-settings__card">
					<div class="ttk-settings__card-header">
						<h2><?php esc_html_e( 'Thông tin người gửi', 'tnstack-toolkit' ); ?></h2>
						<p><?php esc_html_e( 'Tên và địa chỉ xuất hiện trong email gửi từ website.', 'tnstack-toolkit' ); ?></p>
					</div>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="tnstack_smtp_from"><?php esc_html_e( 'Email gửi đi', 'tnstack-toolkit' ); ?></label></th>
							<td><input id="tnstack_smtp_from" name="from" type="email" class="regular-text" value="<?php echo esc_attr( $s['from'] ); ?>" placeholder="hello@example.com"></td>
						</tr>
						<tr>
							<th scope="row"><label for="tnstack_smtp_fromname"><?php esc_html_e( 'Tên người gửi', 'tnstack-toolkit' ); ?></label></th>
							<td><input id="tnstack_smtp_fromname" name="fromname" type="text" class="regular-text" value="<?php echo esc_attr( $s['fromname'] ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></td>
						</tr>
					</table>
				</section>
			</div>

			<div class="ttk-settings__actions">
				<?php submit_button( __( 'Lưu thay đổi', 'tnstack-toolkit' ), 'primary', 'tnstack_smtp_save' ); ?>
			</div>
		</form>
	</div>
	<?php
}

/**
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
 */
function tnstack_smtp_configure( $phpmailer ) {
	$s = tnstack_smtp_settings();
	if ( empty( $s['enabled'] ) || empty( $s['host'] ) ) {
		return;
	}

	$phpmailer->isSMTP();
	$phpmailer->Host       = $s['host'];
	$phpmailer->Port       = (int) $s['port'];
	$phpmailer->SMTPAuth   = ! empty( $s['user'] );
	$phpmailer->Username   = $s['user'];
	$phpmailer->Password   = $s['password'];
	$phpmailer->SMTPSecure = $s['secure'] ?: '';

	if ( $s['from'] ) {
		$phpmailer->setFrom( $s['from'], $s['fromname'] ?: $s['from'] );
	}
}
