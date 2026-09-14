<?php
/**
 * Product meta boxes and save handlers.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

class Slim_Catalog_Product_Meta {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . Slim_Catalog_Post_Types::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
	}

	public static function add_meta_boxes() {
		add_meta_box(
			'slim-catalog-product-data',
			__( 'Product Data', 'slim-catalog' ),
			array( __CLASS__, 'render_meta_box' ),
			Slim_Catalog_Post_Types::POST_TYPE,
			'normal',
			'high'
		);
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'slim_catalog_save_product', 'slim_catalog_product_nonce' );

		$price             = get_post_meta( $post->ID, '_slim_price', true );
		$sale_price        = get_post_meta( $post->ID, '_slim_sale_price', true );
		$sku               = get_post_meta( $post->ID, '_slim_sku', true );
		$badge             = get_post_meta( $post->ID, '_slim_badge', true );
		$gallery           = (array) get_post_meta( $post->ID, '_slim_gallery', true );
		$short_description = get_post_meta( $post->ID, '_slim_short_description', true );
		$featured          = (bool) get_post_meta( $post->ID, '_slim_featured', true );
		$cta_mode          = get_post_meta( $post->ID, '_slim_cta_mode', true ) ?: 'inherit';
		$cta_label         = get_post_meta( $post->ID, '_slim_cta_label', true );
		$cta_hotline       = get_post_meta( $post->ID, '_slim_cta_hotline', true );
		$cta_url           = get_post_meta( $post->ID, '_slim_cta_url', true );
		$cta_recipient     = get_post_meta( $post->ID, '_slim_cta_recipient', true );
		$cta_form_id       = absint( get_post_meta( $post->ID, '_slim_cta_form_id', true ) );
		$brand             = get_post_meta( $post->ID, '_slim_brand', true );
		$stock_status      = get_post_meta( $post->ID, '_slim_stock_status', true ) ?: 'instock';

		$gallery_string = implode( ',', array_map( 'intval', $gallery ) );
		?>
		<div class="slim-catalog-admin-meta">
			<div class="slim-catalog-admin-meta__grid">
				<p>
					<label for="slim_price"><strong><?php esc_html_e( 'Regular Price', 'slim-catalog' ); ?></strong></label>
					<input type="number" step="0.01" min="0" id="slim_price" name="slim_price" value="<?php echo esc_attr( $price ); ?>" class="widefat" />
				</p>
				<p>
					<label for="slim_sale_price"><strong><?php esc_html_e( 'Sale Price', 'slim-catalog' ); ?></strong></label>
					<input type="number" step="0.01" min="0" id="slim_sale_price" name="slim_sale_price" value="<?php echo esc_attr( $sale_price ); ?>" class="widefat" />
				</p>
				<p>
					<label for="slim_sku"><strong><?php esc_html_e( 'SKU', 'slim-catalog' ); ?></strong></label>
					<input type="text" id="slim_sku" name="slim_sku" value="<?php echo esc_attr( $sku ); ?>" class="widefat" />
				</p>
				<p>
					<label for="slim_badge"><strong><?php esc_html_e( 'Badge', 'slim-catalog' ); ?></strong></label>
					<select id="slim_badge" name="slim_badge" class="widefat">
						<option value=""><?php esc_html_e( 'None', 'slim-catalog' ); ?></option>
						<option value="new" <?php selected( $badge, 'new' ); ?>><?php esc_html_e( 'New', 'slim-catalog' ); ?></option>
						<option value="sale" <?php selected( $badge, 'sale' ); ?>><?php esc_html_e( 'Sale', 'slim-catalog' ); ?></option>
						<option value="hot" <?php selected( $badge, 'hot' ); ?>><?php esc_html_e( 'Hot', 'slim-catalog' ); ?></option>
						<option value="featured" <?php selected( $badge, 'featured' ); ?>><?php esc_html_e( 'Featured', 'slim-catalog' ); ?></option>
					</select>
				</p>
			</div>

			<p>
				<label for="slim_short_description"><strong><?php esc_html_e( 'Short Description', 'slim-catalog' ); ?></strong></label>
				<textarea id="slim_short_description" name="slim_short_description" rows="3" class="widefat"><?php echo esc_textarea( $short_description ); ?></textarea>
			</p>

			<div class="slim-catalog-cta-settings" style="margin:18px 0;padding:18px;border:1px solid #dbe3ef;border-radius:12px;background:#f8fafc">
				<h3 style="margin-top:0">Nút mua hàng riêng</h3>
				<p class="description">Để “Theo cài đặt chung” nếu sản phẩm này không cần hành động riêng.</p>
				<div class="slim-catalog-admin-meta__grid">
					<p><label for="slim_cta_mode"><strong>Hành động</strong></label><select class="widefat" id="slim_cta_mode" name="slim_cta_mode"><option value="inherit" <?php selected( $cta_mode, 'inherit' ); ?>>Theo cài đặt chung</option><option value="hotline" <?php selected( $cta_mode, 'hotline' ); ?>>Hiện hotline</option><option value="form" <?php selected( $cta_mode, 'form' ); ?>>Mở form liên hệ</option><option value="zalo" <?php selected( $cta_mode, 'zalo' ); ?>>Mở Zalo</option><option value="url" <?php selected( $cta_mode, 'url' ); ?>>URL tùy chỉnh</option><option value="hidden" <?php selected( $cta_mode, 'hidden' ); ?>>Ẩn nút</option></select></p>
					<p><label for="slim_cta_label"><strong>Nội dung nút</strong></label><input class="widefat" id="slim_cta_label" name="slim_cta_label" value="<?php echo esc_attr( $cta_label ); ?>" placeholder="Để trống để dùng nội dung chung"></p>
					<p><label for="slim_cta_hotline"><strong>Hotline/Zalo riêng</strong></label><input class="widefat" id="slim_cta_hotline" name="slim_cta_hotline" value="<?php echo esc_attr( $cta_hotline ); ?>" placeholder="0901 234 567"></p>
					<p><label for="slim_cta_url"><strong>URL tùy chỉnh</strong></label><input type="url" class="widefat" id="slim_cta_url" name="slim_cta_url" value="<?php echo esc_attr( $cta_url ); ?>" placeholder="https://..."></p>
				</div>
				<p><label for="slim_cta_recipient"><strong>Email nhận form riêng</strong></label><input type="email" class="widefat" id="slim_cta_recipient" name="slim_cta_recipient" value="<?php echo esc_attr( $cta_recipient ); ?>" placeholder="Để trống để dùng email chung"></p>
				<?php if ( post_type_exists( 'tnstack_form' ) ) : $forms = get_posts( array( 'post_type'=>'tnstack_form','post_status'=>'publish','posts_per_page'=>-1 ) ); ?><p><label for="slim_cta_form_id"><strong>Biểu mẫu sẽ mở</strong></label><select class="widefat" id="slim_cta_form_id" name="slim_cta_form_id"><option value="0">Form mặc định</option><?php foreach($forms as$form): ?><option value="<?php echo absint($form->ID); ?>" <?php selected($cta_form_id,$form->ID); ?>><?php echo esc_html($form->post_title); ?></option><?php endforeach; ?></select></p><?php endif; ?>
			</div>
			<div class="slim-catalog-admin-meta__grid"><p><label for="slim_brand"><strong>Thương hiệu (Schema)</strong></label><input class="widefat" id="slim_brand" name="slim_brand" value="<?php echo esc_attr($brand); ?>"></p><p><label for="slim_stock_status"><strong>Tình trạng kho</strong></label><select class="widefat" id="slim_stock_status" name="slim_stock_status"><option value="instock" <?php selected($stock_status,'instock'); ?>>Còn hàng</option><option value="outofstock" <?php selected($stock_status,'outofstock'); ?>>Hết hàng</option></select></p></div>

			<p>
				<label>
					<input type="checkbox" name="slim_featured" value="1" <?php checked( $featured ); ?> />
					<?php esc_html_e( 'Featured product', 'slim-catalog' ); ?>
				</label>
			</p>

			<div class="slim-catalog-admin-gallery">
				<label><strong><?php esc_html_e( 'Product Gallery', 'slim-catalog' ); ?></strong></label>
				<input type="hidden" id="slim_gallery" name="slim_gallery" value="<?php echo esc_attr( $gallery_string ); ?>" />
				<div id="slim-gallery-preview" class="slim-catalog-admin-gallery__preview">
					<?php foreach ( $gallery as $attachment_id ) : ?>
						<?php echo wp_get_attachment_image( (int) $attachment_id, 'thumbnail' ); ?>
					<?php endforeach; ?>
				</div>
				<p>
					<button type="button" class="button" id="slim-gallery-add"><?php esc_html_e( 'Add Images', 'slim-catalog' ); ?></button>
					<button type="button" class="button" id="slim-gallery-clear"><?php esc_html_e( 'Clear Gallery', 'slim-catalog' ); ?></button>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['slim_catalog_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['slim_catalog_product_nonce'] ) ), 'slim_catalog_save_product' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$price      = isset( $_POST['slim_price'] ) ? sanitize_text_field( wp_unslash( $_POST['slim_price'] ) ) : '';
		$sale_price = isset( $_POST['slim_sale_price'] ) ? sanitize_text_field( wp_unslash( $_POST['slim_sale_price'] ) ) : '';
		$sku        = isset( $_POST['slim_sku'] ) ? sanitize_text_field( wp_unslash( $_POST['slim_sku'] ) ) : '';
		$badge      = isset( $_POST['slim_badge'] ) ? sanitize_key( wp_unslash( $_POST['slim_badge'] ) ) : '';
		$gallery    = isset( $_POST['slim_gallery'] ) ? sanitize_text_field( wp_unslash( $_POST['slim_gallery'] ) ) : '';
		$short_desc = isset( $_POST['slim_short_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['slim_short_description'] ) ) : '';
		$featured   = ! empty( $_POST['slim_featured'] );
		$cta_mode   = sanitize_key( wp_unslash( $_POST['slim_cta_mode'] ?? 'inherit' ) );
		if ( ! in_array( $cta_mode, array( 'inherit', 'hotline', 'form', 'zalo', 'url', 'hidden' ), true ) ) $cta_mode = 'inherit';

		$gallery_ids = array_filter( array_map( 'intval', explode( ',', $gallery ) ) );

		update_post_meta( $post_id, '_slim_price', $price );
		update_post_meta( $post_id, '_slim_sale_price', $sale_price );
		update_post_meta( $post_id, '_slim_sku', $sku );
		update_post_meta( $post_id, '_slim_badge', $badge );
		update_post_meta( $post_id, '_slim_gallery', $gallery_ids );
		update_post_meta( $post_id, '_slim_short_description', $short_desc );
		update_post_meta( $post_id, '_slim_featured', $featured ? '1' : '' );
		update_post_meta( $post_id, '_slim_cta_mode', $cta_mode );
		update_post_meta( $post_id, '_slim_cta_label', sanitize_text_field( wp_unslash( $_POST['slim_cta_label'] ?? '' ) ) );
		update_post_meta( $post_id, '_slim_cta_hotline', sanitize_text_field( wp_unslash( $_POST['slim_cta_hotline'] ?? '' ) ) );
		update_post_meta( $post_id, '_slim_cta_url', esc_url_raw( wp_unslash( $_POST['slim_cta_url'] ?? '' ) ) );
		update_post_meta( $post_id, '_slim_cta_recipient', sanitize_email( wp_unslash( $_POST['slim_cta_recipient'] ?? '' ) ) );
		update_post_meta( $post_id, '_slim_cta_form_id', absint( $_POST['slim_cta_form_id'] ?? 0 ) );
		update_post_meta( $post_id, '_slim_brand', sanitize_text_field( wp_unslash( $_POST['slim_brand'] ?? '' ) ) );
		update_post_meta( $post_id, '_slim_stock_status', ( $_POST['slim_stock_status'] ?? '' ) === 'outofstock' ? 'outofstock' : 'instock' );
	}
}
