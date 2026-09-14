<?php
/** Popup Form module integrated into TNStack Toolkit. */

if (!defined('ABSPATH')) exit;

require_once tnstack_core_path( 'inc/modules/popup-leads.php' );
require_once tnstack_core_path( 'inc/modules/popup-forms.php' );
require_once tnstack_core_path( 'inc/modules/popup-webhooks.php' );

final class TNStack_Popup_Form {
    const OPTION = 'tn_popup_form_settings';

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
		add_filter('option_page_capability_tn_popup_form_group', function(){ return TNStack_Account_Permissions::MANAGE_CAP; });
        add_action('wp_enqueue_scripts', [$this, 'frontend_assets']);
        add_action('wp_footer', [$this, 'render_popup']);
        add_action('wp_ajax_tn_popup_submit', [$this, 'handle_submit']);
        add_action('wp_ajax_nopriv_tn_popup_submit', [$this, 'handle_submit']);
		add_action('tnstack_popup_cleanup_leads', [$this, 'cleanup_leads']);
		if (!wp_next_scheduled('tnstack_popup_cleanup_leads')) wp_schedule_event(time() + DAY_IN_SECONDS, 'daily', 'tnstack_popup_cleanup_leads');
    }

    public function defaults() {
        return [
            'enabled'       => 1,
            'delay'         => 5,
            'title'         => 'Bạn cần hỗ trợ?',
            'description'   => 'Để lại thông tin, chúng tôi sẽ liên hệ với bạn sớm nhất.',
            'button_text'   => 'Gửi thông tin',
            'success'       => 'Cảm ơn bạn! Thông tin đã được gửi thành công.',
            'recipient'     => get_option('admin_email'),
            'subject'       => 'Khách hàng gửi form Popup',
            'close_text'    => '×',
            'show_once'     => 0,
			'store_submissions' => 1,
			'retention_days' => 365,
            'fields'        => [
                ['type'=>'text','name'=>'name','label'=>'Họ và tên','placeholder'=>'Nhập họ và tên','required'=>1],
                ['type'=>'tel','name'=>'phone','label'=>'Số điện thoại','placeholder'=>'Nhập số điện thoại','required'=>1],
                ['type'=>'email','name'=>'email','label'=>'Email','placeholder'=>'Nhập email','required'=>0],
                ['type'=>'textarea','name'=>'message','label'=>'Nội dung','placeholder'=>'Bạn cần hỗ trợ vấn đề gì?','required'=>0],
            ],
        ];
    }

    public function get_settings() {
        $saved = get_option(self::OPTION, []);
        $settings = wp_parse_args($saved, $this->defaults());
        if (!isset($settings['fields']) || !is_array($settings['fields']) || !$settings['fields']) {
            $settings['fields'] = $this->defaults()['fields'];
        }
        return $settings;
    }

	public function get_form_settings( $form_id = 'default' ) {
		$defaults = $this->get_settings();
		return ( 'default' !== (string) $form_id && class_exists( 'TNStack_Popup_Forms' ) ) ? TNStack_Popup_Forms::settings( absint( $form_id ), $defaults ) : $defaults;
	}

    public function register_settings() {
        register_setting('tn_popup_form_group', self::OPTION, [
            'sanitize_callback' => [$this, 'sanitize_settings']
        ]);
    }

    public function sanitize_settings($input) {
        $defaults = $this->defaults();
        $out = $defaults;

        $out['enabled'] = !empty($input['enabled']) ? 1 : 0;
        $out['delay'] = max(0, min(3600, absint($input['delay'] ?? 5)));
        $out['title'] = sanitize_text_field($input['title'] ?? '');
        $out['description'] = sanitize_textarea_field($input['description'] ?? '');
        $out['button_text'] = sanitize_text_field($input['button_text'] ?? 'Gửi thông tin');
        $out['success'] = sanitize_text_field($input['success'] ?? '');
        $out['recipient'] = sanitize_email($input['recipient'] ?? get_option('admin_email'));
        $out['subject'] = sanitize_text_field($input['subject'] ?? '');
        $out['close_text'] = sanitize_text_field($input['close_text'] ?? '×');
        $out['show_once'] = !empty($input['show_once']) ? 1 : 0;
		$out['store_submissions'] = !empty($input['store_submissions']) ? 1 : 0;
		$out['retention_days'] = max(0, min(3650, absint($input['retention_days'] ?? 365)));

        $allowed_types = ['text','email','tel','number','url','textarea','select','checkbox'];
        $fields = [];
        if (!empty($input['fields']) && is_array($input['fields'])) {
            foreach ($input['fields'] as $field) {
                $type = in_array(($field['type'] ?? ''), $allowed_types, true) ? $field['type'] : 'text';
                $name = sanitize_key($field['name'] ?? '');
                $label = sanitize_text_field($field['label'] ?? '');
                if (!$name || !$label) continue;

                $item = [
                    'type' => $type,
                    'name' => $name,
                    'label' => $label,
                    'placeholder' => sanitize_text_field($field['placeholder'] ?? ''),
                    'required' => !empty($field['required']) ? 1 : 0,
                    'options' => [],
                ];

                if ($type === 'select') {
                    $raw_options = preg_split('/\r\n|\r|\n/', (string)($field['options'] ?? ''));
                    foreach ($raw_options as $option) {
                        $option = sanitize_text_field($option);
                        if ($option !== '') $item['options'][] = $option;
                    }
                }
                $fields[] = $item;
            }
        }
        $out['fields'] = $fields ?: $defaults['fields'];
        return $out;
    }

    public function settings_page() {
        if (!current_user_can(TNStack_Account_Permissions::MANAGE_CAP)) return;
        $s = $this->get_settings();
        ?>
        <div class="wrap tnpf-admin">
			<header class="tns-page-hero"><span class="tns-page-hero__icon"><span class="dashicons dashicons-feedback"></span></span><div><h1>TN Popup Form</h1><p>Tạo biểu mẫu liên hệ, quản lý trường dữ liệu và trải nghiệm gửi form.</p></div></header>

            <form method="post" action="options.php">
                <?php settings_fields('tn_popup_form_group'); ?>

                <div class="tnpf-card">
                    <h2>Cài đặt popup</h2>
                    <table class="form-table">
                        <tr>
                            <th>Trạng thái</th>
                            <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[enabled]" value="1" <?php checked($s['enabled'],1); ?>> Bật popup</label></td>
                        </tr>
                        <tr>
                            <th>Thời gian hiển thị</th>
                            <td>
                                <input type="number" min="0" max="3600" class="small-text" name="<?php echo esc_attr(self::OPTION); ?>[delay]" value="<?php echo esc_attr($s['delay']); ?>"> giây
                                <p class="description">Ví dụ 5 = popup xuất hiện sau 5 giây.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Tiêu đề</th>
                            <td><input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[title]" value="<?php echo esc_attr($s['title']); ?>"></td>
                        </tr>
                        <tr>
                            <th>Mô tả</th>
                            <td><textarea rows="3" class="large-text" name="<?php echo esc_attr(self::OPTION); ?>[description]"><?php echo esc_textarea($s['description']); ?></textarea></td>
                        </tr>
                        <tr>
                            <th>Nút gửi</th>
                            <td><input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[button_text]" value="<?php echo esc_attr($s['button_text']); ?>"></td>
                        </tr>
                        <tr>
                            <th>Thông báo thành công</th>
                            <td><input type="text" class="large-text" name="<?php echo esc_attr(self::OPTION); ?>[success]" value="<?php echo esc_attr($s['success']); ?>"></td>
                        </tr>
                        <tr>
                            <th>Email nhận</th>
                            <td><input type="email" class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[recipient]" value="<?php echo esc_attr($s['recipient']); ?>"></td>
                        </tr>
                        <tr>
                            <th>Tiêu đề email</th>
                            <td><input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[subject]" value="<?php echo esc_attr($s['subject']); ?>"></td>
                        </tr>
                        <tr>
                            <th>Hiển thị một lần</th>
                            <td>
                                <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[show_once]" value="1" <?php checked($s['show_once'],1); ?>> Chỉ hiển thị 1 lần trên mỗi trình duyệt</label>
                                <p class="description">Dùng cookie 30 ngày. Bỏ chọn nếu muốn popup xuất hiện mỗi lần tải trang.</p>
                            </td>
                        </tr>
						<tr><th>Lưu yêu cầu</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[store_submissions]" value="1" <?php checked($s['store_submissions'],1); ?>> Lưu yêu cầu vào trang “Yêu cầu liên hệ”</label><p><label>Tự xóa sau <input class="small-text" type="number" min="0" max="3650" name="<?php echo esc_attr(self::OPTION); ?>[retention_days]" value="<?php echo esc_attr($s['retention_days']); ?>"> ngày</label> <span class="description">Nhập 0 để giữ vô thời hạn.</span></p><p><a class="button" href="<?php echo esc_url( admin_url('edit.php?post_type=tnstack_lead') ); ?>">Mở hộp thư yêu cầu</a></p></td></tr>
                    </table>
                </div>

                <div class="tnpf-card">
                    <div class="tnpf-fields-head">
                        <div>
                            <h2>Fields của form</h2>
                            <p class="description">Kéo thả để sắp xếp. Name nên viết không dấu, không khoảng trắng.</p>
                        </div>
                        <button type="button" class="button button-primary" id="tnpf-add-field">+ Thêm field</button>
                    </div>

                    <div id="tnpf-fields">
                        <?php foreach ($s['fields'] as $i => $field) : ?>
                            <?php $this->field_editor($i, $field); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php submit_button('Lưu cấu hình'); ?>
            </form>
        </div>
        <?php
        $this->admin_inline_assets();
    }

    private function field_editor($i, $field) {
        ?>
        <div class="tnpf-field-row">
            <div class="tnpf-drag">☷</div>
            <div class="tnpf-field-content">
                <div class="tnpf-grid">
                    <p>
                        <label>Loại field</label>
                        <select name="<?php echo esc_attr(self::OPTION); ?>[fields][<?php echo esc_attr($i); ?>][type]" class="tnpf-type">
                            <?php foreach (['text'=>'Text','email'=>'Email','tel'=>'Số điện thoại','number'=>'Number','url'=>'URL','textarea'=>'Textarea','select'=>'Select','checkbox'=>'Checkbox'] as $type => $label) : ?>
                                <option value="<?php echo esc_attr($type); ?>" <?php selected($field['type'] ?? 'text', $type); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label>Name</label>
                        <input type="text" name="<?php echo esc_attr(self::OPTION); ?>[fields][<?php echo esc_attr($i); ?>][name]" value="<?php echo esc_attr($field['name'] ?? ''); ?>" placeholder="phone">
                    </p>
                    <p>
                        <label>Nhãn</label>
                        <input type="text" name="<?php echo esc_attr(self::OPTION); ?>[fields][<?php echo esc_attr($i); ?>][label]" value="<?php echo esc_attr($field['label'] ?? ''); ?>" placeholder="Số điện thoại">
                    </p>
                    <p>
                        <label>Placeholder</label>
                        <input type="text" name="<?php echo esc_attr(self::OPTION); ?>[fields][<?php echo esc_attr($i); ?>][placeholder]" value="<?php echo esc_attr($field['placeholder'] ?? ''); ?>" placeholder="Nhập thông tin">
                    </p>
                </div>

                <p class="tnpf-options">
                    <label>Options (mỗi dòng một lựa chọn, chỉ dùng cho Select)</label>
                    <textarea rows="3" name="<?php echo esc_attr(self::OPTION); ?>[fields][<?php echo esc_attr($i); ?>][options]"><?php echo esc_textarea(implode("\n", $field['options'] ?? [])); ?></textarea>
                </p>

                <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[fields][<?php echo esc_attr($i); ?>][required]" value="1" <?php checked(!empty($field['required'])); ?>> Bắt buộc</label>
            </div>
            <button type="button" class="button tnpf-remove">Xóa</button>
        </div>
        <?php
    }

    private function admin_inline_assets() {
        ?>
        <style>
            .tnpf-admin{max-width:1100px}
            .tnpf-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px;margin:20px 0;box-shadow:0 4px 18px rgba(15,23,42,.04)}
            .tnpf-card h2{margin-top:0}
            .tnpf-fields-head{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:18px}
            .tnpf-field-row{display:flex;gap:12px;align-items:flex-start;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin-bottom:12px}
            .tnpf-drag{font-size:24px;color:#94a3b8;cursor:move;padding-top:25px}
            .tnpf-field-content{flex:1}
            .tnpf-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
            .tnpf-grid p,.tnpf-options{margin:0 0 12px}
            .tnpf-grid label,.tnpf-options label{display:block;font-weight:600;margin-bottom:5px}
            .tnpf-grid input,.tnpf-grid select,.tnpf-options textarea{width:100%}
            .tnpf-remove{margin-top:25px;color:#b42318!important}
            @media(max-width:900px){.tnpf-grid{grid-template-columns:repeat(2,1fr)}}
            @media(max-width:600px){.tnpf-grid{grid-template-columns:1fr}.tnpf-fields-head{align-items:flex-start;flex-direction:column}}
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function(){
            const wrap=document.getElementById('tnpf-fields');
            const add=document.getElementById('tnpf-add-field');
            let index=wrap.querySelectorAll('.tnpf-field-row').length;

            add.addEventListener('click', function(){
                const row=document.createElement('div');
                row.className='tnpf-field-row';
                const p='tn_popup_form_settings[fields]['+index+']';
                row.innerHTML=`
                    <div class="tnpf-drag">☷</div>
                    <div class="tnpf-field-content">
                        <div class="tnpf-grid">
                            <p><label>Loại field</label><select name="${p}[type]" class="tnpf-type">
                                <option value="text">Text</option><option value="email">Email</option><option value="tel">Số điện thoại</option><option value="number">Number</option><option value="url">URL</option><option value="textarea">Textarea</option><option value="select">Select</option><option value="checkbox">Checkbox</option>
                            </select></p>
                            <p><label>Name</label><input type="text" name="${p}[name]" placeholder="phone"></p>
                            <p><label>Nhãn</label><input type="text" name="${p}[label]" placeholder="Số điện thoại"></p>
                            <p><label>Placeholder</label><input type="text" name="${p}[placeholder]" placeholder="Nhập thông tin"></p>
                        </div>
                        <p class="tnpf-options"><label>Options (mỗi dòng một lựa chọn, chỉ dùng cho Select)</label><textarea rows="3" name="${p}[options]"></textarea></p>
                        <label><input type="checkbox" name="${p}[required]" value="1"> Bắt buộc</label>
                    </div>
                    <button type="button" class="button tnpf-remove">Xóa</button>`;
                wrap.appendChild(row);
                index++;
            });

            wrap.addEventListener('click', function(e){
                if(e.target.classList.contains('tnpf-remove')) e.target.closest('.tnpf-field-row').remove();
            });
        });
        </script>
        <?php
    }

    public function frontend_assets() {
        $s = $this->get_settings();
        wp_enqueue_style('tn-popup-form', tnstack_core_uri('assets/popup-form/popup.css'), [], TNSTACK_TOOLKIT_VERSION);
        wp_enqueue_script('tn-popup-form', tnstack_core_uri('assets/popup-form/popup.js'), [], TNSTACK_TOOLKIT_VERSION, true);
        wp_localize_script('tn-popup-form', 'TNPopupForm', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tn_popup_submit'),
        ]);
    }

    public function render_popup() {
        $s = $this->get_settings();
        ?>
		<?php if ( empty( $s['enabled'] ) && ! TNStack_Popup_Forms::has_forms() ) return; ?>
		<div id="tn-popup-form" class="tnpf-overlay" aria-hidden="true" data-trigger="delay" data-delay="<?php echo esc_attr( absint($s['delay']) * 1000 ); ?>" data-show-once="<?php echo empty($s['show_once'])?'0':'1'; ?>" data-default-enabled="<?php echo empty($s['enabled'])?'0':'1'; ?>">
            <div class="tnpf-modal" role="dialog" aria-modal="true" aria-labelledby="tnpf-title">
                <button type="button" class="tnpf-close" aria-label="Đóng"><?php echo esc_html($s['close_text']); ?></button>
                <div class="tnpf-accent"></div>
                <div class="tnpf-body">
                    <div class="tnpf-icon" aria-hidden="true">✦</div>
                    <h2 id="tnpf-title"><?php echo esc_html($s['title']); ?></h2>
                    <?php if ($s['description']) : ?><p class="tnpf-description"><?php echo esc_html($s['description']); ?></p><?php endif; ?>

                    <form id="tnpf-form" novalidate>
						<input type="hidden" name="form_id" value="default">
						<input type="hidden" name="product_id" value="">
						<input type="hidden" name="product_name" value="">
						<input type="hidden" name="product_variant" value="">
						<input type="hidden" name="source_url" value="">
						<input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">
                        <div class="tnpf-fields">
                        <?php foreach ($s['fields'] as $field) :
                            $name = sanitize_key($field['name']);
                            $id = 'tnpf-' . $name;
                            $required = !empty($field['required']);
                            $type = $field['type'];
                            ?>
                            <div class="tnpf-field tnpf-field-<?php echo esc_attr($type); ?>">
                                <?php if ($type !== 'checkbox') : ?>
                                    <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($field['label']); ?><?php echo $required ? ' *' : ''; ?></label>
                                <?php endif; ?>

                                <?php if ($type === 'textarea') : ?>
                                    <textarea id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>" <?php echo $required ? 'required' : ''; ?>></textarea>
                                <?php elseif ($type === 'select') : ?>
                                    <select id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" <?php echo $required ? 'required' : ''; ?>>
                                        <option value="">-- Vui lòng chọn --</option>
                                        <?php foreach (($field['options'] ?? []) as $option) : ?>
                                            <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($type === 'checkbox') : ?>
                                    <label class="tnpf-check"><input type="checkbox" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="1" <?php echo $required ? 'required' : ''; ?>><span><?php echo esc_html($field['label']); ?><?php echo $required ? ' *' : ''; ?></span></label>
                                <?php else : ?>
                                    <input type="<?php echo esc_attr($type); ?>" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>" <?php echo $required ? 'required' : ''; ?>>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <div class="tnpf-message" role="status"></div>
                        <button type="submit" class="tnpf-submit">
                            <span class="tnpf-submit-text"><?php echo esc_html($s['button_text']); ?></span>
                            <span class="tnpf-spinner" aria-hidden="true"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function handle_submit() {
        check_ajax_referer('tn_popup_submit', 'nonce');
		if ( ! empty( $_POST['website'] ) ) wp_send_json_error( [ 'message' => 'Yêu cầu không hợp lệ.' ], 400 );
		$client_ip = function_exists( 'tnstack_core_security_client_ip' ) ? tnstack_core_security_client_ip() : sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
		$rate_key = 'tnpf_rate_' . md5( $client_ip );
		if ( (int) get_transient( $rate_key ) >= 5 ) wp_send_json_error( [ 'message' => 'Bạn gửi quá nhanh. Vui lòng thử lại sau.' ], 429 );

		$form_id = sanitize_text_field( wp_unslash( $_POST['form_id'] ?? 'default' ) );
        $s = $this->get_form_settings( $form_id );
        $fields = $s['fields'];
        $lines = [];
        $valid = true;
		$values = [];

        foreach ($fields as $field) {
            $name = sanitize_key($field['name']);
            $value = isset($_POST[$name]) ? wp_unslash($_POST[$name]) : '';

            if ($field['type'] === 'checkbox') {
                $value = !empty($value) ? 'Có' : 'Không';
            } elseif (is_array($value)) {
                $value = implode(', ', array_map('sanitize_text_field', $value));
            } else {
                $value = ($field['type'] === 'textarea') ? sanitize_textarea_field($value) : sanitize_text_field($value);
            }

            if (!empty($field['required']) && $value === '') $valid = false;
            if ($field['type'] === 'email' && $value !== '' && !is_email($value)) $valid = false;

            $lines[] = $field['label'] . ': ' . $value;
			$values[$name] = $value;
        }

		$product_id = absint($_POST['product_id'] ?? 0);
		$product_name = sanitize_text_field(wp_unslash($_POST['product_name'] ?? ''));
		$product_variant = sanitize_text_field(wp_unslash($_POST['product_variant'] ?? ''));
		if ($product_id || $product_name) {
			array_unshift($lines, 'Sản phẩm: ' . ($product_name ?: '#' . $product_id));
			if ($product_variant) { $lines[] = 'Biến thể: ' . $product_variant; $values['product_variant'] = $product_variant; }
			if ($product_id) $lines[] = 'Link: ' . get_permalink($product_id);
		}

        if (!$valid) {
            wp_send_json_error(['message' => 'Vui lòng kiểm tra lại thông tin trong form.'], 400);
        }
		set_transient( $rate_key, (int) get_transient( $rate_key ) + 1, 10 * MINUTE_IN_SECONDS );
		$source_url = esc_url_raw( wp_unslash( $_POST['source_url'] ?? '' ) );
		if ($source_url) $lines[] = 'Nguồn: ' . $source_url;
		$lead_id = ! empty( $s['store_submissions'] ) ? TNStack_Popup_Leads::create( $values, $product_id, $source_url ) : 0;
		do_action( 'tnstack_form_submitted', array( 'lead_id'=>$lead_id, 'form_id'=>$form_id, 'product_id'=>$product_id, 'source_url'=>$source_url, 'fields'=>$values ) );

        $body = implode("\n", $lines);
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

		$recipient = $s['recipient'];
		if ( $product_id ) {
			$product_recipient = sanitize_email( get_post_meta( $product_id, '_slim_cta_recipient', true ) );
			if ( $product_recipient ) $recipient = $product_recipient;
		}
        $sent = wp_mail($recipient, $s['subject'], $body, $headers);
		if ( $lead_id ) update_post_meta( $lead_id, '_tnstack_lead_mail_status', $sent ? 'sent' : 'failed' );

        if (!$sent) {
            wp_send_json_error(['message' => 'Không thể gửi form lúc này. Vui lòng thử lại sau.'], 500);
        }
		if ( $product_id && class_exists( 'Slim_Catalog_Product_Stats' ) ) Slim_Catalog_Product_Stats::record( $product_id, 'form' );

        wp_send_json_success(['message' => $s['success']]);
    }

	public function cleanup_leads() {
		$days = absint($this->get_settings()['retention_days'] ?? 365);
		if (!$days) return;
		$ids = get_posts(['post_type' => TNStack_Popup_Leads::POST_TYPE, 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => 200, 'date_query' => [['before' => $days . ' days ago']]]);
		foreach ($ids as $id) wp_delete_post($id, true);
	}
}

$GLOBALS['tnstack_popup_form'] = new TNStack_Popup_Form();

/** Rendered by the Toolkit module settings router. */
function tnstack_popup_form_render_admin() {
	$GLOBALS['tnstack_popup_form']->settings_page();
}
