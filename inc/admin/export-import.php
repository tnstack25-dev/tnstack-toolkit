<?php
/**
 * Toolkit settings export / import.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_Toolkit_Export_Import {

	/**
	 * Boot hooks.
	 */
	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 25 );
		add_action( 'admin_post_tnstack_export_settings', array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_tnstack_import_settings', array( __CLASS__, 'handle_import' ) );
	}

	public static function register_menu() {
		add_submenu_page(
			TNStack_Toolkit_Features_Dashboard::PAGE_SLUG,
			__( 'Export / Import', 'tnstack-toolkit' ),
			__( 'Export / Import', 'tnstack-toolkit' ),
			TNStack_Account_Permissions::MANAGE_CAP,
			'tnstack-export-import',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function handle_export() {
		if ( ! current_user_can( TNStack_Account_Permissions::MANAGE_CAP ) ) {
			wp_die( esc_html__( 'Unauthorized', 'tnstack-toolkit' ) );
		}
		check_admin_referer( 'tnstack_export' );

		$payload = array(
			'version'     => TNSTACK_TOOLKIT_VERSION,
			'exported_at' => gmdate( 'c' ),
			'modules'     => get_option( TNSTACK_TOOLKIT_SETTINGS_OPTION, array() ),
			'performance' => get_option( 'tnstack_core_optimization_settings', array() ),
		);

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=tnstack-toolkit-' . gmdate( 'Y-m-d' ) . '.json' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		exit;
	}

	public static function handle_import() {
		if ( ! current_user_can( TNStack_Account_Permissions::MANAGE_CAP ) ) {
			wp_die( esc_html__( 'Unauthorized', 'tnstack-toolkit' ) );
		}
		check_admin_referer( 'tnstack_import' );

		$file = isset( $_FILES['import_file'] ) && is_array( $_FILES['import_file'] )
			? $_FILES['import_file']
			: array();

		if (
			empty( $file['tmp_name'] )
			|| ! isset( $file['error'] )
			|| UPLOAD_ERR_OK !== (int) $file['error']
			|| empty( $file['size'] )
			|| (int) $file['size'] > 2 * MB_IN_BYTES
		) {
			wp_safe_redirect( add_query_arg( 'import', 'error', admin_url( 'admin.php?page=tnstack-export-import' ) ) );
			exit;
		}

		$filename = isset( $file['name'] ) ? sanitize_file_name( wp_unslash( $file['name'] ) ) : '';
		if ( 'json' !== strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
			wp_safe_redirect( add_query_arg( 'import', 'error', admin_url( 'admin.php?page=tnstack-export-import' ) ) );
			exit;
		}

		$json = file_get_contents( $file['tmp_name'] );
		if ( ! is_string( $json ) ) {
			wp_safe_redirect( add_query_arg( 'import', 'error', admin_url( 'admin.php?page=tnstack-export-import' ) ) );
			exit;
		}

		$data = json_decode( $json, true );

		if ( ! is_array( $data ) || JSON_ERROR_NONE !== json_last_error() ) {
			wp_safe_redirect( add_query_arg( 'import', 'error', admin_url( 'admin.php?page=tnstack-export-import' ) ) );
			exit;
		}

		if ( ! empty( $data['modules'] ) && is_array( $data['modules'] ) ) {
			tnstack_toolkit_update_admin_settings( $data['modules'] );
		}

		if ( ! empty( $data['performance'] ) && is_array( $data['performance'] ) && function_exists( 'tnstack_core_optimization_update_settings' ) ) {
			tnstack_core_optimization_update_settings( $data['performance'] );
		}

		wp_safe_redirect( add_query_arg( 'import', 'ok', admin_url( 'admin.php?page=tnstack-export-import' ) ) );
		exit;
	}

	public static function render_page() {
		if ( ! current_user_can( TNStack_Account_Permissions::MANAGE_CAP ) ) {
			return;
		}

		$import_status = isset( $_GET['import'] ) ? sanitize_key( wp_unslash( $_GET['import'] ) ) : '';
		$import_status = in_array( $import_status, array( 'ok', 'error' ), true ) ? $import_status : '';
		?>
		<div class="wrap ttk-settings ttk-settings--transfer">
			<header class="ttk-settings__hero">
				<span class="ttk-settings__hero-icon"><span class="dashicons dashicons-migrate"></span></span>
				<div>
					<span class="ttk-settings__eyebrow"><?php esc_html_e( 'TNStack Toolkit', 'tnstack-toolkit' ); ?></span>
					<h1><?php esc_html_e( 'Export / Import Settings', 'tnstack-toolkit' ); ?></h1>
					<p><?php esc_html_e( 'Di chuyển cấu hình module và tối ưu giữa website staging và production.', 'tnstack-toolkit' ); ?></p>
				</div>
			</header>

			<?php if ( 'ok' === $import_status ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Import thành công. Cấu hình mới đã được áp dụng.', 'tnstack-toolkit' ); ?></p></div>
			<?php elseif ( 'error' === $import_status ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Không thể import. Vui lòng kiểm tra tệp JSON và thử lại.', 'tnstack-toolkit' ); ?></p></div>
			<?php endif; ?>

			<div class="ttk-settings__grid">
				<section class="ttk-settings__card ttk-settings__transfer-card">
					<span class="ttk-settings__transfer-icon"><span class="dashicons dashicons-download"></span></span>
					<h2><?php esc_html_e( 'Xuất cấu hình', 'tnstack-toolkit' ); ?></h2>
					<p><?php esc_html_e( 'Tải xuống toàn bộ trạng thái module và cấu hình hiệu năng dưới dạng tệp JSON.', 'tnstack-toolkit' ); ?></p>
					<div class="ttk-settings__meta">
						<span><?php esc_html_e( 'Định dạng JSON', 'tnstack-toolkit' ); ?></span>
						<span>TNStack v<?php echo esc_html( TNSTACK_TOOLKIT_VERSION ); ?></span>
					</div>
					<p>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tnstack_export_settings' ), 'tnstack_export' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Tải tệp cấu hình', 'tnstack-toolkit' ); ?>
						</a>
					</p>
				</section>

				<section class="ttk-settings__card ttk-settings__transfer-card">
					<span class="ttk-settings__transfer-icon"><span class="dashicons dashicons-upload"></span></span>
					<h2><?php esc_html_e( 'Nhập cấu hình', 'tnstack-toolkit' ); ?></h2>
					<p><?php esc_html_e( 'Chọn tệp JSON đã xuất từ TNStack Toolkit. Dung lượng tối đa 2 MB.', 'tnstack-toolkit' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
						<input type="hidden" name="action" value="tnstack_import_settings">
						<?php wp_nonce_field( 'tnstack_import' ); ?>
						<label class="ttk-settings__file">
							<span class="screen-reader-text"><?php esc_html_e( 'Chọn tệp JSON để import', 'tnstack-toolkit' ); ?></span>
							<input type="file" name="import_file" accept="application/json,.json" required>
						</label>
						<?php submit_button( __( 'Import cấu hình', 'tnstack-toolkit' ) ); ?>
					</form>
				</section>
			</div>
		</div>
		<?php
	}
}

// Booted from bootstrap.php via ::boot().
