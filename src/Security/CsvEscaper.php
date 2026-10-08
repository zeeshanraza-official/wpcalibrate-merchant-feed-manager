<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Escapes values for CSV output and protects against CSV formula injection.
 */
class CsvEscaper {

	/**
	 * Characters that trigger formula execution in spreadsheet software.
	 *
	 * @var array<string>
	 */
	private const FORMULA_TRIGGERS = [ '=', '+', '-', '@', "\t", "\r" ];

	/**
	 * Escape cell value for CSV, neutralizing formula triggers.
	 *
	 * @param mixed $value Cell value.
	 * @param bool  $sanitize_formulas Whether to neutralize formula triggers (true by default).
	 * @return string Escaped and quoted CSV field.
	 */
	public static function escape_cell( mixed $value, bool $sanitize_formulas = true ): string {
		if ( null === $value || '' === $value ) {
			return '';
		}

		$string_val = (string) $value;

		// Mitigate CSV Formula Injection (CWE-1236)
		if ( $sanitize_formulas && '' !== $string_val ) {
			$first_char = $string_val[0];
			if ( in_array( $first_char, self::FORMULA_TRIGGERS, true ) ) {
				$string_val = "'" . $string_val;
			}
		}

		// Double-quote quotes
		$escaped_quotes = str_replace( '"', '""', $string_val );

		// If string contains comma, quote, newline, or tab, wrap in double quotes
		if ( preg_match( '/[",\r\n\t]/', $escaped_quotes ) || str_starts_with( $string_val, "'" ) ) {
			return '"' . $escaped_quotes . '"';
		}

		return $escaped_quotes;
	}

	/**
	 * Format an array of row values as a standard CSV line (with CRLF).
	 *
	 * @param array<int, mixed> $row Array of column values.
	 * @param string            $delimiter Column delimiter (default comma).
	 * @param bool              $sanitize_formulas Formula injection mitigation.
	 * @return string Complete CSV line ending with CRLF.
	 */
	public static function format_row( array $row, string $delimiter = ',', bool $sanitize_formulas = true ): string {
		$cells = array_map(
			static fn( $cell ) => self::escape_cell( $cell, $sanitize_formulas ),
			$row
		);

		return implode( $delimiter, $cells ) . "\r\n";
	}
}
