<?php
/**
 * Product gallery partial for UX Builder.
 *
 * @package SlimCatalog
 * @var Slim_Catalog_Product $product
 */

defined( 'ABSPATH' ) || exit;

$gallery = $product->get_gallery_ids();
$badge   = $product->get_badge();
?>
<div class="sc-detail__gallery sc-product-gallery" data-sc-gallery data-sc-product-detail data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
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
