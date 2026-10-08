<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Channels;

use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Product\NormalizedProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Channel adapter for Pinterest Shopping / Catalog feeds.
 */
class PinterestCatalog extends AbstractChannel {

	public function get_identifier(): string {
		return 'pinterest';
	}

	public function get_name(): string {
		return __( 'Pinterest Catalog', 'wpcalibrate-merchant-feed-manager' );
	}

	public function get_supported_formats(): array {
		return [ 'csv', 'xml' ];
	}

	public function get_default_format(): string {
		return 'csv';
	}

	public function get_required_fields(): array {
		return [ 'id', 'title', 'description', 'link', 'image_link', 'price', 'availability', 'condition' ];
	}

	public function get_recommended_fields(): array {
		return [ 'brand', 'google_product_category', 'item_group_id', 'sale_price' ];
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

		if ( ! empty( $product->google_product_category ) ) {
			$xml .= $this->render_xml_element( 'g:google_product_category', $product->google_product_category, true );
		}

		if ( $product->is_variation && ! empty( $product->item_group_id ) ) {
			$xml .= $this->render_xml_element( 'g:item_group_id', $product->item_group_id );
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
			'condition',
			'brand',
			'google_product_category',
			'item_group_id',
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
			$product->condition,
			$product->brand ?: get_bloginfo( 'name' ),
			$product->google_product_category,
			$product->is_variation ? $product->item_group_id : '',
		];
	}
}
