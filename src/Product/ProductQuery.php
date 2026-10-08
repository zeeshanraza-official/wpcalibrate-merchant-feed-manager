<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Executes memory-efficient, chunked queries for WooCommerce products.
 */
class ProductQuery {

	/**
	 * Get total count of published candidate products.
	 *
	 * @return int
	 */
	public static function get_total_product_count(): int {
		if ( function_exists( 'wp_count_posts' ) ) {
			$counts = wp_count_posts( 'product' );
			return isset( $counts->publish ) ? (int) $counts->publish : 0;
		}

		return 0;
	}

	/**
	 * Get a batch of product IDs for feed generation.
	 *
	 * @param int $limit Batch size.
	 * @param int $offset Starting offset.
	 * @return array<int> Product IDs.
	 */
	public static function get_product_ids_batch( int $limit = 100, int $offset = 0 ): array {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return [];
		}

		$ids = wc_get_products(
			[
				'status'  => 'publish',
				'limit'   => max( 1, $limit ),
				'offset'  => max( 0, $offset ),
				'orderby' => 'ID',
				'order'   => 'ASC',
				'return'  => 'ids',
			]
		);

		return is_array( $ids ) ? array_map( 'intval', $ids ) : [];
	}

	/**
	 * Safely fetch a WooCommerce product by ID.
	 *
	 * @param int $product_id Product ID.
	 * @return \WC_Product|null Returns null if product has been deleted or cannot be loaded.
	 */
	public static function get_product( int $product_id ): ?\WC_Product {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$product = wc_get_product( $product_id );
		return ( $product instanceof \WC_Product ) ? $product : null;
	}
}
