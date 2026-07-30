<?php
/**
 * GitHub Releases updater for TNStack Toolkit.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_GitHub_Updater {

	const REPOSITORY       = 'tnstack25-dev/tnstack-toolkit';
	const SETTINGS_OPTION  = 'tnstack_github_updater_settings';
	const RELEASE_CACHE    = 'tnstack_github_latest_release';
	const RELEASE_CACHE_TTL = 6 * HOUR_IN_SECONDS;
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
		add_filter( 'upgrader_pre_download', array( __CLASS__, 'pre_download_private_package' ), 10, 4 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'normalize_package_directory' ), 10, 4 );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ), 30 );
			add_action( 'admin_post_tnstack_github_updater_save', array( __CLASS__, 'handle_save' ) );
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
	 * Download a private GitHub API package without forwarding the token to
	 * GitHub's redirected object-storage host.
	 *
	 * @param bool|WP_Error|string $reply      Existing pre-download result.
	 * @param string               $package    Package URL.
	 * @param WP_Upgrader          $upgrader   Upgrader instance.
	 * @param array                $hook_extra Upgrade context.
	 * @return bool|WP_Error|string
	 */
	public static function pre_download_private_package( $reply, $package, $upgrader, $hook_extra ) {
		unset( $upgrader );

		if ( false !== $reply || ! self::is_our_upgrade( $hook_extra ) || ! self::is_repository_api_url( $package ) ) {
			return $reply;
		}

		$token = self::token();
		if ( '' === $token ) {
			return $reply;
		}

		$response = wp_remote_get(
			$package,
			array(
				'timeout'     => 30,
				'redirection' => 0,
				'headers'     => self::github_headers( true ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( in_array( $code, array( 301, 302, 303, 307, 308 ), true ) ) {
			$location = wp_remote_retrieve_header( $response, 'location' );
			if ( ! $location || ! wp_http_validate_url( $location ) ) {
				return new WP_Error( 'tnstack_github_redirect', __( 'GitHub không trả về URL tải xuống hợp lệ.', 'tnstack-toolkit' ) );
			}

			if ( ! function_exists( 'download_url' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			return download_url( $location, 300 );
		}

		if ( 200 !== $code ) {
			return new WP_Error(
				'tnstack_github_download',
				sprintf(
					/* translators: %d: HTTP status code. */
					__( 'Không thể tải gói cập nhật từ GitHub (HTTP %d).', 'tnstack-toolkit' ),
					$code
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$temp = wp_tempnam( self::ASSET_NAME );
		if ( ! $temp || false === file_put_contents( $temp, $body ) ) {
			return new WP_Error( 'tnstack_github_temp_file', __( 'Không thể tạo tệp cập nhật tạm thời.', 'tnstack-toolkit' ) );
		}

		return $temp;
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
			__( 'GitHub Updates', 'tnstack-toolkit' ),
			__( 'GitHub Updates', 'tnstack-toolkit' ),
			'manage_options',
			'tnstack-github-updates',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Save the optional private repository token.
	 */
	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'tnstack-toolkit' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'tnstack_github_updater_save' );

		if ( ! defined( 'TNSTACK_GITHUB_TOKEN' ) ) {
			$settings = self::settings();

			if ( ! empty( $_POST['remove_token'] ) ) {
				$settings['token'] = '';
			} elseif ( isset( $_POST['github_token'] ) && '' !== trim( wp_unslash( $_POST['github_token'] ) ) ) {
				$settings['token'] = sanitize_text_field( trim( wp_unslash( $_POST['github_token'] ) ) );
			}

			update_site_option( self::SETTINGS_OPTION, $settings );
		}

		self::clear_cache();
		wp_safe_redirect( add_query_arg( 'github-updater', 'saved', self::admin_url() ) );
		exit;
	}

	/**
	 * Force WordPress and GitHub release caches to refresh.
	 */
	public static function handle_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'tnstack-toolkit' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'tnstack_github_updater_check' );
		self::clear_cache();
		self::get_release( true );
		delete_site_transient( 'update_plugins' );

		if ( function_exists( 'wp_update_plugins' ) ) {
			wp_update_plugins();
		}

		wp_safe_redirect( add_query_arg( 'github-updater', 'checked', self::admin_url() ) );
		exit;
	}

	/**
	 * Render GitHub updater settings and connection state.
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$release      = self::get_release();
		$token        = self::token();
		$token_source = defined( 'TNSTACK_GITHUB_TOKEN' ) ? 'wp-config.php' : ( '' !== $token ? __( 'Database', 'tnstack-toolkit' ) : __( 'Không có', 'tnstack-toolkit' ) );
		$status       = isset( $_GET['github-updater'] ) ? sanitize_key( wp_unslash( $_GET['github-updater'] ) ) : '';
		?>
		<div class="wrap ttk-settings ttk-settings--updater">
			<header class="ttk-settings__hero">
				<span class="ttk-settings__hero-icon"><span class="dashicons dashicons-update"></span></span>
				<div>
					<span class="ttk-settings__eyebrow"><?php esc_html_e( 'TNStack Toolkit', 'tnstack-toolkit' ); ?></span>
					<h1><?php esc_html_e( 'GitHub Updates', 'tnstack-toolkit' ); ?></h1>
					<p><?php esc_html_e( 'Nhận bản phát hành mới từ GitHub và cập nhật trực tiếp trong WordPress.', 'tnstack-toolkit' ); ?></p>
				</div>
			</header>

			<?php if ( 'saved' === $status ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Đã lưu cấu hình GitHub updater.', 'tnstack-toolkit' ); ?></p></div>
			<?php elseif ( 'checked' === $status ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Đã kiểm tra lại bản phát hành GitHub.', 'tnstack-toolkit' ); ?></p></div>
			<?php endif; ?>

			<?php if ( is_wp_error( $release ) ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $release->get_error_message() ); ?></p></div>
			<?php endif; ?>

			<section class="ttk-settings__card">
				<div class="ttk-settings__card-header">
					<h2><?php esc_html_e( 'Trạng thái cập nhật', 'tnstack-toolkit' ); ?></h2>
					<p><a href="<?php echo esc_url( self::repository_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( self::repository() ); ?></a></p>
				</div>
				<div class="ttk-settings__update-status">
					<div class="ttk-settings__update-stat">
						<span><?php esc_html_e( 'Bản đang cài', 'tnstack-toolkit' ); ?></span>
						<strong><?php echo esc_html( TNSTACK_TOOLKIT_VERSION ); ?></strong>
					</div>
					<div class="ttk-settings__update-stat">
						<span><?php esc_html_e( 'GitHub Release', 'tnstack-toolkit' ); ?></span>
						<strong><?php echo esc_html( is_wp_error( $release ) ? __( 'Chưa kết nối', 'tnstack-toolkit' ) : $release['version'] ); ?></strong>
					</div>
					<div class="ttk-settings__update-stat">
						<span><?php esc_html_e( 'Nguồn token', 'tnstack-toolkit' ); ?></span>
						<strong><?php echo esc_html( $token_source ); ?></strong>
					</div>
				</div>
			</section>

			<section class="ttk-settings__card">
				<div class="ttk-settings__card-header">
					<h2><?php esc_html_e( 'Private repository token', 'tnstack-toolkit' ); ?></h2>
					<p><?php esc_html_e( 'Repo public không cần token. Repo private cần fine-grained token có quyền Contents: Read-only.', 'tnstack-toolkit' ); ?></p>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="tnstack_github_updater_save">
					<?php wp_nonce_field( 'tnstack_github_updater_save' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="tnstack_github_token"><?php esc_html_e( 'GitHub token', 'tnstack-toolkit' ); ?></label></th>
							<td>
								<?php if ( defined( 'TNSTACK_GITHUB_TOKEN' ) ) : ?>
									<p><strong><?php esc_html_e( 'Token đang được quản lý trong wp-config.php.', 'tnstack-toolkit' ); ?></strong></p>
								<?php else : ?>
									<div class="ttk-settings__token-row">
										<input type="password" id="tnstack_github_token" name="github_token" class="regular-text" value="" placeholder="<?php echo '' !== $token ? esc_attr__( 'Token đã được lưu — để trống để giữ nguyên', 'tnstack-toolkit' ) : 'github_pat_…'; ?>" autocomplete="new-password">
										<?php if ( '' !== $token ) : ?>
											<label><input type="checkbox" name="remove_token" value="1"> <?php esc_html_e( 'Xóa token', 'tnstack-toolkit' ); ?></label>
										<?php endif; ?>
									</div>
								<?php endif; ?>
								<p class="description"><?php esc_html_e( 'Khuyến nghị an toàn: khai báo token bằng hằng số sau trong wp-config.php.', 'tnstack-toolkit' ); ?></p>
								<code class="ttk-settings__code">define( 'TNSTACK_GITHUB_TOKEN', 'github_pat_xxx' );</code>
							</td>
						</tr>
					</table>
					<?php if ( ! defined( 'TNSTACK_GITHUB_TOKEN' ) ) : ?>
						<div class="ttk-settings__actions ttk-settings__card-actions">
							<?php submit_button( __( 'Lưu token', 'tnstack-toolkit' ), 'primary', 'submit', false ); ?>
						</div>
					<?php endif; ?>
				</form>
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
		}

		$url      = 'https://api.github.com/repos/' . self::repository() . '/releases/latest';
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => self::github_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			$message = 404 === $code
				? __( 'Không tìm thấy GitHub Release. Nếu repo private, hãy cấu hình token; đồng thời cần tạo ít nhất một Release.', 'tnstack-toolkit' )
				: sprintf(
					/* translators: %d: HTTP status code. */
					__( 'GitHub API trả về HTTP %d.', 'tnstack-toolkit' ),
					$code
				);

			return new WP_Error( 'tnstack_github_api', $message );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			return new WP_Error( 'tnstack_github_release', __( 'Dữ liệu GitHub Release không hợp lệ.', 'tnstack-toolkit' ) );
		}

		$version = ltrim( sanitize_text_field( (string) $data['tag_name'] ), "vV \t\n\r\0\x0B" );
		if ( ! preg_match( '/^\d+(?:\.\d+){1,3}(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ) {
			return new WP_Error( 'tnstack_github_version', __( 'Tag GitHub Release không phải phiên bản hợp lệ.', 'tnstack-toolkit' ) );
		}

		$package = self::release_package_url( $data );
		if ( '' === $package ) {
			return new WP_Error( 'tnstack_github_package', __( 'GitHub Release không có gói ZIP để cập nhật.', 'tnstack-toolkit' ) );
		}

		$release = array(
			'version'      => $version,
			'tag'          => (string) $data['tag_name'],
			'html_url'     => esc_url_raw( $data['html_url'] ?? self::repository_url() ),
			'package'      => esc_url_raw( $package ),
			'body'         => isset( $data['body'] ) ? (string) $data['body'] : '',
			'published_at' => isset( $data['published_at'] ) ? (string) $data['published_at'] : '',
		);

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
		$token  = self::token();
		$assets = isset( $release['assets'] ) && is_array( $release['assets'] ) ? $release['assets'] : array();

		foreach ( $assets as $asset ) {
			if ( ! is_array( $asset ) || self::ASSET_NAME !== ( $asset['name'] ?? '' ) ) {
				continue;
			}

			if ( '' !== $token && ! empty( $asset['url'] ) ) {
				return (string) $asset['url'];
			}

			return isset( $asset['browser_download_url'] ) ? (string) $asset['browser_download_url'] : '';
		}

		return isset( $release['zipball_url'] ) ? (string) $release['zipball_url'] : '';
	}

	/**
	 * @param bool $download Request binary content.
	 * @return array<string, string>
	 */
	private static function github_headers( $download = false ) {
		$headers = array(
			'Accept'               => $download ? 'application/octet-stream' : 'application/vnd.github+json',
			'X-GitHub-Api-Version' => '2026-03-10',
			'User-Agent'           => 'TNStack-Toolkit/' . TNSTACK_TOOLKIT_VERSION . '; ' . home_url( '/' ),
		);

		$token = self::token();
		if ( '' !== $token ) {
			$headers['Authorization'] = 'Bearer ' . $token;
		}

		return $headers;
	}

	/**
	 * @return array<string, string>
	 */
	private static function settings() {
		$settings = get_site_option( self::SETTINGS_OPTION, array() );
		return is_array( $settings ) ? wp_parse_args( $settings, array( 'token' => '' ) ) : array( 'token' => '' );
	}

	/**
	 * @return string
	 */
	private static function token() {
		if ( defined( 'TNSTACK_GITHUB_TOKEN' ) && is_string( TNSTACK_GITHUB_TOKEN ) ) {
			return trim( TNSTACK_GITHUB_TOKEN );
		}

		$settings = self::settings();
		return isset( $settings['token'] ) ? trim( (string) $settings['token'] ) : '';
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
	 * @param string $url URL to inspect.
	 * @return bool
	 */
	private static function is_repository_api_url( $url ) {
		$expected = '/repos/' . self::repository() . '/';
		return 'api.github.com' === wp_parse_url( $url, PHP_URL_HOST )
			&& 0 === strpos( (string) wp_parse_url( $url, PHP_URL_PATH ), $expected );
	}

	/**
	 * Clear updater caches.
	 */
	private static function clear_cache() {
		delete_site_transient( self::RELEASE_CACHE );
		delete_site_transient( 'update_plugins' );
	}
}
