<?php
/**
 * Product variations admin meta box.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

class Slim_Catalog_Product_Variations_Meta {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . Slim_Catalog_Post_Types::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
	}

	public static function add_meta_boxes() {
		add_meta_box(
			'slim-catalog-product-variations',
			__( 'Product Variations', 'slim-catalog' ),
			array( __CLASS__, 'render_meta_box' ),
			Slim_Catalog_Post_Types::POST_TYPE,
			'normal',
			'default'
		);
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'slim_catalog_save_variations', 'slim_catalog_variations_nonce' );

		$product_type = Slim_Catalog_Product_Variations::get_product_type( $post->ID );
		$attributes   = Slim_Catalog_Product_Variations::get_attributes( $post->ID );
		$variations   = get_post_meta( $post->ID, Slim_Catalog_Product_Variations::META_VARIATIONS, true );
		$variations   = is_array( $variations ) ? $variations : array();

		$attributes_json = wp_json_encode( $attributes );
		$variations_json = wp_json_encode( $variations );
		?>
		<div class="slim-catalog-variations" data-slim-variations>
			<p>
				<label for="slim_product_type"><strong><?php esc_html_e( 'Product Type', 'slim-catalog' ); ?></strong></label>
				<select id="slim_product_type" name="slim_product_type" class="widefat">
					<option value="simple" <?php selected( $product_type, 'simple' ); ?>><?php esc_html_e( 'Simple product', 'slim-catalog' ); ?></option>
					<option value="variable" <?php selected( $product_type, 'variable' ); ?>><?php esc_html_e( 'Variable product', 'slim-catalog' ); ?></option>
				</select>
			</p>

			<div class="slim-catalog-variations__panel" data-slim-variable-panel <?php echo 'variable' === $product_type ? '' : 'hidden'; ?>>
				<h4><?php esc_html_e( 'Attributes', 'slim-catalog' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Add attributes like Size or Color, then enter options separated by commas.', 'slim-catalog' ); ?></p>
				<div class="slim-catalog-variations__attributes" data-slim-attributes-list></div>
				<p><button type="button" class="button" data-slim-add-attribute><?php esc_html_e( 'Add attribute', 'slim-catalog' ); ?></button></p>

				<h4><?php esc_html_e( 'Variations', 'slim-catalog' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Create one row per variation with its own price, SKU, and optional image.', 'slim-catalog' ); ?></p>
				<div class="slim-catalog-variations__table-wrap">
					<table class="widefat striped slim-catalog-variations__table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Attributes', 'slim-catalog' ); ?></th>
								<th><?php esc_html_e( 'Price', 'slim-catalog' ); ?></th>
								<th><?php esc_html_e( 'Sale', 'slim-catalog' ); ?></th>
								<th><?php esc_html_e( 'SKU', 'slim-catalog' ); ?></th>
								<th><?php esc_html_e( 'Image', 'slim-catalog' ); ?></th>
								<th><?php esc_html_e( 'Enabled', 'slim-catalog' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody data-slim-variations-list></tbody>
					</table>
				</div>
				<p>
					<button type="button" class="button" data-slim-add-variation><?php esc_html_e( 'Add variation', 'slim-catalog' ); ?></button>
					<button type="button" class="button" data-slim-generate-variations><?php esc_html_e( 'Generate all combinations', 'slim-catalog' ); ?></button>
				</p>
			</div>

			<input type="hidden" name="slim_attributes_json" id="slim_attributes_json" value="<?php echo esc_attr( $attributes_json ); ?>" />
			<input type="hidden" name="slim_variations_json" id="slim_variations_json" value="<?php echo esc_attr( $variations_json ); ?>" />
		</div>
		<?php
	}

	/**
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['slim_catalog_variations_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['slim_catalog_variations_nonce'] ) ), 'slim_catalog_save_variations' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$product_type = isset( $_POST['slim_product_type'] ) && 'variable' === $_POST['slim_product_type'] ? 'variable' : 'simple';

		update_post_meta( $post_id, Slim_Catalog_Product_Variations::META_TYPE, $product_type );

		$attributes = array();
		$variations = array();

		if ( isset( $_POST['slim_attributes_json'] ) ) {
			$decoded = json_decode( wp_unslash( $_POST['slim_attributes_json'] ), true );
			$attributes = Slim_Catalog_Product_Variations::normalize_attributes( is_array( $decoded ) ? $decoded : array() );
		}

		if ( isset( $_POST['slim_variations_json'] ) ) {
			$decoded = json_decode( wp_unslash( $_POST['slim_variations_json'] ), true );
			$variations = is_array( $decoded ) ? $decoded : array();
		}

		update_post_meta( $post_id, Slim_Catalog_Product_Variations::META_ATTRIBUTES, $attributes );
		update_post_meta( $post_id, Slim_Catalog_Product_Variations::META_VARIATIONS, Slim_Catalog_Product_Variations::normalize_variations( $variations ) );
	}
}