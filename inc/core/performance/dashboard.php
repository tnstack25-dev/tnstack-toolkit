<?php
/**
 * Performance and security management dashboard.
 *
 * @package TNStackCore
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_Core_Performance_Dashboard {

	const PAGE_SLUG = 'tnstack-core-performance';

	/**
	 * Boot admin hooks.
	 */
	public static function boot() {
		add_action( 'admin_init', array( __CLASS__, 'handle_settings_save' ) );
		add_action( 'admin_post_tnstack_core_flush_cache', array( __CLASS__, 'handle_flush_cache' ) );
		add_action( 'admin_post_flatsome_child_flush_cache', array( __CLASS__, 'handle_flush_cache' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'register_admin_bar' ), 100 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( self::PAGE_SLUG !== $page ) {
			return;
		}

		$path = tnstack_core_path( 'assets/css/performance-admin.css' );

		wp_enqueue_style(
			'tnstack-core-performance-admin',
			tnstack_core_uri( 'assets/css/performance-admin.css' ),
			array(),
			tnstack_core_asset_version( $path )
		);

		wp_register_script( 'tnstack-core-performance-admin', false, array(), TNSTACK_CORE_VERSION, true );
		wp_enqueue_script( 'tnstack-core-performance-admin' );
		wp_add_inline_script(
			'tnstack-core-performance-admin',
			"(function(){var s=document.getElementById('captcha_provider');if(!s){return;}var rows=document.querySelectorAll('.fcp-captcha-api-keys');function t(){var h='math'===s.value;rows.forEach(function(r){r.style.display=h?'none':'';});}s.addEventListener('change',t);t();})();"
		);
	}

	/**
	 * Save all settings from the dashboard form.
	 */
	public static function handle_settings_save() {
		if ( empty( $_POST['tnstack_core_performance_save'] ) ) {
			return;
		}

		if ( ! current_user_can( TNStack_Account_Permissions::MANAGE_CAP ) ) {
			wp_die( esc_html__( 'Bạn không có quyền sửa cài đặt tối ưu.', 'tnstack-toolkit' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'tnstack_core_performance_settings', 'tnstack_core_performance_nonce' );

		if ( isset( $_POST['optimization'] ) && is_array( $_POST['optimization'] ) ) {
			$input   = wp_unslash( $_POST['optimization'] );
			$current = tnstack_core_optimization_settings();

			foreach ( $current as $group => $values ) {
				if ( ! isset( $input[ $group ] ) || ! is_array( $input[ $group ] ) ) {
					$input[ $group ] = $values;
				}
			}

			// WAF and malware settings are managed on their dedicated page.
			foreach ( array( 'waf_enabled', 'waf_mode', 'waf_allowlist', 'malware_monitor', 'malware_email_alert', 'malware_scan_frequency', 'malware_max_file_size_mb' ) as $key ) {
				$input['security'][ $key ] = $current['security'][ $key ];
			}

			tnstack_core_optimization_update_settings( $input );
		}

		if ( isset( $_POST['cache_ttl'] ) && class_exists( 'Template_Performance_Cache', false ) ) {
			$purge_mode = isset( $_POST['purge_mode'] ) ? sanitize_key( wp_unslash( $_POST['purge_mode'] ) ) : 'selective';

			Template_Performance_Cache::update_settings(
				array(
					'enable_page_cache' => ! empty( $_POST['enable_page_cache'] ),
					'cache_ttl'         => absint( $_POST['cache_ttl'] ),
					'purge_mode'        => $purge_mode,
					'max_cache_files'   => absint( $_POST['max_cache_files'] ?? 5000 ),
				)
			);
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'tab'       => isset( $_POST['active_tab'] ) ? sanitize_key( wp_unslash( $_POST['active_tab'] ) ) : 'cache',
					'fcp-saved' => '1',
				),
				admin_url( 'admin.php?page=' . self::PAGE_SLUG )
			)
		);
		exit;
	}

	/**
	 * Handle the clear-cache action.
	 */
	public static function handle_flush_cache() {
		if ( ! current_user_can( TNStack_Account_Permissions::MANAGE_CAP ) ) {
			wp_die( esc_html__( 'Bạn không có quyền xóa cache.', 'tnstack-toolkit' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'tnstack_core_flush_cache', 'tnstack_core_flush_nonce' );

		$results = self::flush_all_caches();
		$message = sprintf(
			__( 'Đã xóa cache. %d tệp HTML cache đã được xóa.', 'tnstack-toolkit' ),
			(int) $results['page_cache_files']
		);

		set_transient(
			'tnstack_core_performance_notice_' . get_current_user_id(),
			array(
				'type'    => 'success',
				'message' => $message,
			),
			MINUTE_IN_SECONDS
		);

		$redirect = isset( $_GET['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) ) : '';
		$redirect = $redirect ? wp_validate_redirect( $redirect, admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ) : wp_get_referer();

		if ( ! $redirect ) {
			$redirect = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		}

		wp_safe_redirect( remove_query_arg( array( 'tnstack_core_flush_nonce', '_wpnonce', 'redirect_to' ), $redirect ) );
		exit;
	}

	/**
	 * @return array<string, int>
	 */
	private static function flush_all_caches() {
		if ( class_exists( 'Template_Performance_Cache' ) ) {
			return Template_Performance_Cache::flush_everything();
		}

		return array( 'page_cache_files' => 0 );
	}

	/**
	 * @param WP_Admin_Bar $admin_bar Admin bar instance.
	 */
	public static function register_admin_bar( $admin_bar ) {
		if ( ! current_user_can( TNStack_Account_Permissions::ACCESS_CAP ) || ! is_admin_bar_showing() ) {
			return;
		}

		if ( ! class_exists( 'TNStack_Toolkit_Features_Dashboard', false ) ) {
			require_once tnstack_core_path( 'inc/admin/dashboard.php' );
		}

		$toolkit_page_slug = class_exists( 'TNStack_Toolkit_Features_Dashboard', false )
			? TNStack_Toolkit_Features_Dashboard::PAGE_SLUG
			: 'tnstack-toolkit-features';

		$flush_url = wp_nonce_url(
			add_query_arg(
				'redirect_to',
				rawurlencode( self::current_admin_url() ),
				admin_url( 'admin-post.php?action=tnstack_core_flush_cache' )
			),
			'tnstack_core_flush_cache',
			'tnstack_core_flush_nonce'
		);

		$admin_bar->add_node(
			array(
				'id'    => 'tnstack-core-site-optimize',
				'title' => __( 'TNStack Toolkit', 'tnstack-toolkit' ),
				'href'  => admin_url( 'admin.php?page=' . $toolkit_page_slug ),
			)
		);

		$admin_bar->add_node(
			array(
				'id'     => 'tnstack-core-optimization',
				'parent' => 'tnstack-core-site-optimize',
				'title'  => __( 'Tối ưu & Bảo mật', 'tnstack-toolkit' ),
				'href'   => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
			)
		);

		$admin_bar->add_node(
			array(
				'id'     => 'tnstack-core-flush-cache',
				'parent' => 'tnstack-core-optimization',
				'title'  => __( 'Xóa cache', 'tnstack-toolkit' ),
				'href'   => $flush_url,
				'meta'   => array(
					'title' => __( 'Xóa toàn bộ cache trang và cache WordPress', 'tnstack-toolkit' ),
				),
			)
		);
	}

	/**
	 * Render the dashboard.
	 */
	public static function render_page() {
		if ( ! current_user_can( TNStack_Account_Permissions::ACCESS_CAP ) ) {
			wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'tnstack-toolkit' ), '', array( 'response' => 403 ) );
		}

		$notice = get_transient( 'tnstack_core_performance_notice_' . get_current_user_id() );
		if ( is_array( $notice ) ) {
			delete_transient( 'tnstack_core_performance_notice_' . get_current_user_id() );
		}

		$tab           = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'cache';
		$allowed_tabs  = array( 'cache', 'core', 'admin', 'security' );
		$tab           = in_array( $tab, $allowed_tabs, true ) ? $tab : 'cache';
		$stats          = class_exists( 'Template_Performance_Cache', false ) ? Template_Performance_Cache::get_stats() : array();
		$cache_settings = class_exists( 'Template_Performance_Cache', false )
			? Template_Performance_Cache::settings()
			: array(
				'enable_page_cache' => 0,
				'cache_ttl'         => 604800,
				'purge_mode'        => 'selective',
				'max_cache_files'   => 5000,
			);
		$settings      = tnstack_core_optimization_settings();
		$labels        = tnstack_core_optimization_field_labels();
		$flush_url     = wp_nonce_url(
			admin_url( 'admin-post.php?action=tnstack_core_flush_cache&redirect_to=' . rawurlencode( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=' . $tab ) ) ),
			'tnstack_core_flush_cache',
			'tnstack_core_flush_nonce'
		);
		$base_url      = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		?>
		<div class="wrap fcp-performance-wrap">
			<header class="fcp-performance-header">
				<div class="fcp-performance-header__icon"><span class="dashicons dashicons-shield-alt"></span></div>
				<div>
					<h1><?php esc_html_e( 'Tối ưu & Bảo mật', 'tnstack-toolkit' ); ?></h1>
					<p><?php esc_html_e( 'Quản lý cache, tối ưu WordPress core/theme và các lớp bảo mật nâng cao.', 'tnstack-toolkit' ); ?></p>
				</div>
			</header>

			<?php
			if ( isset( $_GET['fcp-saved'] ) ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Đã lưu cài đặt tối ưu và bảo mật.', 'tnstack-toolkit' ) . '</p></div>';
			}
			?>

			<?php if ( is_array( $notice ) && ! empty( $notice['message'] ) ) : ?>
				<div class="notice notice-<?php echo esc_attr( $notice['type'] ?? 'success' ); ?> is-dismissible">
					<p><?php echo esc_html( $notice['message'] ); ?></p>
				</div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper fcp-tabs">
				<?php
				$tabs = array(
					'cache'    => __( 'Cache', 'tnstack-toolkit' ),
					'core'     => __( 'Core & Theme', 'tnstack-toolkit' ),
					'admin'    => __( 'Admin', 'tnstack-toolkit' ),
					'security' => __( 'Bảo mật', 'tnstack-toolkit' ),
				);
				foreach ( $tabs as $slug => $label ) :
					?>
					<a class="nav-tab <?php echo $tab === $slug ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base_url ) ); ?>">
						<?php echo esc_html( $label ); ?>
						<span class="fcp-tab-count"><?php echo esc_html( self::count_enabled( $slug, $settings, $cache_settings ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="<?php echo esc_url( add_query_arg( 'tab', $tab, $base_url ) ); ?>" class="fcp-settings-form">
				<?php wp_nonce_field( 'tnstack_core_performance_settings', 'tnstack_core_performance_nonce' ); ?>
				<input type="hidden" name="active_tab" value="<?php echo esc_attr( $tab ); ?>">

				<?php if ( 'cache' === $tab ) : ?>
					<div class="fcp-performance-grid">
						<section class="fcp-card fcp-card--stats">
							<h2><?php esc_html_e( 'Trạng thái cache', 'tnstack-toolkit' ); ?></h2>
							<dl class="fcp-stats">
								<div>
									<dt><?php esc_html_e( 'Cache trang', 'tnstack-toolkit' ); ?></dt>
									<dd><span class="fcp-badge fcp-badge--<?php echo ! empty( $stats['enabled'] ) ? 'on' : 'off'; ?>"><?php echo ! empty( $stats['enabled'] ) ? esc_html__( 'Đang bật', 'tnstack-toolkit' ) : esc_html__( 'Đang tắt', 'tnstack-toolkit' ); ?></span></dd>
								</div>
								<div>
									<dt><?php esc_html_e( 'Số tệp cache', 'tnstack-toolkit' ); ?></dt>
									<dd><?php echo esc_html( number_format_i18n( (int) ( $stats['file_count'] ?? 0 ) ) ); ?></dd>
								</div>
								<div>
									<dt><?php esc_html_e( 'Dung lượng', 'tnstack-toolkit' ); ?></dt>
									<dd><?php echo esc_html( size_format( (int) ( $stats['total_bytes'] ?? 0 ) ) ); ?></dd>
								</div>
								<div>
									<dt><?php esc_html_e( 'TTL', 'tnstack-toolkit' ); ?></dt>
									<dd><?php echo esc_html( self::format_duration( (int) ( $stats['ttl'] ?? 0 ) ) ); ?></dd>
								</div>
								<div>
									<dt><?php esc_html_e( 'Chế độ xóa cache', 'tnstack-toolkit' ); ?></dt>
									<dd><?php echo 'selective' === ( $stats['purge_mode'] ?? 'selective' ) ? esc_html__( 'Theo trang (khuyên dùng)', 'tnstack-toolkit' ) : esc_html__( 'Toàn bộ', 'tnstack-toolkit' ); ?></dd>
								</div>
								<div>
									<dt><?php esc_html_e( 'Cập nhật gần nhất', 'tnstack-toolkit' ); ?></dt>
									<dd><?php echo ! empty( $stats['newest_mtime'] ) ? esc_html( wp_date( 'd/m/Y H:i:s', (int) $stats['newest_mtime'] ) ) : esc_html__( 'Chưa có cache', 'tnstack-toolkit' ); ?></dd>
								</div>
								<div>
									<dt><?php esc_html_e( 'Thư mục', 'tnstack-toolkit' ); ?></dt>
									<dd><code><?php echo esc_html( (string) ( $stats['directory'] ?? '' ) ); ?></code></dd>
								</div>
							</dl>
							<p class="fcp-card__actions">
								<a class="button button-primary button-hero" href="<?php echo esc_url( $flush_url ); ?>"><?php esc_html_e( 'Xóa toàn bộ cache', 'tnstack-toolkit' ); ?></a>
							</p>
						</section>

						<section class="fcp-card">
							<h2><?php esc_html_e( 'Cài đặt cache trang', 'tnstack-toolkit' ); ?></h2>
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row"><?php esc_html_e( 'Bật cache trang', 'tnstack-toolkit' ); ?></th>
									<td>
										<label><input type="checkbox" name="enable_page_cache" value="1" <?php checked( ! empty( $cache_settings['enable_page_cache'] ) ); ?>> <?php esc_html_e( 'Lưu HTML cho khách chưa đăng nhập', 'tnstack-toolkit' ); ?></label>
										<p class="description"><?php esc_html_e( 'Không cache admin, giỏ hàng, thanh toán, tài khoản và người dùng đã đăng nhập.', 'tnstack-toolkit' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="cache_ttl"><?php esc_html_e( 'Thời gian sống', 'tnstack-toolkit' ); ?></label></th>
									<td>
										<input type="number" id="cache_ttl" name="cache_ttl" min="300" max="604800" step="300" value="<?php echo esc_attr( (int) ( $cache_settings['cache_ttl'] ?? 604800 ) ); ?>">
										<span><?php esc_html_e( 'giây', 'tnstack-toolkit' ); ?></span>
										<p class="description"><?php esc_html_e( 'Mặc định 604.800 giây (7 ngày), tối đa 7 ngày. Cron dọn tệp hết hạn mỗi giờ; đổi theme/Customizer vẫn xóa toàn bộ.', 'tnstack-toolkit' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="purge_mode"><?php esc_html_e( 'Khi lưu bài/sản phẩm', 'tnstack-toolkit' ); ?></label></th>
									<td>
										<select id="purge_mode" name="purge_mode">
											<option value="selective" <?php selected( $cache_settings['purge_mode'] ?? 'selective', 'selective' ); ?>><?php esc_html_e( 'Chỉ xóa trang liên quan (khuyên dùng cho site lớn)', 'tnstack-toolkit' ); ?></option>
											<option value="full" <?php selected( $cache_settings['purge_mode'] ?? 'selective', 'full' ); ?>><?php esc_html_e( 'Xóa toàn bộ cache', 'tnstack-toolkit' ); ?></option>
										</select>
										<p class="description"><?php esc_html_e( 'Chế độ selective xóa URL bài viết, archive, taxonomy, shop và trang chủ liên quan — phù hợp khi có hàng nghìn bài/sản phẩm.', 'tnstack-toolkit' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="max_cache_files"><?php esc_html_e( 'Giới hạn tệp cache', 'tnstack-toolkit' ); ?></label></th>
									<td>
										<input type="number" id="max_cache_files" name="max_cache_files" min="500" max="50000" step="500" value="<?php echo esc_attr( (int) ( $cache_settings['max_cache_files'] ?? 5000 ) ); ?>">
										<p class="description"><?php esc_html_e( 'Khi vượt giới hạn, các tệp cũ nhất sẽ bị xóa tự động. Giúp kiểm soát dung lượng ổ đĩa với catalog lớn.', 'tnstack-toolkit' ); ?></p>
									</td>
								</tr>
							</table>
						</section>
					</div>
				<?php endif; ?>

				<?php if ( in_array( $tab, array( 'core', 'admin', 'security' ), true ) ) : ?>
					<section class="fcp-card fcp-card--wide">
						<h2>
							<?php
							echo esc_html(
								array(
									'core'     => __( 'Tối ưu WordPress Core & Theme', 'tnstack-toolkit' ),
									'admin'    => __( 'Tối ưu WordPress Admin', 'tnstack-toolkit' ),
									'security' => __( 'Bảo mật nâng cao', 'tnstack-toolkit' ),
								)[ $tab ]
							);
							?>
						</h2>
						<p class="description fcp-section-intro">
							<?php echo esc_html( self::tab_description( $tab ) ); ?>
						</p>

						<div class="fcp-toggle-grid">
							<?php foreach ( $labels[ $tab ] as $key => $label ) : ?>
								<label class="fcp-toggle">
									<input type="checkbox" name="optimization[<?php echo esc_attr( $tab ); ?>][<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $settings[ $tab ][ $key ] ) ); ?>>
									<span class="fcp-toggle__label"><?php echo esc_html( $label ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>

						<?php if ( 'core' === $tab ) : ?>
							<table class="form-table fcp-number-fields" role="presentation">
								<tr>
									<th scope="row"><label for="revisions_to_keep"><?php esc_html_e( 'Số revision giữ lại', 'tnstack-toolkit' ); ?></label></th>
									<td>
										<input type="number" id="revisions_to_keep" name="optimization[core][revisions_to_keep]" min="0" max="20" value="<?php echo esc_attr( (int) $settings['core']['revisions_to_keep'] ); ?>">
										<p class="description"><?php esc_html_e( 'Áp dụng khi bật giới hạn revision. Đặt 0 để tắt revision.', 'tnstack-toolkit' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="heartbeat_interval"><?php esc_html_e( 'Heartbeat interval (giây)', 'tnstack-toolkit' ); ?></label></th>
									<td>
										<input type="number" id="heartbeat_interval" name="optimization[core][heartbeat_interval]" min="15" max="300" value="<?php echo esc_attr( (int) $settings['core']['heartbeat_interval'] ); ?>">
									</td>
								</tr>
							</table>
						<?php endif; ?>

						<?php if ( 'security' === $tab ) : ?>
							<?php if ( tnstack_core_security_captcha_needs_api_keys() && ! tnstack_core_security_captcha_ready() ) : ?>
								<div class="notice notice-warning inline fcp-inline-notice">
									<p>
										<?php esc_html_e( 'CAPTCHA đang bật nhưng chưa có Site Key / Secret Key. Chọn "Phép tính đơn giản" nếu không dùng Cloudflare/Google, hoặc đăng ký key tại Turnstile / reCAPTCHA.', 'tnstack-toolkit' ); ?>
									</p>
								</div>
							<?php endif; ?>

							<table class="form-table fcp-number-fields" role="presentation">
								<tr>
									<th scope="row"><label for="login_max_attempts"><?php esc_html_e( 'Giới hạn sai / tài khoản', 'tnstack-toolkit' ); ?></label></th>
									<td>
										<input type="number" id="login_max_attempts" name="optimization[security][login_max_attempts]" min="3" max="20" value="<?php echo esc_attr( (int) $settings['security']['login_max_attempts'] ); ?>">
										<span><?php esc_html_e( 'lần', 'tnstack-toolkit' ); ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="login_lockout_minutes"><?php esc_html_e( 'Khóa tài khoản (phút)', 'tnstack-toolkit' ); ?></label></th>
									<td>
										<input type="number" id="login_lockout_minutes" name="optimization[security][login_lockout_minutes]" min="5" max="240" value="<?php echo esc_attr( (int) $settings['security']['login_lockout_minutes'] ); ?>">
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="ip_login_max_attempts"><?php esc_html_e( 'Giới hạn sai / IP', 'tnstack-toolkit' ); ?></label></th>
									<td>
										<input type="number" id="ip_login_max_attempts" name="optimization[security][ip_login_max_attempts]" min="5" max="100" value="<?php echo esc_attr( (int) $settings['security']['ip_login_max_attempts'] ); ?>">
										<span><?php esc_html_e( 'lần', 'tnstack-toolkit' ); ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="ip_lockout_minutes"><?php esc_html_e( 'Khóa IP (phút)', 'tnstack-toolkit' ); ?></label></th>
									<td>
										<input type="number" id="ip_lockout_minutes" name="optimization[security][ip_lockout_minutes]" min="15" max="1440" value="<?php echo esc_attr( (int) $settings['security']['ip_lockout_minutes'] ); ?>">
									</td>
								</tr>
							</table>

							<div class="fcp-captcha-panel">
								<h3><?php esc_html_e( 'Cấu hình CAPTCHA đăng nhập', 'tnstack-toolkit' ); ?></h3>
								<table class="form-table" role="presentation">
									<tr>
										<th scope="row"><label for="captcha_provider"><?php esc_html_e( 'Nhà cung cấp', 'tnstack-toolkit' ); ?></label></th>
										<td>
											<select id="captcha_provider" name="optimization[security][captcha_provider]">
												<option value="math" <?php selected( $settings['security']['captcha_provider'], 'math' ); ?>><?php esc_html_e( 'Phép tính đơn giản (không cần API key)', 'tnstack-toolkit' ); ?></option>
												<option value="turnstile" <?php selected( $settings['security']['captcha_provider'], 'turnstile' ); ?>><?php esc_html_e( 'Cloudflare Turnstile', 'tnstack-toolkit' ); ?></option>
												<option value="recaptcha_v2" <?php selected( $settings['security']['captcha_provider'], 'recaptcha_v2' ); ?>><?php esc_html_e( 'Google reCAPTCHA v2', 'tnstack-toolkit' ); ?></option>
											</select>
											<p class="description"><?php esc_html_e( 'Phép tính: người dùng nhập kết quả cộng/trừ (ví dụ 7 + 3). Phù hợp site nhỏ, không cần đăng ký dịch vụ bên thứ ba.', 'tnstack-toolkit' ); ?></p>
										</td>
									</tr>
									<tr class="fcp-captcha-api-keys" <?php echo 'math' === $settings['security']['captcha_provider'] ? 'style="display:none;"' : ''; ?>>
										<th scope="row"><label for="captcha_site_key"><?php esc_html_e( 'Site Key', 'tnstack-toolkit' ); ?></label></th>
										<td>
											<input type="text" class="large-text code" id="captcha_site_key" name="optimization[security][captcha_site_key]" value="<?php echo esc_attr( $settings['security']['captcha_site_key'] ); ?>" autocomplete="off">
										</td>
									</tr>
									<tr class="fcp-captcha-api-keys" <?php echo 'math' === $settings['security']['captcha_provider'] ? 'style="display:none;"' : ''; ?>>
										<th scope="row"><label for="captcha_secret_key"><?php esc_html_e( 'Secret Key', 'tnstack-toolkit' ); ?></label></th>
										<td>
											<input type="password" class="large-text code" id="captcha_secret_key" name="optimization[security][captcha_secret_key]" value="<?php echo esc_attr( $settings['security']['captcha_secret_key'] ); ?>" autocomplete="new-password">
											<p class="description">
												<?php
												printf(
													/* translators: 1: Turnstile URL, 2: reCAPTCHA URL */
													__( 'Turnstile: %1$s — reCAPTCHA: %2$s', 'tnstack-toolkit' ),
													'https://dash.cloudflare.com/?to=/:account/turnstile',
													'https://www.google.com/recaptcha/admin'
												);
												?>
											</p>
										</td>
									</tr>
								</table>
							</div>

						<?php endif; ?>
					</section>
				<?php endif; ?>

				<div class="fcp-savebar">
					<?php submit_button( __( 'Lưu cài đặt', 'tnstack-toolkit' ), 'primary', 'tnstack_core_performance_save', false ); ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * @param string               $tab            Active tab.
	 * @param array<string, mixed> $settings       Optimization settings.
	 * @param array<string, mixed> $cache_settings Cache settings.
	 */
	private static function count_enabled( $tab, $settings, $cache_settings ) {
		if ( 'cache' === $tab ) {
			return ! empty( $cache_settings['enable_page_cache'] ) ? 'ON' : 'OFF';
		}

		if ( empty( $settings[ $tab ] ) ) {
			return '0';
		}

		$exclude = array_merge(
			tnstack_core_optimization_meta_keys()['numeric'],
			tnstack_core_optimization_meta_keys()['string'],
			array( 'waf_enabled', 'malware_monitor', 'malware_email_alert' )
		);
		$count   = 0;

		foreach ( $settings[ $tab ] as $key => $value ) {
			if ( in_array( $key, $exclude, true ) ) {
				continue;
			}
			if ( ! empty( $value ) ) {
				$count++;
			}
		}

		return (string) $count;
	}

	/**
	 * @param string $tab Tab slug.
	 */
	private static function tab_description( $tab ) {
		$descriptions = array(
			'core'     => __( 'Giảm tải WordPress core và theme Flatsome Child: gỡ asset thừa, tối ưu JS/CSS/ảnh cho frontend.', 'tnstack-toolkit' ),
			'admin'    => __( 'Tăng tốc wp-admin: giảm request cập nhật, heartbeat và asset plugin không cần thiết.', 'tnstack-toolkit' ),
			'security' => __( 'Hardening WordPress: chặn XML-RPC/REST, brute-force và CAPTCHA trên wp-login.php.', 'tnstack-toolkit' ),
		);

		return $descriptions[ $tab ] ?? '';
	}

	/**
	 * @return string
	 */
	private static function current_admin_url() {
		global $pagenow;

		$url = admin_url( $pagenow ?? 'index.php' );

		if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
			$url .= '?' . sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) );
		}

		return $url;
	}

	/**
	 * @param int $seconds Duration in seconds.
	 */
	private static function format_duration( $seconds ) {
		$seconds = max( 0, (int) $seconds );

		if ( $seconds < MINUTE_IN_SECONDS ) {
			return sprintf( _n( '%d giây', '%d giây', $seconds, 'tnstack-toolkit' ), $seconds );
		}

		if ( $seconds < HOUR_IN_SECONDS ) {
			$minutes = (int) round( $seconds / MINUTE_IN_SECONDS );
			return sprintf( _n( '%d phút', '%d phút', $minutes, 'tnstack-toolkit' ), $minutes );
		}

		$hours = (int) round( $seconds / HOUR_IN_SECONDS );
		return sprintf( _n( '%d giờ', '%d giờ', $hours, 'tnstack-toolkit' ), $hours );
	}
}

TNStack_Core_Performance_Dashboard::boot();
