<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Feed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Domain model representing a single merchant feed entity.
 */
class FeedModel {

	public int $id = 0;
	public string $name = '';
	public string $slug = '';
	public string $token = '';
	public string $channel = 'google';
	public string $format = 'xml';
	public string $status = 'active';
	public string $target_country = 'US';
	public string $target_language = 'en';
	public string $target_currency = 'USD';
	public string $schedule = 'daily';
	public int $batch_size = 100;
	public array $rules = [];
	public array $mappings = [];
	public array $overrides = [];
	public array $category_mappings = [];
	public ?string $last_run_at = null;
	public ?string $next_run_at = null;
	public string $last_status = 'never_run';
	public int $item_count = 0;
	public string $file_path = '';
	public int $file_size = 0;
	public string $created_at = '';
	public string $updated_at = '';

	/**
	 * Create an instance from database row array or object.
	 *
	 * @param array<string, mixed>|object $data Raw database row.
	 * @return self
	 */
	public static function from_record( array|object $data ): self {
		$row = (array) $data;
		$model = new self();

		$model->id              = (int) ( $row['id'] ?? 0 );
		$model->name            = (string) ( $row['name'] ?? '' );
		$model->slug            = (string) ( $row['slug'] ?? '' );
		$model->token           = (string) ( $row['token'] ?? '' );
		$model->channel         = (string) ( $row['channel'] ?? 'google' );
		$model->format          = (string) ( $row['format'] ?? 'xml' );
		$model->status          = (string) ( $row['status'] ?? 'active' );
		$model->target_country  = (string) ( $row['target_country'] ?? '' );
		$model->target_language = (string) ( $row['target_language'] ?? '' );
		$model->target_currency = (string) ( $row['target_currency'] ?? '' );
		$model->schedule        = (string) ( $row['schedule'] ?? 'daily' );
		$model->batch_size      = max( 10, (int) ( $row['batch_size'] ?? 100 ) );

		$model->rules             = self::decode_json( $row['rules_json'] ?? null );
		$model->mappings          = self::decode_json( $row['mappings_json'] ?? null );
		$model->overrides         = self::decode_json( $row['overrides_json'] ?? null );
		$model->category_mappings = self::decode_json( $row['category_mappings_json'] ?? null );

		$model->last_run_at   = ! empty( $row['last_run_at'] ) ? (string) $row['last_run_at'] : null;
		$model->next_run_at   = ! empty( $row['next_run_at'] ) ? (string) $row['next_run_at'] : null;
		$model->last_status   = (string) ( $row['last_status'] ?? 'never_run' );
		$model->item_count    = (int) ( $row['item_count'] ?? 0 );
		$model->file_path     = (string) ( $row['file_path'] ?? '' );
		$model->file_size     = (int) ( $row['file_size'] ?? 0 );
		$model->created_at    = (string) ( $row['created_at'] ?? '' );
		$model->updated_at    = (string) ( $row['updated_at'] ?? '' );

		return $model;
	}

	/**
	 * Convert model to array for database insertion or update.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return [
			'name'                   => $this->name,
			'slug'                   => $this->slug,
			'token'                  => $this->token,
			'channel'                => $this->channel,
			'format'                 => $this->format,
			'status'                 => $this->status,
			'target_country'         => $this->target_country,
			'target_language'        => $this->target_language,
			'target_currency'        => $this->target_currency,
			'schedule'               => $this->schedule,
			'batch_size'             => $this->batch_size,
			'rules_json'             => wp_json_encode( $this->rules ),
			'mappings_json'          => wp_json_encode( $this->mappings ),
			'overrides_json'         => wp_json_encode( $this->overrides ),
			'category_mappings_json' => wp_json_encode( $this->category_mappings ),
			'last_run_at'            => $this->last_run_at,
			'next_run_at'            => $this->next_run_at,
			'last_status'            => $this->last_status,
			'item_count'             => $this->item_count,
			'file_path'              => $this->file_path,
			'file_size'              => $this->file_size,
		];
	}

	/**
	 * Is feed active?
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return 'active' === $this->status;
	}

	/**
	 * Decode JSON with fallback to empty array.
	 *
	 * @param mixed $json Raw JSON string or null.
	 * @return array<mixed>
	 */
	private static function decode_json( mixed $json ): array {
		if ( empty( $json ) || ! is_string( $json ) ) {
			return [];
		}

		$decoded = json_decode( $json, true );
		return is_array( $decoded ) ? $decoded : [];
	}
}
