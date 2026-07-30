<?php
/**
 * Product detail template.
 *
 * @package SlimCatalog
 * @var Slim_Catalog_Product $product
 */

defined( 'ABSPATH' ) || exit;

$settings   = slim_catalog_get_settings();
$gallery    = $product->get_gallery_ids();
$badge      = $product->get_badge();
$cta_url    = slim_catalog_get_cta_url( $product );
$categories = $product->get_categories();
?>
<article class="sc-detail" data-sc-product-detail data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
	<div class="sc-detail__gallery" data-sc-gallery>
		<div class="sc-detail__main-image">
			<?php if ( ! empty( $gallery ) ) : ?>
				<?php echo wp_get_attachment_image( $gallery[0], 'large', false, array( 'class' => 'sc-detail__image sc-detail__image--active', 'data-sc-main-image' => '' ) ); ?>
			<?php else : ?>
				<div class="sc-detail__placeholder" aria-hidden="true"></div>
			<?php endif; ?>

			<?php if ( $badge ) : ?>
				<span class="sc-badge sc-badge--<?php echo esc_attr( $badge ); ?>"><?php echo esc_html( slim_catalog_badge_label( $badge ) ); ?></span>
			<?php elseif ( $product->is_on_sale() ) : ?>
				<span class="sc-badge sc-badge--sale"><?php esc_html_e( 'Sale', 'slim-catalog' ); ?></span>
			<?php endif; ?>

			<?php if ( count( $gallery ) > 1 ) : ?>
				<button type="button" class="sc-gallery__nav sc-gallery__nav--prev" data-sc-gallery-prev aria-label="<?php esc_attr_e( 'Previous image', 'slim-catalog' ); ?>">
					<span aria-hidden="true">&#8249;</span>
				</button>
				<button type="button" class="sc-gallery__nav sc-gallery__nav--next" data-sc-gallery-next aria-label="<?php esc_attr_e( 'Next image', 'slim-catalog' ); ?>">
					<span aria-hidden="true">&#8250;</span>
				</button>
			<?php endif; ?>
		</div>

		<?php if ( count( $gallery ) > 1 ) : ?>
			<div class="sc-detail__thumbs" data-sc-gallery-thumbs>
				<?php foreach ( $gallery as $index => $attachment_id ) : ?>
					<?php
					$full_image = wp_get_attachment_image_src( $attachment_id, 'large' );
					$full_src   = $full_image ? $full_image[0] : '';
					$full_set   = wp_get_attachment_image_srcset( $attachment_id, 'large' );
					$image_alt  = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
					?>
					<button type="button" class="sc-detail__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-sc-thumb data-image-id="<?php echo esc_attr( $attachment_id ); ?>" data-image-src="<?php echo esc_url( $full_src ); ?>" data-image-srcset="<?php echo esc_attr( $full_set ?: '' ); ?>" data-image-alt="<?php echo esc_attr( $image_alt ); ?>" aria-label="<?php esc_attr_e( 'View image', 'slim-catalog' ); ?>" aria-current="<?php echo 0 === $index ? 'true' : 'false'; ?>">
						<?php echo wp_get_attachment_image( $attachment_id, 'thumbnail' ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="sc-detail__summary">
		<?php if ( ! empty( $categories ) ) : ?>
			<div class="sc-detail__categories">
				<?php foreach ( $categories as $category ) : ?>
					<a class="sc-detail__category" href="<?php echo esc_url( get_term_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<h1 class="sc-detail__title"><?php echo esc_html( $product->get_title() ); ?></h1>

		<p class="sc-detail__sku" data-sc-product-sku <?php echo $product->get_sku() ? '' : 'hidden'; ?>>
			<?php esc_html_e( 'SKU:', 'slim-catalog' ); ?>
			<span data-sc-product-sku-value><?php echo esc_html( $product->get_sku() ); ?></span>
		</p>

		<div class="sc-detail__price" data-sc-product-price><?php echo wp_kses_post( $product->get_price_html() ); ?></div>

		<?php slim_catalog_get_template( 'product-variations', array( 'product' => $product ) ); ?>

		<?php if ( $product->get_excerpt() ) : ?>
			<div class="sc-detail__excerpt"><?php echo wp_kses_post( wpautop( $product->get_excerpt() ) ); ?></div>
		<?php endif; ?>

		<div class="sc-detail__actions">
			<a class="sc-button" href="<?php echo esc_url( $cta_url ); ?>">
				<?php echo esc_html( $settings['cta_label'] ); ?>
			</a>
		</div>

		<?php slim_catalog_get_template( 'contact-info' ); ?>
	</div>

	<?php if ( $product->get_content() ) : ?>
		<div class="sc-detail__content">
			<h2><?php esc_html_e( 'Description', 'slim-catalog' ); ?></h2>
			<?php echo wp_kses_post( $product->get_content() ); ?>
		</div>
	<?php endif; ?>
</article>
