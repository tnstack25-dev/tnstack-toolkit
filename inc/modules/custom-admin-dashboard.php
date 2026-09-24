<?php
/**
 * Modern WordPress dashboard.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_Custom_Admin_Dashboard {

	/**
	 * Register dashboard hooks.
	 */
	public static function boot() {
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'replace_widgets' ), 100 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'get_user_option_show_welcome_panel', '__return_true' );
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
		add_action( 'welcome_panel', array( __CLASS__, 'render' ) );
	}

	/**
	 * Remove the legacy widget grid so the custom dashboard owns the page.
	 */
	public static function replace_widgets() {
		global $wp_meta_boxes;

		// WordPress registers this callback while preparing the dashboard screen,
		// so it must be removed here instead of only during plugin bootstrap.
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
		$wp_meta_boxes['dashboard'] = array();
	}

	/**
	 * Load dashboard-only assets.
	 *
	 * @param string $hook Current admin hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'index.php' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'tnstack-custom-dashboard',
			TNSTACK_TOOLKIT_URI . 'assets/css/custom-admin-dashboard.css',
			array( 'dashicons' ),
			TNSTACK_TOOLKIT_VERSION
		);
		wp_enqueue_script(
			'tnstack-custom-dashboard',
			TNSTACK_TOOLKIT_URI . 'assets/js/custom-admin-dashboard.js',
			array(),
			TNSTACK_TOOLKIT_VERSION,
			true
		);
		wp_localize_script(
			'tnstack-custom-dashboard',
			'TNStackDashboardData',
			array(
				'activity' => self::activity_data(),
				'labels'   => array(
					'empty' => __( 'Chưa có nội dung xuất bản trong khoảng thời gian này.', 'tnstack-toolkit' ),
					'items' => __( 'nội dung', 'tnstack-toolkit' ),
				),
			)
		);
	}

	/**
	 * Add a class only on the main dashboard screen.
	 *
	 * @param string $classes Body classes.
	 * @return string
	 */
	public static function body_class( $classes ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && 'dashboard' === $screen->id ) {
			$classes .= ' tnstack-modern-dashboard';
		}

		return $classes;
	}

	/**
	 * Render the dashboard interface.
	 */
	public static function render() {
		$counts        = self::content_counts();
		$recent_args   = array(
				'post_type'      => 'post',
				'post_status'    => current_user_can( 'edit_posts' ) ? array( 'publish', 'draft', 'pending' ) : 'publish',
				'posts_per_page' => 5,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			);
		if ( current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_others_posts' ) ) {
			$recent_args['author'] = get_current_user_id();
		}
		$recent_posts    = get_posts( $recent_args );
		$recent_comments = get_comments(
			array(
				'number' => 4,
				'status' => 'approve',
			)
		);
		$current_user    = wp_get_current_user();
		?>
		<div class="tnstack-dashboard" data-tnstack-dashboard>
			<header class="tnstack-dashboard__hero">
				<div>
					<p class="tnstack-dashboard__eyebrow"><?php esc_html_e( 'TỔNG QUAN WEBSITE', 'tnstack-toolkit' ); ?></p>
					<h1><?php echo esc_html( sprintf( __( 'Xin chào, %s!', 'tnstack-toolkit' ), $current_user->display_name ) ); ?></h1>
					<p><?php esc_html_e( 'Theo dõi nội dung và thực hiện các công việc quản trị thường dùng tại một nơi.', 'tnstack-toolkit' ); ?></p>
				</div>
				<div class="tnstack-dashboard__status"><span></span><?php esc_html_e( 'Website đang hoạt động', 'tnstack-toolkit' ); ?></div>
			</header>

			<section class="tnstack-dashboard__stats" aria-label="<?php esc_attr_e( 'Thống kê nội dung', 'tnstack-toolkit' ); ?>">
				<?php
				self::stat_card( 'edit_posts', 'admin-post', __( 'Bài viết', 'tnstack-toolkit' ), $counts['posts'], admin_url( 'edit.php' ), 'blue' );
				self::stat_card( 'edit_pages', 'admin-page', __( 'Trang', 'tnstack-toolkit' ), $counts['pages'], admin_url( 'edit.php?post_type=page' ), 'violet' );
				self::stat_card( 'upload_files', 'format-image', __( 'Thư viện', 'tnstack-toolkit' ), $counts['media'], admin_url( 'upload.php' ), 'amber' );
				self::stat_card( 'list_users', 'groups', __( 'Thành viên', 'tnstack-toolkit' ), $counts['users'], admin_url( 'users.php' ), 'emerald' );
				?>
			</section>

			<?php if ( $counts['woocommerce'] && current_user_can( 'manage_woocommerce' ) ) : ?>
				<section class="tnstack-dashboard__commerce" aria-label="<?php esc_attr_e( 'Thống kê cửa hàng', 'tnstack-toolkit' ); ?>">
					<div><span class="dashicons dashicons-products"></span><strong><?php echo esc_html( number_format_i18n( $counts['products'] ) ); ?></strong><small><?php esc_html_e( 'Sản phẩm', 'tnstack-toolkit' ); ?></small></div>
					<div><span class="dashicons dashicons-cart"></span><strong><?php echo esc_html( number_format_i18n( $counts['orders'] ) ); ?></strong><small><?php esc_html_e( 'Đơn hàng', 'tnstack-toolkit' ); ?></small></div>
					<div><span class="dashicons dashicons-money-alt"></span><strong><?php echo wp_kses_post( wc_price( $counts['revenue'] ) ); ?></strong><small><?php esc_html_e( 'Doanh thu hoàn tất', 'tnstack-toolkit' ); ?></small></div>
				</section>
			<?php endif; ?>

			<div class="tnstack-dashboard__grid">
				<section class="tnstack-dashboard__panel tnstack-dashboard__activity">
					<div class="tnstack-dashboard__panel-head">
						<div><h2><?php esc_html_e( 'Hoạt động nội dung', 'tnstack-toolkit' ); ?></h2><p><?php esc_html_e( 'Số nội dung được xuất bản theo ngày', 'tnstack-toolkit' ); ?></p></div>
						<select data-tnstack-period aria-label="<?php esc_attr_e( 'Khoảng thời gian', 'tnstack-toolkit' ); ?>">
							<option value="7"><?php esc_html_e( '7 ngày', 'tnstack-toolkit' ); ?></option>
							<option value="14"><?php esc_html_e( '14 ngày', 'tnstack-toolkit' ); ?></option>
							<option value="30" selected><?php esc_html_e( '30 ngày', 'tnstack-toolkit' ); ?></option>
						</select>
					</div>
					<div class="tnstack-dashboard__chart" data-tnstack-chart role="img" aria-label="<?php esc_attr_e( 'Biểu đồ nội dung xuất bản', 'tnstack-toolkit' ); ?>"></div>
				</section>

				<section class="tnstack-dashboard__panel">
					<div class="tnstack-dashboard__panel-head"><div><h2><?php esc_html_e( 'Thao tác nhanh', 'tnstack-toolkit' ); ?></h2><p><?php esc_html_e( 'Các công việc thường dùng', 'tnstack-toolkit' ); ?></p></div></div>
					<div class="tnstack-dashboard__actions">
						<?php self::quick_action( 'edit_posts', 'plus-alt2', __( 'Viết bài mới', 'tnstack-toolkit' ), admin_url( 'post-new.php' ) ); ?>
						<?php self::quick_action( 'edit_pages', 'admin-page', __( 'Tạo trang mới', 'tnstack-toolkit' ), admin_url( 'post-new.php?post_type=page' ) ); ?>
						<?php self::quick_action( 'upload_files', 'upload', __( 'Tải tệp lên', 'tnstack-toolkit' ), admin_url( 'media-new.php' ) ); ?>
						<?php self::quick_action( 'manage_options', 'admin-generic', __( 'Cài đặt website', 'tnstack-toolkit' ), admin_url( 'options-general.php' ) ); ?>
					</div>
				</section>
			</div>

			<div class="tnstack-dashboard__grid tnstack-dashboard__grid--lower">
				<section class="tnstack-dashboard__panel">
					<div class="tnstack-dashboard__panel-head"><div><h2><?php esc_html_e( 'Nội dung gần đây', 'tnstack-toolkit' ); ?></h2><p><?php esc_html_e( 'Các bài viết vừa được cập nhật', 'tnstack-toolkit' ); ?></p></div><a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>"><?php esc_html_e( 'Xem tất cả', 'tnstack-toolkit' ); ?></a></div>
					<div class="tnstack-dashboard__list">
						<?php if ( $recent_posts ) : foreach ( $recent_posts as $post ) : ?>
							<?php $status_object = get_post_status_object( $post->post_status ); ?>
							<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ?: get_permalink( $post ) ); ?>"><span class="dashicons dashicons-media-document"></span><span><strong><?php echo esc_html( get_the_title( $post ) ?: __( '(Không có tiêu đề)', 'tnstack-toolkit' ) ); ?></strong><small><?php echo esc_html( $status_object ? $status_object->label : $post->post_status ); ?> · <?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $post->post_modified ) ) ); ?></small></span></a>
						<?php endforeach; else : ?>
							<p class="tnstack-dashboard__empty"><?php esc_html_e( 'Chưa có bài viết.', 'tnstack-toolkit' ); ?></p>
						<?php endif; ?>
					</div>
				</section>

				<section class="tnstack-dashboard__panel">
					<div class="tnstack-dashboard__panel-head"><div><h2><?php esc_html_e( 'Bình luận mới', 'tnstack-toolkit' ); ?></h2><p><?php esc_html_e( 'Phản hồi gần nhất từ người đọc', 'tnstack-toolkit' ); ?></p></div><a href="<?php echo esc_url( admin_url( 'edit-comments.php' ) ); ?>"><?php esc_html_e( 'Quản lý', 'tnstack-toolkit' ); ?></a></div>
					<div class="tnstack-dashboard__comments">
						<?php if ( $recent_comments ) : foreach ( $recent_comments as $comment ) : ?>
							<div><?php echo get_avatar( $comment, 38 ); ?><span><strong><?php echo esc_html( $comment->comment_author ); ?></strong><small><?php echo esc_html( wp_trim_words( $comment->comment_content, 12 ) ); ?></small></span></div>
						<?php endforeach; else : ?>
							<p class="tnstack-dashboard__empty"><?php esc_html_e( 'Chưa có bình luận được duyệt.', 'tnstack-toolkit' ); ?></p>
						<?php endif; ?>
					</div>
				</section>
			</div>

			<footer class="tnstack-dashboard__system">
				<span><i></i><?php esc_html_e( 'Hệ thống ổn định', 'tnstack-toolkit' ); ?></span>
				<span>WordPress <?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
				<span>PHP <?php echo esc_html( PHP_VERSION ); ?></span>
				<?php if ( current_user_can( 'view_site_health_checks' ) ) : ?><a href="<?php echo esc_url( admin_url( 'site-health.php' ) ); ?>"><?php esc_html_e( 'Kiểm tra sức khỏe website', 'tnstack-toolkit' ); ?></a><?php endif; ?>
			</footer>
		</div>
		<?php
	}

	/**
	 * Content counters.
	 *
	 * @return array<string, int|bool|float>
	 */
	private static function content_counts() {
		$post_counts = wp_count_posts( 'post' );
		$page_counts = wp_count_posts( 'page' );
		$media       = wp_count_posts( 'attachment' );
		$is_woo      = class_exists( 'WooCommerce' ) && current_user_can( 'manage_woocommerce' );
		$product_counts = $is_woo ? wp_count_posts( 'product' ) : null;
		$orders      = 0;
		$revenue     = 0.0;

		if ( $is_woo ) {
			foreach ( array_keys( wc_get_order_statuses() ) as $status ) {
				$orders += (int) wc_orders_count( str_replace( 'wc-', '', $status ) );
			}
			$revenue = self::woocommerce_revenue();
		}

		return array(
			'posts'       => isset( $post_counts->publish ) ? (int) $post_counts->publish : 0,
			'pages'       => isset( $page_counts->publish ) ? (int) $page_counts->publish : 0,
			'media'       => isset( $media->inherit ) ? (int) $media->inherit : 0,
			'users'       => (int) count_users()['total_users'],
			'woocommerce' => $is_woo,
			'products'    => $product_counts && isset( $product_counts->publish ) ? (int) $product_counts->publish : 0,
			'orders'      => $orders,
			'revenue'     => $revenue,
		);
	}

	/**
	 * Read completed revenue from WooCommerce analytics when available.
	 *
	 * @return float
	 */
	private static function woocommerce_revenue() {
		global $wpdb;

		$table = $wpdb->prefix . 'wc_order_stats';
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( $found !== $table ) {
			return 0.0;
		}

		return (float) $wpdb->get_var( "SELECT COALESCE(SUM(net_total), 0) FROM {$table} WHERE status IN ('wc-completed','wc-processing')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Published content for the last 30 days.
	 *
	 * @return array<int, array<string, int|string>>
	 */
	private static function activity_data() {
		$cached = get_transient( 'tnstack_dashboard_activity_30' );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		$today = current_datetime()->setTime( 0, 0, 0 );
		$start = $today->modify( '-29 days' )->format( 'Y-m-d H:i:s' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(post_date) AS activity_date, COUNT(ID) AS total FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('post','page') AND post_date >= %s GROUP BY DATE(post_date)",
				$start
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$totals = array();

		foreach ( $rows as $row ) {
			$totals[ $row['activity_date'] ] = (int) $row['total'];
		}

		$data = array();
		for ( $offset = 29; $offset >= 0; $offset-- ) {
			$date      = $today->modify( '-' . $offset . ' days' );
			$key       = $date->format( 'Y-m-d' );
			$data[]    = array(
				'label' => $date->format( 'd/m' ),
				'value' => $totals[ $key ] ?? 0,
			);
		}

		set_transient( 'tnstack_dashboard_activity_30', $data, 15 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * Render one stat card.
	 */
	private static function stat_card( $capability, $icon, $label, $value, $url, $tone ) {
		if ( ! current_user_can( $capability ) ) {
			return;
		}
		?>
		<a class="tnstack-dashboard__stat tnstack-dashboard__stat--<?php echo esc_attr( $tone ); ?>" href="<?php echo esc_url( $url ); ?>">
			<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
			<span><strong><?php echo esc_html( number_format_i18n( $value ) ); ?></strong><small><?php echo esc_html( $label ); ?></small></span>
			<i class="dashicons dashicons-arrow-right-alt2"></i>
		</a>
		<?php
	}

	/**
	 * Render a quick action if the current user can use it.
	 */
	private static function quick_action( $capability, $icon, $label, $url ) {
		if ( ! current_user_can( $capability ) ) {
			return;
		}
		?>
		<a href="<?php echo esc_url( $url ); ?>"><span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span><?php echo esc_html( $label ); ?></a>
		<?php
	}
}

TNStack_Custom_Admin_Dashboard::boot();
