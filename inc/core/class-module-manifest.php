<?php
/**
 * Single source of truth for all toolkit modules.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

final class TNStack_Module_Manifest {

	const CONFIG_MODULES  = 'modules';
	const CONFIG_PROJECT  = 'project';
	const CONFIG_FEATURES = 'features';

	/**
	 * @var array<string, array<string, mixed>>|null
	 */
	private static $definitions = null;

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions() {
		if ( null !== self::$definitions ) {
			return self::$definitions;
		}

		self::$definitions = array(
			'performance'            => self::entry( 'core', self::CONFIG_MODULES, true, 'subsystem', 'inc/core/performance/init.php', __( 'Tối ưu & Bảo mật', 'tnstack-toolkit' ), __( 'Cache, hardening, TN Optimization dashboard.', 'tnstack-toolkit' ), 'shield-alt', 'core' ),
			'code-snippets'          => self::entry( 'core', self::CONFIG_MODULES, false, 'simple', 'inc/modules/code-snippets.php', __( 'Code Snippets', 'tnstack-toolkit' ), __( 'CSS/JS và code head/footer từ admin.', 'tnstack-toolkit' ), 'editor-code', 'dev', 'tnstack_snippets_render_admin' ),
			'webp-converter'         => self::entry( 'core', self::CONFIG_MODULES, false, 'simple', 'inc/modules/webp-converter.php', __( 'WebP Converter', 'tnstack-toolkit' ), __( 'Tự động tạo WebP khi upload ảnh.', 'tnstack-toolkit' ), 'format-image', 'performance' ),
			'lazy-media'             => self::entry( 'core', self::CONFIG_MODULES, true, 'simple', 'inc/modules/lazy-media.php', __( 'Lazy Media', 'tnstack-toolkit' ), __( 'Lazy load iframe và video.', 'tnstack-toolkit' ), 'video-alt3', 'performance' ),
			'cf7-honeypot'           => self::entry( 'core', self::CONFIG_MODULES, true, 'simple', 'inc/modules/cf7-honeypot.php', __( 'CF7 Honeypot', 'tnstack-toolkit' ), __( 'Chống spam Contact Form 7.', 'tnstack-toolkit' ), 'shield', 'security' ),
			'maintenance-mode'       => self::entry( 'core', self::CONFIG_MODULES, false, 'simple', 'inc/modules/maintenance-mode.php', __( 'Maintenance Mode', 'tnstack-toolkit' ), __( 'Chế độ bảo trì website.', 'tnstack-toolkit' ), 'hammer', 'system', 'tnstack_maintenance_render_admin' ),
			'schema-markup'          => self::entry( 'seo', self::CONFIG_MODULES, true, 'simple', 'inc/modules/schema-markup.php', __( 'Schema Markup', 'tnstack-toolkit' ), __( 'JSON-LD Article, Product, FAQ, Organization.', 'tnstack-toolkit' ), 'search', 'seo' ),
			'redirect-manager'       => self::entry( 'seo', self::CONFIG_MODULES, false, 'simple', 'inc/modules/redirect-manager.php', __( 'Redirect Manager', 'tnstack-toolkit' ), __( '301 redirects và log 404.', 'tnstack-toolkit' ), 'randomize', 'seo', 'tnstack_redirect_render_admin' ),
			'analytics-injector'     => self::entry( 'seo', self::CONFIG_MODULES, false, 'simple', 'inc/modules/analytics-injector.php', __( 'Analytics / GTM', 'tnstack-toolkit' ), __( 'Nhúng Google Tag Manager hoặc GA4.', 'tnstack-toolkit' ), 'chart-bar', 'seo', 'tnstack_analytics_render_admin' ),
			'background-gradient'    => self::entry( 'ux-builder', self::CONFIG_MODULES, true, 'package', 'inc/modules/background-gradient/init.php', __( 'Background Gradient', 'tnstack-toolkit' ), __( 'Gradient nền cho UX Builder.', 'tnstack-toolkit' ), 'art', 'ux' ),
			'pricing-grid-table'     => self::entry( 'ux-builder', self::CONFIG_MODULES, true, 'package', 'inc/modules/pricing-grid-table/init.php', __( 'Pricing Grid Table', 'tnstack-toolkit' ), __( 'Bảng giá pricing_grid shortcode.', 'tnstack-toolkit' ), 'grid-view', 'ux' ),
			'slide-row'              => self::entry( 'ux-builder', self::CONFIG_MODULES, true, 'package', 'inc/modules/slide-row/init.php', __( 'Slide Row', 'tnstack-toolkit' ), __( 'Hàng slider/carousel UX Builder.', 'tnstack-toolkit' ), 'images-alt2', 'ux' ),
			'ux-image-border-radius' => self::entry( 'ux-builder', self::CONFIG_MODULES, true, 'simple', 'inc/modules/ux-image-border-radius.php', __( 'Image Border Radius', 'tnstack-toolkit' ), __( 'Bo góc element Image.', 'tnstack-toolkit' ), 'format-image', 'ux' ),
			'faq-accordion'          => self::entry( 'ux-builder', self::CONFIG_MODULES, true, 'package', 'inc/modules/faq-accordion/init.php', __( 'FAQ Accordion', 'tnstack-toolkit' ), __( 'FAQ element + schema markup.', 'tnstack-toolkit' ), 'editor-help', 'ux' ),
			'countdown-timer'        => self::entry( 'ux-builder', self::CONFIG_MODULES, true, 'package', 'inc/modules/countdown-timer/init.php', __( 'Countdown Timer', 'tnstack-toolkit' ), __( 'Đếm ngược cho landing/flash sale.', 'tnstack-toolkit' ), 'clock', 'ux' ),
			'slim-catalog'           => self::entry( 'content', self::CONFIG_MODULES, true, 'package', 'inc/modules/slim-catalog/init.php', __( 'Slim Catalog', 'tnstack-toolkit' ), __( 'Catalog sản phẩm không checkout.', 'tnstack-toolkit' ), 'store', 'catalog' ),
			'floating-contact'       => self::entry( 'content', self::CONFIG_MODULES, false, 'simple', 'inc/modules/floating-contact.php', __( 'Floating Contact', 'tnstack-toolkit' ), __( 'Nút Zalo, phone, WhatsApp, Messenger, Facebook, TikTok.', 'tnstack-toolkit' ), 'phone', 'content', 'tnstack_floating_contact_render_admin' ),
			'cookie-consent'         => self::entry( 'content', self::CONFIG_MODULES, false, 'simple', 'inc/modules/cookie-consent.php', __( 'Cookie Consent', 'tnstack-toolkit' ), __( 'Banner cookie GDPR đơn giản.', 'tnstack-toolkit' ), 'privacy', 'compliance', 'tnstack_cookie_render_admin' ),
			'table-of-contents'      => self::entry( 'content', self::CONFIG_MODULES, true, 'simple', 'inc/modules/table-of-contents.php', __( 'Table of Contents', 'tnstack-toolkit' ), __( 'Mục lục phân cấp, dark mode và thu gọn cho bài viết.', 'tnstack-toolkit' ), 'list-view', 'content', 'tnstack_toc_render_admin' ),
			'center-image'           => self::entry( 'content', self::CONFIG_MODULES, true, 'simple', 'inc/modules/center-image.php', __( 'Center Image', 'tnstack-toolkit' ), __( 'Căn giữa ảnh trong bài viết.', 'tnstack-toolkit' ), 'align-center', 'content' ),
			'disable-comments'       => self::entry( 'content', self::CONFIG_MODULES, true, 'simple', 'inc/modules/disable-comments.php', __( 'Disable Comments', 'tnstack-toolkit' ), __( 'Tắt comment toàn site.', 'tnstack-toolkit' ), 'hidden', 'content' ),
			'custom-login-url'       => self::entry( 'integrations', self::CONFIG_MODULES, true, 'simple', 'inc/modules/custom-login-url.php', __( 'Custom Login URL', 'tnstack-toolkit' ), __( 'Đổi đường dẫn đăng nhập và chặn truy cập trực tiếp wp-login.php.', 'tnstack-toolkit' ), 'admin-network', 'security', 'tnstack_custom_login_render_admin' ),
			'login-branding'         => self::entry( 'integrations', self::CONFIG_MODULES, false, 'simple', 'inc/modules/login-branding.php', __( 'Login Branding', 'tnstack-toolkit' ), __( 'Tùy chỉnh trang wp-login.', 'tnstack-toolkit' ), 'lock', 'branding', 'tnstack_login_render_admin' ),
			'smtp-email'             => self::entry( 'integrations', self::CONFIG_MODULES, false, 'simple', 'inc/modules/smtp-email.php', __( 'SMTP Email', 'tnstack-toolkit' ), __( 'Gửi mail qua SMTP.', 'tnstack-toolkit' ), 'email', 'system', 'tnstack_smtp_render_admin' ),
			'disable-update-plugin'  => self::entry( 'project', self::CONFIG_PROJECT, false, 'project', 'project/disable-update-plugin.php', __( 'Chặn update plugin', 'tnstack-toolkit' ), __( 'Ẩn update ACF Pro, Button Contact VR.', 'tnstack-toolkit' ), 'update', 'dev' ),
			'bypasss-acf'            => self::entry( 'project', self::CONFIG_PROJECT, false, 'project', 'project/bypasss-acf.php', __( 'Bypass ACF License', 'tnstack-toolkit' ), __( 'License ACF nội bộ — dev/staging.', 'tnstack-toolkit' ), 'admin-plugins', 'dev' ),
		);

		return self::$definitions;
	}

	/**
	 * @param string      $group         Admin UI group key.
	 * @param string      $config_key    modules|project|features.
	 * @param bool        $default       Default enabled state.
	 * @param string      $type          simple|package|subsystem|project.
	 * @param string      $boot          Relative boot file path.
	 * @param string      $title         Admin title.
	 * @param string      $description   Admin description.
	 * @param string      $icon          Dashicon slug.
	 * @param string      $tag           Category tag.
	 * @param string|null $settings_cb   Optional settings page callback.
	 * @return array<string, mixed>
	 */
	private static function entry( $group, $config_key, $default, $type, $boot, $title, $description, $icon, $tag, $settings_cb = null ) {
		$entry = array(
			'group'       => $group,
			'config_key'  => $config_key,
			'default'     => (bool) $default,
			'type'        => $type,
			'boot'        => $boot,
			'title'       => $title,
			'description' => $description,
			'icon'        => $icon,
			'tag'         => $tag,
		);

		if ( $settings_cb ) {
			$entry['settings_callback'] = $settings_cb;
		}

		return $entry;
	}

	/**
	 * @return array<string, bool>
	 */
	public static function module_defaults() {
		$defaults = array();

		foreach ( self::definitions() as $slug => $definition ) {
			if ( self::CONFIG_MODULES !== $definition['config_key'] ) {
				continue;
			}
			$defaults[ $slug ] = $definition['default'];
		}

		return $defaults;
	}

	/**
	 * @return array<string, bool>
	 */
	public static function project_defaults() {
		$defaults = array();

		foreach ( self::definitions() as $slug => $definition ) {
			if ( self::CONFIG_PROJECT !== $definition['config_key'] ) {
				continue;
			}
			$defaults[ $slug ] = $definition['default'];
		}

		return $defaults;
	}

	/**
	 * @param string $slug Module slug.
	 * @return array<string, mixed>|null
	 */
	public static function get( $slug ) {
		$definitions = self::definitions();

		return $definitions[ $slug ] ?? null;
	}

	/**
	 * @return array<string, array<int, array<string, string>>>
	 */
	public static function admin_groups() {
		$groups = array();

		foreach ( self::definitions() as $slug => $definition ) {
			$group_key = $definition['group'];
			$item      = array(
				'slug'        => $slug,
				'title'       => $definition['title'],
				'description' => $definition['description'],
				'icon'        => $definition['icon'],
				'tag'         => $definition['tag'],
			);

			if ( self::CONFIG_PROJECT === $definition['config_key'] ) {
				$item['type'] = 'project';
			}

			if ( ! isset( $groups[ $group_key ] ) ) {
				$groups[ $group_key ] = array();
			}

			$groups[ $group_key ][] = $item;
		}

		return $groups;
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	public static function settings_pages() {
		$pages = array();

		foreach ( self::definitions() as $slug => $definition ) {
			if ( empty( $definition['settings_callback'] ) ) {
				continue;
			}

			$pages[ $slug ] = array(
				'title'    => $definition['title'],
				'file'     => $definition['boot'],
				'callback' => $definition['settings_callback'],
			);
		}

		return $pages;
	}

	/**
	 * @param string $slug Module slug.
	 * @return bool
	 */
	public static function has_settings_page( $slug ) {
		$definition = self::get( $slug );

		return $definition && ! empty( $definition['settings_callback'] );
	}
}
