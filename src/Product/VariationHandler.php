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
 * Normalizes WooCommerce variation objects into NormalizedProduct models.
 */
class VariationHandler {

	/**
	 * Normalize a single variation of a variable product.
	 *
	 * @param \WC_Product_Variation $variation WooCommerce variation.
	 * @param \WC_Product_Variable  $parent Parent variable product.
	 * @param FeedModel             $feed Feed configuration.
	 * @return NormalizedProduct
	 */
	public static function normalize_variation(
		\WC_Product_Variation $variation,
		\WC_Product_Variable $parent,
		FeedModel $feed
	): NormalizedProduct {
		$norm = new NormalizedProduct();

		$norm->product_id    = $variation->get_id();
		$norm->is_variation  = true;
		$norm->parent_id     = $parent->get_id();
		$norm->item_group_id = (string) $parent->get_id();
		$norm->id            = (string) $variation->get_id();

		// Currency
		$norm->currency = ! empty( $feed->target_currency ) ? $feed->target_currency : ( function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD' );

		// Core fields with parent fallbacks
		$norm->title = (string) FieldMappingResolver::resolve( 'title', $variation, $feed, $parent );
		if ( empty( $norm->title ) ) {
			$norm->title = $parent->get_name();
		}

		$norm->description = (string) FieldMappingResolver::resolve( 'description', $variation, $feed, $parent );
		$norm->link        = (string) FieldMappingResolver::resolve( 'link', $variation, $feed, $parent );

		// Image: variation image or fallback to parent
		$var_img_id = $variation->get_image_id();
		if ( $var_img_id ) {
			$norm->image_link = (string) wp_get_attachment_image_url( (int) $var_img_id, 'full' );
		} else {
			$norm->image_link = (string) FieldMappingResolver::resolve( 'image_link', $parent, $feed );
		}

		// Additional images from parent gallery
		$gallery_ids = $parent->get_gallery_image_ids();
		if ( ! empty( $gallery_ids ) ) {
			foreach ( $gallery_ids as $gid ) {
				$url = wp_get_attachment_image_url( (int) $gid, 'full' );
				if ( $url && $url !== $norm->image_link ) {
					$norm->additional_image_links[] = $url;
				}
			}
		}

		// Prices
		$price = $variation->get_regular_price() ?: $variation->get_price();
		if ( '' === $price || false === $price ) {
			$price = $parent->get_regular_price() ?: $parent->get_price();
		}
		$norm->price = (float) $price;

		$sale_price = $variation->get_sale_price();
		if ( '' !== $sale_price && false !== $sale_price && (float) $sale_price < $norm->price ) {
			$norm->sale_price = (float) $sale_price;
		}

		// Availability
		$norm->availability = (string) FieldMappingResolver::resolve( 'availability', $variation, $feed, $parent );

		// Identifiers
		$norm->brand = (string) FieldMappingResolver::resolve( 'brand', $variation, $feed, $parent );
		$norm->gtin  = (string) FieldMappingResolver::resolve( 'gtin', $variation, $feed, $parent );
		$norm->mpn   = (string) FieldMappingResolver::resolve( 'mpn', $variation, $feed, $parent );

		$norm->condition         = (string) FieldMappingResolver::resolve( 'condition', $variation, $feed, $parent ) ?: 'new';
		$norm->identifier_exists = ! empty( $norm->gtin ) || ( ! empty( $norm->brand ) && ! empty( $norm->mpn ) );

		// Category mapping
		$norm->google_product_category = CategoryMappingManager::resolve_product_category( $parent, $feed );
		$norm->product_type            = (string) FieldMappingResolver::resolve_mapped_source( 'product_cat', $parent );

		// Variation specific attributes
		$norm->color = (string) ( $variation->get_attribute( 'color' ) ?: $variation->get_attribute( 'pa_color' ) ?: $parent->get_attribute( 'color' ) ?: $parent->get_attribute( 'pa_color' ) );
		$norm->size  = (string) ( $variation->get_attribute( 'size' ) ?: $variation->get_attribute( 'pa_size' ) ?: $parent->get_attribute( 'size' ) ?: $parent->get_attribute( 'pa_size' ) );

		// Weight
		$weight = $variation->get_weight() ?: $parent->get_weight();
		if ( ! empty( $weight ) ) {
			$unit = function_exists( 'get_option' ) ? get_option( 'woocommerce_weight_unit', 'kg' ) : 'kg';
			$norm->shipping_weight = $weight . ' ' . $unit;
		}

		// Custom labels
		for ( $i = 0; $i <= 4; $i++ ) {
			$key = 'custom_label_' . $i;
			$norm->custom_labels[ $key ] = (string) FieldMappingResolver::resolve( $key, $variation, $feed, $parent );
		}

		return $norm;
	}
}
