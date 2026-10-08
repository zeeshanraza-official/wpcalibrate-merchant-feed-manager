<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Domain entity representing a normalized product record with all merchant fields.
 */
class NormalizedProduct {

	public int $product_id = 0;
	public string $id = '';
	public string $item_group_id = '';
	public bool $is_variation = false;
	public int $parent_id = 0;

	public string $title = '';
	public string $description = '';
	public string $link = '';
	public string $image_link = '';
	public array $additional_image_links = [];

	public string $availability = 'in_stock';
	public float $price = 0.0;
	public ?float $sale_price = null;
	public ?string $sale_price_effective_date = null;
	public string $currency = 'USD';

	public string $brand = '';
	public string $gtin = '';
	public string $mpn = '';
	public bool $identifier_exists = true;
	public string $condition = 'new';

	public string $google_product_category = '';
	public string $product_type = '';

	public string $color = '';
	public string $size = '';
	public string $gender = '';
	public string $age_group = '';
	public string $material = '';
	public string $pattern = '';

	public string $shipping_weight = '';
	public array $shipping_dimensions = [];

	public array $custom_labels = [
		'custom_label_0' => '',
		'custom_label_1' => '',
		'custom_label_2' => '',
		'custom_label_3' => '',
		'custom_label_4' => '',
	];

	public array $custom_fields = [];
	public array $raw_attributes = [];

	/**
	 * Get formatted price string for feeds (e.g. "29.99 USD").
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	public function format_price( float $amount ): string {
		return sprintf( '%.2f %s', $amount, $this->currency );
	}

	/**
	 * Convert to standard dictionary.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return [
			'id'                         => $this->id,
			'item_group_id'              => $this->item_group_id,
			'is_variation'               => $this->is_variation,
			'parent_id'                  => $this->parent_id,
			'title'                      => $this->title,
			'description'                => $this->description,
			'link'                       => $this->link,
			'image_link'                 => $this->image_link,
			'additional_image_links'     => $this->additional_image_links,
			'availability'               => $this->availability,
			'price'                      => $this->price,
			'sale_price'                 => $this->sale_price,
			'currency'                   => $this->currency,
			'brand'                      => $this->brand,
			'gtin'                       => $this->gtin,
			'mpn'                        => $this->mpn,
			'identifier_exists'          => $this->identifier_exists,
			'condition'                  => $this->condition,
			'google_product_category'    => $this->google_product_category,
			'product_type'               => $this->product_type,
			'color'                      => $this->color,
			'size'                       => $this->size,
			'gender'                     => $this->gender,
			'age_group'                  => $this->age_group,
			'material'                   => $this->material,
			'pattern'                    => $this->pattern,
			'shipping_weight'            => $this->shipping_weight,
			'custom_label_0'             => $this->custom_labels['custom_label_0'] ?? '',
			'custom_label_1'             => $this->custom_labels['custom_label_1'] ?? '',
			'custom_label_2'             => $this->custom_labels['custom_label_2'] ?? '',
			'custom_label_3'             => $this->custom_labels['custom_label_3'] ?? '',
			'custom_label_4'             => $this->custom_labels['custom_label_4'] ?? '',
			'custom_fields'              => $this->custom_fields,
		];
	}
}
