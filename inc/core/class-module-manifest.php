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
			'login-authentication'   => self::entry( 'core', self::CONFIG_MODULES, true, 'simple', 'inc/modules/login-authentication.php', __( 'Xác thực đăng nhập', 'tnstack-toolkit' ), __( 'CAPTCHA, honeypot và giới hạn đăng nhập sai tại trang login.', 'tnstack-toolkit' ), 'shield', 'security', 'tnstack_login_authentication_render_admin' ),
			'cf7-honeypot'           => self::entry( 'core', self::CONFIG_MODULES, true, 'simple', 'inc/modules/cf7-honeypot.php', __( 'CF7 Honeypot', 'tnstack-toolkit' ), __( 'Chống spam Contact Form 7.', 'tnstack-toolkit' ), 'shield', 'security' ),
			'maintenance-mode'       => self::entry( 'core', self::CONFIG_MODULES, false, 'simple', 'inc/modules/maintenance-mode.php', __( 'Maintenance Mode', 'tnstack-toolkit' ), __( 'Chế độ bảo trì website.', 'tnstack-toolkit' ), 'hammer', 'system', 'tnstack_maintenance_render_admin' ),
			'schema-markup'          => self::entry( 'seo', self::CONFIG_MODULES, true, 'simple', 'inc/modules/schema-markup.php', __( 'Schema Markup', 'tnstack-toolkit' ), __( 'JSON-LD Article, Product, FAQ, Organization.', 'tnstack-toolkit' ), 'search', 'seo' ),
			'pricing-grid-table'     => self::entry( 'ux-builder', self::CONFIG_MODULES, true, 'package', 'inc/modules/pricing-grid-table/init.php', __( 'Pricing Grid Table', 'tnstack-toolkit' ), __( 'Bảng giá pricing_grid shortcode.', 'tnstack-toolkit' ), 'grid-view', 'ux' ),
			'faq-accordion'          => self::entry( 'ux-builder', self::CONFIG_MODULES, true, 'package', 'inc/modules/faq-accordion/init.php', __( 'FAQ Accordion', 'tnstack-toolkit' ), __( 'FAQ element + schema markup.', 'tnstack-toolkit' ), 'editor-help', 'ux' ),
			'countdown-timer'        => self::entry( 'ux-builder', self::CONFIG_MODULES, true, 'package', 'inc/modules/countdown-timer/init.php', __( 'Countdown Timer', 'tnstack-toolkit' ), __( 'Đếm ngược cho landing/flash sale.', 'tnstack-toolkit' ), 'clock', 'ux' ),
			'slim-catalog'           => self::entry( 'content', self::CONFIG_MODULES, true, 'package', 'inc/modules/slim-catalog/init.php', __( 'Sản phẩm', 'tnstack-toolkit' ), __( 'Quản lý và hiển thị sản phẩm bằng shortcode.', 'tnstack-toolkit' ), 'store', 'catalog' ),
			'popup-form'             => self::entry( 'content', self::CONFIG_MODULES, true, 'simple', 'inc/modules/popup-form.php', __( 'Popup Form', 'tnstack-toolkit' ), __( 'Form liên hệ popup dùng tự động hoặc từ nút mua hàng.', 'tnstack-toolkit' ), 'feedback', 'content', 'tnstack_popup_form_render_admin' ),
			'floating-contact'       => self::entry( 'content', self::CONFIG_MODULES, false, 'simple', 'inc/modules/floating-contact.php', __( 'Floating Contact', 'tnstack-toolkit' ), __( 'Nút Zalo, phone, WhatsApp, Messenger, Facebook, TikTok.', 'tnstack-toolkit' ), 'phone', 'content', 'tnstack_floating_contact_render_admin' ),
			'cookie-consent'         => self::entry( 'content', self::CONFIG_MODULES, false, 'simple', 'inc/modules/cookie-consent.php', __( 'Cookie Consent', 'tnstack-toolkit' ), __( 'Banner cookie GDPR đơn giản.', 'tnstack-toolkit' ), 'privacy', 'compliance', 'tnstack_cookie_render_admin' ),
			'disable-comments'       => self::entry( 'content', self::CONFIG_MODULES, true, 'simple', 'inc/modules/disable-comments.php', __( 'Disable Comments', 'tnstack-toolkit' ), __( 'Tắt comment toàn site.', 'tnstack-toolkit' ), 'hidden', 'content' ),
			'custom-login-url'       => self::entry( 'integrations', self::CONFIG_MODULES, true, 'simple', 'inc/modules/custom-login-url.php', __( 'Custom Login URL', 'tnstack-toolkit' ), __( 'Đổi đường dẫn đăng nhập và chặn wp-login.php/wp-admin cho khách.', 'tnstack-toolkit' ), 'admin-network', 'security', 'tnstack_custom_login_render_admin' ),
			'disable-update-plugin'  => self::entry( 'project', self::CONFIG_PROJECT, false, 'project', 'project/disable-update-plugin.php', __( 'Chặn cập nhật plugin', 'tnstack-toolkit' ), __( 'Chọn từng plugin đã cài để ẩn và tắt cập nhật.', 'tnstack-toolkit' ), 'update', 'dev', 'tnstack_block_plugin_updates_render_admin' ),
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
