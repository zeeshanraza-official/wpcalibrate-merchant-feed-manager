<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Channels;

use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Product\NormalizedProduct;
use WPCalibrate\MerchantFeedManager\Security\Security;
use WPCalibrate\MerchantFeedManager\Security\XmlEscaper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adapter for customizable XML feeds allowing administrator configured tag names and fields.
 */
class CustomXmlChannel extends AbstractChannel {

	public function get_identifier(): string {
		return 'custom_xml';
	}

	public function get_name(): string {
		return __( 'Custom XML', 'wpcalibrate-merchant-feed-manager' );
	}

	public function get_supported_formats(): array {
		return [ 'xml' ];
	}

	public function get_default_format(): string {
		return 'xml';
	}

	public function get_required_fields(): array {
		return [ 'id', 'title', 'price' ];
	}

	public function get_recommended_fields(): array {
		return [ 'description', 'link', 'image_link' ];
	}

	public function get_xml_open_tags( FeedModel $feed ): string {
		$root_tag = ! empty( $feed->overrides['custom_root_tag'] )
			? Security::sanitize_xml_tag( (string) $feed->overrides['custom_root_tag'] )
			: 'products';

		return "<{$root_tag}>\n";
	}

	public function get_xml_close_tags( FeedModel $feed ): string {
		$root_tag = ! empty( $feed->overrides['custom_root_tag'] )
			? Security::sanitize_xml_tag( (string) $feed->overrides['custom_root_tag'] )
			: 'products';

		return "</{$root_tag}>\n";
	}

	public function format_xml_item( NormalizedProduct $product, FeedModel $feed ): string {
		$item_tag = ! empty( $feed->overrides['custom_item_tag'] )
			? Security::sanitize_xml_tag( (string) $feed->overrides['custom_item_tag'] )
			: 'product';

		$dict = $product->to_array();
		$xml  = "  <{$item_tag}>\n";

		// Default field list if none specified
		$field_map = ! empty( $feed->mappings ) ? $feed->mappings : [
			'id'          => 'id',
			'title'       => 'title',
			'description' => 'description',
			'link'        => 'link',
			'image_link'  => 'image_link',
			'price'       => 'price',
			'currency'    => 'currency',
			'brand'       => 'brand',
			'sku'         => 'sku',
		];

		foreach ( $field_map as $field_key => $config ) {
			$tag_name = is_array( $config ) && ! empty( $config['custom_tag'] )
				? Security::sanitize_xml_tag( (string) $config['custom_tag'] )
				: Security::sanitize_xml_tag( (string) $field_key );

			$val = $dict[ $field_key ] ?? '';
			if ( is_array( $val ) ) {
				$val = implode( ', ', array_filter( $val, 'is_scalar' ) );
			} elseif ( is_bool( $val ) ) {
				$val = $val ? 'true' : 'false';
			} elseif ( is_float( $val ) ) {
				$val = sprintf( '%.2f', $val );
			} else {
				$val = (string) $val;
			}

			if ( '' !== $val ) {
				$xml .= $this->render_xml_element( $tag_name, $val, true );
			}
		}

		$xml .= "  </{$item_tag}>\n";
		return $xml;
	}

	public function get_csv_headers( FeedModel $feed ): array {
		return [];
	}

	public function format_csv_row( NormalizedProduct $product, FeedModel $feed ): array {
		return [];
	}
}
