<?php
/**
 * GitHub Releases updater for TNStack Toolkit.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_GitHub_Updater {

	const REPOSITORY       = 'tnstack25-dev/tnstack-toolkit';
	const RELEASE_CACHE    = 'tnstack_github_latest_release';
	const ERROR_CACHE      = 'tnstack_github_release_error';
	const RELEASE_CACHE_TTL = 6 * HOUR_IN_SECONDS;
	const ERROR_CACHE_TTL  = 30 * MINUTE_IN_SECONDS;
	const PLUGIN_SLUG      = 'tnstack-toolkit';
	const ASSET_NAME       = 'tnstack-toolkit.zip';

	/**
	 * @var bool
	 */
	private static $booted = false;

	/**
	 * Register updater hooks.
	 */
	public static function boot() {
		if ( self::$booted ) {
			return;
		}

		self::$booted = true;

		add_filter( 'update_plugins_github.com', array( __CLASS__, 'filter_update' ), 10, 4 );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_information' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'normalize_package_directory' ), 10, 4 );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ), 30 );
			add_action( 'admin_post_tnstack_github_updater_check', array( __CLASS__, 'handle_check' ) );
		}
	}

	/**
	 * Supply update data for the plugin Update URI hostname.
	 *
	 * @param array|false $update      Existing update.
	 * @param array       $plugin_data Plugin headers.
	 * @param string      $plugin_file Plugin basename.
	 * @param string[]    $locales     Installed locales.
	 * @return array|false
	 */
	public static function filter_update( $update, $plugin_data, $plugin_file, $locales ) {
		unset( $locales );

		if ( plugin_basename( TNSTACK_TOOLKIT_FILE ) !== $plugin_file ) {
			return $update;
		}

		$release = self::get_release();
		if ( is_wp_error( $release ) ) {
			return false;
		}

		$current = isset( $plugin_data['Version'] ) ? (string) $plugin_data['Version'] : TNSTACK_TOOLKIT_VERSION;
		if ( ! version_compare( $release['version'], $current, '>' ) ) {
			return false;
		}

		return array(
			'id'           => self::repository_url(),
			'slug'         => self::PLUGIN_SLUG,
			'version'      => $release['version'],
			'url'          => $release['html_url'],
			'package'      => $release['package'],
			'requires'     => '6.0',
			'requires_php' => '7.4',
			'autoupdate'   => false,
		);
	}

	/**
	 * Provide the "View details" modal in the Plugins screen.
	 *
	 * @param false|object|array $result Existing result.
	 * @param string             $action API action.
	 * @param object             $args   API arguments.
	 * @return false|object|array
	 */
	public static function plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || self::PLUGIN_SLUG !== $args->slug ) {
			return $result;
		}

		$release = self::get_release();
		if ( is_wp_error( $release ) ) {
			return $result;
		}

		$changelog = '' !== $release['body']
			? wpautop( esc_html( $release['body'] ) )
			: '<p>' . esc_html__( 'Xem ghi chú phát hành trên GitHub.', 'tnstack-toolkit' ) . '</p>';

		return (object) array(
			'name'          => 'TNStack Toolkit',
			'slug'          => self::PLUGIN_SLUG,
			'version'       => $release['version'],
			'author'        => '<a href="https://tnstack.com">TNStack</a>',
			'homepage'      => self::repository_url(),
			'requires'      => '6.0',
			'requires_php'  => '7.4',
			'last_updated'  => $release['published_at'],
			'download_link' => $release['package'],
			'sections'      => array(
				'description' => '<p>' . esc_html__( 'TNStack Toolkit được cập nhật trực tiếp từ GitHub Releases.', 'tnstack-toolkit' ) . '</p>',
				'changelog'   => $changelog,
			),
		);
	}

	/**
	 * Ensure GitHub-generated archives install into the existing plugin slug.
	 *
	 * @param string      $source        Extracted source path.
	 * @param string      $remote_source Remote working directory.
	 * @param WP_Upgrader $upgrader      Upgrader instance.
	 * @param array       $hook_extra    Upgrade context.
	 * @return string|WP_Error
	 */
	public static function normalize_package_directory( $source, $remote_source, $upgrader, $hook_extra ) {
		unset( $upgrader );

		if ( ! self::is_our_upgrade( $hook_extra ) || self::PLUGIN_SLUG === basename( untrailingslashit( $source ) ) ) {
			return $source;
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			return $source;
		}

		$destination = trailingslashit( $remote_source ) . self::PLUGIN_SLUG . '/';
		if ( $wp_filesystem->exists( $destination ) ) {
			$wp_filesystem->delete( $destination, true );
		}

		if ( ! $wp_filesystem->move( $source, $destination, true ) ) {
			return new WP_Error( 'tnstack_github_source', __( 'Không thể chuẩn hóa thư mục gói cập nhật GitHub.', 'tnstack-toolkit' ) );
		}

		return $destination;
	}

	/**
	 * Register the updater settings page.
	 */
	public static function register_admin_page() {
		add_submenu_page(
			TNStack_Toolkit_Features_Dashboard::PAGE_SLUG,
			__( 'Plugin & Hệ thống', 'tnstack-toolkit' ),
			__( 'Plugin & Hệ thống', 'tnstack-toolkit' ),
			TNStack_Account_Permissions::ACCESS_CAP,
			'tnstack-github-updates',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Force WordPress and GitHub release caches to refresh.
	 */
	public static function handle_check() {
		if ( ! current_user_can( TNStack_Account_Permissions::MANAGE_CAP ) ) {
			wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'tnstack-toolkit' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'tnstack_github_updater_check' );
		self::clear_cache();
		$release = self::get_release( true );
		delete_site_transient( 'update_plugins' );

		if ( function_exists( 'wp_update_plugins' ) ) {
			wp_update_plugins();
		}

		wp_safe_redirect( add_query_arg( 'github-updater', is_wp_error( $release ) ? 'check-error' : 'checked', self::admin_url() ) );
		exit;
	}

	/**
	 * Render GitHub updater settings and connection state.
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( TNStack_Account_Permissions::ACCESS_CAP ) ) {
			return;
		}

		global $wpdb;

		$release      = get_site_transient( self::RELEASE_CACHE );
		$release      = is_array( $release ) && ! empty( $release['version'] ) ? $release : false;
		$cached_error = get_site_transient( self::ERROR_CACHE );
		$status       = isset( $_GET['github-updater'] ) ? sanitize_key( wp_unslash( $_GET['github-updater'] ) ) : '';
		$latest       = $release ? $release['version'] : __( 'Chưa kiểm tra', 'tnstack-toolkit' );
		$update_state = ! $release
			? __( 'Chưa kiểm tra', 'tnstack-toolkit' )
			: ( version_compare( $release['version'], TNSTACK_TOOLKIT_VERSION, '>' )
				? __( 'Có bản cập nhật', 'tnstack-toolkit' )
				: __( 'Đã mới nhất', 'tnstack-toolkit' ) );
		$checked_at   = $release && ! empty( $release['checked_at'] )
			? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $release['checked_at'] )
			: __( 'Chưa có', 'tnstack-toolkit' );
		$theme        = wp_get_theme();
		$environment  = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		$https        = is_ssl() || 0 === strpos( home_url( '/' ), 'https://' );
		$debug        = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$file_edit    = ! defined( 'DISALLOW_FILE_EDIT' ) || ! DISALLOW_FILE_EDIT;
		$server_name  = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : __( 'Không xác định', 'tnstack-toolkit' );
		$curl         = function_exists( 'curl_version' ) ? curl_version() : array();
		$curl         = is_array( $curl ) ? $curl : array();

		$plugin_info = array(
			__( 'Phiên bản đang cài', 'tnstack-toolkit' ) => TNSTACK_TOOLKIT_VERSION,
			__( 'Bản phát hành mới nhất', 'tnstack-toolkit' ) => $latest,
			__( 'Trạng thái cập nhật', 'tnstack-toolkit' ) => $update_state,
			__( 'Lần kiểm tra gần nhất', 'tnstack-toolkit' ) => $checked_at,
			__( 'Kho mã nguồn', 'tnstack-toolkit' )       => self::repository(),
			__( 'Kênh cập nhật', 'tnstack-toolkit' )     => 'GitHub Releases',
		);
		$website_info = array(
			__( 'Địa chỉ website', 'tnstack-toolkit' )   => home_url( '/' ),
			__( 'WordPress', 'tnstack-toolkit' )         => get_bloginfo( 'version' ),
			__( 'Môi trường', 'tnstack-toolkit' )        => $environment,
			__( 'Ngôn ngữ', 'tnstack-toolkit' )          => get_locale(),
			__( 'Múi giờ', 'tnstack-toolkit' )           => wp_timezone_string(),
			__( 'Giao diện đang dùng', 'tnstack-toolkit' ) => trim( $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) ),
			__( 'Multisite', 'tnstack-toolkit' )         => is_multisite() ? __( 'Có', 'tnstack-toolkit' ) : __( 'Không', 'tnstack-toolkit' ),
		);
		$server_info = array(
			__( 'PHP', 'tnstack-toolkit' )               => PHP_VERSION,
			__( 'Cơ sở dữ liệu', 'tnstack-toolkit' )     => $wpdb->db_version(),
			__( 'Web server', 'tnstack-toolkit' )        => $server_name,
			__( 'Bộ nhớ WordPress', 'tnstack-toolkit' )  => defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : __( 'Mặc định', 'tnstack-toolkit' ),
			__( 'Giới hạn tải lên', 'tnstack-toolkit' )  => size_format( wp_max_upload_size() ),
			__( 'Thời gian thực thi tối đa', 'tnstack-toolkit' ) => (string) ini_get( 'max_execution_time' ) . 's',
			__( 'cURL', 'tnstack-toolkit' )              => isset( $curl['version'] ) ? $curl['version'] : __( 'Không khả dụng', 'tnstack-toolkit' ),
		);
		?>
		<div class="wrap ttk-settings ttk-settings--updater">
			<header class="ttk-settings__hero">
				<span class="ttk-settings__hero-icon"><span class="dashicons dashicons-admin-tools"></span></span>
				<div>
					<span class="ttk-settings__eyebrow"><?php esc_html_e( 'TNStack Toolkit', 'tnstack-toolkit' ); ?></span>
					<h1><?php esc_html_e( 'Thông tin plugin & hệ thống', 'tnstack-toolkit' ); ?></h1>
					<p><?php esc_html_e( 'Theo dõi cập nhật plugin, cấu hình website và khả năng tương thích máy chủ tại một nơi.', 'tnstack-toolkit' ); ?></p>
				</div>
			</header>

			<?php if ( 'checked' === $status ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Đã làm mới thông tin bản phát hành GitHub.', 'tnstack-toolkit' ); ?></p></div>
			<?php elseif ( 'check-error' === $status ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'Không thể kết nối GitHub. Kết quả lỗi được lưu tạm để tránh làm chậm trang quản trị.', 'tnstack-toolkit' ); ?></p></div>
			<?php endif; ?>

			<?php if ( is_array( $cached_error ) && ! empty( $cached_error['message'] ) ) : ?>
				<div class="notice notice-warning"><p><?php echo esc_html( $cached_error['message'] ); ?></p></div>
			<?php endif; ?>

			<div class="ttk-system-summary">
				<div class="ttk-system-summary__item">
					<span class="dashicons dashicons-admin-plugins"></span>
					<div><small><?php esc_html_e( 'Plugin', 'tnstack-toolkit' ); ?></small><strong><?php echo esc_html( TNSTACK_TOOLKIT_VERSION ); ?></strong></div>
				</div>
				<div class="ttk-system-summary__item">
					<span class="dashicons dashicons-update"></span>
					<div><small><?php esc_html_e( 'Cập nhật', 'tnstack-toolkit' ); ?></small><strong><?php echo esc_html( $update_state ); ?></strong></div>
				</div>
				<div class="ttk-system-summary__item">
					<span class="dashicons dashicons-shield"></span>
					<div><small><?php esc_html_e( 'HTTPS', 'tnstack-toolkit' ); ?></small><strong><?php echo esc_html( $https ? __( 'Đang bật', 'tnstack-toolkit' ) : __( 'Đang tắt', 'tnstack-toolkit' ) ); ?></strong></div>
				</div>
				<div class="ttk-system-summary__item">
					<span class="dashicons dashicons-performance"></span>
					<div><small><?php esc_html_e( 'PHP', 'tnstack-toolkit' ); ?></small><strong><?php echo esc_html( PHP_VERSION ); ?></strong></div>
				</div>
			</div>

			<div class="ttk-system-grid">
				<?php
				$sections = array(
					array( 'icon' => 'admin-plugins', 'title' => __( 'Thông tin plugin', 'tnstack-toolkit' ), 'items' => $plugin_info ),
					array( 'icon' => 'admin-site-alt3', 'title' => __( 'Thông tin website', 'tnstack-toolkit' ), 'items' => $website_info ),
					array( 'icon' => 'database', 'title' => __( 'Thông tin máy chủ', 'tnstack-toolkit' ), 'items' => $server_info ),
				);
				foreach ( $sections as $section ) :
					?>
					<section class="ttk-settings__card ttk-system-card">
						<div class="ttk-settings__card-header ttk-system-card__header">
							<span class="dashicons dashicons-<?php echo esc_attr( $section['icon'] ); ?>"></span>
							<h2><?php echo esc_html( $section['title'] ); ?></h2>
						</div>
						<dl class="ttk-system-list">
							<?php foreach ( $section['items'] as $label => $value ) : ?>
								<div>
									<dt><?php echo esc_html( $label ); ?></dt>
									<dd><?php echo esc_html( $value ); ?></dd>
								</div>
							<?php endforeach; ?>
						</dl>
					</section>
				<?php endforeach; ?>
			</div>

			<section class="ttk-settings__card ttk-security-card">
				<div class="ttk-settings__card-header">
					<h2><?php esc_html_e( 'Tổng quan bảo mật', 'tnstack-toolkit' ); ?></h2>
					<p><?php esc_html_e( 'Chỉ quản trị viên được xem trang này. Đường dẫn hệ thống và địa chỉ IP không được hiển thị.', 'tnstack-toolkit' ); ?></p>
				</div>
				<div class="ttk-security-checks">
					<div class="<?php echo esc_attr( $https ? 'is-good' : 'is-warning' ); ?>">
						<span class="dashicons dashicons-<?php echo esc_attr( $https ? 'yes-alt' : 'warning' ); ?>"></span>
						<strong>HTTPS</strong>
						<small><?php echo esc_html( $https ? __( 'Kết nối an toàn đang bật', 'tnstack-toolkit' ) : __( 'Nên bật HTTPS', 'tnstack-toolkit' ) ); ?></small>
					</div>
					<div class="<?php echo esc_attr( $debug ? 'is-warning' : 'is-good' ); ?>">
						<span class="dashicons dashicons-<?php echo esc_attr( $debug ? 'warning' : 'yes-alt' ); ?>"></span>
						<strong>WP_DEBUG</strong>
						<small><?php echo esc_html( $debug ? __( 'Đang bật; nên tắt trên website thật', 'tnstack-toolkit' ) : __( 'Đang tắt', 'tnstack-toolkit' ) ); ?></small>
					</div>
					<div class="<?php echo esc_attr( $file_edit ? 'is-warning' : 'is-good' ); ?>">
						<span class="dashicons dashicons-<?php echo esc_attr( $file_edit ? 'warning' : 'yes-alt' ); ?>"></span>
						<strong>DISALLOW_FILE_EDIT</strong>
						<small><?php echo esc_html( $file_edit ? __( 'Trình sửa file đang bật', 'tnstack-toolkit' ) : __( 'Trình sửa file đã tắt', 'tnstack-toolkit' ) ); ?></small>
					</div>
				</div>
			</section>

			<section class="ttk-settings__card">
				<div class="ttk-settings__card-header">
					<h2><?php esc_html_e( 'Cập nhật từ GitHub', 'tnstack-toolkit' ); ?></h2>
					<p><?php esc_html_e( 'Plugin nhận bản cập nhật công khai từ GitHub Releases của kho mã nguồn:', 'tnstack-toolkit' ); ?> <a href="<?php echo esc_url( self::repository_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( self::repository() ); ?></a></p>
				</div>
				<div class="ttk-settings__public-update">
					<span class="dashicons dashicons-yes-alt"></span>
					<div>
						<strong><?php esc_html_e( 'Kênh cập nhật công khai đã sẵn sàng', 'tnstack-toolkit' ); ?></strong>
						<p><?php esc_html_e( 'WordPress tải trực tiếp gói tnstack-toolkit.zip từ bản phát hành GitHub.', 'tnstack-toolkit' ); ?></p>
					</div>
				</div>
			</section>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="tnstack_github_updater_check">
				<?php wp_nonce_field( 'tnstack_github_updater_check' ); ?>
				<div class="ttk-settings__actions">
					<?php submit_button( __( 'Kiểm tra cập nhật ngay', 'tnstack-toolkit' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Fetch and normalize the latest published GitHub release.
	 *
	 * @param bool $force Ignore the cached release.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function get_release( $force = false ) {
		if ( ! $force ) {
			$cached = get_site_transient( self::RELEASE_CACHE );
			if ( is_array( $cached ) && ! empty( $cached['version'] ) ) {
				return $cached;
			}

			$cached_error = get_site_transient( self::ERROR_CACHE );
			if ( is_array( $cached_error ) && ! empty( $cached_error['message'] ) ) {
				return new WP_Error(
					isset( $cached_error['code'] ) ? sanitize_key( $cached_error['code'] ) : 'tnstack_github_cached_error',
					(string) $cached_error['message']
				);
			}
		} else {
			delete_site_transient( self::RELEASE_CACHE );
			delete_site_transient( self::ERROR_CACHE );
		}

		$url      = 'https://api.github.com/repos/' . self::repository() . '/releases/latest';
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 8,
				'redirection' => 2,
				'headers'     => self::github_headers(),
				'limit_response_size' => 1048576,
			)
		);

		if ( is_wp_error( $response ) ) {
			return self::cache_error( $response );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			$message = 404 === $code
				? __( 'Không tìm thấy GitHub Release. Hãy tạo ít nhất một bản phát hành công khai có gói tnstack-toolkit.zip.', 'tnstack-toolkit' )
				: sprintf(
					/* translators: %d: HTTP status code. */
					__( 'GitHub API trả về HTTP %d.', 'tnstack-toolkit' ),
					$code
				);

			return self::cache_error( new WP_Error( 'tnstack_github_api', $message ) );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			return self::cache_error( new WP_Error( 'tnstack_github_release', __( 'Dữ liệu GitHub Release không hợp lệ.', 'tnstack-toolkit' ) ) );
		}

		$version = ltrim( sanitize_text_field( (string) $data['tag_name'] ), "vV \t\n\r\0\x0B" );
		if ( ! preg_match( '/^\d+(?:\.\d+){1,3}(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ) {
			return self::cache_error( new WP_Error( 'tnstack_github_version', __( 'Tag GitHub Release không phải phiên bản hợp lệ.', 'tnstack-toolkit' ) ) );
		}

		$package = self::release_package_url( $data );
		if ( '' === $package || ! self::is_trusted_package_url( $package ) ) {
			return self::cache_error( new WP_Error( 'tnstack_github_package', __( 'GitHub Release không có gói ZIP để cập nhật.', 'tnstack-toolkit' ) ) );
		}

		$release = array(
			'version'      => $version,
			'tag'          => (string) $data['tag_name'],
			'html_url'     => esc_url_raw( $data['html_url'] ?? self::repository_url() ),
			'package'      => esc_url_raw( $package ),
			'body'         => isset( $data['body'] ) ? (string) $data['body'] : '',
			'published_at' => isset( $data['published_at'] ) ? (string) $data['published_at'] : '',
			'checked_at'   => time(),
		);

		delete_site_transient( self::ERROR_CACHE );
		set_site_transient( self::RELEASE_CACHE, $release, self::RELEASE_CACHE_TTL );
		return $release;
	}

	/**
	 * Select the release asset, falling back to GitHub's source ZIP.
	 *
	 * @param array<string, mixed> $release GitHub release response.
	 * @return string
	 */
	private static function release_package_url( $release ) {
		$assets = isset( $release['assets'] ) && is_array( $release['assets'] ) ? $release['assets'] : array();

		foreach ( $assets as $asset ) {
			if ( ! is_array( $asset ) || self::ASSET_NAME !== ( $asset['name'] ?? '' ) ) {
				continue;
			}

			return isset( $asset['browser_download_url'] ) ? (string) $asset['browser_download_url'] : '';
		}

		return isset( $release['zipball_url'] ) ? (string) $release['zipball_url'] : '';
	}

	/**
	 * @return array<string, string>
	 */
	private static function github_headers() {
		return array(
			'Accept'               => 'application/vnd.github+json',
			'X-GitHub-Api-Version' => '2022-11-28',
			'User-Agent'           => 'TNStack-Toolkit/' . TNSTACK_TOOLKIT_VERSION . ' WordPress/' . get_bloginfo( 'version' ),
		);
	}

	/**
	 * @return string
	 */
	private static function repository() {
		$repository = defined( 'TNSTACK_GITHUB_REPOSITORY' ) && is_string( TNSTACK_GITHUB_REPOSITORY )
			? TNSTACK_GITHUB_REPOSITORY
			: self::REPOSITORY;

		$repository = trim( $repository, "/ \t\n\r\0\x0B" );
		return preg_match( '/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repository ) ? $repository : self::REPOSITORY;
	}

	/**
	 * @return string
	 */
	private static function repository_url() {
		return 'https://github.com/' . self::repository();
	}

	/**
	 * @return string
	 */
	private static function admin_url() {
		return admin_url( 'admin.php?page=tnstack-github-updates' );
	}

	/**
	 * @param array<string, mixed> $hook_extra Upgrade context.
	 * @return bool
	 */
	private static function is_our_upgrade( $hook_extra ) {
		return ! empty( $hook_extra['plugin'] )
			&& plugin_basename( TNSTACK_TOOLKIT_FILE ) === $hook_extra['plugin'];
	}

	/**
	 * Only accept public packages hosted by GitHub over HTTPS.
	 *
	 * @param string $url Package URL.
	 * @return bool
	 */
	private static function is_trusted_package_url( $url ) {
		if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			return false;
		}

		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		return 'github.com' === $host
			|| 'api.github.com' === $host
			|| 'objects.githubusercontent.com' === $host
			|| 'release-assets.githubusercontent.com' === $host
			|| ( strlen( $host ) > 22 && '.githubusercontent.com' === substr( $host, -22 ) );
	}

	/**
	 * Cache connection failures briefly to prevent repeated slow requests.
	 *
	 * @param WP_Error $error Error to cache.
	 * @return WP_Error
	 */
	private static function cache_error( $error ) {
		set_site_transient(
			self::ERROR_CACHE,
			array(
				'code'    => $error->get_error_code(),
				'message' => $error->get_error_message(),
			),
			self::ERROR_CACHE_TTL
		);

		return $error;
	}

	/**
	 * Clear updater caches.
	 */
	private static function clear_cache() {
		delete_site_transient( self::RELEASE_CACHE );
		delete_site_transient( self::ERROR_CACHE );
		delete_site_transient( 'update_plugins' );
	}
}
