<?php
/**
 * Product variation selectors.
 *
 * @package SlimCatalog
 * @var Slim_Catalog_Product $product
 */

defined( 'ABSPATH' ) || exit;

if ( ! $product->is_variable() ) {
	return;
}

$payload = $product->get_variations_payload();

if ( empty( $payload['attributes'] ) || empty( $payload['variations'] ) ) {
	return;
}
?>
<div class="sc-variations" data-sc-variations data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
	<script type="application/json" data-sc-variations-data><?php echo wp_json_encode( $payload ); ?></script>

	<?php foreach ( $payload['attributes'] as $attribute ) : ?>
		<div class="sc-variations__group">
			<label class="sc-variations__label"><?php echo esc_html( $attribute['name'] ); ?></label>
			<div class="sc-variations__options" role="group" aria-label="<?php echo esc_attr( $attribute['name'] ); ?>">
				<?php foreach ( $attribute['options'] as $option ) : ?>
					<button
						type="button"
						class="sc-variations__option"
						data-sc-variation-option
						data-attribute="<?php echo esc_attr( $attribute['slug'] ); ?>"
						data-value="<?php echo esc_attr( $option ); ?>"
					><?php echo esc_html( $option ); ?></button>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>

	<p class="sc-variations__notice" data-sc-variation-notice hidden><?php esc_html_e( 'This combination is not available.', 'slim-catalog' ); ?></p>
</div>