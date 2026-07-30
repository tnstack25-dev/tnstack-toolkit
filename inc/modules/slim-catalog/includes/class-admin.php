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
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'manage_' . Slim_Catalog_Post_Types::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . Slim_Catalog_Post_Types::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
	}

	public static function register_menu() {
		add_submenu_page(
			'edit.php?post_type=' . Slim_Catalog_Post_Types::POST_TYPE,
			__( 'Settings', 'slim-catalog' ),
			__( 'Settings', 'slim-catalog' ),
			'manage_options',
			'slim-catalog-settings',
			array( __CLASS__, 'render_settings_page' )
		);
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
			'hotline'           => sanitize_text_field( $input['hotline'] ?? '' ),
			'zalo'              => sanitize_text_field( $input['zalo'] ?? '' ),
			'email'             => sanitize_email( $input['email'] ?? '' ),
			'address'           => sanitize_textarea_field( $input['address'] ?? '' ),
		);
	}

	public static function render_settings_page() {
		$settings = slim_catalog_get_settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Slim Catalog Settings', 'slim-catalog' ); ?></h1>
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

				<?php submit_button(); ?>
				<p class="description"><?php esc_html_e( 'After changing slugs, visit Settings → Permalinks and click Save to refresh rewrite rules.', 'slim-catalog' ); ?></p>
			</form>

			<hr />
			<h2><?php esc_html_e( 'Shortcodes', 'slim-catalog' ); ?></h2>
			<ul style="list-style:disc;padding-left:1.5rem;">
				<li><code>[ux_slim_products]</code> — <?php esc_html_e( 'Products (slider/row)', 'slim-catalog' ); ?></li>
				<li><code>[ux_slim_featured_products]</code> — <?php esc_html_e( 'Featured products', 'slim-catalog' ); ?></li>
				<li><code>[ux_slim_products_list]</code> — <?php esc_html_e( 'Products list', 'slim-catalog' ); ?></li>
				<li><code>[ux_slim_product_categories]</code> — <?php esc_html_e( 'Product categories grid', 'slim-catalog' ); ?></li>
				<li><code>[slim_products]</code> — <?php esc_html_e( 'Simple product grid shortcode', 'slim-catalog' ); ?></li>
			</ul>
			<h2><?php esc_html_e( 'UX Builder Elements', 'slim-catalog' ); ?></h2>
			<ul style="list-style:disc;padding-left:1.5rem;">
				<li><?php esc_html_e( 'Products', 'slim-catalog' ); ?></li>
				<li><?php esc_html_e( 'Featured Products', 'slim-catalog' ); ?></li>
				<li><?php esc_html_e( 'Latest Products', 'slim-catalog' ); ?></li>
				<li><?php esc_html_e( 'Products List', 'slim-catalog' ); ?></li>
				<li><?php esc_html_e( 'Product Categories', 'slim-catalog' ); ?></li>
				<li><?php esc_html_e( 'All Products', 'slim-catalog' ); ?></li>
				<li><?php esc_html_e( 'Product Gallery', 'slim-catalog' ); ?></li>
				<li><?php esc_html_e( 'Product Title', 'slim-catalog' ); ?></li>
				<li><?php esc_html_e( 'Product Price', 'slim-catalog' ); ?></li>
				<li><?php esc_html_e( 'Product Short Description', 'slim-catalog' ); ?></li>
				<li><?php esc_html_e( 'Product Contact Button', 'slim-catalog' ); ?></li>
				<li><?php esc_html_e( 'Product Description', 'slim-catalog' ); ?></li>
				<li><?php esc_html_e( 'Product Variations', 'slim-catalog' ); ?></li>
				<li><?php esc_html_e( 'Related Products', 'slim-catalog' ); ?></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
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
