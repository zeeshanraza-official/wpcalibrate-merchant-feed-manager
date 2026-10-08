<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Feed;

use WPCalibrate\MerchantFeedManager\Database\Schema;
use WPCalibrate\MerchantFeedManager\Security\Security;
use WPCalibrate\MerchantFeedManager\Support\Logger;
use WPCalibrate\MerchantFeedManager\Validation\IssueRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository for managing Feed entities in custom database table.
 */
class FeedRepository {

	/**
	 * Insert a new feed entity.
	 *
	 * @param FeedModel $feed Feed model.
	 * @return int Inserted ID or 0 on failure.
	 */
	public static function insert( FeedModel $feed ): int {
		global $wpdb;

		if ( empty( $feed->token ) ) {
			$feed->token = wp_generate_password( 32, false );
		}

		if ( empty( $feed->slug ) ) {
			$feed->slug = sanitize_title( $feed->name );
		}

		$table = Schema::get_feeds_table();
		$data  = $feed->to_array();
		$now   = current_time( 'mysql', true );
		$data['created_at'] = $now;
		$data['updated_at'] = $now;

		$inserted = $wpdb->insert(
			$table,
			$data,
			[
				'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
				'%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s',
				'%d', '%s', '%s'
			]
		);

		if ( false === $inserted ) {
			Logger::error( 'Failed to insert feed into database', [ 'feed_name' => $feed->name ] );
			return 0;
		}

		$feed->id = (int) $wpdb->insert_id;
		return $feed->id;
	}

	/**
	 * Update an existing feed entity.
	 *
	 * @param FeedModel $feed Feed model.
	 * @return bool
	 */
	public static function update( FeedModel $feed ): bool {
		global $wpdb;

		if ( 0 === $feed->id ) {
			return false;
		}

		$table = Schema::get_feeds_table();
		$data  = $feed->to_array();
		$data['updated_at'] = current_time( 'mysql', true );

		$updated = $wpdb->update(
			$table,
			$data,
			[ 'id' => $feed->id ],
			[
				'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
				'%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s',
				'%d', '%s'
			],
			[ '%d' ]
		);

		return false !== $updated;
	}

	/**
	 * Delete a feed entity and associated files.
	 *
	 * @param int $id Feed ID.
	 * @param bool $delete_files Whether to remove generated files.
	 * @return bool
	 */
	public static function delete( int $id, bool $delete_files = true ): bool {
		global $wpdb;

		$feed = self::get_by_id( $id );
		if ( ! $feed ) {
			return false;
		}

		if ( $delete_files ) {
			FeedStorage::delete_feed_files( $feed );
		}

		// Delete associated issues and category mappings
		IssueRepository::delete_by_feed( $id );

		$cat_table = Schema::get_category_mappings_table();
		$wpdb->delete( $cat_table, [ 'feed_id' => $id ], [ '%d' ] );

		$runs_table = Schema::get_runs_table();
		$wpdb->delete( $runs_table, [ 'feed_id' => $id ], [ '%d' ] );

		$table = Schema::get_feeds_table();
		$deleted = $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );

		return false !== $deleted;
	}

	/**
	 * Duplicate an existing feed.
	 *
	 * @param int $id Source Feed ID.
	 * @return int New duplicate Feed ID or 0 on failure.
	 */
	public static function duplicate( int $id ): int {
		$source = self::get_by_id( $id );
		if ( ! $source ) {
			return 0;
		}

		$clone = clone $source;
		$clone->id          = 0;
		$clone->name        = sprintf( '%s (Copy)', $source->name );
		$clone->slug        = sanitize_title( $clone->name . '-' . wp_generate_password( 4, false ) );
		$clone->token       = wp_generate_password( 32, false );
		$clone->status      = 'inactive';
		$clone->last_status = 'never_run';
		$clone->last_run_at = null;
		$clone->next_run_at = null;
		$clone->item_count  = 0;
		$clone->file_path   = '';
		$clone->file_size   = 0;

		return self::insert( $clone );
	}

	/**
	 * Get a feed by its ID.
	 *
	 * @param int $id Feed ID.
	 * @return FeedModel|null
	 */
	public static function get_by_id( int $id ): ?FeedModel {
		global $wpdb;

		$table = Schema::get_feeds_table();
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
			ARRAY_A
		);

		return $row ? FeedModel::from_record( $row ) : null;
	}

	/**
	 * Get a feed by its public token.
	 *
	 * @param string $token Feed token.
	 * @return FeedModel|null
	 */
	public static function get_by_token( string $token ): ?FeedModel {
		global $wpdb;

		if ( empty( $token ) ) {
			return null;
		}

		$table = Schema::get_feeds_table();
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE token = %s", $token ),
			ARRAY_A
		);

		return $row ? FeedModel::from_record( $row ) : null;
	}

	/**
	 * Get all feeds with optional filtering and pagination.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<FeedModel>
	 */
	public static function get_all( array $args = [] ): array {
		global $wpdb;

		$table    = Schema::get_feeds_table();
		$defaults = [
			'status'  => '',
			'channel' => '',
			'orderby' => 'id',
			'order'   => 'DESC',
			'limit'   => 50,
			'offset'  => 0,
		];
		$params   = array_merge( $defaults, $args );

		$where_clauses = [ '1=1' ];
		$where_values  = [];

		if ( ! empty( $params['status'] ) ) {
			$where_clauses[] = 'status = %s';
			$where_values[]  = $params['status'];
		}

		if ( ! empty( $params['channel'] ) ) {
			$where_clauses[] = 'channel = %s';
			$where_values[]  = $params['channel'];
		}

		$allowed_order = [ 'id', 'name', 'status', 'last_run_at', 'created_at', 'item_count' ];
		$orderby = Security::sanitize_orderby( (string) $params['orderby'], $allowed_order, 'id' );
		$order   = Security::sanitize_order_dir( (string) $params['order'] );

		$where_sql = implode( ' AND ', $where_clauses );
		$sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$where_values[] = (int) $params['limit'];
		$where_values[] = (int) $params['offset'];

		$rows = $wpdb->get_results(
			$wpdb->prepare( $sql, ...$where_values ),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return [];
		}

		return array_map( static fn( $row ) => FeedModel::from_record( $row ), $rows );
	}

	/**
	 * Get total count of feeds matching filter.
	 *
	 * @param string $status Filter by status.
	 * @return int
	 */
	public static function count_feeds( string $status = '' ): int {
		global $wpdb;

		$table = Schema::get_feeds_table();
		if ( ! empty( $status ) ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(id) FROM {$table} WHERE status = %s", $status )
			);
		}

		return (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );
	}

	/**
	 * Count feeds with errors.
	 *
	 * @return int
	 */
	public static function count_error_feeds(): int {
		global $wpdb;
		$table = Schema::get_feeds_table();
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(id) FROM {$table} WHERE last_status = %s", 'failed' )
		);
	}
}
