<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Category;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Standard Google Product Category taxonomy dataset and search utility.
 * Designed to be extensible via filter 'wpcmfm_google_product_categories'.
 */
class GoogleTaxonomy {

	/**
	 * Core standard Google Product Taxonomy tree (curated common branches with official Google numeric IDs).
	 *
	 * @return array<int, string> Map of ID => Full category path.
	 */
	public static function get_categories(): array {
		$categories = [
			166  => 'Apparel & Accessories',
			212  => 'Apparel & Accessories > Clothing',
			1604 => 'Apparel & Accessories > Clothing > Shirts & Tops',
			207  => 'Apparel & Accessories > Clothing > Pants',
			2271 => 'Apparel & Accessories > Clothing > Dresses',
			5322 => 'Apparel & Accessories > Clothing > Outerwear',
			187  => 'Apparel & Accessories > Shoes',
			167  => 'Apparel & Accessories > Clothing Accessories',
			178  => 'Apparel & Accessories > Jewelry',
			188  => 'Apparel & Accessories > Watches',
			536  => 'Home & Garden',
			632  => 'Home & Garden > Decor',
			696  => 'Home & Garden > Kitchen & Dining',
			569  => 'Home & Garden > Furniture',
			433  => 'Home & Garden > Bedding',
			700  => 'Home & Garden > Lawn & Garden',
			222  => 'Electronics',
			267  => 'Electronics > Computers',
			278  => 'Electronics > Audio',
			268  => 'Electronics > Video',
			269  => 'Electronics > Cameras & Optics',
			499  => 'Food, Beverages & Tobacco',
			422  => 'Food, Beverages & Tobacco > Beverages',
			423  => 'Food, Beverages & Tobacco > Food Items',
			5605 => 'Health & Beauty',
			473  => 'Health & Beauty > Personal Care',
			2853 => 'Health & Beauty > Cosmetics',
			479  => 'Health & Beauty > Hair Care',
			988  => 'Toys & Games',
			1239 => 'Toys & Games > Games',
			1234 => 'Toys & Games > Toys',
			2092 => 'Sporting Goods',
			1025 => 'Sporting Goods > Exercise & Fitness',
			1006 => 'Sporting Goods > Outdoor Recreation',
			783  => 'Hardware',
			436  => 'Media',
			888  => 'Vehicles & Parts',
			825  => 'Baby & Toddler',
			1    => 'Animals & Pet Supplies',
			2    => 'Animals & Pet Supplies > Pet Supplies',
			3    => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies',
			4    => 'Animals & Pet Supplies > Pet Supplies > Cat Supplies',
			437  => 'Office Supplies',
			5048 => 'Business & Industrial',
		];

		/**
		 * Filters the available Google Product Categories taxonomy list.
		 *
		 * @param array<int, string> $categories Map of ID => Path.
		 */
		return (array) apply_filters( 'wpcmfm_google_product_categories', $categories );
	}

	/**
	 * Search category tree by name or substring.
	 *
	 * @param string $keyword Search term.
	 * @param int    $limit Max results.
	 * @return array<array{id: int, name: string}>
	 */
	public static function search( string $keyword, int $limit = 20 ): array {
		$results = [];
		$keyword = trim( strtolower( $keyword ) );
		$all     = self::get_categories();

		foreach ( $all as $id => $name ) {
			if ( '' === $keyword || str_contains( strtolower( $name ), $keyword ) || str_contains( (string) $id, $keyword ) ) {
				$results[] = [
					'id'   => $id,
					'name' => $name,
				];

				if ( count( $results ) >= $limit ) {
					break;
				}
			}
		}

		return $results;
	}

	/**
	 * Get category name by ID.
	 *
	 * @param int|string $id Category ID.
	 * @return string Category path or ID as string if unknown.
	 */
	public static function get_name( int|string $id ): string {
		$categories = self::get_categories();
		$int_id     = (int) $id;

		return $categories[ $int_id ] ?? (string) $id;
	}
}
