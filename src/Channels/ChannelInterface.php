<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Channels;

use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Product\NormalizedProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface that all merchant channel adapters must implement.
 */
interface ChannelInterface {

	/**
	 * Unique channel identifier (e.g. 'google', 'meta', 'microsoft').
	 *
	 * @return string
	 */
	public function get_identifier(): string;

	/**
	 * Human-readable channel display name.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Supported file formats for this channel.
	 *
	 * @return array<string> List of formats ('xml', 'csv').
	 */
	public function get_supported_formats(): array;

	/**
	 * Default format for this channel.
	 *
	 * @return string
	 */
	public function get_default_format(): string;

	/**
	 * List of merchant fields strictly required by this channel.
	 *
	 * @return array<string>
	 */
	public function get_required_fields(): array;

	/**
	 * List of merchant fields strongly recommended by this channel.
	 *
	 * @return array<string>
	 */
	public function get_recommended_fields(): array;

	/**
	 * Get XML header string (e.g. <?xml version="1.0" encoding="UTF-8"?>).
	 *
	 * @param FeedModel $feed Feed model.
	 * @return string
	 */
	public function get_xml_header( FeedModel $feed ): string;

	/**
	 * Get XML opening tags (e.g. <rss version="2.0" xmlns:g="..."> <channel> <title>...).
	 *
	 * @param FeedModel $feed Feed model.
	 * @return string
	 */
	public function get_xml_open_tags( FeedModel $feed ): string;

	/**
	 * Get XML closing tags (e.g. </channel> </rss>).
	 *
	 * @param FeedModel $feed Feed model.
	 * @return string
	 */
	public function get_xml_close_tags( FeedModel $feed ): string;

	/**
	 * Format a normalized product into a single XML item string.
	 *
	 * @param NormalizedProduct $product Normalized product.
	 * @param FeedModel         $feed Feed configuration.
	 * @return string XML item.
	 */
	public function format_xml_item( NormalizedProduct $product, FeedModel $feed ): string;

	/**
	 * Get ordered CSV header columns.
	 *
	 * @param FeedModel $feed Feed model.
	 * @return array<string> Header names.
	 */
	public function get_csv_headers( FeedModel $feed ): array;

	/**
	 * Format a normalized product into an array of CSV cell values matching get_csv_headers().
	 *
	 * @param NormalizedProduct $product Normalized product.
	 * @param FeedModel         $feed Feed configuration.
	 * @return array<mixed>
	 */
	public function format_csv_row( NormalizedProduct $product, FeedModel $feed ): array;
}
