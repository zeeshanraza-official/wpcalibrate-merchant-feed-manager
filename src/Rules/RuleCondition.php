<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Rules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Value object representing a single filter rule condition.
 */
class RuleCondition {

	public const TYPE_INCLUDE = 'include';
	public const TYPE_EXCLUDE = 'exclude';

	public string $type = self::TYPE_INCLUDE;
	public string $field = 'category';
	public string $operator = 'equals';
	public mixed $value = '';

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $data Raw condition data.
	 */
	public function __construct( array $data = [] ) {
		$this->type     = ( $data['type'] ?? 'include' ) === 'exclude' ? self::TYPE_EXCLUDE : self::TYPE_INCLUDE;
		$this->field    = sanitize_key( (string) ( $data['field'] ?? 'category' ) );
		$this->operator = sanitize_key( (string) ( $data['operator'] ?? 'equals' ) );
		$this->value    = $data['value'] ?? '';
	}

	/**
	 * Evaluate this condition against a given extracted product field value.
	 *
	 * @param mixed $actual Actual product value.
	 * @return bool True if condition matches.
	 */
	public function matches( mixed $actual ): bool {
		$expected = $this->value;

		switch ( $this->operator ) {
			case 'equals':
				if ( is_array( $actual ) ) {
					return in_array( $expected, $actual, false );
				}
				return (string) $actual === (string) $expected;

			case 'not_equals':
				if ( is_array( $actual ) ) {
					return ! in_array( $expected, $actual, false );
				}
				return (string) $actual !== (string) $expected;

			case 'contains':
				if ( is_array( $actual ) ) {
					foreach ( $actual as $item ) {
						if ( str_contains( strtolower( (string) $item ), strtolower( (string) $expected ) ) ) {
							return true;
						}
					}
					return false;
				}
				return str_contains( strtolower( (string) $actual ), strtolower( (string) $expected ) );

			case 'does_not_contain':
				if ( is_array( $actual ) ) {
					foreach ( $actual as $item ) {
						if ( str_contains( strtolower( (string) $item ), strtolower( (string) $expected ) ) ) {
							return false;
						}
					}
					return true;
				}
				return ! str_contains( strtolower( (string) $actual ), strtolower( (string) $expected ) );

			case 'greater_than':
				return (float) $actual > (float) $expected;

			case 'less_than':
				return (float) $actual < (float) $expected;

			case 'in':
				$list = is_array( $expected ) ? $expected : array_map( 'trim', explode( ',', (string) $expected ) );
				if ( is_array( $actual ) ) {
					return ! empty( array_intersect( $actual, $list ) );
				}
				return in_array( (string) $actual, array_map( 'strval', $list ), false );

			case 'not_in':
				$list = is_array( $expected ) ? $expected : array_map( 'trim', explode( ',', (string) $expected ) );
				if ( is_array( $actual ) ) {
					return empty( array_intersect( $actual, $list ) );
				}
				return ! in_array( (string) $actual, array_map( 'strval', $list ), false );

			default:
				return false;
		}
	}
}
