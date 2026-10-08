<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Channels;

use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Product\NormalizedProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adapter for customizable CSV feeds allowing store managers to configure columns.
 */
class CustomCsvChannel extends AbstractChannel {

	public function get_identifier(): string {
		return 'custom_csv';
	}

	public function get_name(): string {
		return __( 'Custom CSV', 'wpcalibrate-merchant-feed-manager' );
	}

	public function get_supported_formats(): array {
		return [ 'csv' ];
	}

	public function get_default_format(): string {
		return 'csv';
	}

	public function get_required_fields(): array {
		return [ 'id', 'title', 'price' ];
	}

	public function get_recommended_fields(): array {
		return [ 'sku', 'description', 'link', 'image_link' ];
	}

	public function format_xml_item( NormalizedProduct $product, FeedModel $feed ): string {
		return '';
	}

	public function get_csv_headers( FeedModel $feed ): array {
		if ( ! empty( $feed->mappings ) ) {
			$headers = [];
			foreach ( $feed->mappings as $field_key => $config ) {
				$label = is_array( $config ) && ! empty( $config['custom_header'] )
					? sanitize_text_field( (string) $config['custom_header'] )
					: sanitize_text_field( (string) $field_key );
				$headers[] = $label;
			}
			return $headers;
		}

		return [ 'id', 'title', 'description', 'link', 'image_link', 'price', 'currency', 'availability', 'brand' ];
	}

	public function format_csv_row( NormalizedProduct $product, FeedModel $feed ): array {
		$dict = $product->to_array();
		$row  = [];

		$fields = ! empty( $feed->mappings ) ? array_keys( $feed->mappings ) : [
			'id', 'title', 'description', 'link', 'image_link', 'price', 'currency', 'availability', 'brand'
		];

		foreach ( $fields as $field_key ) {
			$val = $dict[ $field_key ] ?? '';
			if ( is_array( $val ) ) {
				$val = implode( ', ', array_filter( $val, 'is_scalar' ) );
			} elseif ( is_bool( $val ) ) {
				$val = $val ? 'yes' : 'no';
			} elseif ( is_float( $val ) ) {
				$val = sprintf( '%.2f', $val );
			} else {
				$val = (string) $val;
			}
			$row[] = $val;
		}

		return $row;
	}
}
