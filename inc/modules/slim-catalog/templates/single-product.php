<?php
/**
 * Single product template.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

get_header();

$product = Slim_Catalog_Product::get( get_the_ID() );
?>
<main class="sc-page sc-page--single">
	<div class="container sc-container">
		<?php if ( $product ) : ?>
			<?php slim_catalog_get_template( 'product-detail', array( 'product' => $product ) ); ?>

			<?php
			$categories = wp_list_pluck( $product->get_categories(), 'term_id' );
			$related    = slim_catalog_get_products_query(
				array(
					'posts_per_page' => 4,
					'category'       => ! empty( $categories ) ? $categories[0] : '',
					'post__not_in'   => array( $product->get_id() ),
				)
			);
			?>
			<?php if ( $related->have_posts() ) : ?>
				<section class="sc-related">
					<h2 class="sc-related__title"><?php esc_html_e( 'You may also like', 'slim-catalog' ); ?></h2>
					<div class="sc-grid sc-grid--related">
						<?php
						while ( $related->have_posts() ) :
							$related->the_post();
							$related_product = Slim_Catalog_Product::get( get_the_ID() );

							if ( $related_product ) {
								slim_catalog_product_card( $related_product );
							}
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</section>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();