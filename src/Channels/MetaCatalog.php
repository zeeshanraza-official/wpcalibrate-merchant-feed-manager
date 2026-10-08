<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Channels;

use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Product\NormalizedProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Channel adapter for Meta (Facebook and Instagram) catalog feeds.
 */
class MetaCatalog extends AbstractChannel {

	public function get_identifier(): string {
		return 'meta';
	}

	public function get_name(): string {
		return __( 'Meta / Facebook & Instagram', 'wpcalibrate-merchant-feed-manager' );
	}

	public function get_supported_formats(): array {
		return [ 'csv', 'xml' ];
	}

	public function get_default_format(): string {
		return 'csv';
	}

	public function get_required_fields(): array {
		return [ 'id', 'title', 'description', 'availability', 'condition', 'price', 'link', 'image_link', 'brand' ];
	}

	public function get_recommended_fields(): array {
		return [ 'sale_price', 'item_group_id', 'google_product_category', 'gtin', 'mpn' ];
	}

	/**
	 * Map availability to Meta catalog specification: 'in stock', 'out of stock', 'available for order', 'preorder'.
	 */
	private function map_meta_availability( string $availability ): string {
		return match ( $availability ) {
			'in_stock'     => 'in stock',
			'out_of_stock' => 'out of stock',
			'backorder'    => 'available for order',
			'preorder'     => 'preorder',
			default        => 'in stock',
		};
	}

	public function format_xml_item( NormalizedProduct $product, FeedModel $feed ): string {
		$xml  = "  <item>\n";
		$xml .= $this->render_xml_element( 'g:id', $product->id );
		$xml .= $this->render_xml_element( 'g:title', $product->title, true );
		$xml .= $this->render_xml_element( 'g:description', $product->description, true );
		$xml .= $this->render_xml_element( 'g:link', $product->link );
		$xml .= $this->render_xml_element( 'g:image_link', $product->image_link );
		$xml .= $this->render_xml_element( 'g:brand', $product->brand ?: get_bloginfo( 'name' ), true );
		$xml .= $this->render_xml_element( 'g:condition', $product->condition );
		$xml .= $this->render_xml_element( 'g:availability', $this->map_meta_availability( $product->availability ) );
		$xml .= $this->render_xml_element( 'g:price', $product->format_price( $product->price ) );

		if ( null !== $product->sale_price && $product->sale_price < $product->price ) {
			$xml .= $this->render_xml_element( 'g:sale_price', $product->format_price( $product->sale_price ) );
		}

		if ( $product->is_variation && ! empty( $product->item_group_id ) ) {
			$xml .= $this->render_xml_element( 'g:item_group_id', $product->item_group_id );
		}

		if ( ! empty( $product->google_product_category ) ) {
			$xml .= $this->render_xml_element( 'g:google_product_category', $product->google_product_category, true );
		}

		if ( ! empty( $product->gtin ) ) {
			$xml .= $this->render_xml_element( 'g:gtin', $product->gtin );
		}

		if ( ! empty( $product->mpn ) ) {
			$xml .= $this->render_xml_element( 'g:mpn', $product->mpn );
		}

		$xml .= "  </item>\n";
		return $xml;
	}

	public function get_csv_headers( FeedModel $feed ): array {
		return [
			'id',
			'title',
			'description',
			'availability',
			'condition',
			'price',
			'link',
			'image_link',
			'brand',
			'sale_price',
			'item_group_id',
			'google_product_category',
			'gtin',
			'mpn',
		];
	}

	public function format_csv_row( NormalizedProduct $product, FeedModel $feed ): array {
		return [
			$product->id,
			$product->title,
			$product->description,
			$this->map_meta_availability( $product->availability ),
			$product->condition,
			$product->format_price( $product->price ),
			$product->link,
			$product->image_link,
			$product->brand ?: get_bloginfo( 'name' ),
			null !== $product->sale_price ? $product->format_price( $product->sale_price ) : '',
			$product->is_variation ? $product->item_group_id : '',
			$product->google_product_category,
			$product->gtin,
			$product->mpn,
		];
	}
}
