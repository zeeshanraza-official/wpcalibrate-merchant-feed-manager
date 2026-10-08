<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Feed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Domain model representing a single feed generation run/execution.
 */
class FeedRunModel {

	public int $id = 0;
	public int $feed_id = 0;
	public string $run_token = '';
	public string $status = 'queued';
	public int $current_offset = 0;
	public int $total_products = 0;
	public int $processed_products = 0;
	public int $included_records = 0;
	public int $excluded_records = 0;
	public int $warning_count = 0;
	public int $error_count = 0;
	public ?string $message = null;
	public ?string $started_at = null;
	public ?string $finished_at = null;
	public string $created_at = '';

	/**
	 * Instantiate from database row.
	 *
	 * @param array<string, mixed>|object $data Database row.
	 * @return self
	 */
	public static function from_record( array|object $data ): self {
		$row = (array) $data;
		$model = new self();

		$model->id                 = (int) ( $row['id'] ?? 0 );
		$model->feed_id             = (int) ( $row['feed_id'] ?? 0 );
		$model->run_token           = (string) ( $row['run_token'] ?? '' );
		$model->status              = (string) ( $row['status'] ?? 'queued' );
		$model->current_offset      = (int) ( $row['current_offset'] ?? 0 );
		$model->total_products      = (int) ( $row['total_products'] ?? 0 );
		$model->processed_products  = (int) ( $row['processed_products'] ?? 0 );
		$model->included_records    = (int) ( $row['included_records'] ?? 0 );
		$model->excluded_records    = (int) ( $row['excluded_records'] ?? 0 );
		$model->warning_count       = (int) ( $row['warning_count'] ?? 0 );
		$model->error_count         = (int) ( $row['error_count'] ?? 0 );
		$model->message             = ! empty( $row['message'] ) ? (string) $row['message'] : null;
		$model->started_at          = ! empty( $row['started_at'] ) ? (string) $row['started_at'] : null;
		$model->finished_at         = ! empty( $row['finished_at'] ) ? (string) $row['finished_at'] : null;
		$model->created_at          = (string) ( $row['created_at'] ?? '' );

		return $model;
	}

	/**
	 * Convert model to array for storage.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return [
			'feed_id'            => $this->feed_id,
			'run_token'          => $this->run_token,
			'status'             => $this->status,
			'current_offset'     => $this->current_offset,
			'total_products'     => $this->total_products,
			'processed_products' => $this->processed_products,
			'included_records'   => $this->included_records,
			'excluded_records'   => $this->excluded_records,
			'warning_count'      => $this->warning_count,
			'error_count'        => $this->error_count,
			'message'            => $this->message,
			'started_at'         => $this->started_at,
			'finished_at'        => $this->finished_at,
		];
	}
}
