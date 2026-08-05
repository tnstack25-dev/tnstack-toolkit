<?php
/**
 * Floating contact buttons (Zalo, phone, WhatsApp, Messenger, Facebook, TikTok).
 *
 * @package TNStackToolkit
 */

defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', 'tnstack_floating_contact_enqueue_assets');
add_action('wp_footer', 'tnstack_floating_contact_render', 50);

/**
 * @return array<string, mixed>
 */
function tnstack_floating_contact_defaults()
{
	return array(
		'enabled' => 1,
		'zalo' => '',
		'show_zalo_number' => 0,
		'phone' => '',
		'show_phone_number' => 0,
		'phone_2' => '',
		'show_phone_2_number' => 0,
		'whatsapp' => '',
		'messenger' => '',
		'facebook' => '',
		'tiktok' => '',
		'position' => 'right',
	);
}

/**
 * @return array<string, mixed>
 */
function tnstack_floating_contact_settings()
{
	return tnstack_module_get_settings('floating-contact', tnstack_floating_contact_defaults());
}

/**
 * Enqueue floating contact styles when buttons are configured.
 */
function tnstack_floating_contact_enqueue_assets()
{
	if (is_admin() || empty(tnstack_floating_contact_get_links())) {
		return;
	}

	$path = tnstack_core_path('assets/css/floating-contact.css');

	wp_enqueue_style(
		'tnstack-floating-contact',
		tnstack_core_uri('assets/css/floating-contact.css'),
		array(),
		tnstack_core_asset_version($path)
	);
}

function tnstack_floating_contact_render_admin()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$saved = false;

	if (isset($_POST['tnstack_fc_save'])) {
		check_admin_referer('tnstack_fc');
		$position = isset($_POST['position']) ? sanitize_key(wp_unslash($_POST['position'])) : 'right';
		tnstack_module_update_settings(
			'floating-contact',
			array(
				'zalo' => sanitize_text_field(wp_unslash($_POST['zalo'] ?? '')),
				'show_zalo_number' => isset($_POST['show_zalo_number']) ? 1 : 0,
				'phone' => sanitize_text_field(wp_unslash($_POST['phone'] ?? '')),
				'show_phone_number' => isset($_POST['show_phone_number']) ? 1 : 0,
				'phone_2' => sanitize_text_field(wp_unslash($_POST['phone_2'] ?? '')),
				'show_phone_2_number' => isset($_POST['show_phone_2_number']) ? 1 : 0,
				'whatsapp' => sanitize_text_field(wp_unslash($_POST['whatsapp'] ?? '')),
				'messenger' => esc_url_raw(wp_unslash($_POST['messenger'] ?? '')),
				'facebook' => esc_url_raw(wp_unslash($_POST['facebook'] ?? '')),
				'tiktok' => esc_url_raw(wp_unslash($_POST['tiktok'] ?? '')),
				'position' => in_array($position, array('left', 'right'), true) ? $position : 'right',
			),
			tnstack_floating_contact_defaults()
		);
		$saved = true;
	}

	$s = tnstack_floating_contact_settings();
	?>
	<div class="wrap ttk-settings ttk-settings--floating">
		<header class="ttk-settings__hero">
			<span class="ttk-settings__hero-icon"><span class="dashicons dashicons-phone"></span></span>
			<div>
				<span class="ttk-settings__eyebrow"><?php esc_html_e('TNStack Toolkit', 'tnstack-toolkit'); ?></span>
				<h1><?php esc_html_e('Floating Contact', 'tnstack-toolkit'); ?></h1>
				<p><?php esc_html_e('Thiết lập các kênh liên hệ nổi để khách hàng kết nối nhanh trên mọi thiết bị.', 'tnstack-toolkit'); ?></p>
			</div>
		</header>

		<?php if ($saved): ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e('Đã lưu cài đặt Floating Contact.', 'tnstack-toolkit'); ?></p></div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field('tnstack_fc'); ?>
			<section class="ttk-settings__card">
				<div class="ttk-settings__card-header">
					<h2><?php esc_html_e('Kênh liên hệ', 'tnstack-toolkit'); ?></h2>
					<p><?php esc_html_e('Để trống những kênh bạn không muốn hiển thị.', 'tnstack-toolkit'); ?></p>
				</div>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="tnstack_fc_zalo"><?php esc_html_e('Zalo', 'tnstack-toolkit'); ?></label></th>
						<td>
							<input type="tel" id="tnstack_fc_zalo" name="zalo" class="regular-text" value="<?php echo esc_attr($s['zalo']); ?>" placeholder="0123.456.798">
							<label><input type="checkbox" name="show_zalo_number" value="1" <?php checked(!empty($s['show_zalo_number'])); ?>> <?php esc_html_e('Hiển thị số bên cạnh nút', 'tnstack-toolkit'); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tnstack_fc_phone"><?php esc_html_e('Số điện thoại 1', 'tnstack-toolkit'); ?></label></th>
						<td>
							<input type="tel" id="tnstack_fc_phone" name="phone" class="regular-text" value="<?php echo esc_attr($s['phone']); ?>" placeholder="0123.456.798">
							<label><input type="checkbox" name="show_phone_number" value="1" <?php checked(!empty($s['show_phone_number'])); ?>> <?php esc_html_e('Hiển thị số bên cạnh nút', 'tnstack-toolkit'); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tnstack_fc_phone_2"><?php esc_html_e('Số điện thoại 2', 'tnstack-toolkit'); ?></label></th>
						<td>
							<input type="tel" id="tnstack_fc_phone_2" name="phone_2" class="regular-text" value="<?php echo esc_attr($s['phone_2']); ?>" placeholder="0987.654.321">
							<label><input type="checkbox" name="show_phone_2_number" value="1" <?php checked(!empty($s['show_phone_2_number'])); ?>> <?php esc_html_e('Hiển thị số bên cạnh nút', 'tnstack-toolkit'); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tnstack_fc_whatsapp">WhatsApp</label></th>
						<td>
							<input type="tel" id="tnstack_fc_whatsapp" name="whatsapp" class="regular-text" value="<?php echo esc_attr($s['whatsapp']); ?>" placeholder="84123456789">
							<p class="description"><?php esc_html_e('Nhập mã quốc gia, không dùng dấu cộng.', 'tnstack-toolkit'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tnstack_fc_messenger">Messenger</label></th>
						<td><input type="url" id="tnstack_fc_messenger" name="messenger" class="regular-text" value="<?php echo esc_attr($s['messenger']); ?>" placeholder="https://m.me/page"></td>
					</tr>
					<tr>
						<th scope="row"><label for="tnstack_fc_facebook">Facebook</label></th>
						<td><input type="url" id="tnstack_fc_facebook" name="facebook" class="regular-text" value="<?php echo esc_attr($s['facebook']); ?>" placeholder="https://www.facebook.com/page"></td>
					</tr>
					<tr>
						<th scope="row"><label for="tnstack_fc_tiktok">TikTok</label></th>
						<td><input type="url" id="tnstack_fc_tiktok" name="tiktok" class="regular-text" value="<?php echo esc_attr($s['tiktok']); ?>" placeholder="https://www.tiktok.com/@username"></td>
					</tr>
					<tr>
						<th scope="row"><label for="tnstack_fc_position"><?php esc_html_e('Vị trí hiển thị', 'tnstack-toolkit'); ?></label></th>
						<td>
							<select id="tnstack_fc_position" name="position">
								<option value="right" <?php selected($s['position'], 'right'); ?>><?php esc_html_e('Bên phải', 'tnstack-toolkit'); ?></option>
								<option value="left" <?php selected($s['position'], 'left'); ?>><?php esc_html_e('Bên trái', 'tnstack-toolkit'); ?></option>
							</select>
						</td>
					</tr>
				</table>
			</section>
			<div class="ttk-settings__actions">
				<?php submit_button(__('Lưu thay đổi', 'tnstack-toolkit'), 'primary', 'tnstack_fc_save'); ?>
			</div>
		</form>
	</div>
	<?php
}

/**
 * @return array<int, array<string, string>>
 */
function tnstack_floating_contact_get_links()
{
	$s = tnstack_floating_contact_settings();
	$links = array();

	if (!empty($s['zalo'])) {
		$links[] = array(
			'url' => 'https://zalo.me/' . rawurlencode(preg_replace('/\D/', '', $s['zalo'])),
			'label' => 'Zalo',
			'class' => 'zalo',
			'text' => !empty($s['show_zalo_number']) ? $s['zalo'] : '',
		);
	}

	if (!empty($s['phone'])) {
		$links[] = array(
			'url' => 'tel:' . preg_replace('/\s+/', '', $s['phone']),
			'label' => __('Gọi điện', 'tnstack-toolkit'),
			'class' => 'phone',
			'text' => !empty($s['show_phone_number']) ? $s['phone'] : '',
		);
	}

	if (!empty($s['phone_2'])) {
		$links[] = array(
			'url' => 'tel:' . preg_replace('/\s+/', '', $s['phone_2']),
			'label' => __('Gọi điện', 'tnstack-toolkit'),
			'class' => 'phone',
			'text' => !empty($s['show_phone_2_number']) ? $s['phone_2'] : '',
		);
	}

	if (!empty($s['whatsapp'])) {
		$links[] = array(
			'url' => 'https://wa.me/' . rawurlencode(preg_replace('/\D/', '', $s['whatsapp'])),
			'label' => 'WhatsApp',
			'class' => 'wa',
		);
	}

	if (!empty($s['messenger'])) {
		$links[] = array(
			'url' => $s['messenger'],
			'label' => 'Messenger',
			'class' => 'fb',
		);
	}

	if (!empty($s['facebook'])) {
		$links[] = array(
			'url' => $s['facebook'],
			'label' => 'Facebook',
			'class' => 'facebook',
		);
	}

	if (!empty($s['tiktok'])) {
		$links[] = array(
			'url' => $s['tiktok'],
			'label' => 'TikTok',
			'class' => 'tiktok',
		);
	}

	return $links;
}

/**
 * @param string $type Button type.
 * @return string
 */
function tnstack_floating_contact_icon($type)
{
	$icons = array(
		'zalo' => '<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50" fill="none">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M22.782 0.166016H27.199C33.2653 0.166016 36.8103 1.05701 39.9572 2.74421C43.1041 4.4314 45.5875 6.89585 47.2557 10.0428C48.9429 13.1897 49.8339 16.7347 49.8339 22.801V27.1991C49.8339 33.2654 48.9429 36.8104 47.2557 39.9573C45.5685 43.1042 43.1041 45.5877 39.9572 47.2559C36.8103 48.9431 33.2653 49.8341 27.199 49.8341H22.8009C16.7346 49.8341 13.1896 48.9431 10.0427 47.2559C6.89583 45.5687 4.41243 43.1042 2.7442 39.9573C1.057 36.8104 0.166016 33.2654 0.166016 27.1991V22.801C0.166016 16.7347 1.057 13.1897 2.7442 10.0428C4.43139 6.89585 6.89583 4.41245 10.0427 2.74421C13.1707 1.05701 16.7346 0.166016 22.782 0.166016Z" fill="#0068FF"/>
				<path opacity="0.12" fill-rule="evenodd" clip-rule="evenodd" d="M49.8336 26.4736V27.1994C49.8336 33.2657 48.9427 36.8107 47.2555 39.9576C45.5683 43.1045 43.1038 45.5879 39.9569 47.2562C36.81 48.9434 33.265 49.8344 27.1987 49.8344H22.8007C17.8369 49.8344 14.5612 49.2378 11.8104 48.0966L7.27539 43.4267L49.8336 26.4736Z" fill="#001A33"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M7.779 43.5892C10.1019 43.846 13.0061 43.1836 15.0682 42.1825C24.0225 47.1318 38.0197 46.8954 46.4923 41.4732C46.8209 40.9803 47.1279 40.4677 47.4128 39.9363C49.1062 36.7779 50.0004 33.22 50.0004 27.1316V22.7175C50.0004 16.629 49.1062 13.0711 47.4128 9.91273C45.7385 6.75436 43.2461 4.28093 40.0877 2.58758C36.9293 0.894239 33.3714 0 27.283 0H22.8499C17.6644 0 14.2982 0.652754 11.4699 1.89893C11.3153 2.03737 11.1636 2.17818 11.0151 2.32135C2.71734 10.3203 2.08658 27.6593 9.12279 37.0782C9.13064 37.0921 9.13933 37.1061 9.14889 37.1203C10.2334 38.7185 9.18694 41.5154 7.55068 43.1516C7.28431 43.399 7.37944 43.5512 7.779 43.5892Z" fill="white"/>
				<path d="M20.5632 17H10.8382V19.0853H17.5869L10.9329 27.3317C10.7244 27.635 10.5728 27.9194 10.5728 28.5639V29.0947H19.748C20.203 29.0947 20.5822 28.7156 20.5822 28.2606V27.1421H13.4922L19.748 19.2938C19.8428 19.1801 20.0134 18.9716 20.0893 18.8768L20.1272 18.8199C20.4874 18.2891 20.5632 17.8341 20.5632 17.2844V17Z" fill="#0068FF"/>
				<path d="M32.9416 29.0947H34.3255V17H32.2402V28.3933C32.2402 28.7725 32.5435 29.0947 32.9416 29.0947Z" fill="#0068FF"/>
				<path d="M25.814 19.6924C23.1979 19.6924 21.0747 21.8156 21.0747 24.4317C21.0747 27.0478 23.1979 29.171 25.814 29.171C28.4301 29.171 30.5533 27.0478 30.5533 24.4317C30.5723 21.8156 28.4491 19.6924 25.814 19.6924ZM25.814 27.2184C24.2785 27.2184 23.0273 25.9672 23.0273 24.4317C23.0273 22.8962 24.2785 21.645 25.814 21.645C27.3495 21.645 28.6007 22.8962 28.6007 24.4317C28.6007 25.9672 27.3685 27.2184 25.814 27.2184Z" fill="#0068FF"/>
				<path d="M40.4867 19.6162C37.8516 19.6162 35.7095 21.7584 35.7095 24.3934C35.7095 27.0285 37.8516 29.1707 40.4867 29.1707C43.1217 29.1707 45.2639 27.0285 45.2639 24.3934C45.2639 21.7584 43.1217 19.6162 40.4867 19.6162ZM40.4867 27.2181C38.9322 27.2181 37.681 25.9669 37.681 24.4124C37.681 22.8579 38.9322 21.6067 40.4867 21.6067C42.0412 21.6067 43.2924 22.8579 43.2924 24.4124C43.2924 25.9669 42.0412 27.2181 40.4867 27.2181Z" fill="#0068FF"/>
				<path d="M29.4562 29.0944H30.5747V19.957H28.6221V28.2793C28.6221 28.7153 29.0012 29.0944 29.4562 29.0944Z" fill="#0068FF"/>
			</svg>',
		'phone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24 11.36 11.36 0 0 0 3.58.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.36 11.36 0 0 0 .57 3.58 1 1 0 0 1-.25 1.01l-2.2 2.2z"/></svg>',
		'wa' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 2.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.881 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>',
		'fb' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.04c-5.5 0-10 4.13-10 9.22 0 2.91 1.46 5.51 3.74 7.2l-.95 2.83 3.24-1.06c.95.26 1.96.41 2.97.41 5.5 0 10-4.13 10-9.22S17.5 2.04 12 2.04zm1.03 12.41-2.61-2.78-4.82 2.78 5.21-5.54 2.68 2.85 4.75-2.85-5.21 5.54z"/></svg>',
		'facebook' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.413c0-3.025 1.792-4.697 4.533-4.697 1.313 0 2.686.236 2.686.236v2.971h-1.513c-1.49 0-1.956.931-1.956 1.887v2.263h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>',
		'tiktok' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.589 6.686a4.793 4.793 0 0 1-3.77-4.245V2h-3.445v13.672a2.896 2.896 0 0 1-5.201 1.743 2.895 2.895 0 0 1 3.183-4.51V9.384a6.329 6.329 0 0 0-5.394 10.692 6.33 6.33 0 0 0 10.857-4.403V8.738a8.182 8.182 0 0 0 4.773 1.526V6.842a4.831 4.831 0 0 1-1.003-.156z"/></svg>',
	);

	$icon = $icons[$type] ?? $icons['phone'];

	return '<span class="tnstack-fc__icon">' . $icon . '</span>';
}

function tnstack_floating_contact_render()
{
	if (is_admin()) {
		return;
	}

	$links = tnstack_floating_contact_get_links();
	if (empty($links)) {
		return;
	}

	$s = tnstack_floating_contact_settings();
	$pos_cls = 'left' === $s['position'] ? 'tnstack-fc--left' : 'tnstack-fc--right';
	?>
	<div class="tnstack-fc <?php echo esc_attr($pos_cls); ?>">
		<?php foreach ($links as $index => $link): ?>
			<?php
			$is_external = 0 !== strpos($link['url'], 'tel:');
			$delay = (float) $index * 0.35;
			?>
			<div class="tnstack-fc__item tnstack-fc__item--<?php echo esc_attr($link['class']); ?>"
				style="--tnstack-fc-delay: <?php echo esc_attr($delay); ?>s">
				<span class="tnstack-fc__wave" aria-hidden="true"></span>
				<span class="tnstack-fc__wave tnstack-fc__wave--2" aria-hidden="true"></span>
				<a href="<?php echo esc_url($link['url']); ?>"
					class="tnstack-fc__btn tnstack-fc__btn--<?php echo esc_attr($link['class']); ?>" <?php echo $is_external ? 'target="_blank" rel="noopener noreferrer"' : ''; ?> aria-label="<?php echo esc_attr($link['label']); ?>">
					<?php if (!empty($link['text'])): ?>
						<span class="tnstack-fc__number"><?php echo esc_html($link['text']); ?></span>
					<?php endif; ?>
					<?php echo tnstack_floating_contact_icon($link['class']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG markup is static. ?>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}
