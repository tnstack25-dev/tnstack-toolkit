<?php
/**
 * Toolkit features management dashboard.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_Toolkit_Features_Dashboard {

	const PAGE_SLUG = 'tnstack-toolkit-features';

	/**
	 * Boot admin hooks.
	 */
	public static function boot() {
		add_action( 'admin_init', array( __CLASS__, 'handle_settings_save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		$css_path = tnstack_core_path( 'assets/css/toolkit-admin.css' );
		$js_path  = tnstack_core_path( 'assets/js/toolkit-admin.js' );

		wp_enqueue_style(
			'tnstack-toolkit-admin',
			tnstack_core_uri( 'assets/css/toolkit-admin.css' ),
			array(),
			tnstack_core_asset_version( $css_path )
		);

		wp_enqueue_script(
			'tnstack-toolkit-admin',
			tnstack_core_uri( 'assets/js/toolkit-admin.js' ),
			array(),
			tnstack_core_asset_version( $js_path ),
			true
		);
	}

	/**
	 * Save feature toggles from the dashboard form.
	 */
	public static function handle_settings_save() {
		if ( empty( $_POST['tnstack_toolkit_features_save'] ) ) {
			return;
		}

		if ( ! current_user_can( TNStack_Account_Permissions::MANAGE_CAP ) ) {
			wp_die( esc_html__( 'Bạn không có quyền sửa cài đặt Toolkit.', 'tnstack-toolkit' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'tnstack_toolkit_features_settings', 'tnstack_toolkit_features_nonce' );

		$input = isset( $_POST['toolkit'] ) && is_array( $_POST['toolkit'] ) ? wp_unslash( $_POST['toolkit'] ) : array();

		tnstack_toolkit_update_admin_settings( $input );

		wp_safe_redirect(
			add_query_arg(
				'ttk-saved',
				'1',
				admin_url( 'admin.php?page=' . self::PAGE_SLUG )
			)
		);
		exit;
	}

	/**
	 * @param array<string, mixed> $settings Current settings.
	 * @return array{enabled: int, total: int}
	 */
	private static function count_modules( $settings ) {
		$enabled = 0;
		$total   = 0;

		foreach ( $settings['modules'] as $value ) {
			++$total;
			if ( $value ) {
				++$enabled;
			}
		}
		foreach ( $settings['project'] as $value ) {
			++$total;
			if ( $value ) {
				++$enabled;
			}
		}

		return array(
			'enabled' => $enabled,
			'total'   => $total,
		);
	}

	/**
	 * @param array<string, mixed> $item     Module item.
	 * @param bool                 $checked  Is enabled.
	 * @param string               $field    Input name.
	 */
	private static function render_module_card( $item, $checked, $field ) {
		$slug     = $item['slug'] ?? '';
		$tag      = $item['tag'] ?? '';
		$cat_map  = tnstack_toolkit_get_category_labels();
		$tag_text = isset( $cat_map[ $tag ] ) ? $cat_map[ $tag ] : $tag;
		$search   = strtolower( $item['title'] . ' ' . $item['description'] . ' ' . $tag_text );
		$has_settings = $slug && function_exists( 'tnstack_toolkit_module_has_settings' ) && tnstack_toolkit_module_has_settings( $slug );
		?>
		<label class="ttk-module <?php echo $checked ? 'is-active' : ''; ?>" data-search="<?php echo esc_attr( $search ); ?>">
			<input type="checkbox" name="<?php echo esc_attr( $field ); ?>" value="1" <?php checked( $checked ); ?> class="ttk-module__input">
			<div class="ttk-module__top">
				<span class="ttk-module__icon"><span class="dashicons dashicons-<?php echo esc_attr( $item['icon'] ); ?>"></span></span>
				<span class="ttk-module__info">
					<strong class="ttk-module__title"><?php echo esc_html( $item['title'] ); ?></strong>
					<span class="ttk-module__desc"><?php echo esc_html( $item['description'] ); ?></span>
					<?php if ( $has_settings ) : ?>
						<span class="ttk-module__hint">
							<?php if ( $checked ) : ?>
								<a href="<?php echo esc_url( tnstack_toolkit_module_settings_url( $slug ) ); ?>" class="ttk-module__config" onclick="event.stopPropagation();">
									<span class="dashicons dashicons-admin-generic"></span>
									<?php esc_html_e( 'Cấu hình', 'tnstack-toolkit' ); ?>
								</a>
							<?php else : ?>
								<?php esc_html_e( 'Bật module và lưu để mở trang cấu hình.', 'tnstack-toolkit' ); ?>
							<?php endif; ?>
						</span>
					<?php endif; ?>
				</span>
			</div>
			<div class="ttk-module__footer">
				<?php if ( $tag_text ) : ?>
					<span class="ttk-tag"><?php echo esc_html( $tag_text ); ?></span>
				<?php else : ?>
					<span></span>
				<?php endif; ?>
				<span class="ttk-switch" aria-hidden="true"></span>
			</div>
		</label>
		<?php
	}

	/**
	 * Render the features dashboard.
	 */
	public static function render_page() {
		if ( ! current_user_can( TNStack_Account_Permissions::ACCESS_CAP ) ) {
			wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'tnstack-toolkit' ), '', array( 'response' => 403 ) );
		}

		$settings   = tnstack_toolkit_has_admin_settings() ? tnstack_toolkit_admin_settings() : tnstack_core_config();
		$profiles   = tnstack_toolkit_get_profiles();
		$groups     = tnstack_toolkit_get_module_groups();
		$group_meta = tnstack_toolkit_get_group_meta();
		$features = tnstack_toolkit_get_feature_items();
		$counts   = self::count_modules( $settings );
		?>
		<div class="wrap ttk-wrap">
			<header class="ttk-hero">
				<div class="ttk-hero__content">
					<span class="ttk-hero__badge">
						<span class="dashicons dashicons-admin-tools" style="font-size:14px;width:14px;height:14px;"></span>
						TNStack Toolkit v<?php echo esc_html( TNSTACK_TOOLKIT_VERSION ); ?>
					</span>
					<h1><?php esc_html_e( 'Quản lý tính năng', 'tnstack-toolkit' ); ?></h1>
					<p><?php esc_html_e( 'Bật/tắt module theo nhu cầu dự án. Cấu hình lưu trong database — không cần chỉnh file.', 'tnstack-toolkit' ); ?></p>
				</div>
				<div class="ttk-hero__stats">
					<div class="ttk-hero-stat ttk-hero-stat--accent">
						<span class="ttk-hero-stat__value" data-ttk-enabled><?php echo esc_html( (string) $counts['enabled'] ); ?></span>
						<span class="ttk-hero-stat__label"><?php esc_html_e( 'Đang bật', 'tnstack-toolkit' ); ?></span>
					</div>
					<div class="ttk-hero-stat">
						<span class="ttk-hero-stat__value" data-ttk-total><?php echo esc_html( (string) $counts['total'] ); ?></span>
						<span class="ttk-hero-stat__label"><?php esc_html_e( 'Tổng module', 'tnstack-toolkit' ); ?></span>
					</div>
					<div class="ttk-hero-stat">
						<span class="ttk-hero-stat__value"><?php echo esc_html( ucfirst( $settings['profile'] ) ); ?></span>
						<span class="ttk-hero-stat__label"><?php esc_html_e( 'Profile', 'tnstack-toolkit' ); ?></span>
					</div>
				</div>
			</header>

			<?php
			if ( isset( $_GET['ttk-saved'] ) ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Đã lưu cài đặt tính năng thành công. Các trang cấu hình module đã bật sẽ xuất hiện trong menu bên trái.', 'tnstack-toolkit' ) . '</p></div>';
			}
			?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>" class="ttk-form">
				<?php wp_nonce_field( 'tnstack_toolkit_features_settings', 'tnstack_toolkit_features_nonce' ); ?>
				<input type="hidden" name="tnstack_toolkit_features_save" value="1">

				<div class="ttk-layout">
					<aside class="ttk-sidebar">
						<div class="ttk-search">
							<input type="search" class="ttk-search__input" placeholder="<?php esc_attr_e( 'Tìm module...', 'tnstack-toolkit' ); ?>" autocomplete="off">
						</div>
						<nav class="ttk-nav" aria-label="<?php esc_attr_e( 'Nhóm tính năng', 'tnstack-toolkit' ); ?>">
							<button type="button" class="ttk-nav__item is-active" data-target="ttk-group-profile" data-group="profile">
								<span class="ttk-nav__dot" style="background:#4f46e5;"></span>
								<?php esc_html_e( 'Profile', 'tnstack-toolkit' ); ?>
							</button>
							<?php foreach ( $groups as $group_key => $items ) :
								$meta  = $group_meta[ $group_key ] ?? array();
								$color = $meta['color'] ?? '#64748b';
								$active_in_group = 0;
								foreach ( $items as $item ) {
									$type = $item['type'] ?? 'module';
									$slug = $item['slug'];
									if ( 'project' === $type ? ! empty( $settings['project'][ $slug ] ) : ! empty( $settings['modules'][ $slug ] ) ) {
										++$active_in_group;
									}
								}
								?>
								<button type="button" class="ttk-nav__item" data-target="ttk-group-<?php echo esc_attr( $group_key ); ?>" data-group="<?php echo esc_attr( $group_key ); ?>">
									<span class="ttk-nav__dot" style="background:<?php echo esc_attr( $color ); ?>;"></span>
									<?php echo esc_html( $meta['label'] ?? $group_key ); ?>
									<span class="ttk-nav__count"><?php echo esc_html( (string) $active_in_group ); ?></span>
								</button>
							<?php endforeach; ?>
							<button type="button" class="ttk-nav__item" data-target="ttk-group-features" data-group="features">
								<span class="ttk-nav__dot" style="background:#64748b;"></span>
								<?php esc_html_e( 'Hệ thống', 'tnstack-toolkit' ); ?>
								<span class="ttk-nav__count"><?php echo esc_html( (string) count( array_filter( $settings['features'] ) ) ); ?></span>
							</button>
						</nav>

						<?php
						$quick_links = array();

						if ( function_exists( 'tnstack_toolkit_module_settings_pages' ) ) {
							foreach ( tnstack_toolkit_module_settings_pages() as $slug => $page ) {
								if ( tnstack_core_module_enabled( $slug ) ) {
									$quick_links[] = array(
										'url'   => tnstack_toolkit_module_settings_url( $slug ),
										'label' => $page['title'],
										'icon'  => 'admin-generic',
									);
								}
							}
						}

						if ( tnstack_core_module_enabled( 'performance' ) && class_exists( 'TNStack_Core_Performance_Dashboard' ) ) {
							$quick_links[] = array(
								'url'   => admin_url( 'admin.php?page=' . TNStack_Core_Performance_Dashboard::PAGE_SLUG ),
								'label' => __( 'Tối ưu & Bảo mật', 'tnstack-toolkit' ),
								'icon'  => 'shield-alt',
							);
						}

						if ( tnstack_core_module_enabled( 'slim-catalog' ) ) {
							$quick_links[] = array(
								'url'   => admin_url( 'edit.php?post_type=slim_product' ),
								'label' => __( 'Slim Catalog', 'tnstack-toolkit' ),
								'icon'  => 'store',
							);
						}

						$quick_links[] = array(
							'url'   => admin_url( 'admin.php?page=tnstack-export-import' ),
							'label' => __( 'Export / Import', 'tnstack-toolkit' ),
							'icon'  => 'download',
						);

						if ( ! empty( $quick_links ) ) :
							?>
							<div class="ttk-sidebar-block">
								<p class="ttk-sidebar-block__title"><?php esc_html_e( 'Cấu hình nhanh', 'tnstack-toolkit' ); ?></p>
								<div class="ttk-quicklinks">
									<?php foreach ( $quick_links as $link ) : ?>
										<a href="<?php echo esc_url( $link['url'] ); ?>" class="ttk-quicklink">
											<span class="dashicons dashicons-<?php echo esc_attr( $link['icon'] ); ?>"></span>
											<?php echo esc_html( $link['label'] ); ?>
										</a>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
					</aside>

					<div class="ttk-main">
						<section class="ttk-section" id="ttk-group-profile">
							<div class="ttk-section__header">
								<span class="ttk-section__icon" style="background:#4f46e5;">
									<span class="dashicons dashicons-admin-settings"></span>
								</span>
								<div>
									<h2 class="ttk-section__title"><?php esc_html_e( 'Profile dự án', 'tnstack-toolkit' ); ?></h2>
									<p class="ttk-section__desc"><?php esc_html_e( 'Preset nhanh — tự động điều chỉnh module mặc định khi lưu.', 'tnstack-toolkit' ); ?></p>
								</div>
							</div>
							<div class="ttk-profiles">
								<?php foreach ( $profiles as $slug => $profile ) : ?>
									<label class="ttk-profile">
										<input type="radio" name="toolkit[profile]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $settings['profile'], $slug ); ?> class="ttk-profile__input">
										<span class="ttk-profile__card">
											<span class="ttk-profile__icon"><span class="dashicons dashicons-<?php echo esc_attr( $profile['icon'] ); ?>"></span></span>
											<span class="ttk-profile__label"><?php echo esc_html( $profile['label'] ); ?></span>
											<span class="ttk-profile__desc"><?php echo esc_html( $profile['description'] ); ?></span>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
						</section>

						<?php foreach ( $groups as $group_key => $items ) :
							$meta  = $group_meta[ $group_key ] ?? array();
							$color = $meta['color'] ?? '#64748b';
							?>
							<section class="ttk-section" id="ttk-group-<?php echo esc_attr( $group_key ); ?>">
								<div class="ttk-section__header">
									<span class="ttk-section__icon" style="background:<?php echo esc_attr( $color ); ?>;">
										<span class="dashicons dashicons-<?php echo esc_attr( tnstack_toolkit_group_icon( $group_key ) ); ?>"></span>
									</span>
									<div>
										<h2 class="ttk-section__title"><?php echo esc_html( $meta['label'] ?? $group_key ); ?></h2>
										<p class="ttk-section__desc"><?php echo esc_html( $meta['description'] ?? '' ); ?></p>
									</div>
								</div>
								<div class="ttk-grid">
									<?php foreach ( $items as $item ) :
										$type    = $item['type'] ?? 'module';
										$slug    = $item['slug'];
										$checked = 'project' === $type ? ! empty( $settings['project'][ $slug ] ) : ! empty( $settings['modules'][ $slug ] );
										$field   = 'project' === $type ? "toolkit[project][{$slug}]" : "toolkit[modules][{$slug}]";
										self::render_module_card( $item, $checked, $field );
									endforeach; ?>
								</div>
							</section>
						<?php endforeach; ?>

						<section class="ttk-section" id="ttk-group-features">
							<div class="ttk-section__header">
								<span class="ttk-section__icon" style="background:#64748b;">
									<span class="dashicons dashicons-admin-generic"></span>
								</span>
								<div>
									<h2 class="ttk-section__title"><?php esc_html_e( 'Tính năng hệ thống', 'tnstack-toolkit' ); ?></h2>
									<p class="ttk-section__desc"><?php esc_html_e( 'Tích hợp platform — ảnh hưởng hành vi toàn site.', 'tnstack-toolkit' ); ?></p>
								</div>
							</div>
							<div class="ttk-grid">
								<?php foreach ( $features as $slug => $item ) :
									self::render_module_card(
										$item,
										! empty( $settings['features'][ $slug ] ),
										"toolkit[features][{$slug}]"
									);
								endforeach; ?>
							</div>
						</section>

					</div>
				</div>

				<div class="ttk-savebar">
					<span class="ttk-savebar__info" data-ttk-save-info>
						<?php
						printf(
							/* translators: 1: enabled count, 2: total count */
							esc_html__( '%1$d / %2$d module đang bật', 'tnstack-toolkit' ),
							(int) $counts['enabled'],
							(int) $counts['total']
						);
						?>
					</span>
					<div class="ttk-savebar__actions">
						<button type="submit" class="button button-primary ttk-btn-save">
							<!-- <span class="dashicons dashicons-saved"></span> -->
							<?php esc_html_e( 'Lưu cài đặt', 'tnstack-toolkit' ); ?>
						</button>
					</div>
				</div>
			</form>
		</div>
		<?php
	}
}
