<?php
/**
 * Product variation helpers.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

class Slim_Catalog_Product_Variations {

	const META_TYPE       = '_slim_product_type';
	const META_ATTRIBUTES = '_slim_attributes';
	const META_VARIATIONS = '_slim_variations';

	/**
	 * @param int $product_id Product ID.
	 * @return string
	 */
	public static function get_product_type( $product_id ) {
		$type = get_post_meta( $product_id, self::META_TYPE, true );

		return 'variable' === $type ? 'variable' : 'simple';
	}

	/**
	 * @param int $product_id Product ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_attributes( $product_id ) {
		$attributes = get_post_meta( $product_id, self::META_ATTRIBUTES, true );

		return is_array( $attributes ) ? self::normalize_attributes( $attributes ) : array();
	}

	/**
	 * @param int $product_id Product ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_variations( $product_id, $enabled_only = true ) {
		$variations = get_post_meta( $product_id, self::META_VARIATIONS, true );
		$variations = is_array( $variations ) ? self::normalize_variations( $variations ) : array();

		if ( ! $enabled_only ) {
			return $variations;
		}

		return array_values(
			array_filter(
				$variations,
				function ( $variation ) {
					return ! empty( $variation['enabled'] );
				}
			)
		);
	}

	/**
	 * @param Slim_Catalog_Product $product Product object.
	 */
	public static function is_variable( $product ) {
		if ( ! $product || ! $product->exists() ) {
			return false;
		}

		if ( 'variable' !== self::get_product_type( $product->get_id() ) ) {
			return false;
		}

		return ! empty( self::get_attributes( $product->get_id() ) ) && ! empty( self::get_variations( $product->get_id() ) );
	}

	/**
	 * @param array<int, array<string, mixed>> $attributes Raw attributes.
	 * @return array<int, array<string, mixed>>
	 */
	public static function normalize_attributes( $attributes ) {
		$normalized = array();

		foreach ( $attributes as $attribute ) {
			if ( empty( $attribute['name'] ) ) {
				continue;
			}

			$name    = sanitize_text_field( $attribute['name'] );
			$slug    = ! empty( $attribute['slug'] ) ? sanitize_title( $attribute['slug'] ) : sanitize_title( $name );
			$options = array();

			if ( ! empty( $attribute['options'] ) && is_array( $attribute['options'] ) ) {
				foreach ( $attribute['options'] as $option ) {
					$option = trim( (string) $option );

					if ( '' !== $option ) {
						$options[] = sanitize_text_field( $option );
					}
				}
			}

			$options = array_values( array_unique( $options ) );

			if ( empty( $options ) ) {
				continue;
			}

			$normalized[] = array(
				'name'    => $name,
				'slug'    => $slug,
				'options' => $options,
			);
		}

		return $normalized;
	}

	/**
	 * @param array<int, array<string, mixed>> $variations Raw variations.
	 * @return array<int, array<string, mixed>>
	 */
	public static function normalize_variations( $variations ) {
		$normalized = array();

		foreach ( $variations as $index => $variation ) {
			if ( empty( $variation['attributes'] ) || ! is_array( $variation['attributes'] ) ) {
				continue;
			}

			$attributes = array();

			foreach ( $variation['attributes'] as $slug => $value ) {
				$slug  = sanitize_title( (string) $slug );
				$value = sanitize_text_field( (string) $value );

				if ( '' !== $slug && '' !== $value ) {
					$attributes[ $slug ] = $value;
				}
			}

			if ( empty( $attributes ) ) {
				continue;
			}

			$enabled = ! isset( $variation['enabled'] ) || ! empty( $variation['enabled'] );

			$normalized[] = array(
				'id'         => ! empty( $variation['id'] ) ? sanitize_key( $variation['id'] ) : 'variation_' . ( $index + 1 ),
				'attributes' => $attributes,
				'price'      => isset( $variation['price'] ) && '' !== $variation['price'] ? (float) $variation['price'] : null,
				'sale_price' => isset( $variation['sale_price'] ) && '' !== $variation['sale_price'] ? (float) $variation['sale_price'] : null,
				'sku'        => sanitize_text_field( $variation['sku'] ?? '' ),
				'image_id'   => (int) ( $variation['image_id'] ?? 0 ),
				'enabled'    => $enabled,
			);
		}

		return $normalized;
	}

	/**
	 * @param Slim_Catalog_Product     $product  Product object.
	 * @param array<string, string>    $selected Selected attribute values keyed by slug.
	 * @return array<string, mixed>|null
	 */
	public static function find_variation( $product, $selected ) {
		if ( ! self::is_variable( $product ) ) {
			return null;
		}

		$variations = self::get_variations( $product->get_id() );

		foreach ( $variations as $variation ) {
			$match = true;

			foreach ( $variation['attributes'] as $slug => $value ) {
				if ( ! isset( $selected[ $slug ] ) || $selected[ $slug ] !== $value ) {
					$match = false;
					break;
				}
			}

			if ( $match ) {
				return $variation;
			}
		}

		return null;
	}

	/**
	 * @param Slim_Catalog_Product $product Product object.
	 * @return array<string, mixed>
	 */
	public static function get_frontend_payload( $product ) {
		if ( ! self::is_variable( $product ) ) {
			return array();
		}

		$variations = array();

		foreach ( self::get_variations( $product->get_id() ) as $variation ) {
			$regular = $variation['price'];
			$sale    = $variation['sale_price'];

			if ( null === $regular && null === $sale ) {
				$regular = $product->get_regular_price_raw();
				$sale    = $product->get_sale_price_raw();
			}

			$image_url = '';

			if ( ! empty( $variation['image_id'] ) ) {
				$image_url = wp_get_attachment_image_url( (int) $variation['image_id'], 'large' ) ?: '';
			}

			$variations[] = array(
				'id'         => $variation['id'],
				'attributes' => $variation['attributes'],
				'price_html' => slim_catalog_format_price_html( $regular, $sale ),
				'sku'        => $variation['sku'],
				'image_id'   => (int) $variation['image_id'],
				'image_url'  => $image_url,
			);
		}

		return array(
			'attributes' => self::get_attributes( $product->get_id() ),
			'variations' => $variations,
			'default'    => array(
				'price_html' => $product->get_price_html(),
				'sku'        => $product->get_sku(),
				'image_id'   => $product->get_image_id(),
			),
		);
	}
}