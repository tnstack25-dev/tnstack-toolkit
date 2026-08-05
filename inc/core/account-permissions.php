<?php
/**
 * Per-account access and delegated WordPress capabilities.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_Account_Permissions {

	const OPTION    = 'tnstack_toolkit_account_permissions';
	const PAGE_SLUG = 'tnstack-account-permissions';
	const ACCESS_CAP = 'tnstack_toolkit_access';
	const MANAGE_CAP = 'tnstack_toolkit_manage_settings';
	const SECURITY_CAP = 'tnstack_toolkit_view_security';
	const PERMISSIONS_CAP = 'tnstack_toolkit_manage_permissions';

	/** @var array<int, array<string, int>>|null */
	private static $settings_cache = null;

	/**
	 * Register permission enforcement and the admin page.
	 */
	public static function boot() {
		// Run after WP Site Monitor Agent so an explicit Toolkit grant is not overwritten.
		add_filter( 'user_has_cap', array( __CLASS__, 'filter_user_capabilities' ), 1000, 4 );
		add_filter( 'repu_allow_editor_capability', array( __CLASS__, 'allow_delegated_protected_capability' ), 10, 3 );
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 30 );
		add_action( 'admin_init', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * @return array<int, array<string, int>>
	 */
	public static function settings() {
		if ( null !== self::$settings_cache ) {
			return self::$settings_cache;
		}

		$stored = get_option( self::OPTION, array() );

		self::$settings_cache = self::sanitize_authorized_users( is_array( $stored ) ? $stored : array() );

		return self::$settings_cache;
	}

	/**
	 * @param mixed $users Raw permissions map.
	 * @return array<int, array<string, int>>
	 */
	private static function sanitize_authorized_users( $users ) {
		if ( ! is_array( $users ) ) {
			return array();
		}

		$allowed = array( 'access_toolkit', 'view_security', 'manage_settings', 'manage_plugins', 'install_plugins', 'manage_themes', 'edit_files' );
		$clean   = array();

		foreach ( $users as $user_id => $permissions ) {
			$user_id = absint( $user_id );
			if ( ! $user_id || ! is_array( $permissions ) || ! get_user_by( 'id', $user_id ) ) {
				continue;
			}

			$clean[ $user_id ] = array();
			$has_permission    = false;
			foreach ( $allowed as $permission ) {
				$clean[ $user_id ][ $permission ] = ! empty( $permissions[ $permission ] ) ? 1 : 0;
				$has_permission = $has_permission || ! empty( $clean[ $user_id ][ $permission ] );
			}

			if ( ! $has_permission ) {
				unset( $clean[ $user_id ] );
			}
		}

		return $clean;
	}

	/**
	 * @return array<string, string>
	 */
	private static function managed_capability_map() {
		return array(
			'activate_plugins'   => 'manage_plugins',
			'deactivate_plugins' => 'manage_plugins',
			'update_plugins'     => 'manage_plugins',
			'delete_plugins'     => 'manage_plugins',
			'resume_plugins'     => 'manage_plugins',
			'install_plugins'    => 'install_plugins',
			'upload_plugins'     => 'install_plugins',
			'switch_themes'      => 'manage_themes',
			'edit_theme_options' => 'manage_themes',
			'update_themes'      => 'manage_themes',
			'delete_themes'      => 'manage_themes',
			'resume_themes'      => 'manage_themes',
			'edit_plugins'       => 'edit_files',
			'edit_themes'        => 'edit_files',
			'edit_files'         => 'edit_files',
		);
	}

	/**
	 * Add Toolkit and delegated WordPress capabilities for the current account.
	 */
	public static function filter_user_capabilities( $allcaps, $caps, $args, $user ) {
		unset( $caps, $args );

		$user_id = isset( $user->ID ) ? absint( $user->ID ) : 0;
		if ( ! $user_id ) {
			return $allcaps;
		}

		$authorized = self::settings();
		if ( empty( $authorized ) ) {
			if ( ! empty( $allcaps['manage_options'] ) ) {
				$allcaps[ self::ACCESS_CAP ]      = true;
				$allcaps[ self::MANAGE_CAP ]      = true;
				$allcaps[ self::SECURITY_CAP ]    = true;
				$allcaps[ self::PERMISSIONS_CAP ] = true;
			}
			return $allcaps;
		}

		$permissions = isset( $authorized[ $user_id ] ) ? $authorized[ $user_id ] : array();
		$has_access  = ! empty( $permissions['access_toolkit'] );
		$can_manage  = $has_access && ! empty( $permissions['manage_settings'] );

		$allcaps[ self::ACCESS_CAP ]      = $has_access;
		$allcaps[ self::MANAGE_CAP ]      = $can_manage;
		$allcaps[ self::SECURITY_CAP ]    = $has_access && ! empty( $permissions['view_security'] );
		$allcaps[ self::PERMISSIONS_CAP ] = $can_manage && ! empty( $allcaps['manage_options'] );

		foreach ( self::managed_capability_map() as $capability => $permission_key ) {
			$allcaps[ $capability ] = ! empty( $permissions[ $permission_key ] );
		}

		return $allcaps;
	}

	/**
	 * Compatibility with plugins that protect delegated editor capabilities.
	 */
	public static function allow_delegated_protected_capability( $allowed, $capability, $user_id ) {
		if ( $allowed ) {
			return true;
		}

		$authorized = self::settings();
		$permissions = isset( $authorized[ absint( $user_id ) ] ) ? $authorized[ absint( $user_id ) ] : array();
		$map = self::managed_capability_map();

		return isset( $map[ $capability ] ) && ! empty( $permissions[ $map[ $capability ] ] );
	}

	public static function register_menu() {
		add_submenu_page(
			TNStack_Toolkit_Features_Dashboard::PAGE_SLUG,
			__( 'Phân quyền tài khoản', 'tnstack-toolkit' ),
			__( 'Phân quyền tài khoản', 'tnstack-toolkit' ),
			self::PERMISSIONS_CAP,
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function enqueue_assets() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( self::PAGE_SLUG !== $page ) {
			return;
		}

		$path = tnstack_core_path( 'assets/css/module-settings-admin.css' );
		wp_enqueue_style( 'tnstack-module-settings-admin', tnstack_core_uri( 'assets/css/module-settings-admin.css' ), array(), tnstack_core_asset_version( $path ) );
	}

	public static function handle_save() {
		if ( empty( $_POST['tnstack_account_permissions_save'] ) ) {
			return;
		}

		if ( ! current_user_can( self::PERMISSIONS_CAP ) ) {
			wp_die( esc_html__( 'Bạn không có quyền thay đổi phân quyền Toolkit.', 'tnstack-toolkit' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'tnstack_account_permissions', 'tnstack_account_permissions_nonce' );
		$input = isset( $_POST['authorized_users'] ) ? wp_unslash( $_POST['authorized_users'] ) : array();
		$authorized = self::sanitize_authorized_users( $input );

		if ( empty( $authorized ) ) {
			self::redirect_with_status( 'empty' );
		}

		$has_admin_manager = false;
		foreach ( $authorized as $user_id => $permissions ) {
			$user = get_user_by( 'id', $user_id );
			if ( $user && user_can( $user, 'manage_options' ) && ! empty( $permissions['access_toolkit'] ) && ! empty( $permissions['manage_settings'] ) ) {
				$has_admin_manager = true;
				break;
			}
		}

		if ( ! $has_admin_manager ) {
			self::redirect_with_status( 'admin_required' );
		}

		update_option( self::OPTION, $authorized, false );
		self::$settings_cache = $authorized;
		self::redirect_with_status( 'saved' );
	}

	private static function redirect_with_status( $status ) {
		wp_safe_redirect( add_query_arg( 'permissions', sanitize_key( $status ), admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ) );
		exit;
	}

	public static function render_page() {
		if ( ! current_user_can( self::PERMISSIONS_CAP ) ) {
			wp_die( esc_html__( 'Bạn không có quyền truy cập trang phân quyền Toolkit.', 'tnstack-toolkit' ), '', array( 'response' => 403 ) );
		}

		$authorized = self::settings();
		$status = isset( $_GET['permissions'] ) ? sanitize_key( wp_unslash( $_GET['permissions'] ) ) : '';
		$users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
		?>
		<div class="wrap ttk-settings">
			<header class="ttk-settings__hero">
				<span class="ttk-settings__hero-icon"><span class="dashicons dashicons-admin-users"></span></span>
				<div>
					<span class="ttk-settings__eyebrow"><?php esc_html_e( 'TNStack Toolkit', 'tnstack-toolkit' ); ?></span>
					<h1><?php esc_html_e( 'Phân quyền tài khoản', 'tnstack-toolkit' ); ?></h1>
					<p><?php esc_html_e( 'Chỉ định tài khoản được truy cập Toolkit và cấp riêng các quyền quản trị WordPress cần thiết.', 'tnstack-toolkit' ); ?></p>
				</div>
			</header>

			<?php if ( 'saved' === $status ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Đã lưu phân quyền tài khoản.', 'tnstack-toolkit' ); ?></p></div>
			<?php elseif ( 'empty' === $status ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'Phải cấp ít nhất một quyền cho một tài khoản.', 'tnstack-toolkit' ); ?></p></div>
			<?php elseif ( 'admin_required' === $status ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'Phải giữ lại ít nhất một quản trị viên có quyền truy cập và sửa cài đặt Toolkit để tránh bị khóa.', 'tnstack-toolkit' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'tnstack_account_permissions', 'tnstack_account_permissions_nonce' ); ?>
				<div class="ttk-settings__card">
					<h2><?php esc_html_e( 'Danh sách quyền', 'tnstack-toolkit' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Sau khi lưu lần đầu, tài khoản không được chọn sẽ không thể mở Toolkit. Các quyền plugin và giao diện hoạt động độc lập với quyền truy cập Toolkit.', 'tnstack-toolkit' ); ?></p>
					<div style="overflow-x:auto;margin-top:16px">
						<table class="widefat striped">
							<thead><tr>
								<th><?php esc_html_e( 'Tài khoản', 'tnstack-toolkit' ); ?></th>
								<th><?php esc_html_e( 'Truy cập Toolkit', 'tnstack-toolkit' ); ?></th>
								<th><?php esc_html_e( 'Xem WAF & mã độc', 'tnstack-toolkit' ); ?></th>
								<th><?php esc_html_e( 'Sửa cài đặt Toolkit', 'tnstack-toolkit' ); ?></th>
								<th><?php esc_html_e( 'Quản lý plugin', 'tnstack-toolkit' ); ?></th>
								<th><?php esc_html_e( 'Cài plugin', 'tnstack-toolkit' ); ?></th>
								<th><?php esc_html_e( 'Quản lý giao diện', 'tnstack-toolkit' ); ?></th>
								<th><?php esc_html_e( 'Sửa tệp plugin/giao diện', 'tnstack-toolkit' ); ?></th>
							</tr></thead>
							<tbody>
							<?php foreach ( $users as $account ) : $permissions = isset( $authorized[ $account->ID ] ) ? $authorized[ $account->ID ] : array(); ?>
								<tr>
									<td><strong><?php echo esc_html( $account->display_name ); ?></strong><br><code><?php echo esc_html( $account->user_login ); ?></code></td>
									<?php foreach ( array( 'access_toolkit', 'view_security', 'manage_settings', 'manage_plugins', 'install_plugins', 'manage_themes', 'edit_files' ) as $permission ) : ?>
										<td><input type="checkbox" name="authorized_users[<?php echo esc_attr( $account->ID ); ?>][<?php echo esc_attr( $permission ); ?>]" value="1" <?php checked( ! empty( $permissions[ $permission ] ) ); ?>></td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<p class="description"><strong><?php esc_html_e( 'Cảnh báo:', 'tnstack-toolkit' ); ?></strong> <?php esc_html_e( 'Quyền sửa tệp có thể thay đổi trực tiếp mã nguồn website. Chỉ cấp cho tài khoản tin cậy.', 'tnstack-toolkit' ); ?></p>
				</div>
				<?php submit_button( __( 'Lưu phân quyền', 'tnstack-toolkit' ), 'primary', 'tnstack_account_permissions_save' ); ?>
			</form>
		</div>
		<?php
	}
}
