<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Mapping;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry of available data sources for mapping product fields.
 */
class SourceRegistry {

	/**
	 * Get standard available mapping sources for admin UI dropdowns.
	 *
	 * @return array<string, array{label: string, sources: array<string, string>}>
	 */
	public static function get_grouped_sources(): array {
		$groups = [
			'standard' => [
				'label'   => __( 'Standard Product Fields', 'wpcalibrate-merchant-feed-manager' ),
				'sources' => [
					'id'                => __( 'Product ID', 'wpcalibrate-merchant-feed-manager' ),
					'title'             => __( 'Product Title', 'wpcalibrate-merchant-feed-manager' ),
					'description'       => __( 'Full Description', 'wpcalibrate-merchant-feed-manager' ),
					'short_description' => __( 'Short Description', 'wpcalibrate-merchant-feed-manager' ),
					'sku'               => __( 'SKU', 'wpcalibrate-merchant-feed-manager' ),
					'permalink'         => __( 'Product URL', 'wpcalibrate-merchant-feed-manager' ),
					'image_url'         => __( 'Main Image URL', 'wpcalibrate-merchant-feed-manager' ),
					'price'             => __( 'Active Price', 'wpcalibrate-merchant-feed-manager' ),
					'regular_price'     => __( 'Regular Price', 'wpcalibrate-merchant-feed-manager' ),
					'sale_price'        => __( 'Sale Price', 'wpcalibrate-merchant-feed-manager' ),
					'stock_status'      => __( 'Stock Status', 'wpcalibrate-merchant-feed-manager' ),
					'stock_quantity'    => __( 'Stock Quantity', 'wpcalibrate-merchant-feed-manager' ),
					'weight'            => __( 'Weight', 'wpcalibrate-merchant-feed-manager' ),
					'length'            => __( 'Length', 'wpcalibrate-merchant-feed-manager' ),
					'width'             => __( 'Width', 'wpcalibrate-merchant-feed-manager' ),
					'height'            => __( 'Height', 'wpcalibrate-merchant-feed-manager' ),
				],
			],
			'taxonomies' => [
				'label'   => __( 'Taxonomies & Categories', 'wpcalibrate-merchant-feed-manager' ),
				'sources' => [
					'product_cat' => __( 'Product Categories (Path)', 'wpcalibrate-merchant-feed-manager' ),
					'primary_cat' => __( 'Primary / First Category', 'wpcalibrate-merchant-feed-manager' ),
					'product_tag' => __( 'Product Tags', 'wpcalibrate-merchant-feed-manager' ),
				],
			],
			'special' => [
				'label'   => __( 'Special / Custom', 'wpcalibrate-merchant-feed-manager' ),
				'sources' => [
					'static'      => __( 'Static Value', 'wpcalibrate-merchant-feed-manager' ),
					'custom_meta' => __( 'Custom Meta Key', 'wpcalibrate-merchant-feed-manager' ),
				],
			],
		];

		// If WooCommerce taxonomy attributes exist, append them
		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			$attributes = wc_get_attribute_taxonomies();
			if ( ! empty( $attributes ) ) {
				$attr_sources = [];
				foreach ( $attributes as $tax ) {
					$key = 'attr_pa_' . $tax->attribute_name;
					$attr_sources[ $key ] = $tax->attribute_label ?: $tax->attribute_name;
				}
				$groups['attributes'] = [
					'label'   => __( 'Global Attributes', 'wpcalibrate-merchant-feed-manager' ),
					'sources' => $attr_sources,
				];
			}
		}

		/**
		 * Filter available source options.
		 *
		 * @param array $groups Source groups.
		 */
		return (array) apply_filters( 'wpcmfm_mapping_sources', $groups );
	}
}
