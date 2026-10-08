<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Validation;

use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Product\NormalizedProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates normalized products against merchant feed standards and channel-specific rules.
 */
class ValidationEngine {

	/**
	 * Validate a normalized product against common merchant standards and return all issues found.
	 *
	 * @param NormalizedProduct $product Normalized product entity.
	 * @param FeedModel         $feed Feed configuration.
	 * @return array<ValidationIssue> List of validation issues.
	 */
	public static function validate_product( NormalizedProduct $product, FeedModel $feed ): array {
		$issues = [];
		$pid = $product->product_id;
		$vid = $product->is_variation ? $product->product_id : 0;

		// 1. Title checks
		if ( empty( trim( $product->title ) ) ) {
			$issues[] = new ValidationIssue(
				$pid,
				'title',
				ValidationIssue::SEVERITY_ERROR,
				'missing_title',
				__( 'Product title is empty.', 'wpcalibrate-merchant-feed-manager' ),
				$vid
			);
		} elseif ( mb_strlen( $product->title ) > 150 ) {
			$issues[] = new ValidationIssue(
				$pid,
				'title',
				ValidationIssue::SEVERITY_WARNING,
				'title_too_long',
				__( 'Product title exceeds 150 characters, which may be truncated by merchant platforms.', 'wpcalibrate-merchant-feed-manager' ),
				$vid
			);
		}

		// 2. Description checks
		if ( empty( trim( $product->description ) ) ) {
			$issues[] = new ValidationIssue(
				$pid,
				'description',
				ValidationIssue::SEVERITY_MISSING_DATA,
				'missing_description',
				__( 'Product description is missing.', 'wpcalibrate-merchant-feed-manager' ),
				$vid
			);
		}

		// 3. Link checks
		if ( empty( $product->link ) ) {
			$issues[] = new ValidationIssue(
				$pid,
				'link',
				ValidationIssue::SEVERITY_ERROR,
				'missing_link',
				__( 'Product landing page URL is missing.', 'wpcalibrate-merchant-feed-manager' ),
				$vid
			);
		} elseif ( ! filter_var( $product->link, FILTER_VALIDATE_URL ) ) {
			$issues[] = new ValidationIssue(
				$pid,
				'link',
				ValidationIssue::SEVERITY_ERROR,
				'malformed_link',
				__( 'Product landing page URL is malformed.', 'wpcalibrate-merchant-feed-manager' ),
				$vid
			);
		}

		// 4. Image URL checks
		if ( empty( $product->image_link ) ) {
			$issues[] = new ValidationIssue(
				$pid,
				'image_link',
				ValidationIssue::SEVERITY_ERROR,
				'missing_image_link',
				__( 'Main product image URL is missing.', 'wpcalibrate-merchant-feed-manager' ),
				$vid
			);
		} elseif ( ! filter_var( $product->image_link, FILTER_VALIDATE_URL ) ) {
			$issues[] = new ValidationIssue(
				$pid,
				'image_link',
				ValidationIssue::SEVERITY_ERROR,
				'malformed_image_link',
				__( 'Main product image URL is invalid or malformed.', 'wpcalibrate-merchant-feed-manager' ),
				$vid
			);
		}

		// 5. Price checks
		if ( $product->price <= 0.0 ) {
			$issues[] = new ValidationIssue(
				$pid,
				'price',
				ValidationIssue::SEVERITY_ERROR,
				'invalid_price',
				__( 'Product price must be greater than 0.00.', 'wpcalibrate-merchant-feed-manager' ),
				$vid
			);
		}

		if ( null !== $product->sale_price && $product->sale_price >= $product->price ) {
			$issues[] = new ValidationIssue(
				$pid,
				'sale_price',
				ValidationIssue::SEVERITY_WARNING,
				'sale_price_invalid',
				__( 'Sale price is greater than or equal to the regular price.', 'wpcalibrate-merchant-feed-manager' ),
				$vid
			);
		}

		// 6. Availability
		if ( empty( $product->availability ) ) {
			$issues[] = new ValidationIssue(
				$pid,
				'availability',
				ValidationIssue::SEVERITY_ERROR,
				'missing_availability',
				__( 'Availability status is not specified.', 'wpcalibrate-merchant-feed-manager' ),
				$vid
			);
		}

		// 7. Identifiers (GTIN, MPN, Brand)
		if ( ! empty( $product->gtin ) ) {
			if ( ! GtinValidator::is_valid( $product->gtin ) ) {
				$issues[] = new ValidationIssue(
					$pid,
					'gtin',
					ValidationIssue::SEVERITY_WARNING,
					'invalid_gtin',
					sprintf(
						/* translators: %s: GTIN code */
						__( 'GTIN code "%s" failed structural length or GS1 checksum verification.', 'wpcalibrate-merchant-feed-manager' ),
						esc_html( $product->gtin )
					),
					$vid
				);
			}
		} elseif ( $product->identifier_exists && empty( $product->mpn ) && empty( $product->brand ) ) {
			$issues[] = new ValidationIssue(
				$pid,
				'identifiers',
				ValidationIssue::SEVERITY_MISSING_DATA,
				'missing_unique_identifiers',
				__( 'Product is missing GTIN, Brand, or MPN identifiers.', 'wpcalibrate-merchant-feed-manager' ),
				$vid
			);
		}

		// 8. Google Product Category
		if ( 'google' === $feed->channel && empty( $product->google_product_category ) ) {
			$issues[] = new ValidationIssue(
				$pid,
				'google_product_category',
				ValidationIssue::SEVERITY_WARNING,
				'missing_google_product_category',
				__( 'Google Product Category is not mapped for this item.', 'wpcalibrate-merchant-feed-manager' ),
				$vid
			);
		}

		// 9. Variation specific requirements
		if ( $product->is_variation ) {
			if ( empty( $product->item_group_id ) ) {
				$issues[] = new ValidationIssue(
					$pid,
					'item_group_id',
					ValidationIssue::SEVERITY_ERROR,
					'missing_item_group_id',
					__( 'Variation is missing required item_group_id.', 'wpcalibrate-merchant-feed-manager' ),
					$vid
				);
			}
		}

		/**
		 * Filter validation issues for a product.
		 *
		 * @param array<ValidationIssue> $issues List of issues.
		 * @param NormalizedProduct      $product Normalized product.
		 * @param FeedModel              $feed Feed configuration.
		 */
		return (array) apply_filters( 'wpcmfm_product_validation_issues', $issues, $product, $feed );
	}
}
