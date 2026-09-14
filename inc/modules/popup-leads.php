<?php
/** Lead inbox for Popup Form submissions. */

defined( 'ABSPATH' ) || exit;

final class TNStack_Popup_Leads {
	const POST_TYPE = 'tnstack_lead';
	const STATUS_META = '_tnstack_lead_status';

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ), 6 );
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 28 );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'status_filter' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_query' ) );
		add_action( 'admin_post_tnstack_export_leads', array( __CLASS__, 'export_csv' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'privacy_exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'privacy_erasers' ) );
	}

	public static function register_post_type() {
		register_post_type( self::POST_TYPE, array(
			'labels' => array( 'name' => 'Yêu cầu liên hệ', 'singular_name' => 'Yêu cầu liên hệ', 'edit_item' => 'Xử lý yêu cầu', 'search_items' => 'Tìm yêu cầu', 'not_found' => 'Chưa có yêu cầu nào' ),
			'public' => false, 'show_ui' => true, 'show_in_menu' => false, 'supports' => array( 'title' ),
			'capability_type' => 'post', 'map_meta_cap' => true,
		) );
	}

	public static function register_menu() {
		add_submenu_page( TNStack_Toolkit_Features_Dashboard::PAGE_SLUG, 'Yêu cầu liên hệ', 'Yêu cầu liên hệ', 'manage_options', 'edit.php?post_type=' . self::POST_TYPE );
	}

	public static function create( $values, $product_id = 0, $source_url = '' ) {
		$name  = sanitize_text_field( $values['name'] ?? '' );
		$phone = sanitize_text_field( $values['phone'] ?? '' );
		$email = sanitize_email( $values['email'] ?? '' );
		$title = $name ?: ( $phone ?: ( $email ?: 'Khách hàng mới' ) );
		$id = wp_insert_post( array( 'post_type' => self::POST_TYPE, 'post_status' => 'publish', 'post_title' => $title ) );
		if ( is_wp_error( $id ) || ! $id ) return 0;
		update_post_meta( $id, self::STATUS_META, 'new' );
		update_post_meta( $id, '_tnstack_lead_fields', array_map( 'sanitize_textarea_field', $values ) );
		update_post_meta( $id, '_tnstack_lead_product_id', absint( $product_id ) );
		update_post_meta( $id, '_tnstack_lead_source_url', esc_url_raw( $source_url ) );
		$client_ip = function_exists( 'tnstack_core_security_client_ip' ) ? tnstack_core_security_client_ip() : sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
		update_post_meta( $id, '_tnstack_lead_ip_hash', hash( 'sha256', $client_ip . wp_salt( 'nonce' ) ) );
		do_action( 'tnstack_lead_created', (int) $id, array( 'fields'=>$values, 'product_id'=>absint($product_id), 'source_url'=>esc_url_raw($source_url) ) );
		return (int) $id;
	}

	public static function add_meta_boxes() {
		add_meta_box( 'tnstack-lead-details', 'Thông tin yêu cầu', array( __CLASS__, 'render_details' ), self::POST_TYPE, 'normal', 'high' );
	}

	public static function render_details( $post ) {
		wp_nonce_field( 'tnstack_save_lead', 'tnstack_lead_nonce' );
		$fields = (array) get_post_meta( $post->ID, '_tnstack_lead_fields', true );
		$status = get_post_meta( $post->ID, self::STATUS_META, true ) ?: 'new';
		$notes = get_post_meta( $post->ID, '_tnstack_lead_notes', true );
		$product_id = absint( get_post_meta( $post->ID, '_tnstack_lead_product_id', true ) );
		?>
		<div class="tnstack-lead-grid">
			<section><h3>Thông tin khách hàng</h3><dl><?php foreach ( $fields as $label => $value ) : ?><dt><?php echo esc_html( ucwords( str_replace( '_', ' ', $label ) ) ); ?></dt><dd><?php echo nl2br( esc_html( $value ) ); ?></dd><?php endforeach; ?></dl></section>
			<section><h3>Xử lý</h3><p><label>Trạng thái<select class="widefat" name="tnstack_lead_status"><?php foreach ( self::statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label></p><p><label>Ghi chú nội bộ<textarea class="widefat" rows="8" name="tnstack_lead_notes"><?php echo esc_textarea( $notes ); ?></textarea></label></p></section>
		</div><?php if ( $product_id ) : ?><p><strong>Sản phẩm:</strong> <a href="<?php echo esc_url( get_edit_post_link( $product_id ) ); ?>"><?php echo esc_html( get_the_title( $product_id ) ); ?></a></p><?php endif; ?><?php $source = get_post_meta( $post->ID, '_tnstack_lead_source_url', true ); if ( $source ) : ?><p><strong>Nguồn:</strong> <a href="<?php echo esc_url( $source ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $source ); ?></a></p><?php endif; ?>
		<style>.tnstack-lead-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}.tnstack-lead-grid section{padding:18px;border:1px solid #dbe3ef;border-radius:12px}.tnstack-lead-grid h3{margin-top:0}.tnstack-lead-grid dl{display:grid;grid-template-columns:140px 1fr;gap:10px;margin:0}.tnstack-lead-grid dt{font-weight:600}.tnstack-lead-grid dd{margin:0}@media(max-width:782px){.tnstack-lead-grid{grid-template-columns:1fr}}</style>
		<?php
	}

	public static function save( $post_id ) {
		if ( ! isset( $_POST['tnstack_lead_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tnstack_lead_nonce'] ) ), 'tnstack_save_lead' ) || ! current_user_can( 'edit_post', $post_id ) ) return;
		$status = sanitize_key( wp_unslash( $_POST['tnstack_lead_status'] ?? 'new' ) );
		if ( ! isset( self::statuses()[ $status ] ) ) $status = 'new';
		update_post_meta( $post_id, self::STATUS_META, $status );
		update_post_meta( $post_id, '_tnstack_lead_notes', sanitize_textarea_field( wp_unslash( $_POST['tnstack_lead_notes'] ?? '' ) ) );
	}

	public static function statuses() { return array( 'new' => 'Mới', 'processing' => 'Đang xử lý', 'contacted' => 'Đã liên hệ', 'completed' => 'Hoàn tất' ); }
	public static function columns( $columns ) { return array( 'cb' => $columns['cb'], 'title' => 'Khách hàng', 'lead_contact' => 'Liên hệ', 'lead_product' => 'Sản phẩm', 'lead_status' => 'Trạng thái', 'date' => 'Thời gian' ); }
	public static function column( $column, $post_id ) {
		$fields = (array) get_post_meta( $post_id, '_tnstack_lead_fields', true );
		if ( 'lead_contact' === $column ) echo esc_html( implode( ' · ', array_filter( array( $fields['phone'] ?? '', $fields['email'] ?? '' ) ) ) );
		if ( 'lead_product' === $column ) { $id = absint( get_post_meta( $post_id, '_tnstack_lead_product_id', true ) ); echo $id ? esc_html( get_the_title( $id ) ) : '—'; }
		if ( 'lead_status' === $column ) { $status = get_post_meta( $post_id, self::STATUS_META, true ) ?: 'new'; echo esc_html( self::statuses()[ $status ] ?? $status ); }
	}

	public static function status_filter( $post_type ) {
		if ( self::POST_TYPE !== $post_type ) return;
		$current = sanitize_key( wp_unslash( $_GET['lead_status'] ?? '' ) );
		echo '<select name="lead_status"><option value="">Tất cả trạng thái</option>';
		foreach ( self::statuses() as $key => $label ) echo '<option value="' . esc_attr( $key ) . '" ' . selected( $current, $key, false ) . '>' . esc_html( $label ) . '</option>';
		echo '</select> <a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tnstack_export_leads' ), 'tnstack_export_leads' ) ) . '">Xuất CSV</a>';
	}
	public static function filter_query( $query ) { if ( is_admin() && $query->is_main_query() && self::POST_TYPE === $query->get( 'post_type' ) && ! empty( $_GET['lead_status'] ) ) $query->set( 'meta_query', array( array( 'key' => self::STATUS_META, 'value' => sanitize_key( wp_unslash( $_GET['lead_status'] ) ) ) ) ); }

	public static function export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Bạn không có quyền.' );
		check_admin_referer( 'tnstack_export_leads' );
		$posts = get_posts( array( 'post_type' => self::POST_TYPE, 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC' ) );
		nocache_headers(); header( 'Content-Type: text/csv; charset=UTF-8' ); header( 'Content-Disposition: attachment; filename="tnstack-leads-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' ); fwrite( $out, "\xEF\xBB\xBF" ); fputcsv( $out, array( 'Thời gian', 'Tên', 'Điện thoại', 'Email', 'Nội dung', 'Sản phẩm', 'Trạng thái' ) );
		foreach ( $posts as $post ) { $f = (array) get_post_meta( $post->ID, '_tnstack_lead_fields', true ); $pid = absint( get_post_meta( $post->ID, '_tnstack_lead_product_id', true ) ); $status = get_post_meta( $post->ID, self::STATUS_META, true ); fputcsv( $out, array( $post->post_date, $f['name'] ?? '', $f['phone'] ?? '', $f['email'] ?? '', $f['message'] ?? '', $pid ? get_the_title( $pid ) : '', self::statuses()[ $status ] ?? $status ) ); }
		fclose( $out ); exit;
	}

	public static function privacy_exporters( $exporters ) { $exporters['tnstack-popup-leads'] = array( 'exporter_friendly_name' => 'TNStack Popup Leads', 'callback' => array( __CLASS__, 'privacy_export' ) ); return $exporters; }
	public static function privacy_export( $email, $page = 1 ) { $items = array(); $query = new WP_Query( array( 'post_type' => self::POST_TYPE, 'posts_per_page' => 100, 'paged' => $page, 'meta_query' => array( array( 'key' => '_tnstack_lead_fields', 'value' => $email, 'compare' => 'LIKE' ) ) ) ); foreach ( $query->posts as $post ) { $fields = (array) get_post_meta( $post->ID, '_tnstack_lead_fields', true ); if ( sanitize_email( $fields['email'] ?? '' ) !== sanitize_email( $email ) ) continue; $data = array(); foreach ( $fields as $key => $value ) $data[] = array( 'name' => $key, 'value' => $value ); $items[] = array( 'group_id' => 'tnstack-popup-leads', 'group_label' => 'Yêu cầu liên hệ', 'item_id' => 'lead-' . $post->ID, 'data' => $data ); } return array( 'data' => $items, 'done' => $query->max_num_pages <= $page ); }
	public static function privacy_erasers( $erasers ) { $erasers['tnstack-popup-leads'] = array( 'eraser_friendly_name' => 'TNStack Popup Leads', 'callback' => array( __CLASS__, 'privacy_erase' ) ); return $erasers; }
	public static function privacy_erase( $email, $page = 1 ) { $query = new WP_Query( array( 'post_type' => self::POST_TYPE, 'posts_per_page' => 100, 'paged' => $page, 'meta_query' => array( array( 'key' => '_tnstack_lead_fields', 'value' => $email, 'compare' => 'LIKE' ) ) ) ); $removed = false; foreach ( $query->posts as $post ) { $fields = (array) get_post_meta( $post->ID, '_tnstack_lead_fields', true ); if ( sanitize_email( $fields['email'] ?? '' ) === sanitize_email( $email ) ) { wp_delete_post( $post->ID, true ); $removed = true; } } return array( 'items_removed' => $removed, 'items_retained' => false, 'messages' => array(), 'done' => $query->max_num_pages <= $page ); }
}

TNStack_Popup_Leads::boot();
