<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Channels;

use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Product\NormalizedProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Channel adapter for Microsoft Advertising / Bing Shopping feeds.
 */
class MicrosoftShopping extends AbstractChannel {

	public function get_identifier(): string {
		return 'microsoft';
	}

	public function get_name(): string {
		return __( 'Microsoft / Bing Shopping', 'wpcalibrate-merchant-feed-manager' );
	}

	public function get_supported_formats(): array {
		return [ 'xml', 'csv' ];
	}

	public function get_default_format(): string {
		return 'xml';
	}

	public function get_required_fields(): array {
		return [ 'id', 'title', 'link', 'price', 'description', 'image_link' ];
	}

	public function get_recommended_fields(): array {
		return [ 'availability', 'brand', 'gtin', 'mpn', 'condition', 'item_group_id', 'product_type' ];
	}

	public function format_xml_item( NormalizedProduct $product, FeedModel $feed ): string {
		$xml  = "  <item>\n";
		$xml .= $this->render_xml_element( 'g:id', $product->id );
		$xml .= $this->render_xml_element( 'g:title', $product->title, true );
		$xml .= $this->render_xml_element( 'g:description', $product->description, true );
		$xml .= $this->render_xml_element( 'g:link', $product->link );
		$xml .= $this->render_xml_element( 'g:image_link', $product->image_link );
		$xml .= $this->render_xml_element( 'g:price', $product->format_price( $product->price ) );

		if ( null !== $product->sale_price && $product->sale_price < $product->price ) {
			$xml .= $this->render_xml_element( 'g:sale_price', $product->format_price( $product->sale_price ) );
		}

		$xml .= $this->render_xml_element( 'g:availability', $product->availability );
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

		if ( $product->is_variation && ! empty( $product->item_group_id ) ) {
			$xml .= $this->render_xml_element( 'g:item_group_id', $product->item_group_id );
		}

		if ( ! empty( $product->product_type ) ) {
			$xml .= $this->render_xml_element( 'g:product_type', $product->product_type, true );
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
			'price',
			'sale_price',
			'availability',
			'brand',
			'gtin',
			'mpn',
			'item_group_id',
			'condition',
			'product_type',
		];
	}

	public function format_csv_row( NormalizedProduct $product, FeedModel $feed ): array {
		return [
			$product->id,
			$product->title,
			$product->description,
			$product->link,
			$product->image_link,
			$product->format_price( $product->price ),
			null !== $product->sale_price ? $product->format_price( $product->sale_price ) : '',
			$product->availability,
			$product->brand,
			$product->gtin,
			$product->mpn,
			$product->is_variation ? $product->item_group_id : '',
			$product->condition,
			$product->product_type,
		];
	}
}
