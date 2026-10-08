<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Product;

use WPCalibrate\MerchantFeedManager\Category\CategoryMappingManager;
use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Mapping\FieldMappingResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transforms WooCommerce products into clean NormalizedProduct records.
 */
class ProductNormalizer {

	/**
	 * Normalize a WooCommerce product into one or more NormalizedProduct instances.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @param FeedModel   $feed Feed configuration.
	 * @return array<NormalizedProduct> List of normalized products (may contain multiple for variable products).
	 */
	public static function normalize( \WC_Product $product, FeedModel $feed ): array {
		if ( $product->is_type( 'variable' ) ) {
			return self::normalize_variable_product( $product, $feed );
		}

		return [ self::normalize_single_product( $product, $feed ) ];
	}

	/**
	 * Normalize a standard single product (simple, external, grouped).
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @param FeedModel   $feed Feed configuration.
	 * @return NormalizedProduct
	 */
	public static function normalize_single_product( \WC_Product $product, FeedModel $feed ): NormalizedProduct {
		$norm = new NormalizedProduct();

		$norm->product_id    = $product->get_id();
		$norm->id            = (string) FieldMappingResolver::resolve( 'id', $product, $feed );
		$norm->is_variation  = false;
		$norm->item_group_id = '';
		$norm->parent_id     = 0;

		// Currency
		$norm->currency = ! empty( $feed->target_currency ) ? $feed->target_currency : ( function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD' );

		// Core fields
		$norm->title       = (string) FieldMappingResolver::resolve( 'title', $product, $feed );
		$norm->description = (string) FieldMappingResolver::resolve( 'description', $product, $feed );
		$norm->link        = (string) FieldMappingResolver::resolve( 'link', $product, $feed );
		$norm->image_link  = (string) FieldMappingResolver::resolve( 'image_link', $product, $feed );

		// Additional gallery images
		$gallery_ids = $product->get_gallery_image_ids();
		if ( ! empty( $gallery_ids ) ) {
			foreach ( $gallery_ids as $gid ) {
				$url = wp_get_attachment_image_url( (int) $gid, 'full' );
				if ( $url && $url !== $norm->image_link ) {
					$norm->additional_image_links[] = $url;
				}
			}
		}

		// Pricing
		$regular_price = $product->get_regular_price();
		$active_price  = $product->get_price();
		$norm->price   = (float) ( $regular_price ?: $active_price );

		$sale_price = $product->get_sale_price();
		if ( '' !== $sale_price && false !== $sale_price && (float) $sale_price < $norm->price ) {
			$norm->sale_price = (float) $sale_price;
		}

		// Availability
		$norm->availability = (string) FieldMappingResolver::resolve( 'availability', $product, $feed );

		// Identifiers
		$norm->brand = (string) FieldMappingResolver::resolve( 'brand', $product, $feed );
		$norm->gtin  = (string) FieldMappingResolver::resolve( 'gtin', $product, $feed );
		$norm->mpn   = (string) FieldMappingResolver::resolve( 'mpn', $product, $feed );

		$norm->condition         = (string) FieldMappingResolver::resolve( 'condition', $product, $feed ) ?: 'new';
		$norm->identifier_exists = ! empty( $norm->gtin ) || ( ! empty( $norm->brand ) && ! empty( $norm->mpn ) );

		// Category mapping
		$norm->google_product_category = CategoryMappingManager::resolve_product_category( $product, $feed );
		$norm->product_type            = (string) FieldMappingResolver::resolve_mapped_source( 'product_cat', $product );

		// Attributes
		$norm->color = (string) FieldMappingResolver::resolve( 'color', $product, $feed );
		$norm->size  = (string) FieldMappingResolver::resolve( 'size', $product, $feed );

		// Weight
		$weight = $product->get_weight();
		if ( ! empty( $weight ) ) {
			$unit = function_exists( 'get_option' ) ? get_option( 'woocommerce_weight_unit', 'kg' ) : 'kg';
			$norm->shipping_weight = $weight . ' ' . $unit;
		}

		// Custom labels
		for ( $i = 0; $i <= 4; $i++ ) {
			$key = 'custom_label_' . $i;
			$norm->custom_labels[ $key ] = (string) FieldMappingResolver::resolve( $key, $product, $feed );
		}

		return $norm;
	}

	/**
	 * Normalize a variable product into individual variation records.
	 *
	 * @param \WC_Product_Variable $variable Variable product.
	 * @param FeedModel            $feed Feed configuration.
	 * @return array<NormalizedProduct>
	 */
	public static function normalize_variable_product( \WC_Product_Variable $variable, FeedModel $feed ): array {
		$records = [];
		$variation_ids = $variable->get_children();

		if ( empty( $variation_ids ) ) {
			// Variable product has no variations: export parent as standalone
			return [ self::normalize_single_product( $variable, $feed ) ];
		}

		$seen_ids = [];
		foreach ( $variation_ids as $var_id ) {
			$variation = wc_get_product( $var_id );
			if ( ! $variation instanceof \WC_Product_Variation ) {
				continue;
			}

			$record = VariationHandler::normalize_variation( $variation, $variable, $feed );

			// Prevent duplicate IDs across variations
			if ( in_array( $record->id, $seen_ids, true ) ) {
				$record->id = sprintf( '%d_%d', $variable->get_id(), $var_id );
			}
			$seen_ids[] = $record->id;

			$records[] = $record;
		}

		return $records;
	}
}
