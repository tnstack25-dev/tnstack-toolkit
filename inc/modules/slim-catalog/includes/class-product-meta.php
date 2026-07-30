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

		$gallery_ids = array_filter( array_map( 'intval', explode( ',', $gallery ) ) );

		update_post_meta( $post_id, '_slim_price', $price );
		update_post_meta( $post_id, '_slim_sale_price', $sale_price );
		update_post_meta( $post_id, '_slim_sku', $sku );
		update_post_meta( $post_id, '_slim_badge', $badge );
		update_post_meta( $post_id, '_slim_gallery', $gallery_ids );
		update_post_meta( $post_id, '_slim_short_description', $short_desc );
		update_post_meta( $post_id, '_slim_featured', $featured ? '1' : '' );
	}
}