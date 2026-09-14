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
$image_id = $product->get_image_id();
$style = $style ?? 'default'; $image_ratio=$image_ratio??'square'; $show_description=$show_description??true; $show_price=$show_price??true; $show_category=$show_category??true; $show_badge=$show_badge??true; $show_button=$show_button??true; $title_lines=$title_lines??2; $description_lines=$description_lines??3; $radius=$radius??12;
?>
<article class="sc-card sc-card--<?php echo esc_attr( $style ); ?> sc-card--ratio-<?php echo esc_attr($image_ratio); ?>" style="--sc-card-radius:<?php echo absint($radius); ?>px;--sc-title-lines:<?php echo absint($title_lines); ?>;--sc-description-lines:<?php echo absint($description_lines); ?>">
	<a class="sc-card__media" href="<?php echo esc_url( $product->get_permalink() ); ?>">
		<?php if ( $image_id ) : ?>
			<?php echo wp_get_attachment_image( $image_id, 'large', false, array( 'class' => 'sc-card__image', 'loading' => 'lazy' ) ); ?>
		<?php else : ?>
			<div class="sc-card__placeholder" aria-hidden="true"></div>
		<?php endif; ?>

		<?php if ( $show_badge && $badge ) : ?>
			<span class="sc-badge sc-badge--<?php echo esc_attr( $badge ); ?>"><?php echo esc_html( slim_catalog_badge_label( $badge ) ); ?></span>
		<?php elseif ( $show_badge && $product->is_on_sale() ) : ?>
			<span class="sc-badge sc-badge--sale"><?php esc_html_e( 'Sale', 'slim-catalog' ); ?></span>
		<?php endif; ?>
	</a>

	<div class="sc-card__body">
		<?php $categories = $product->get_categories(); ?>
		<?php if ( $show_category && ! empty( $categories ) ) : ?>
			<div class="sc-card__meta">
				<?php foreach ( array_slice( $categories, 0, 2 ) as $category ) : ?>
					<a class="sc-card__category" href="<?php echo esc_url( get_term_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<h3 class="sc-card__title">
			<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_title() ); ?></a>
		</h3>

		<?php if ( $show_description && $product->get_excerpt() ) : ?>
			<p class="sc-card__excerpt"><?php echo esc_html( wp_trim_words( $product->get_excerpt(), 18 ) ); ?></p>
		<?php endif; ?>

		<div class="sc-card__footer">
			<?php if($show_price): ?><div class="sc-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div><?php endif; ?>
			<?php if($show_button) echo wp_kses_post( slim_catalog_render_cta( $product ) ); ?>
		</div>
	</div>
</article>
