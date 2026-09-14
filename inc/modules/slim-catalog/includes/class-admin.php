<?php
/**
 * Admin settings and assets.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

class Slim_Catalog_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_filter( 'option_page_capability_slim_catalog_settings_group', array( __CLASS__, 'settings_capability' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'manage_' . Slim_Catalog_Post_Types::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . Slim_Catalog_Post_Types::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_action( 'wp_ajax_tnstack_shortcode_preview', array( __CLASS__, 'ajax_shortcode_preview' ) );
	}

	public static function register_menu() {
		add_submenu_page(
			'edit.php?post_type=' . Slim_Catalog_Post_Types::POST_TYPE,
			__( 'Settings', 'slim-catalog' ),
			__( 'Settings', 'slim-catalog' ),
			TNStack_Account_Permissions::MANAGE_CAP,
			'slim-catalog-settings',
			array( __CLASS__, 'render_settings_page' )
		);
		add_submenu_page(
			'edit.php?post_type=' . Slim_Catalog_Post_Types::POST_TYPE,
			'Tạo shortcode',
			'Tạo shortcode',
			TNStack_Account_Permissions::ACCESS_CAP,
			'slim-catalog-shortcode-generator',
			array( __CLASS__, 'render_shortcode_generator' )
		);
		add_submenu_page(
			'edit.php?post_type=' . Slim_Catalog_Post_Types::POST_TYPE,
			'Hướng dẫn shortcode',
			'Hướng dẫn shortcode',
			TNStack_Account_Permissions::ACCESS_CAP,
			'slim-catalog-shortcodes',
			array( __CLASS__, 'render_shortcode_guide' )
		);
	}

	public static function render_shortcode_generator() {
		$categories = get_terms( array( 'taxonomy' => Slim_Catalog_Post_Types::TAXONOMY, 'hide_empty' => false ) );
		$products = get_posts( array( 'post_type' => Slim_Catalog_Post_Types::POST_TYPE, 'post_status' => 'publish', 'posts_per_page' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
		?>
		<div class="wrap sc-generator"><div class="sc-generator__hero"><div><h1>Tạo shortcode sản phẩm</h1><p>Chọn kiểu hiển thị, xem trước và sao chép shortcode mà không cần UX Builder.</p></div><span class="dashicons dashicons-shortcode"></span></div>
		<div class="sc-generator__layout"><section class="sc-generator__panel"><div class="sc-generator__grid">
		<label>Loại hiển thị<select data-scg="type"><option value="ux_slim_products">Slider / hàng sản phẩm</option><option value="slim_products">Lưới sản phẩm</option><option value="slim_products_all">Tất cả sản phẩm</option><option value="slim_product">Một card sản phẩm</option><option value="slim_product_detail">Chi tiết sản phẩm</option><option value="slim_product_categories">Danh mục sản phẩm</option></select></label>
		<label data-general>Số sản phẩm<input type="number" min="1" max="100" value="8" data-scg="products"></label><label data-general>Số cột<select data-scg="columns"><option>2</option><option>3</option><option selected>4</option><option>5</option><option>6</option></select></label>
		<label data-general>Kiểu bố cục<select data-scg="layout"><option value="slider">Slider</option><option value="row">Hàng</option></select></label><label data-general>Mỗi lần trượt<select data-scg="slide_by"><option value="1">1 sản phẩm</option><option value="page">Cả nhóm</option></select></label>
		<label data-general>Danh mục<select data-scg="category"><option value="">Tất cả danh mục</option><?php if ( ! is_wp_error( $categories ) ) foreach ( $categories as $term ) : ?><option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option><?php endforeach; ?></select></label>
		<label data-single style="display:none">Sản phẩm<select data-scg="product"><option value="">Chọn sản phẩm</option><?php foreach ( $products as $product ) : ?><option value="<?php echo esc_attr( $product->ID ); ?>"><?php echo esc_html( $product->post_title ); ?></option><?php endforeach; ?></select></label>
		<label data-general>Sắp xếp<select data-scg="orderby"><option value="date">Mới nhất</option><option value="title">Tên</option><option value="menu_order">Thứ tự</option><option value="rand">Ngẫu nhiên</option></select></label><label data-general>Chiều sắp xếp<select data-scg="order"><option value="DESC">Giảm dần</option><option value="ASC">Tăng dần</option></select></label>
		<label data-general class="sc-generator__check"><input type="checkbox" data-scg="featured"> Chỉ sản phẩm nổi bật</label>
		</div><div class="sc-generator__code"><code data-scg-output></code><button type="button" class="button button-primary" data-scg-copy>Sao chép</button></div><button type="button" class="button button-secondary" data-scg-preview>Xem trước shortcode</button></section>
		<section class="sc-generator__preview"><div class="sc-generator__preview-head"><strong>Xem trước</strong><span data-scg-state>Nhấn “Xem trước shortcode”</span></div><div data-scg-preview-output class="sc-generator__preview-body"></div></section></div></div>
		<style>.sc-generator{max-width:1280px}.sc-generator__hero{display:flex;align-items:center;justify-content:space-between;margin:20px 0;padding:25px 28px;border-radius:16px;background:linear-gradient(135deg,#312e81,#7c3aed);color:#fff}.sc-generator__hero h1{margin:0 0 5px;color:#fff}.sc-generator__hero p{margin:0;color:#ede9fe}.sc-generator__hero .dashicons{width:54px;height:54px;font-size:54px}.sc-generator__layout{display:grid;grid-template-columns:420px 1fr;gap:20px}.sc-generator__panel,.sc-generator__preview{border:1px solid #dbe3ef;border-radius:15px;background:#fff;box-shadow:0 5px 18px rgba(15,23,42,.05)}.sc-generator__panel{padding:22px}.sc-generator__grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}.sc-generator__grid label:first-child{grid-column:1/-1}.sc-generator__grid label{font-weight:600}.sc-generator__grid input:not([type=checkbox]),.sc-generator__grid select{display:block;width:100%;margin-top:6px}.sc-generator__check{grid-column:1/-1}.sc-generator__code{display:flex;align-items:center;gap:10px;margin:20px 0 12px;padding:12px;border-radius:10px;background:#f1f5f9}.sc-generator__code code{flex:1;overflow-wrap:anywhere}.sc-generator__preview-head{display:flex;justify-content:space-between;padding:15px 18px;border-bottom:1px solid #e2e8f0}.sc-generator__preview-head span{color:#64748b}.sc-generator__preview-body{min-height:320px;padding:20px;overflow:auto}@media(max-width:960px){.sc-generator__layout{grid-template-columns:1fr}}@media(max-width:600px){.sc-generator__grid{grid-template-columns:1fr}.sc-generator__grid label:first-child,.sc-generator__check{grid-column:auto}}</style>
		<script>document.addEventListener('DOMContentLoaded',function(){var root=document.querySelector('.sc-generator');if(!root)return;var get=function(k){return root.querySelector('[data-scg="'+k+'"]');},output=root.querySelector('[data-scg-output]'),single=root.querySelector('[data-single]'),general=root.querySelectorAll('[data-general]');function build(){var type=get('type').value,isSingle=type==='slim_product'||type==='slim_product_detail',a=[];single.style.display=isSingle?'block':'none';general.forEach(function(el){el.style.display=isSingle?'none':'';});if(isSingle){if(get('product').value)a.push('id="'+get('product').value+'"');}else if(type==='slim_product_categories'){a.push('show_all="true"','hide_empty="false"');}else{var countKey=type==='ux_slim_products'?'products':(type==='slim_products_all'?'per_page':'limit');a.push(countKey+'="'+get('products').value+'"','columns="'+get('columns').value+'"');if(type==='ux_slim_products')a.push('type="'+get('layout').value+'"','slide_by="'+get('slide_by').value+'"');if(get('category').value)a.push('category="'+get('category').value+'"');a.push('orderby="'+get('orderby').value+'"','order="'+get('order').value+'"');if(get('featured').checked)a.push('featured="true"');}output.textContent='['+type+(a.length?' '+a.join(' '):'')+']';}root.querySelectorAll('[data-scg]').forEach(function(el){el.addEventListener('change',build);el.addEventListener('input',build);});root.querySelector('[data-scg-copy]').addEventListener('click',function(){navigator.clipboard.writeText(output.textContent).then(function(){root.querySelector('[data-scg-copy]').textContent='Đã sao chép';setTimeout(function(){root.querySelector('[data-scg-copy]').textContent='Sao chép';},1400);});});root.querySelector('[data-scg-preview]').addEventListener('click',function(){var state=root.querySelector('[data-scg-state]'),preview=root.querySelector('[data-scg-preview-output]');state.textContent='Đang tải...';var data=new URLSearchParams({action:'tnstack_shortcode_preview',nonce:'<?php echo esc_js( wp_create_nonce( 'tnstack_shortcode_preview' ) ); ?>',shortcode:output.textContent});fetch(ajaxurl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:data}).then(function(r){return r.json();}).then(function(r){if(!r.success)throw new Error();preview.innerHTML=r.data.html;state.textContent='Đã cập nhật';window.dispatchEvent(new Event('load'));}).catch(function(){state.textContent='Không thể xem trước';});});build();});</script>
		<?php
	}

	public static function ajax_shortcode_preview() {
		check_ajax_referer( 'tnstack_shortcode_preview', 'nonce' );
		if ( ! current_user_can( TNStack_Account_Permissions::ACCESS_CAP ) ) wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 );
		$shortcode = trim( wp_unslash( $_POST['shortcode'] ?? '' ) );
		$allowed = array( 'ux_slim_products', 'slim_products', 'slim_products_all', 'slim_product', 'slim_product_detail', 'slim_product_categories' );
		if ( ! preg_match( '/^\[([a-z0-9_]+)/', $shortcode, $match ) || ! in_array( $match[1], $allowed, true ) ) wp_send_json_error( array( 'message' => 'Shortcode không hợp lệ.' ), 400 );
		wp_send_json_success( array( 'html' => do_shortcode( $shortcode ) ) );
	}

	public static function render_shortcode_guide() {
		$items = array(
			'[slim_products limit="12" columns="3" category="dien-thoai" featured="false" orderby="date" order="DESC" ids="12,18" title="Sản phẩm" subtitle="Mới nhất"]' => 'Lưới sản phẩm. Lọc theo danh mục, nổi bật, ID; đổi số lượng, số cột, thứ tự và tiêu đề.',
			'[slim_products limit="8" columns="4" card_style="minimal" image_ratio="portrait" show_description="false" show_category="false" show_badge="true" show_price="true" show_button="true" title_lines="2" description_lines="3" radius="16"]' => 'Mẫu card: default, minimal, horizontal hoặc overlay. Tùy chỉnh tỷ lệ ảnh square, landscape, portrait, auto; ẩn/hiện từng thành phần, giới hạn số dòng và độ bo góc.',
			'[slim_product_filter columns="4" per_page="12" category="" show_price="true"]' => 'Bộ lọc AJAX theo tên/SKU, danh mục, khoảng giá và sắp xếp; có nút Xem thêm.',
			'[tn_popup_trigger id="123" label="Nhận tư vấn"]' => 'Nút mở một Popup Form theo ID.',
			'[slim_products_all columns="3" category="" featured="false" per_page="12" pagination="true" show_categories="true" title="Tất cả sản phẩm"]' => 'Trang danh mục đầy đủ, có phân trang và bộ lọc danh mục.',
			'[slim_product id="123" style="default" image_ratio="square" show_price="true" show_button="true"]' => 'Một card sản phẩm theo ID. Có thể dùng slug="ten-san-pham" thay cho ID và chọn style default, minimal, horizontal hoặc overlay.',
			'[slim_product_detail id="123"]' => 'Chi tiết đầy đủ của một sản phẩm theo ID hoặc slug.',
			'[slim_product_categories show_all="true" hide_empty="false"]' => 'Danh sách danh mục sản phẩm.',
			'[ux_slim_products products="8" columns="4" type="slider" slide_by="1" category="" orderby="date" order="DESC"]' => 'Hiển thị 4 sản phẩm nhưng mỗi lần kéo hoặc bấm mũi tên chỉ trượt 1 sản phẩm. Đặt slide_by="page" để trượt theo cả nhóm.',
			'[ux_slim_featured_products products="8" columns="4"]' => 'Sản phẩm nổi bật.',
			'[ux_slim_latest_products products="8" columns="4"]' => 'Sản phẩm mới nhất.',
			'[ux_slim_products_list products="8" category=""]' => 'Danh sách sản phẩm dạng gọn.',
			'[ux_slim_products_all per_page="12" columns="3" pagination="true"]' => 'Toàn bộ sản phẩm.',
			'[ux_slim_product_categories hide_empty="false"]' => 'Danh mục sản phẩm dạng lưới.',
			'[ux_slim_product_gallery product_id="123"]' => 'Thư viện ảnh sản phẩm.',
			'[ux_slim_product_title product_id="123"]' => 'Tên sản phẩm.',
			'[ux_slim_product_price product_id="123"]' => 'Giá sản phẩm.',
			'[ux_slim_product_excerpt product_id="123"]' => 'Mô tả ngắn.',
			'[ux_slim_product_variations product_id="123"]' => 'Các biến thể sản phẩm.',
			'[ux_slim_product_cta product_id="123" size="large"]' => 'Nút mua hàng theo kiểu đã chọn trong Cài đặt Sản phẩm.',
			'[ux_slim_product_description product_id="123"]' => 'Mô tả đầy đủ.',
			'[ux_slim_product_related product_id="123" products="4" columns="4"]' => 'Sản phẩm liên quan.',
			'[pricing_grid]...[/pricing_grid]' => 'Bảng giá. Dùng pricing_grid_row và pricing_grid_cell bên trong.',
			'[ttk_faq] [ttk_faq_item question="Câu hỏi?"]Câu trả lời[/ttk_faq_item] [/ttk_faq]' => 'FAQ accordion kèm schema.',
			'[ttk_countdown date="2026-12-31 23:59:59"]' => 'Bộ đếm ngược.',
		);
		?>
		<div class="wrap"><h1>Hướng dẫn sử dụng shortcode</h1><p>Sao chép shortcode vào nội dung, Text element hoặc trình soạn thảo. Các thuộc tính để trống sẽ dùng giá trị mặc định.</p>
		<table class="widefat striped"><thead><tr><th style="width:58%">Shortcode và tham số</th><th>Công dụng</th></tr></thead><tbody>
		<?php foreach ( $items as $code => $description ) : ?><tr><td><code style="white-space:normal"><?php echo esc_html( $code ); ?></code></td><td><?php echo esc_html( $description ); ?></td></tr><?php endforeach; ?>
		</tbody></table><p><strong>Nút mua hàng:</strong> chọn “Hiện số hotline” hoặc “Mở form liên hệ” tại Sản phẩm → Cài đặt.</p></div>
		<?php
	}

	public static function settings_capability() {
		return TNStack_Account_Permissions::MANAGE_CAP;
	}

	public static function register_settings() {
		register_setting(
			'slim_catalog_settings_group',
			'slim_catalog_settings',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);

		add_action( 'update_option_slim_catalog_settings', array( __CLASS__, 'flush_rewrites_on_settings_save' ), 10, 0 );
	}

	public static function flush_rewrites_on_settings_save() {
		delete_transient( 'slim_catalog_settings_cache' );
		Slim_Catalog_Post_Types::register();
		flush_rewrite_rules();
	}

	/**
	 * @param array<string, string> $input Settings input.
	 * @return array<string, string>
	 */
	public static function sanitize_settings( $input ) {
		return array(
			'currency_symbol'   => sanitize_text_field( $input['currency_symbol'] ?? '$' ),
			'currency_position' => in_array( $input['currency_position'] ?? 'before', array( 'before', 'after' ), true ) ? $input['currency_position'] : 'before',
			'color_mode'        => in_array( $input['color_mode'] ?? 'light', array( 'light', 'dark', 'auto' ), true ) ? $input['color_mode'] : 'light',
			'archive_slug'      => sanitize_title( $input['archive_slug'] ?? 'san-pham' ),
			'single_slug'       => sanitize_title( $input['single_slug'] ?? 'san-pham' ),
			'cta_label'         => sanitize_text_field( $input['cta_label'] ?? __( 'Contact Us Now', 'slim-catalog' ) ),
			'cta_mode'          => in_array( $input['cta_mode'] ?? 'hotline', array( 'hotline', 'form' ), true ) ? $input['cta_mode'] : 'hotline',
			'hotline'           => sanitize_text_field( $input['hotline'] ?? '' ),
			'zalo'              => sanitize_text_field( $input['zalo'] ?? '' ),
			'email'             => sanitize_email( $input['email'] ?? '' ),
			'address'           => sanitize_textarea_field( $input['address'] ?? '' ),
			'schema_enabled'    => empty($input['schema_enabled']) ? '0' : '1',
			'currency_code'     => strtoupper( substr( sanitize_key( $input['currency_code'] ?? 'VND' ), 0, 3 ) ),
			'default_brand'     => sanitize_text_field( $input['default_brand'] ?? '' ),
		);
	}

	public static function render_settings_page() {
		$settings = slim_catalog_get_settings();
		?>
		<div class="wrap">
			<header class="tns-page-hero"><span class="tns-page-hero__icon"><span class="dashicons dashicons-store"></span></span><div><h1>Cài đặt Sản phẩm</h1><p>Quản lý hiển thị, nút liên hệ, thông tin doanh nghiệp và Product Schema.</p></div></header>
			<form method="post" action="options.php">
				<?php settings_fields( 'slim_catalog_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="color_mode"><?php esc_html_e( 'Color Mode', 'slim-catalog' ); ?></label></th>
						<td>
							<select name="slim_catalog_settings[color_mode]" id="color_mode">
								<option value="light" <?php selected( $settings['color_mode'], 'light' ); ?>><?php esc_html_e( 'Light', 'slim-catalog' ); ?></option>
								<option value="dark" <?php selected( $settings['color_mode'], 'dark' ); ?>><?php esc_html_e( 'Dark', 'slim-catalog' ); ?></option>
								<option value="auto" <?php selected( $settings['color_mode'], 'auto' ); ?>><?php esc_html_e( 'Follow device', 'slim-catalog' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Applied to product sections, archives, categories, and product detail pages.', 'slim-catalog' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="currency_symbol"><?php esc_html_e( 'Currency Symbol', 'slim-catalog' ); ?></label></th>
						<td><input name="slim_catalog_settings[currency_symbol]" id="currency_symbol" type="text" value="<?php echo esc_attr( $settings['currency_symbol'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Currency Position', 'slim-catalog' ); ?></th>
						<td>
							<select name="slim_catalog_settings[currency_position]">
								<option value="before" <?php selected( $settings['currency_position'], 'before' ); ?>><?php esc_html_e( 'Before amount ($99)', 'slim-catalog' ); ?></option>
								<option value="after" <?php selected( $settings['currency_position'], 'after' ); ?>><?php esc_html_e( 'After amount (99$)', 'slim-catalog' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="archive_slug"><?php esc_html_e( 'Shop Archive Slug', 'slim-catalog' ); ?></label></th>
						<td><input name="slim_catalog_settings[archive_slug]" id="archive_slug" type="text" value="<?php echo esc_attr( $settings['archive_slug'] ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Example: /products/', 'slim-catalog' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="single_slug"><?php esc_html_e( 'Single Product Slug', 'slim-catalog' ); ?></label></th>
						<td><input name="slim_catalog_settings[single_slug]" id="single_slug" type="text" value="<?php echo esc_attr( $settings['single_slug'] ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Example: /product/product-name/', 'slim-catalog' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="cta_label"><?php esc_html_e( 'Contact Button Label', 'slim-catalog' ); ?></label></th>
						<td><input name="slim_catalog_settings[cta_label]" id="cta_label" type="text" value="<?php echo esc_attr( $settings['cta_label'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="cta_mode">Kiểu nút mua hàng</label></th>
						<td><select name="slim_catalog_settings[cta_mode]" id="cta_mode">
							<option value="hotline" <?php selected( $settings['cta_mode'], 'hotline' ); ?>>Nhấp để hiện số hotline</option>
							<option value="form" <?php selected( $settings['cta_mode'], 'form' ); ?>>Mở form liên hệ</option>
						</select></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Contact Information', 'slim-catalog' ); ?></h2>
				<p class="description"><?php esc_html_e( 'These details are shown on product pages and used for the contact button link.', 'slim-catalog' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="hotline"><?php esc_html_e( 'Hotline', 'slim-catalog' ); ?></label></th>
						<td>
							<input name="slim_catalog_settings[hotline]" id="hotline" type="text" value="<?php echo esc_attr( $settings['hotline'] ); ?>" class="regular-text" placeholder="0901 234 567" />
							<p class="description"><?php esc_html_e( 'Used for the "Contact Us Now" phone link.', 'slim-catalog' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="zalo"><?php esc_html_e( 'Zalo', 'slim-catalog' ); ?></label></th>
						<td>
							<input name="slim_catalog_settings[zalo]" id="zalo" type="text" value="<?php echo esc_attr( $settings['zalo'] ); ?>" class="regular-text" placeholder="0901234567" />
							<p class="description"><?php esc_html_e( 'Phone number or full Zalo URL.', 'slim-catalog' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="email"><?php esc_html_e( 'Email', 'slim-catalog' ); ?></label></th>
						<td><input name="slim_catalog_settings[email]" id="email" type="email" value="<?php echo esc_attr( $settings['email'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="address"><?php esc_html_e( 'Address', 'slim-catalog' ); ?></label></th>
						<td>
							<textarea name="slim_catalog_settings[address]" id="address" rows="5" class="large-text" placeholder="<?php esc_attr_e( 'Enter one address per line.', 'slim-catalog' ); ?>"><?php echo esc_textarea( $settings['address'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Enter one address per line.', 'slim-catalog' ); ?></p>
						</td>
					</tr>
				</table>
				<h2>Schema sản phẩm</h2><table class="form-table"><tr><th>Kích hoạt</th><td><label><input type="checkbox" name="slim_catalog_settings[schema_enabled]" value="1" <?php checked(!empty($settings['schema_enabled'])); ?>> Xuất Product JSON-LD trên trang chi tiết</label><p class="description">Tắt nếu plugin SEO khác đã tạo Product schema cho loại nội dung này.</p></td></tr><tr><th><label for="currency_code">Mã tiền tệ</label></th><td><input id="currency_code" name="slim_catalog_settings[currency_code]" value="<?php echo esc_attr($settings['currency_code']); ?>" maxlength="3" class="small-text" placeholder="VND"></td></tr><tr><th><label for="default_brand">Thương hiệu mặc định</label></th><td><input id="default_brand" name="slim_catalog_settings[default_brand]" value="<?php echo esc_attr($settings['default_brand']); ?>" class="regular-text"></td></tr></table>

				<?php submit_button(); ?>
				<p class="description"><?php esc_html_e( 'After changing slugs, visit Settings → Permalinks and click Save to refresh rewrite rules.', 'slim-catalog' ); ?></p>
			</form>

			<hr /><p><a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . Slim_Catalog_Post_Types::POST_TYPE . '&page=slim-catalog-shortcodes' ) ); ?>">Mở hướng dẫn shortcode đầy đủ</a></p>
		</div>
		<?php
	}

	/**
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( isset( $_GET['page'] ) && 'slim-catalog-shortcode-generator' === sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			Slim_Catalog_Frontend::enqueue_assets( true );
			return;
		}
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || Slim_Catalog_Post_Types::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'slim-catalog-admin', SLIM_CATALOG_URL . 'assets/css/admin.css', array(), SLIM_CATALOG_VERSION );
		wp_enqueue_script( 'slim-catalog-admin', SLIM_CATALOG_URL . 'assets/js/admin.js', array( 'jquery' ), SLIM_CATALOG_VERSION, true );
		wp_enqueue_script( 'slim-catalog-variations-admin', SLIM_CATALOG_URL . 'assets/js/variations-admin.js', array( 'jquery' ), SLIM_CATALOG_VERSION, true );
	}

	/**
	 * @param string[] $columns Admin columns.
	 * @return string[]
	 */
	public static function columns( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'title' === $key ) {
				$new['slim_price'] = __( 'Price', 'slim-catalog' );
				$new['slim_sku']   = __( 'SKU', 'slim-catalog' );
			}
		}

		return $new;
	}

	/**
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function column_content( $column, $post_id ) {
		$product = Slim_Catalog_Product::get( $post_id );

		if ( ! $product ) {
			return;
		}

		if ( 'slim_price' === $column ) {
			echo wp_kses_post( $product->get_price_html() );
		}

		if ( 'slim_sku' === $column ) {
			echo esc_html( $product->get_sku() );
		}
	}
}
