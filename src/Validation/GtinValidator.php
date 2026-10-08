<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Validation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Structural and GS1 modulo-10 checksum validation for GTIN (UPC, EAN, ISBN).
 */
class GtinValidator {

	/**
	 * Validate a GTIN string.
	 * Supports GTIN-8, GTIN-12 (UPC-A), GTIN-13 (EAN-13), and GTIN-14.
	 *
	 * @param string $gtin Raw GTIN string.
	 * @return bool True if structurally valid with correct check digit.
	 */
	public static function is_valid( string $gtin ): bool {
		// Clean whitespace and dashes
		$clean = preg_replace( '/[\s\-]/', '', $gtin );

		// Must be digits only
		if ( ! preg_match( '/^\d+$/', $clean ) ) {
			return false;
		}

		$length = strlen( $clean );
		if ( ! in_array( $length, [ 8, 12, 13, 14 ], true ) ) {
			return false;
		}

		// Prevent known dummy sequences like all zeros
		if ( preg_match( '/^0+$/', $clean ) ) {
			return false;
		}

		// Validate GS1 Modulo-10 check digit
		return self::validate_check_digit( $clean );
	}

	/**
	 * Calculate and verify GS1 Modulo-10 checksum.
	 *
	 * @param string $digits Numeric string.
	 * @return bool
	 */
	public static function validate_check_digit( string $digits ): bool {
		$length = strlen( $digits );
		$payload = substr( $digits, 0, $length - 1 );
		$check_digit = (int) $digits[ $length - 1 ];

		$calculated = self::calculate_check_digit( $payload );
		return $calculated === $check_digit;
	}

	/**
	 * Calculate GS1 check digit for a given numeric payload.
	 *
	 * @param string $payload Digits excluding the check digit.
	 * @return int Check digit (0-9).
	 */
	public static function calculate_check_digit( string $payload ): int {
		$sum = 0;
		$reversed = strrev( $payload );

		// In GS1 modulo-10, the digit immediately preceding the check digit is multiplied by 3,
		// the next by 1, alternating.
		for ( $i = 0, $len = strlen( $reversed ); $i < $len; $i++ ) {
			$digit = (int) $reversed[ $i ];
			$multiplier = ( 0 === $i % 2 ) ? 3 : 1;
			$sum += $digit * $multiplier;
		}

		$mod = $sum % 10;
		return ( 0 === $mod ) ? 0 : 10 - $mod;
	}
}
