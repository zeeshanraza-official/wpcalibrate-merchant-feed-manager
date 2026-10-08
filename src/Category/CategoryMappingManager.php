<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Category;

use WPCalibrate\MerchantFeedManager\Database\Schema;
use WPCalibrate\MerchantFeedManager\Feed\FeedModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages mapping WooCommerce product categories to merchant channel categories.
 */
class CategoryMappingManager {

	/**
	 * Save category mappings for a feed.
	 *
	 * @param int $feed_id Feed ID (or 0 for global).
	 * @param string $channel Target channel.
	 * @param array<int, array{id: string, name: string}> $mappings Map of [ wc_cat_id => [ 'id' => ..., 'name' => ... ] ]
	 * @return bool
	 */
	public static function save_mappings( int $feed_id, string $channel, array $mappings ): bool {
		global $wpdb;

		$table = Schema::get_category_mappings_table();
		$wpdb->delete( $table, [ 'feed_id' => $feed_id, 'channel' => $channel ], [ '%d', '%s' ] );

		$now = current_time( 'mysql', true );
		foreach ( $mappings as $wc_cat_id => $data ) {
			$wc_cat_id = (int) $wc_cat_id;
			$mapped_id = sanitize_text_field( (string) ( $data['id'] ?? '' ) );
			$mapped_name = sanitize_text_field( (string) ( $data['name'] ?? '' ) );

			if ( empty( $mapped_id ) && empty( $mapped_name ) ) {
				continue;
			}

			$wpdb->insert(
				$table,
				[
					'feed_id'              => $feed_id,
					'wc_category_id'       => $wc_cat_id,
					'channel'              => $channel,
					'mapped_category_id'   => $mapped_id,
					'mapped_category_name' => $mapped_name ?: GoogleTaxonomy::get_name( $mapped_id ),
					'created_at'           => $now,
					'updated_at'           => $now,
				],
				[ '%d', '%d', '%s', '%s', '%s', '%s', '%s' ]
			);
		}

		return true;
	}

	/**
	 * Get category mappings for a feed and channel.
	 *
	 * @param int $feed_id Feed ID.
	 * @param string $channel Channel name.
	 * @return array<int, array{mapped_id: string, mapped_name: string}>
	 */
	public static function get_mappings( int $feed_id, string $channel ): array {
		global $wpdb;

		$table = Schema::get_category_mappings_table();
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT wc_category_id, mapped_category_id, mapped_category_name 
				 FROM {$table} 
				 WHERE feed_id = %d AND channel = %s",
				$feed_id,
				$channel
			),
			ARRAY_A
		);

		if ( empty( $rows ) && $feed_id > 0 ) {
			// Fallback to global defaults (feed_id = 0)
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT wc_category_id, mapped_category_id, mapped_category_name 
					 FROM {$table} 
					 WHERE feed_id = 0 AND channel = %s",
					$channel
				),
				ARRAY_A
			);
		}

		$map = [];
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$map[ (int) $row['wc_category_id'] ] = [
					'mapped_id'   => (string) $row['mapped_category_id'],
					'mapped_name' => (string) $row['mapped_category_name'],
				];
			}
		}

		return $map;
	}

	/**
	 * Resolve mapped merchant category for a WooCommerce product.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @param FeedModel   $feed Feed model.
	 * @return string Mapped category name/ID or empty string.
	 */
	public static function resolve_product_category( \WC_Product $product, FeedModel $feed ): string {
		$cat_ids = $product->get_category_ids();
		if ( empty( $cat_ids ) && $product->get_parent_id() ) {
			$parent = wc_get_product( $product->get_parent_id() );
			if ( $parent ) {
				$cat_ids = $parent->get_category_ids();
			}
		}

		// First check inline feed category mappings
		if ( ! empty( $feed->category_mappings ) && ! empty( $cat_ids ) ) {
			foreach ( $cat_ids as $cat_id ) {
				if ( ! empty( $feed->category_mappings[ $cat_id ] ) ) {
					$entry = $feed->category_mappings[ $cat_id ];
					return is_array( $entry ) ? (string) ( $entry['name'] ?? $entry['id'] ?? '' ) : (string) $entry;
				}
			}
		}

		// Then check database mappings
		$mappings = self::get_mappings( $feed->id, $feed->channel );
		if ( ! empty( $cat_ids ) ) {
			foreach ( $cat_ids as $cat_id ) {
				if ( isset( $mappings[ $cat_id ] ) ) {
					return $mappings[ $cat_id ]['mapped_name'] ?: $mappings[ $cat_id ]['mapped_id'];
				}
			}
		}

		// Default fallback from feed overrides or defaults if configured
		if ( ! empty( $feed->overrides['default_google_category'] ) ) {
			return (string) $feed->overrides['default_google_category'];
		}

		return '';
	}
}
