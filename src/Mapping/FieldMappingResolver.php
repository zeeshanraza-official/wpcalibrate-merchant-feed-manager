<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Mapping;

use WPCalibrate\MerchantFeedManager\Feed\FeedModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves merchant field values from WooCommerce products based on mappings,
 * defaults, static values, attributes, and feed-specific overrides.
 */
class FieldMappingResolver {

	/**
	 * Resolve a merchant field value for a product.
	 *
	 * @param string      $field Merchant field name (e.g. 'title', 'brand', 'gtin').
	 * @param \WC_Product $product WooCommerce product or variation.
	 * @param FeedModel   $feed Feed model containing mappings and overrides.
	 * @param \WC_Product|null $parent Optional parent product for variations.
	 * @return mixed
	 */
	public static function resolve( string $field, \WC_Product $product, FeedModel $feed, ?\WC_Product $parent = null ): mixed {
		// 1. Check feed-specific override first (without modifying WC product)
		if ( isset( $feed->overrides[ $field ] ) && '' !== $feed->overrides[ $field ] ) {
			return $feed->overrides[ $field ];
		}

		// 2. Check feed field mappings
		$mapping = $feed->mappings[ $field ] ?? null;
		if ( ! empty( $mapping ) ) {
			$value = self::resolve_mapped_source( $mapping, $product, $parent );
			if ( null !== $value && '' !== $value ) {
				return $value;
			}
		}

		// 3. Fallback to standard intelligent WooCommerce defaults
		return self::resolve_default( $field, $product, $parent, $feed );
	}

	/**
	 * Resolve value from a specific mapping rule configuration.
	 *
	 * @param array<string, mixed>|string $mapping Mapping definition.
	 * @param \WC_Product $product WooCommerce product.
	 * @param \WC_Product|null $parent Parent product.
	 * @return mixed
	 */
	public static function resolve_mapped_source( array|string $mapping, \WC_Product $product, ?\WC_Product $parent = null ): mixed {
		if ( is_string( $mapping ) ) {
			$source = $mapping;
			$meta_key = '';
			$static_val = '';
		} else {
			$source     = (string) ( $mapping['source'] ?? '' );
			$meta_key   = (string) ( $mapping['meta_key'] ?? '' );
			$static_val = (string) ( $mapping['static_value'] ?? '' );
		}

		if ( 'static' === $source ) {
			return $static_val;
		}

		if ( 'custom_meta' === $source && ! empty( $meta_key ) ) {
			$safe_key = sanitize_key( $meta_key );
			$val = $product->get_meta( $safe_key, true );
			if ( ( '' === $val || null === $val ) && $parent ) {
				$val = $parent->get_meta( $safe_key, true );
			}
			return $val;
		}

		if ( str_starts_with( $source, 'attr_' ) ) {
			$attr_name = substr( $source, 5 );
			$val = $product->get_attribute( $attr_name );
			if ( empty( $val ) && $parent ) {
				$val = $parent->get_attribute( $attr_name );
			}
			return $val;
		}

		return self::extract_product_property( $source, $product, $parent );
	}

	/**
	 * Extract standard product property.
	 *
	 * @param string $source Source property key.
	 * @param \WC_Product $product Product.
	 * @param \WC_Product|null $parent Parent.
	 * @return mixed
	 */
	private static function extract_product_property( string $source, \WC_Product $product, ?\WC_Product $parent = null ): mixed {
		switch ( $source ) {
			case 'id':
				return (string) $product->get_id();

			case 'title':
				return $product->get_name();

			case 'description':
				$desc = $product->get_description();
				if ( empty( $desc ) && $parent ) {
					$desc = $parent->get_description();
				}
				if ( empty( $desc ) ) {
					$desc = $product->get_short_description();
				}
				return wp_strip_all_tags( $desc );

			case 'short_description':
				$desc = $product->get_short_description();
				if ( empty( $desc ) && $parent ) {
					$desc = $parent->get_short_description();
				}
				return wp_strip_all_tags( $desc );

			case 'sku':
				$sku = $product->get_sku();
				if ( empty( $sku ) && $parent ) {
					$sku = $parent->get_sku();
				}
				return $sku;

			case 'permalink':
				return $product->get_permalink();

			case 'image_url':
				$img_id = $product->get_image_id();
				if ( empty( $img_id ) && $parent ) {
					$img_id = $parent->get_image_id();
				}
				return $img_id ? wp_get_attachment_image_url( (int) $img_id, 'full' ) : '';

			case 'price':
				return (float) $product->get_price();

			case 'regular_price':
				return (float) $product->get_regular_price();

			case 'sale_price':
				$sale = $product->get_sale_price();
				return '' !== $sale ? (float) $sale : null;

			case 'stock_status':
				return $product->get_stock_status();

			case 'weight':
				$weight = $product->get_weight();
				if ( empty( $weight ) && $parent ) {
					$weight = $parent->get_weight();
				}
				return $weight;

			case 'product_cat':
				$cat_ids = $product->get_category_ids();
				if ( empty( $cat_ids ) && $parent ) {
					$cat_ids = $parent->get_category_ids();
				}
				$names = [];
				foreach ( $cat_ids as $cid ) {
					$term = get_term( $cid, 'product_cat' );
					if ( $term && ! is_wp_error( $term ) ) {
						$names[] = $term->name;
					}
				}
				return implode( ' > ', $names );

			default:
				return '';
		}
	}

	/**
	 * Resolve standard default values when no mapping or override exists.
	 *
	 * @param string $field Merchant field name.
	 * @param \WC_Product $product WooCommerce product.
	 * @param \WC_Product|null $parent Parent product.
	 * @param FeedModel $feed Feed model.
	 * @return mixed
	 */
	public static function resolve_default( string $field, \WC_Product $product, ?\WC_Product $parent, FeedModel $feed ): mixed {
		switch ( $field ) {
			case 'id':
				return (string) $product->get_id();

			case 'item_group_id':
				return $parent ? (string) $parent->get_id() : '';

			case 'title':
				return $product->get_name();

			case 'description':
				$desc = $product->get_description();
				if ( empty( $desc ) && $parent ) {
					$desc = $parent->get_description();
				}
				if ( empty( $desc ) ) {
					$desc = $product->get_short_description();
				}
				return wp_strip_all_tags( $desc );

			case 'link':
				return $product->get_permalink();

			case 'image_link':
				$img_id = $product->get_image_id();
				if ( empty( $img_id ) && $parent ) {
					$img_id = $parent->get_image_id();
				}
				return $img_id ? (string) wp_get_attachment_image_url( (int) $img_id, 'full' ) : '';

			case 'availability':
				$stock = $product->get_stock_status();
				if ( 'instock' === $stock ) {
					return 'in_stock';
				}
				if ( 'onbackorder' === $stock ) {
					return 'backorder';
				}
				return 'out_of_stock';

			case 'price':
				return (float) ( $product->get_regular_price() ?: $product->get_price() );

			case 'sale_price':
				$sale = $product->get_sale_price();
				return '' !== $sale ? (float) $sale : null;

			case 'condition':
				return 'new';

			case 'identifier_exists':
				return true;

			case 'brand':
				// Check common brand attributes or meta
				$brand = $product->get_attribute( 'brand' ) ?: $product->get_attribute( 'pa_brand' );
				if ( empty( $brand ) && $parent ) {
					$brand = $parent->get_attribute( 'brand' ) ?: $parent->get_attribute( 'pa_brand' );
				}
				if ( empty( $brand ) ) {
					$brand = (string) $product->get_meta( '_brand', true );
				}
				return $brand ?: get_bloginfo( 'name' );

			case 'gtin':
				$gtin = (string) ( $product->get_meta( '_gtin', true ) ?: $product->get_meta( 'gtin', true ) );
				if ( empty( $gtin ) && $parent ) {
					$gtin = (string) ( $parent->get_meta( '_gtin', true ) ?: $parent->get_meta( 'gtin', true ) );
				}
				return $gtin;

			case 'mpn':
				$mpn = (string) ( $product->get_meta( '_mpn', true ) ?: $product->get_meta( 'mpn', true ) );
				if ( empty( $mpn ) && $parent ) {
					$mpn = (string) ( $parent->get_meta( '_mpn', true ) ?: $parent->get_meta( 'mpn', true ) );
				}
				return $mpn ?: (string) $product->get_sku();

			case 'color':
				$color = $product->get_attribute( 'color' ) ?: $product->get_attribute( 'pa_color' );
				if ( empty( $color ) && $parent ) {
					$color = $parent->get_attribute( 'color' ) ?: $parent->get_attribute( 'pa_color' );
				}
				return $color;

			case 'size':
				$size = $product->get_attribute( 'size' ) ?: $product->get_attribute( 'pa_size' );
				if ( empty( $size ) && $parent ) {
					$size = $parent->get_attribute( 'size' ) ?: $parent->get_attribute( 'pa_size' );
				}
				return $size;

			default:
				return '';
		}
	}
}
