<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Channels;

use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Product\NormalizedProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Channel adapter for Google Merchant Center.
 */
class GoogleMerchantCenter extends AbstractChannel {

	public function get_identifier(): string {
		return 'google';
	}

	public function get_name(): string {
		return __( 'Google Merchant Center', 'wpcalibrate-merchant-feed-manager' );
	}

	public function get_supported_formats(): array {
		return [ 'xml', 'csv' ];
	}

	public function get_default_format(): string {
		return 'xml';
	}

	public function get_required_fields(): array {
		return [ 'id', 'title', 'description', 'link', 'image_link', 'availability', 'price' ];
	}

	public function get_recommended_fields(): array {
		return [ 'brand', 'gtin', 'mpn', 'google_product_category', 'condition', 'item_group_id', 'sale_price', 'shipping_weight' ];
	}

	public function format_xml_item( NormalizedProduct $product, FeedModel $feed ): string {
		$xml  = "  <item>\n";
		$xml .= $this->render_xml_element( 'g:id', $product->id );
		$xml .= $this->render_xml_element( 'g:title', $product->title, true );
		$xml .= $this->render_xml_element( 'g:description', $product->description, true );
		$xml .= $this->render_xml_element( 'g:link', $product->link );
		$xml .= $this->render_xml_element( 'g:image_link', $product->image_link );

		foreach ( $product->additional_image_links as $add_img ) {
			$xml .= $this->render_xml_element( 'g:additional_image_link', $add_img );
		}

		$xml .= $this->render_xml_element( 'g:availability', $product->availability );
		$xml .= $this->render_xml_element( 'g:price', $product->format_price( $product->price ) );

		if ( null !== $product->sale_price && $product->sale_price < $product->price ) {
			$xml .= $this->render_xml_element( 'g:sale_price', $product->format_price( $product->sale_price ) );
		}

		$xml .= $this->render_xml_element( 'g:condition', $product->condition );

		if ( ! empty( $product->brand ) ) {
			$xml .= $this->render_xml_element( 'g:brand', $product->brand, true );
		}

		if ( ! empty( $product->gtin ) ) {
			$xml .= $this->render_xml_element( 'g:gtin', $product->gtin );
		}

		if ( ! empty( $product->mpn ) ) {
			$xml .= $this->render_xml_element( 'g:mpn', $product->mpn );
		}

		$identifier_exists = $product->identifier_exists ? 'yes' : 'no';
		$xml .= $this->render_xml_element( 'g:identifier_exists', $identifier_exists );

		if ( ! empty( $product->google_product_category ) ) {
			$xml .= $this->render_xml_element( 'g:google_product_category', $product->google_product_category, true );
		}

		if ( ! empty( $product->product_type ) ) {
			$xml .= $this->render_xml_element( 'g:product_type', $product->product_type, true );
		}

		if ( $product->is_variation && ! empty( $product->item_group_id ) ) {
			$xml .= $this->render_xml_element( 'g:item_group_id', $product->item_group_id );
		}

		if ( ! empty( $product->color ) ) {
			$xml .= $this->render_xml_element( 'g:color', $product->color );
		}

		if ( ! empty( $product->size ) ) {
			$xml .= $this->render_xml_element( 'g:size', $product->size );
		}

		if ( ! empty( $product->shipping_weight ) ) {
			$xml .= $this->render_xml_element( 'g:shipping_weight', $product->shipping_weight );
		}

		for ( $i = 0; $i <= 4; $i++ ) {
			$key = 'custom_label_' . $i;
			if ( ! empty( $product->custom_labels[ $key ] ) ) {
				$xml .= $this->render_xml_element( 'g:' . $key, $product->custom_labels[ $key ], true );
			}
		}

		$xml .= "  </item>\n";
		return $xml;
	}

	public function get_csv_headers( FeedModel $feed ): array {
		return [
			'id',
			'title',
			'description',
			'link',
			'image_link',
			'additional_image_link',
			'availability',
			'price',
			'sale_price',
			'brand',
			'gtin',
			'mpn',
			'identifier_exists',
			'condition',
			'google_product_category',
			'product_type',
			'item_group_id',
			'color',
			'size',
			'shipping_weight',
			'custom_label_0',
			'custom_label_1',
			'custom_label_2',
			'custom_label_3',
			'custom_label_4',
		];
	}

	public function format_csv_row( NormalizedProduct $product, FeedModel $feed ): array {
		return [
			$product->id,
			$product->title,
			$product->description,
			$product->link,
			$product->image_link,
			implode( ',', $product->additional_image_links ),
			$product->availability,
			$product->format_price( $product->price ),
			null !== $product->sale_price ? $product->format_price( $product->sale_price ) : '',
			$product->brand,
			$product->gtin,
			$product->mpn,
			$product->identifier_exists ? 'yes' : 'no',
			$product->condition,
			$product->google_product_category,
			$product->product_type,
			$product->is_variation ? $product->item_group_id : '',
			$product->color,
			$product->size,
			$product->shipping_weight,
			$product->custom_labels['custom_label_0'] ?? '',
			$product->custom_labels['custom_label_1'] ?? '',
			$product->custom_labels['custom_label_2'] ?? '',
			$product->custom_labels['custom_label_3'] ?? '',
			$product->custom_labels['custom_label_4'] ?? '',
		];
	}
}
