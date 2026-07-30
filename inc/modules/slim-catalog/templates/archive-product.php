<?php
/**
 * Product archive template.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

get_header();

$is_tax   = is_tax( Slim_Catalog_Post_Types::TAXONOMY );
$term     = $is_tax ? get_queried_object() : null;
$settings = slim_catalog_get_settings();
?>
<main class="sc-page sc-page--archive">
	<div class="container sc-container">
		<header class="sc-page__header">
			<?php if ( $is_tax && $term instanceof WP_Term ) : ?>
				<p class="sc-page__eyebrow"><?php esc_html_e( 'Category', 'slim-catalog' ); ?></p>
				<h1 class="sc-page__title"><?php echo esc_html( $term->name ); ?></h1>
				<?php if ( $term->description ) : ?>
					<div class="sc-page__intro"><?php echo wp_kses_post( wpautop( $term->description ) ); ?></div>
				<?php endif; ?>
			<?php else : ?>
				<p class="sc-page__eyebrow"><?php esc_html_e( 'Catalog', 'slim-catalog' ); ?></p>
				<h1 class="sc-page__title"><?php esc_html_e( 'Products', 'slim-catalog' ); ?></h1>
				<p class="sc-page__intro"><?php esc_html_e( 'Browse our curated collection.', 'slim-catalog' ); ?></p>
			<?php endif; ?>
		</header>

		<?php echo do_shortcode( '[slim_product_categories]' ); ?>

		<?php if ( have_posts() ) : ?>
			<div class="sc-grid sc-grid--archive">
				<?php
				while ( have_posts() ) :
					the_post();
					$product = Slim_Catalog_Product::get( get_the_ID() );

					if ( $product ) {
						slim_catalog_product_card( $product );
					}
				endwhile;
				?>
			</div>

			<nav class="sc-pagination">
				<?php
				the_posts_pagination(
					array(
						'mid_size'  => 2,
						'prev_text' => '&larr;',
						'next_text' => '&rarr;',
					)
				);
				?>
			</nav>
		<?php else : ?>
			<p class="sc-empty"><?php esc_html_e( 'No products found.', 'slim-catalog' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();