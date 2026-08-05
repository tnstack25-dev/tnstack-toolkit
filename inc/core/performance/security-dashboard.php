<?php
/**
 * Dedicated WAF and malware monitoring dashboard.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_Security_Dashboard {

	const PAGE_SLUG = 'tnstack-security-monitor';

	/** Register admin hooks. */
	public static function boot() {
		add_action( 'admin_init', array( __CLASS__, 'handle_settings_save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/** @param string $hook Current admin hook. */
	public static function enqueue_assets( $hook ) {
		unset( $hook );
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( self::PAGE_SLUG !== $page ) {
			return;
		}

		$path = tnstack_core_path( 'assets/css/performance-admin.css' );
		wp_enqueue_style( 'tnstack-core-performance-admin', tnstack_core_uri( 'assets/css/performance-admin.css' ), array(), tnstack_core_asset_version( $path ) );
	}

	/** Save only WAF/malware fields while preserving other security settings. */
	public static function handle_settings_save() {
		if ( empty( $_POST['tnstack_security_save'] ) ) {
			return;
		}
		if ( ! current_user_can( TNStack_Account_Permissions::MANAGE_CAP ) || ! current_user_can( TNStack_Account_Permissions::SECURITY_CAP ) ) {
			wp_die( esc_html__( 'Bạn không có quyền sửa cài đặt bảo mật.', 'tnstack-toolkit' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'tnstack_security_settings', 'tnstack_security_nonce' );
		$current  = tnstack_core_optimization_settings();
		$security = $current['security'];
		$input    = isset( $_POST['security'] ) && is_array( $_POST['security'] ) ? wp_unslash( $_POST['security'] ) : array();

		$security['waf_enabled']         = ! empty( $input['waf_enabled'] ) ? 1 : 0;
		$security['waf_mode']            = isset( $input['waf_mode'] ) && in_array( sanitize_key( $input['waf_mode'] ), array( 'block', 'monitor' ), true ) ? sanitize_key( $input['waf_mode'] ) : 'block';
		$security['waf_allowlist']       = sanitize_textarea_field( $input['waf_allowlist'] ?? '' );
		$security['malware_monitor']     = ! empty( $input['malware_monitor'] ) ? 1 : 0;
		$security['malware_email_alert'] = ! empty( $input['malware_email_alert'] ) ? 1 : 0;
		$security['malware_scan_frequency'] = isset( $input['malware_scan_frequency'] ) && in_array( sanitize_key( $input['malware_scan_frequency'] ), array( 'daily', 'weekly' ), true ) ? sanitize_key( $input['malware_scan_frequency'] ) : 'daily';
		$security['malware_max_file_size_mb'] = min( 20, max( 1, absint( $input['malware_max_file_size_mb'] ?? 5 ) ) );

		$current['security'] = $security;
		tnstack_core_optimization_update_settings( $current );

		wp_safe_redirect( add_query_arg( 'saved', '1', admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ) );
		exit;
	}

	/** Render the dedicated security page. */
	public static function render_page() {
		if ( ! current_user_can( TNStack_Account_Permissions::SECURITY_CAP ) ) {
			wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'tnstack-toolkit' ), '', array( 'response' => 403 ) );
		}

		$settings       = tnstack_core_optimization_settings()['security'];
		$events         = TNStack_Core_WAF::recent_events( 20 );
		$scan           = TNStack_Malware_Monitor::get_result();
		$scan_url       = wp_nonce_url( admin_url( 'admin-post.php?action=tnstack_malware_scan_now' ), 'tnstack_malware_scan_now' );
		$notice         = get_transient( 'tnstack_core_performance_notice_' . get_current_user_id() );

		if ( is_array( $notice ) ) {
			delete_transient( 'tnstack_core_performance_notice_' . get_current_user_id() );
		}
		?>
		<div class="wrap fcp-performance-wrap">
			<header class="fcp-performance-header">
				<div class="fcp-performance-header__icon"><span class="dashicons dashicons-shield"></span></div>
				<div><h1><?php esc_html_e( 'WAF & Giám sát mã độc', 'tnstack-toolkit' ); ?></h1><p><?php esc_html_e( 'Chặn request độc hại, theo dõi toàn vẹn tệp và cảnh báo nguy cơ trên website.', 'tnstack-toolkit' ); ?></p></div>
			</header>

			<?php if ( isset( $_GET['saved'] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Đã lưu cài đặt bảo mật.', 'tnstack-toolkit' ); ?></p></div><?php endif; ?>
			<?php if ( is_array( $notice ) && ! empty( $notice['message'] ) ) : ?><div class="notice notice-<?php echo esc_attr( $notice['type'] ?? 'success' ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div><?php endif; ?>

			<form method="post" class="fcp-settings-form">
				<?php wp_nonce_field( 'tnstack_security_settings', 'tnstack_security_nonce' ); ?>

				<section class="fcp-card fcp-card--wide fcp-security-panel">
					<h2><?php esc_html_e( 'Web Application Firewall', 'tnstack-toolkit' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Phát hiện SQL injection, XSS, path traversal, PHP injection và dò tệp nhạy cảm. Log không lưu giá trị query.', 'tnstack-toolkit' ); ?></p>
					<table class="form-table" role="presentation">
						<tr><th scope="row"><?php esc_html_e( 'Trạng thái', 'tnstack-toolkit' ); ?></th><td><label><input type="checkbox" name="security[waf_enabled]" value="1" <?php checked( ! empty( $settings['waf_enabled'] ) ); ?>> <?php esc_html_e( 'Bật WAF', 'tnstack-toolkit' ); ?></label></td></tr>
						<tr><th scope="row"><label for="waf_mode"><?php esc_html_e( 'Chế độ', 'tnstack-toolkit' ); ?></label></th><td><select id="waf_mode" name="security[waf_mode]"><option value="block" <?php selected( $settings['waf_mode'], 'block' ); ?>><?php esc_html_e( 'Chặn và ghi log', 'tnstack-toolkit' ); ?></option><option value="monitor" <?php selected( $settings['waf_mode'], 'monitor' ); ?>><?php esc_html_e( 'Chỉ theo dõi', 'tnstack-toolkit' ); ?></option></select></td></tr>
						<tr><th scope="row"><label for="waf_allowlist"><?php esc_html_e( 'Đường dẫn bỏ qua', 'tnstack-toolkit' ); ?></label></th><td><textarea id="waf_allowlist" name="security[waf_allowlist]" rows="3" class="large-text code" placeholder="/webhook/partner"><?php echo esc_textarea( $settings['waf_allowlist'] ); ?></textarea><p class="description"><?php esc_html_e( 'Mỗi dòng một tiền tố URL; chỉ dùng cho webhook hợp lệ bị nhận diện nhầm.', 'tnstack-toolkit' ); ?></p></td></tr>
					</table>

					<h3><?php esc_html_e( 'Sự kiện gần đây', 'tnstack-toolkit' ); ?></h3>
					<?php if ( empty( $events ) ) : ?><p><?php esc_html_e( 'Chưa ghi nhận request đáng ngờ.', 'tnstack-toolkit' ); ?></p><?php else : ?>
					<div class="fcp-security-table-wrap"><table class="widefat striped fcp-security-table"><thead><tr><th><?php esc_html_e( 'Thời gian', 'tnstack-toolkit' ); ?></th><th><?php esc_html_e( 'Rule', 'tnstack-toolkit' ); ?></th><th><?php esc_html_e( 'Request', 'tnstack-toolkit' ); ?></th><th>IP</th></tr></thead><tbody>
					<?php foreach ( $events as $event ) : ?><tr><td><?php echo esc_html( wp_date( 'd/m/Y H:i:s', (int) ( $event['time'] ?? 0 ) ) ); ?></td><td><code><?php echo esc_html( $event['rule'] ?? '' ); ?></code></td><td><code><?php echo esc_html( strtoupper( $event['method'] ?? '' ) . ' ' . ( $event['path'] ?? '' ) ); ?></code></td><td><?php echo esc_html( $event['ip'] ?? '' ); ?></td></tr><?php endforeach; ?>
					</tbody></table></div><?php endif; ?>
				</section>

				<section class="fcp-card fcp-card--wide fcp-security-panel">
					<h2><?php esc_html_e( 'Giám sát mã độc và toàn vẹn tệp', 'tnstack-toolkit' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Đối chiếu checksum WordPress core, kiểm tra uploads, chữ ký thực thi và thay đổi file. Không tự động xóa file.', 'tnstack-toolkit' ); ?></p>
					<table class="form-table" role="presentation">
						<tr><th scope="row"><?php esc_html_e( 'Trạng thái', 'tnstack-toolkit' ); ?></th><td><label><input type="checkbox" name="security[malware_monitor]" value="1" <?php checked( ! empty( $settings['malware_monitor'] ) ); ?>> <?php esc_html_e( 'Bật quét theo lịch', 'tnstack-toolkit' ); ?></label><br><label><input type="checkbox" name="security[malware_email_alert]" value="1" <?php checked( ! empty( $settings['malware_email_alert'] ) ); ?>> <?php esc_html_e( 'Gửi email cho cảnh báo mức cao', 'tnstack-toolkit' ); ?></label></td></tr>
						<tr><th scope="row"><label for="malware_scan_frequency"><?php esc_html_e( 'Lịch quét', 'tnstack-toolkit' ); ?></label></th><td><select id="malware_scan_frequency" name="security[malware_scan_frequency]"><option value="daily" <?php selected( $settings['malware_scan_frequency'], 'daily' ); ?>><?php esc_html_e( 'Hằng ngày', 'tnstack-toolkit' ); ?></option><option value="weekly" <?php selected( $settings['malware_scan_frequency'], 'weekly' ); ?>><?php esc_html_e( 'Hằng tuần', 'tnstack-toolkit' ); ?></option></select></td></tr>
						<tr><th scope="row"><label for="malware_max_file_size_mb"><?php esc_html_e( 'Kích thước tệp tối đa', 'tnstack-toolkit' ); ?></label></th><td><input type="number" id="malware_max_file_size_mb" name="security[malware_max_file_size_mb]" min="1" max="20" value="<?php echo esc_attr( (int) $settings['malware_max_file_size_mb'] ); ?>"> MB</td></tr>
					</table>
					<p><a href="<?php echo esc_url( $scan_url ); ?>" class="button button-secondary"><?php esc_html_e( 'Quét ngay', 'tnstack-toolkit' ); ?></a></p>

					<?php if ( empty( $scan['completed_at'] ) ) : ?><p><?php esc_html_e( 'Chưa có kết quả quét.', 'tnstack-toolkit' ); ?></p><?php else : ?>
					<p><strong><?php esc_html_e( 'Lần quét gần nhất:', 'tnstack-toolkit' ); ?></strong> <?php echo esc_html( wp_date( 'd/m/Y H:i:s', (int) $scan['completed_at'] ) ); ?> — <?php echo esc_html( number_format_i18n( (int) ( $scan['files_scanned'] ?? 0 ) ) ); ?> <?php esc_html_e( 'tệp', 'tnstack-toolkit' ); ?> — <?php echo esc_html( count( $scan['findings'] ?? array() ) ); ?> <?php esc_html_e( 'cảnh báo', 'tnstack-toolkit' ); ?></p>
					<?php if ( ! empty( $scan['incomplete'] ) ) : ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'Lượt quét chạm giới hạn thời gian hoặc số tệp.', 'tnstack-toolkit' ); ?></p></div><?php endif; ?>
					<?php if ( ! empty( $scan['findings'] ) ) : ?><div class="fcp-security-table-wrap"><table class="widefat striped fcp-security-table"><thead><tr><th><?php esc_html_e( 'Mức độ', 'tnstack-toolkit' ); ?></th><th><?php esc_html_e( 'Rule', 'tnstack-toolkit' ); ?></th><th><?php esc_html_e( 'Tệp', 'tnstack-toolkit' ); ?></th><th><?php esc_html_e( 'Chi tiết', 'tnstack-toolkit' ); ?></th></tr></thead><tbody>
					<?php foreach ( array_slice( $scan['findings'], 0, 50 ) as $finding ) : ?><tr><td><span class="fcp-severity fcp-severity--<?php echo esc_attr( $finding['severity'] ?? 'low' ); ?>"><?php echo esc_html( strtoupper( $finding['severity'] ?? 'low' ) ); ?></span></td><td><code><?php echo esc_html( $finding['rule'] ?? '' ); ?></code></td><td><code><?php echo esc_html( $finding['file'] ?? '' ); ?></code></td><td><?php echo esc_html( $finding['message'] ?? '' ); ?></td></tr><?php endforeach; ?>
					</tbody></table></div><?php endif; ?><?php endif; ?>
				</section>

				<div class="fcp-savebar"><?php submit_button( __( 'Lưu cài đặt', 'tnstack-toolkit' ), 'primary', 'tnstack_security_save', false ); ?></div>
			</form>
		</div>
		<?php
	}
}

TNStack_Security_Dashboard::boot();
