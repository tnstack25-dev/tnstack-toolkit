<?php
/**
 * Product card template.
 *
 * @package SlimCatalog
 * @var Slim_Catalog_Product $product
 * @var string               $style
 */

defined( 'ABSPATH' ) || exit;

$settings = slim_catalog_get_settings();
$badge    = $product->get_badge();
$cta_url  = slim_catalog_get_cta_url( $product );
$image_id = $product->get_image_id();
?>
<article class="sc-card sc-card--<?php echo esc_attr( $style ?? 'card' ); ?>">
	<a class="sc-card__media" href="<?php echo esc_url( $product->get_permalink() ); ?>">
		<?php if ( $image_id ) : ?>
			<?php echo wp_get_attachment_image( $image_id, 'large', false, array( 'class' => 'sc-card__image', 'loading' => 'lazy' ) ); ?>
		<?php else : ?>
			<div class="sc-card__placeholder" aria-hidden="true"></div>
		<?php endif; ?>

		<?php if ( $badge ) : ?>
			<span class="sc-badge sc-badge--<?php echo esc_attr( $badge ); ?>"><?php echo esc_html( slim_catalog_badge_label( $badge ) ); ?></span>
		<?php elseif ( $product->is_on_sale() ) : ?>
			<span class="sc-badge sc-badge--sale"><?php esc_html_e( 'Sale', 'slim-catalog' ); ?></span>
		<?php endif; ?>
	</a>

	<div class="sc-card__body">
		<?php $categories = $product->get_categories(); ?>
		<?php if ( ! empty( $categories ) ) : ?>
			<div class="sc-card__meta">
				<?php foreach ( array_slice( $categories, 0, 2 ) as $category ) : ?>
					<a class="sc-card__category" href="<?php echo esc_url( get_term_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<h3 class="sc-card__title">
			<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_title() ); ?></a>
		</h3>

		<?php if ( $product->get_excerpt() ) : ?>
			<p class="sc-card__excerpt"><?php echo esc_html( wp_trim_words( $product->get_excerpt(), 18 ) ); ?></p>
		<?php endif; ?>

		<div class="sc-card__footer">
			<div class="sc-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
			<a class="sc-button" href="<?php echo esc_url( $cta_url ); ?>">
				<?php echo esc_html( $settings['cta_label'] ); ?>
			</a>
		</div>
	</div>
</article>