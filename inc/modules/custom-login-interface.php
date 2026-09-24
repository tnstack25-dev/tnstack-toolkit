<?php
/**
 * Modern login interface.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_Custom_Login_Interface {

	/**
	 * Register login hooks.
	 */
	public static function boot() {
		add_action( 'login_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'login_head', array( __CLASS__, 'brand_styles' ) );
		add_action( 'login_header', array( __CLASS__, 'open_layout' ) );
		add_action( 'login_footer', array( __CLASS__, 'close_layout' ) );
		add_filter( 'login_headerurl', array( __CLASS__, 'home_url' ) );
		add_filter( 'login_headertext', array( __CLASS__, 'site_name' ) );
		add_filter( 'login_body_class', array( __CLASS__, 'body_classes' ) );
	}

	/**
	 * Enqueue self-hosted login assets.
	 */
	public static function enqueue_assets() {
		wp_enqueue_style( 'tnstack-login-interface', TNSTACK_TOOLKIT_URI . 'assets/css/custom-login-interface.css', array(), TNSTACK_TOOLKIT_VERSION );
		wp_enqueue_script( 'tnstack-login-interface', TNSTACK_TOOLKIT_URI . 'assets/js/custom-login-interface.js', array(), TNSTACK_TOOLKIT_VERSION, true );
	}

	/**
	 * Print the site logo background when one is available.
	 */
	public static function brand_styles() {
		$logo = self::logo_url();

		if ( ! $logo ) {
			return;
		}
		?>
		<style id="tnstack-login-brand">.tnstack-login-brand__logo{background-image:url('<?php echo esc_url( $logo ); ?>')}</style>
		<?php
	}

	/**
	 * Open the two-column shell before WordPress prints #login.
	 */
	public static function open_layout() {
		$copy = self::screen_copy();
		?>
		<div class="tnstack-login-shell">
			<section class="tnstack-login-brand" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<div class="tnstack-login-brand__glow tnstack-login-brand__glow--one"></div>
				<div class="tnstack-login-brand__glow tnstack-login-brand__glow--two"></div>
				<div class="tnstack-login-brand__content">
					<a class="tnstack-login-brand__logo<?php echo self::logo_url() ? ' has-image' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span><?php echo esc_html( self::initials() ); ?></span></a>
					<p class="tnstack-login-brand__eyebrow"><?php esc_html_e( 'TRANG QUẢN TRỊ', 'tnstack-toolkit' ); ?></p>
					<h1><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
					<p><?php esc_html_e( 'Quản lý nội dung, khách hàng và hoạt động website trong một không gian làm việc an toàn.', 'tnstack-toolkit' ); ?></p>
				</div>
				<div class="tnstack-login-brand__footer"><?php echo esc_html( sprintf( __( '© %1$s %2$s', 'tnstack-toolkit' ), wp_date( 'Y' ), get_bloginfo( 'name' ) ) ); ?></div>
			</section>
			<main class="tnstack-login-panel">
				<div class="tnstack-login-panel__inner">
					<div class="tnstack-login-panel__heading">
						<p><?php esc_html_e( 'TNSTACK WORKSPACE', 'tnstack-toolkit' ); ?></p>
						<h2><?php echo esc_html( $copy['title'] ); ?></h2>
						<span><?php echo esc_html( $copy['description'] ); ?></span>
					</div>
		<?php
	}

	/**
	 * Close the shell after WordPress prints #login.
	 */
	public static function close_layout() {
		?>
				</div>
			</main>
		</div>
		<?php
	}

	/**
	 * Add a stable body class for compatibility styling.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_classes( $classes ) {
		$classes[] = 'tnstack-login-page';

		return $classes;
	}

	/**
	 * Use the public home page for the logo link.
	 *
	 * @return string
	 */
	public static function home_url() {
		return home_url( '/' );
	}

	/**
	 * Use the current site name as accessible logo text.
	 *
	 * @return string
	 */
	public static function site_name() {
		return get_bloginfo( 'name' );
	}

	/**
	 * Contextual title and description for each login action.
	 *
	 * @return array<string, string>
	 */
	private static function screen_copy() {
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$screens = array(
			'lostpassword' => array( 'title' => __( 'Khôi phục mật khẩu', 'tnstack-toolkit' ), 'description' => __( 'Nhập email hoặc tên đăng nhập để nhận liên kết đặt lại mật khẩu.', 'tnstack-toolkit' ) ),
			'retrievepassword' => array( 'title' => __( 'Khôi phục mật khẩu', 'tnstack-toolkit' ), 'description' => __( 'Nhập email hoặc tên đăng nhập để nhận liên kết đặt lại mật khẩu.', 'tnstack-toolkit' ) ),
			'rp' => array( 'title' => __( 'Đặt mật khẩu mới', 'tnstack-toolkit' ), 'description' => __( 'Tạo mật khẩu mạnh để tiếp tục bảo vệ tài khoản của bạn.', 'tnstack-toolkit' ) ),
			'resetpass' => array( 'title' => __( 'Đặt mật khẩu mới', 'tnstack-toolkit' ), 'description' => __( 'Tạo mật khẩu mạnh để tiếp tục bảo vệ tài khoản của bạn.', 'tnstack-toolkit' ) ),
			'register' => array( 'title' => __( 'Tạo tài khoản', 'tnstack-toolkit' ), 'description' => __( 'Điền thông tin bên dưới để đăng ký tài khoản mới.', 'tnstack-toolkit' ) ),
			'confirm_admin_email' => array( 'title' => __( 'Xác nhận email quản trị', 'tnstack-toolkit' ), 'description' => __( 'Kiểm tra và xác nhận địa chỉ email quản trị website.', 'tnstack-toolkit' ) ),
		);

		return $screens[ $action ] ?? array(
			'title'       => __( 'Chào mừng trở lại', 'tnstack-toolkit' ),
			'description' => __( 'Đăng nhập để tiếp tục vào trang quản trị website.', 'tnstack-toolkit' ),
		);
	}

	/**
	 * Get the best available site logo.
	 *
	 * @return string
	 */
	private static function logo_url() {
		$custom_logo = (int) get_theme_mod( 'custom_logo' );

		if ( $custom_logo ) {
			$source = wp_get_attachment_image_url( $custom_logo, 'medium' );
			if ( $source ) {
				return $source;
			}
		}

		return (string) get_site_icon_url( 192 );
	}

	/**
	 * Build a short fallback mark from the site name.
	 *
	 * @return string
	 */
	private static function initials() {
		$name  = trim( wp_strip_all_tags( get_bloginfo( 'name' ) ) );
		$words = preg_split( '/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY );
		$mark  = '';

		foreach ( array_slice( (array) $words, 0, 2 ) as $word ) {
			$mark .= function_exists( 'mb_substr' ) ? mb_substr( $word, 0, 1 ) : substr( $word, 0, 1 );
		}

		return strtoupper( $mark ?: 'TN' );
	}
}

TNStack_Custom_Login_Interface::boot();
