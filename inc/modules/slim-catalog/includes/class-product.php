<?php
/**
 * Product data model.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

class Slim_Catalog_Product {

	/** @var WP_Post */
	private $post;

	/** @var array<string, mixed> */
	private $meta = array();

	/**
	 * @param int|WP_Post $product Product ID or post object.
	 */
	public function __construct( $product ) {
		$this->post = get_post( $product );

		if ( ! $this->post || Slim_Catalog_Post_Types::POST_TYPE !== $this->post->post_type ) {
			$this->post = null;
			return;
		}

		$this->meta = array(
			'price'             => get_post_meta( $this->post->ID, '_slim_price', true ),
			'sale_price'        => get_post_meta( $this->post->ID, '_slim_sale_price', true ),
			'sku'               => get_post_meta( $this->post->ID, '_slim_sku', true ),
			'badge'             => get_post_meta( $this->post->ID, '_slim_badge', true ),
			'gallery'           => (array) get_post_meta( $this->post->ID, '_slim_gallery', true ),
			'short_description' => get_post_meta( $this->post->ID, '_slim_short_description', true ),
			'featured'          => (bool) get_post_meta( $this->post->ID, '_slim_featured', true ),
			'product_type'      => Slim_Catalog_Product_Variations::get_product_type( $this->post->ID ),
		);
	}

	/**
	 * @param int|WP_Post $product Product ID or post object.
	 * @return Slim_Catalog_Product|null
	 */
	public static function get( $product ) {
		$instance = new self( $product );

		return $instance->exists() ? $instance : null;
	}

	public function exists() {
		return null !== $this->post;
	}

	public function get_id() {
		return $this->post ? (int) $this->post->ID : 0;
	}

	public function get_post() {
		return $this->post;
	}

	public function get_title() {
		return $this->post ? get_the_title( $this->post ) : '';
	}

	public function get_permalink() {
		return $this->post ? get_permalink( $this->post ) : '';
	}

	public function get_excerpt() {
		if ( ! $this->post ) {
			return '';
		}

		if ( ! empty( $this->meta['short_description'] ) ) {
			return $this->meta['short_description'];
		}

		return has_excerpt( $this->post ) ? get_the_excerpt( $this->post ) : '';
	}

	public function get_content() {
		return $this->post ? apply_filters( 'the_content', $this->post->post_content ) : '';
	}

	public function get_sku() {
		return (string) $this->meta['sku'];
	}

	public function get_badge() {
		return (string) $this->meta['badge'];
	}

	public function is_featured() {
		return (bool) $this->meta['featured'];
	}

	public function is_variable() {
		return Slim_Catalog_Product_Variations::is_variable( $this );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_attributes() {
		return $this->post ? Slim_Catalog_Product_Variations::get_attributes( $this->post->ID ) : array();
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_variations() {
		return $this->post ? Slim_Catalog_Product_Variations::get_variations( $this->post->ID ) : array();
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_variations_payload() {
		return Slim_Catalog_Product_Variations::get_frontend_payload( $this );
	}

	public function get_price_raw() {
		$sale = $this->get_sale_price_raw();

		if ( null !== $sale ) {
			return $sale;
		}

		return $this->get_regular_price_raw();
	}

	public function get_regular_price_raw() {
		$price = $this->meta['price'];

		return '' !== $price && null !== $price ? (float) $price : null;
	}

	public function get_sale_price_raw() {
		$price = $this->meta['sale_price'];

		if ( '' === $price || null === $price ) {
			return null;
		}

		$regular = $this->get_regular_price_raw();

		if ( null === $regular || (float) $price >= $regular ) {
			return null;
		}

		return (float) $price;
	}

	public function is_on_sale() {
		return null !== $this->get_sale_price_raw();
	}

	public function get_price_html() {
		return slim_catalog_format_price_html( $this->get_regular_price_raw(), $this->get_sale_price_raw() );
	}

	public function get_image_id() {
		return $this->post ? (int) get_post_thumbnail_id( $this->post ) : 0;
	}

	/**
	 * @return int[]
	 */
	public function get_gallery_ids() {
		$gallery = array_map( 'intval', $this->meta['gallery'] );
		$gallery = array_filter( $gallery );

		$featured = $this->get_image_id();

		if ( $featured && ! in_array( $featured, $gallery, true ) ) {
			array_unshift( $gallery, $featured );
		}

		return array_values( array_unique( $gallery ) );
	}

	/**
	 * @return WP_Term[]
	 */
	public function get_categories() {
		if ( ! $this->post ) {
			return array();
		}

		$terms = get_the_terms( $this->post, Slim_Catalog_Post_Types::TAXONOMY );

		return is_array( $terms ) ? $terms : array();
	}
}