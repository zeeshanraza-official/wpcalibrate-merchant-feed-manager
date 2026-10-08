<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Feed;

use WPCalibrate\MerchantFeedManager\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database repository for feed runs.
 */
class FeedRunRepository {

	/**
	 * Insert a new feed run.
	 *
	 * @param FeedRunModel $run Run model.
	 * @return int Inserted run ID or 0 on failure.
	 */
	public static function insert( FeedRunModel $run ): int {
		global $wpdb;

		$table = Schema::get_runs_table();
		$data  = $run->to_array();
		$data['created_at'] = current_time( 'mysql', true );

		$inserted = $wpdb->insert(
			$table,
			$data,
			[ '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s' ]
		);

		if ( false === $inserted ) {
			return 0;
		}

		$run->id = (int) $wpdb->insert_id;
		return $run->id;
	}

	/**
	 * Update an existing feed run.
	 *
	 * @param FeedRunModel $run Run model.
	 * @return bool
	 */
	public static function update( FeedRunModel $run ): bool {
		global $wpdb;

		if ( 0 === $run->id ) {
			return false;
		}

		$table = Schema::get_runs_table();
		$data  = $run->to_array();

		$updated = $wpdb->update(
			$table,
			$data,
			[ 'id' => $run->id ],
			[ '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s' ],
			[ '%d' ]
		);

		return false !== $updated;
	}

	/**
	 * Get run by ID.
	 *
	 * @param int $id Run ID.
	 * @return FeedRunModel|null
	 */
	public static function get_by_id( int $id ): ?FeedRunModel {
		global $wpdb;

		$table = Schema::get_runs_table();
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
			ARRAY_A
		);

		return $row ? FeedRunModel::from_record( $row ) : null;
	}

	/**
	 * Get latest run for a specific feed.
	 *
	 * @param int $feed_id Feed ID.
	 * @return FeedRunModel|null
	 */
	public static function get_latest_for_feed( int $feed_id ): ?FeedRunModel {
		global $wpdb;

		$table = Schema::get_runs_table();
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE feed_id = %d ORDER BY id DESC LIMIT 1", $feed_id ),
			ARRAY_A
		);

		return $row ? FeedRunModel::from_record( $row ) : null;
	}

	/**
	 * Get recent runs across all feeds.
	 *
	 * @param int $limit Maximum records.
	 * @return array<FeedRunModel>
	 */
	public static function get_recent_runs( int $limit = 10 ): array {
		global $wpdb;

		$table = Schema::get_runs_table();
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", max( 1, $limit ) ),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return [];
		}

		return array_map( static fn( $row ) => FeedRunModel::from_record( $row ), $rows );
	}

	/**
	 * Delete runs older than a given number of days (data retention policy).
	 *
	 * @param int $days Retention threshold in days.
	 * @return int Number of deleted runs.
	 */
	public static function prune_old_runs( int $days ): int {
		global $wpdb;

		$days = max( 1, $days );
		$cutoff_date = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		$table = Schema::get_runs_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff_date )
		);

		return false !== $deleted ? (int) $deleted : 0;
	}
}
