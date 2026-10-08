<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Validation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Domain entity representing a validation or data-quality issue on a product or variation.
 */
class ValidationIssue {

	public const SEVERITY_ERROR        = 'error';
	public const SEVERITY_WARNING      = 'warning';
	public const SEVERITY_MISSING_DATA = 'missing_data';
	public const SEVERITY_EXCLUDED     = 'excluded';

	public int $id = 0;
	public int $feed_id = 0;
	public int $run_id = 0;
	public int $product_id = 0;
	public int $variation_id = 0;
	public string $severity = self::SEVERITY_WARNING;
	public string $field_name = '';
	public string $issue_code = '';
	public string $message = '';
	public string $created_at = '';

	/**
	 * Construct a new validation issue.
	 */
	public function __construct(
		int $product_id = 0,
		string $field_name = '',
		string $severity = self::SEVERITY_WARNING,
		string $issue_code = '',
		string $message = '',
		int $variation_id = 0
	) {
		$this->product_id   = $product_id;
		$this->field_name   = $field_name;
		$this->severity     = $severity;
		$this->issue_code   = $issue_code;
		$this->message      = $message;
		$this->variation_id = $variation_id;
	}

	/**
	 * Create from database row.
	 *
	 * @param array<string, mixed>|object $data Database row.
	 * @return self
	 */
	public static function from_record( array|object $data ): self {
		$row = (array) $data;
		$issue = new self();

		$issue->id           = (int) ( $row['id'] ?? 0 );
		$issue->feed_id      = (int) ( $row['feed_id'] ?? 0 );
		$issue->run_id       = (int) ( $row['run_id'] ?? 0 );
		$issue->product_id   = (int) ( $row['product_id'] ?? 0 );
		$issue->variation_id = (int) ( $row['variation_id'] ?? 0 );
		$issue->severity     = (string) ( $row['severity'] ?? self::SEVERITY_WARNING );
		$issue->field_name   = (string) ( $row['field_name'] ?? '' );
		$issue->issue_code   = (string) ( $row['issue_code'] ?? '' );
		$issue->message      = (string) ( $row['message'] ?? '' );
		$issue->created_at   = (string) ( $row['created_at'] ?? '' );

		return $issue;
	}

	/**
	 * Convert to array for database storage.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return [
			'feed_id'      => $this->feed_id,
			'run_id'       => $this->run_id,
			'product_id'   => $this->product_id,
			'variation_id' => $this->variation_id,
			'severity'     => $this->severity,
			'field_name'   => $this->field_name,
			'issue_code'   => $this->issue_code,
			'message'      => $this->message,
		];
	}
}
