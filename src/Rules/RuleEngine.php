<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Rules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Evaluates inclusion and exclusion rules against WooCommerce products.
 */
class RuleEngine {

	/**
	 * Determine if a product is eligible for inclusion based on feed rules.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @param array<array<string, mixed>> $raw_rules Feed rules configuration.
	 * @return bool True if product should be included in feed.
	 */
	public static function is_product_eligible( \WC_Product $product, array $raw_rules ): bool {
		if ( empty( $raw_rules ) ) {
			return true;
		}

		$conditions = array_map(
			static fn( $rule ) => new RuleCondition( (array) $rule ),
			$raw_rules
		);

		$include_conditions = [];
		$exclude_conditions = [];

		foreach ( $conditions as $cond ) {
			if ( RuleCondition::TYPE_EXCLUDE === $cond->type ) {
				$exclude_conditions[] = $cond;
			} else {
				$include_conditions[] = $cond;
			}
		}

		// 1. Check Exclusions: If ANY exclude condition matches, product is excluded
		foreach ( $exclude_conditions as $exclude_cond ) {
			$val = self::extract_field_value( $product, $exclude_cond->field );
			if ( $exclude_cond->matches( $val ) ) {
				return false;
			}
		}

		// 2. Check Inclusions: If include rules are defined, ALL must match (AND logic)
		if ( ! empty( $include_conditions ) ) {
			foreach ( $include_conditions as $include_cond ) {
				$val = self::extract_field_value( $product, $include_cond->field );
				if ( ! $include_cond->matches( $val ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Extract attribute or property value from WooCommerce product for rule checking.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @param string      $field Field name.
	 * @return mixed
	 */
	public static function extract_field_value( \WC_Product $product, string $field ): mixed {
		switch ( $field ) {
			case 'product_id':
			case 'id':
				return (string) $product->get_id();

			case 'sku':
				return (string) $product->get_sku();

			case 'product_type':
				return (string) $product->get_type();

			case 'stock_status':
				return (string) $product->get_stock_status();

			case 'price':
				return (float) $product->get_price();

			case 'sale_price':
				return (float) $product->get_sale_price();

			case 'on_sale':
			case 'sale_status':
				return $product->is_on_sale() ? 'yes' : 'no';

			case 'category':
			case 'category_id':
				$cat_ids = $product->get_category_ids();
				if ( empty( $cat_ids ) && $product->get_parent_id() ) {
					$parent = wc_get_product( $product->get_parent_id() );
					if ( $parent ) {
						$cat_ids = $parent->get_category_ids();
					}
				}
				return array_map( 'strval', $cat_ids );

			case 'tag':
			case 'tag_id':
				$tag_ids = $product->get_tag_ids();
				if ( empty( $tag_ids ) && $product->get_parent_id() ) {
					$parent = wc_get_product( $product->get_parent_id() );
					if ( $parent ) {
						$tag_ids = $parent->get_tag_ids();
					}
				}
				return array_map( 'strval', $tag_ids );

			default:
				// Try custom attribute
				$val = $product->get_attribute( $field );
				if ( ! empty( $val ) ) {
					return $val;
				}
				// Try meta
				$meta = $product->get_meta( $field, true );
				if ( '' !== $meta ) {
					return $meta;
				}
				return '';
		}
	}
}
