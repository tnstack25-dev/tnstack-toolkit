<?php
/**
 * Module and feature registry for the admin dashboard.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<string, array<string, string>>
 */
function tnstack_toolkit_get_profiles() {
	return array(
		'standard' => array(
			'label'       => __( 'Standard', 'tnstack-toolkit' ),
			'description' => __( 'Bật đầy đủ module cho dự án đa năng.', 'tnstack-toolkit' ),
			'icon'        => 'admin-site-alt3',
		),
		'blog' => array(
			'label'       => __( 'Blog', 'tnstack-toolkit' ),
			'description' => __( 'Tối giản — tập trung nội dung, không shop.', 'tnstack-toolkit' ),
			'icon'        => 'admin-post',
		),
		'landing' => array(
			'label'       => __( 'Landing', 'tnstack-toolkit' ),
			'description' => __( 'Trang đích marketing, không comment.', 'tnstack-toolkit' ),
			'icon'        => 'welcome-view-site',
		),
		'woocommerce' => array(
			'label'       => __( 'WooCommerce', 'tnstack-toolkit' ),
			'description' => __( 'Tối ưu cho shop Flatsome + WooCommerce.', 'tnstack-toolkit' ),
			'icon'        => 'cart',
		),
		'corporate' => array(
			'label'       => __( 'Corporate', 'tnstack-toolkit' ),
			'description' => __( 'Doanh nghiệp — bảng giá, ít slider.', 'tnstack-toolkit' ),
			'icon'        => 'building',
		),
	);
}

/**
 * @return array<string, array<string, string>>
 */
function tnstack_toolkit_get_group_meta() {
	return array(
		'core'         => array(
			'label'       => __( 'Core & Performance', 'tnstack-toolkit' ),
			'description' => __( 'Tối ưu tốc độ, bảo mật và tiện ích hệ thống.', 'tnstack-toolkit' ),
			'color'       => '#2563eb',
		),
		'seo'          => array(
			'label'       => __( 'SEO & Marketing', 'tnstack-toolkit' ),
			'description' => __( 'Schema, redirects và theo dõi analytics.', 'tnstack-toolkit' ),
			'color'       => '#0891b2',
		),
		'ux-builder'   => array(
			'label'       => __( 'UX Builder', 'tnstack-toolkit' ),
			'description' => __( 'Mở rộng Flatsome UX Builder với elements mới.', 'tnstack-toolkit' ),
			'color'       => '#7c3aed',
		),
		'content'      => array(
			'label'       => __( 'Nội dung & Catalog', 'tnstack-toolkit' ),
			'description' => __( 'Catalog, liên hệ, cookie và mục lục.', 'tnstack-toolkit' ),
			'color'       => '#059669',
		),
		'integrations' => array(
			'label'       => __( 'Tích hợp', 'tnstack-toolkit' ),
			'description' => __( 'Login, email SMTP và branding.', 'tnstack-toolkit' ),
			'color'       => '#db2777',
		),
		'project'      => array(
			'label'       => __( 'Dự án', 'tnstack-toolkit' ),
			'description' => __( 'Tiện ích riêng cho từng deployment.', 'tnstack-toolkit' ),
			'color'       => '#d97706',
		),
		'features'     => array(
			'label'       => __( 'Hệ thống', 'tnstack-toolkit' ),
			'description' => __( 'Tích hợp platform và hành vi toàn site.', 'tnstack-toolkit' ),
			'color'       => '#64748b',
		),
	);
}

/**
 * @return array<string, array<int, array<string, string>>>
 */
function tnstack_toolkit_get_module_groups() {
	return TNStack_Module_Manifest::admin_groups();
}

/**
 * @return array<string, array<string, string>>
 */
function tnstack_toolkit_get_feature_items() {
	return array(
		'woocommerce' => array(
			'title'       => __( 'WooCommerce', 'tnstack-toolkit' ),
			'description' => __( 'Tối ưu và dequeue asset WooCommerce.', 'tnstack-toolkit' ),
			'icon'        => 'cart',
			'tag'         => 'shop',
		),
		'comments' => array(
			'title'       => __( 'Comments', 'tnstack-toolkit' ),
			'description' => __( 'Bật comment — vô hiệu Disable Comments.', 'tnstack-toolkit' ),
			'icon'        => 'admin-comments',
			'tag'         => 'content',
		),
	);
}

/**
 * @return array<string, string>
 */
function tnstack_toolkit_get_group_labels() {
	$meta   = tnstack_toolkit_get_group_meta();
	$labels = array();
	foreach ( $meta as $key => $item ) {
		$labels[ $key ] = $item['label'];
	}
	return $labels;
}

/**
 * @return array<string, string>
 */
function tnstack_toolkit_get_category_labels() {
	return array(
		'seo'         => __( 'SEO', 'tnstack-toolkit' ),
		'performance' => __( 'Performance', 'tnstack-toolkit' ),
		'ux'          => __( 'UX Builder', 'tnstack-toolkit' ),
		'content'     => __( 'Nội dung', 'tnstack-toolkit' ),
		'dev'         => __( 'Developer', 'tnstack-toolkit' ),
		'branding'    => __( 'Branding', 'tnstack-toolkit' ),
		'compliance'  => __( 'Tuân thủ', 'tnstack-toolkit' ),
		'system'      => __( 'Hệ thống', 'tnstack-toolkit' ),
		'shop'        => __( 'Shop', 'tnstack-toolkit' ),
		'catalog'     => __( 'Catalog', 'tnstack-toolkit' ),
		'security'    => __( 'Bảo mật', 'tnstack-toolkit' ),
		'core'        => __( 'Core', 'tnstack-toolkit' ),
	);
}

/**
 * @param string $group_key Group key.
 * @return string Dashicon slug.
 */
function tnstack_toolkit_group_icon( $group_key ) {
	$icons = array(
		'core'         => 'shield-alt',
		'seo'          => 'search',
		'ux-builder'   => 'art',
		'content'      => 'admin-page',
		'integrations' => 'admin-plugins',
		'project'      => 'admin-tools',
		'features'     => 'admin-generic',
	);
	return $icons[ $group_key ] ?? 'admin-generic';
}

