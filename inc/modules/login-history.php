<?php
/** Login history and lockout management. */
defined( 'ABSPATH' ) || exit;

final class TNStack_Login_History {
	const OPTION = 'tnstack_login_history';
	const LIMIT = 500;

	public static function init() {
		add_action( 'wp_login', array( __CLASS__, 'success' ), 10, 2 );
		add_action( 'wp_login_failed', array( __CLASS__, 'failed' ), 10, 2 );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 30 );
		add_action( 'admin_post_tnstack_unlock_login_ip', array( __CLASS__, 'unlock' ) );
		add_action( 'admin_post_tnstack_clear_login_history', array( __CLASS__, 'clear' ) );
	}

	public static function success( $username, $user ) { self::record( $username, 'success', '', $user instanceof WP_User ? $user->ID : 0 ); }
	public static function failed( $username, $error = null ) {
		$reason = $error instanceof WP_Error ? implode( ', ', $error->get_error_codes() ) : 'invalid_credentials';
		self::record( $username, 'failed', $reason );
	}
	private static function record( $username, $status, $reason = '', $user_id = 0 ) {
		$rows = get_option( self::OPTION, array() );
		array_unshift( $rows, array( 'time' => time(), 'username' => sanitize_user( $username ), 'user_id' => absint( $user_id ), 'status' => $status, 'reason' => sanitize_text_field( $reason ), 'ip' => tnstack_core_security_client_ip(), 'agent' => sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ) ) );
		update_option( self::OPTION, array_slice( $rows, 0, self::LIMIT ), false );
	}
	public static function menu() {
		add_submenu_page( TNStack_Toolkit_Features_Dashboard::PAGE_SLUG, 'Lịch sử đăng nhập', 'Lịch sử đăng nhập', TNStack_Account_Permissions::MANAGE_CAP, 'tnstack-login-history', array( __CLASS__, 'page' ) );
	}
	private static function guard( $action ) { if ( ! current_user_can( TNStack_Account_Permissions::MANAGE_CAP ) ) wp_die( 'Bạn không có quyền thực hiện thao tác này.' ); check_admin_referer( $action ); }
	public static function unlock() {
		self::guard( 'tnstack_unlock_login_ip' );
		$ip = sanitize_text_field( wp_unslash( $_POST['ip'] ?? '' ) ); $username = sanitize_user( wp_unslash( $_POST['username'] ?? '' ) );
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) { delete_transient( 'fcp_ip_login_' . md5( $ip ) ); if ( $username ) delete_transient( 'fcp_login_' . md5( strtolower( $username ) . '|' . $ip ) ); }
		wp_safe_redirect( add_query_arg( 'unlocked', '1', admin_url( 'admin.php?page=tnstack-login-history' ) ) ); exit;
	}
	public static function clear() { self::guard( 'tnstack_clear_login_history' ); delete_option( self::OPTION ); wp_safe_redirect( add_query_arg( 'cleared', '1', admin_url( 'admin.php?page=tnstack-login-history' ) ) ); exit; }
	public static function page() {
		if ( ! current_user_can( TNStack_Account_Permissions::MANAGE_CAP ) ) return;
		$rows = get_option( self::OPTION, array() ); $status = sanitize_key( wp_unslash( $_GET['status'] ?? '' ) ); $search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		$rows = array_filter( $rows, function( $row ) use ( $status, $search ) { if ( $status && $row['status'] !== $status ) return false; return ! $search || false !== stripos( $row['username'] . ' ' . $row['ip'], $search ); } );
		?>
		<div class="wrap ttk-login-history"><header class="tns-page-hero"><span class="tns-page-hero__icon"><span class="dashicons dashicons-backup"></span></span><div><h1>Lịch sử đăng nhập</h1><p>Theo dõi 500 lần đăng nhập gần nhất và mở khóa nhanh địa chỉ IP.</p></div></header>
		<?php if ( isset( $_GET['unlocked'] ) ) echo '<div class="notice notice-success"><p>Đã mở khóa IP.</p></div>'; ?>
		<div class="ttk-history-tools"><form><input type="hidden" name="page" value="tnstack-login-history"><input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Tên đăng nhập hoặc IP"><select name="status"><option value="">Tất cả trạng thái</option><option value="success" <?php selected( $status, 'success' ); ?>>Thành công</option><option value="failed" <?php selected( $status, 'failed' ); ?>>Thất bại</option></select><button class="button">Lọc</button></form><form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>"><?php wp_nonce_field('tnstack_clear_login_history'); ?><input type="hidden" name="action" value="tnstack_clear_login_history"><button class="button" onclick="return confirm('Xóa toàn bộ lịch sử?')">Xóa lịch sử</button></form></div>
		<table class="widefat striped"><thead><tr><th>Thời gian</th><th>Tài khoản</th><th>IP</th><th>Trạng thái</th><th>Thiết bị</th><th></th></tr></thead><tbody><?php if(!$rows): ?><tr><td colspan="6">Chưa có dữ liệu.</td></tr><?php else: foreach($rows as $row): ?><tr><td><?php echo esc_html( wp_date('d/m/Y H:i:s', $row['time']) ); ?></td><td><strong><?php echo esc_html($row['username'] ?: '—'); ?></strong></td><td><code><?php echo esc_html($row['ip']); ?></code></td><td><span class="ttk-state ttk-state--<?php echo esc_attr($row['status']); ?>"><?php echo $row['status']==='success'?'Thành công':'Thất bại'; ?></span></td><td title="<?php echo esc_attr($row['agent']); ?>"><?php echo esc_html(wp_html_excerpt($row['agent'],55,'…')); ?></td><td><?php if($row['status']==='failed'): ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><?php wp_nonce_field('tnstack_unlock_login_ip'); ?><input type="hidden" name="action" value="tnstack_unlock_login_ip"><input type="hidden" name="ip" value="<?php echo esc_attr($row['ip']); ?>"><input type="hidden" name="username" value="<?php echo esc_attr($row['username']); ?>"><button class="button button-small">Mở khóa IP</button></form><?php endif; ?></td></tr><?php endforeach; endif; ?></tbody></table></div>
		<style>.ttk-login-history{max-width:1200px}.ttk-history-tools{display:flex;justify-content:space-between;gap:16px;margin:18px 0}.ttk-history-tools form{display:flex;gap:8px}.ttk-state{display:inline-block;padding:4px 9px;border-radius:999px;font-weight:600}.ttk-state--success{color:#166534;background:#dcfce7}.ttk-state--failed{color:#991b1b;background:#fee2e2}.ttk-login-history td{vertical-align:middle}@media(max-width:782px){.ttk-history-tools{align-items:flex-start;flex-direction:column}.ttk-history-tools form:first-child{flex-wrap:wrap}}</style>
		<?php
	}
}
TNStack_Login_History::init();
