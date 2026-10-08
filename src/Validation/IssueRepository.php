<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Validation;

use WPCalibrate\MerchantFeedManager\Database\Schema;
use WPCalibrate\MerchantFeedManager\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository for managing and querying feed validation issues.
 */
class IssueRepository {

	/**
	 * Insert a collection of validation issues in bulk.
	 *
	 * @param array<ValidationIssue> $issues List of issues.
	 * @return int Number of inserted issues.
	 */
	public static function insert_batch( array $issues ): int {
		global $wpdb;

		if ( empty( $issues ) ) {
			return 0;
		}

		$table = Schema::get_issues_table();
		$now   = current_time( 'mysql', true );
		$count = 0;

		// Insert in chunks of 50 to avoid query size limits
		$chunks = array_chunk( $issues, 50 );
		foreach ( $chunks as $chunk ) {
			$placeholders = [];
			$values       = [];

			foreach ( $chunk as $issue ) {
				$placeholders[] = '(%d, %d, %d, %d, %s, %s, %s, %s, %s)';
				$values[]       = $issue->feed_id;
				$values[]       = $issue->run_id;
				$values[]       = $issue->product_id;
				$values[]       = $issue->variation_id;
				$values[]       = $issue->severity;
				$values[]       = $issue->field_name;
				$values[]       = $issue->issue_code;
				$values[]       = $issue->message;
				$values[]       = $now;
			}

			$query = "INSERT INTO {$table} (feed_id, run_id, product_id, variation_id, severity, field_name, issue_code, message, created_at) VALUES " . implode( ', ', $placeholders );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$res = $wpdb->query( $wpdb->prepare( $query, ...$values ) );
			if ( false !== $res ) {
				$count += count( $chunk );
			}
		}

		return $count;
	}

	/**
	 * Get issues with filtering and pagination.
	 *
	 * @param array<string, mixed> $args Filter parameters.
	 * @return array<ValidationIssue>
	 */
	public static function get_issues( array $args = [] ): array {
		global $wpdb;

		$table    = Schema::get_issues_table();
		$defaults = [
			'feed_id'    => 0,
			'run_id'     => 0,
			'product_id' => 0,
			'severity'   => '',
			'issue_code' => '',
			'orderby'    => 'id',
			'order'      => 'DESC',
			'limit'      => 50,
			'offset'     => 0,
		];
		$params = array_merge( $defaults, $args );

		$where_clauses = [ '1=1' ];
		$where_values  = [];

		if ( ! empty( $params['feed_id'] ) ) {
			$where_clauses[] = 'feed_id = %d';
			$where_values[]  = (int) $params['feed_id'];
		}

		if ( ! empty( $params['run_id'] ) ) {
			$where_clauses[] = 'run_id = %d';
			$where_values[]  = (int) $params['run_id'];
		}

		if ( ! empty( $params['product_id'] ) ) {
			$where_clauses[] = 'product_id = %d';
			$where_values[]  = (int) $params['product_id'];
		}

		if ( ! empty( $params['severity'] ) ) {
			$where_clauses[] = 'severity = %s';
			$where_values[]  = (string) $params['severity'];
		}

		if ( ! empty( $params['issue_code'] ) ) {
			$where_clauses[] = 'issue_code = %s';
			$where_values[]  = (string) $params['issue_code'];
		}

		$allowed_order = [ 'id', 'product_id', 'severity', 'field_name', 'created_at' ];
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

		return array_map( static fn( $row ) => ValidationIssue::from_record( $row ), $rows );
	}

	/**
	 * Count total issues matching filters.
	 *
	 * @param array<string, mixed> $args Filters.
	 * @return int
	 */
	public static function count_issues( array $args = [] ): int {
		global $wpdb;

		$table = Schema::get_issues_table();
		$where_clauses = [ '1=1' ];
		$where_values  = [];

		if ( ! empty( $args['feed_id'] ) ) {
			$where_clauses[] = 'feed_id = %d';
			$where_values[]  = (int) $args['feed_id'];
		}

		if ( ! empty( $args['severity'] ) ) {
			$where_clauses[] = 'severity = %s';
			$where_values[]  = (string) $args['severity'];
		}

		$where_sql = implode( ' AND ', $where_clauses );
		if ( empty( $where_values ) ) {
			return (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table} WHERE {$where_sql}" );
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(id) FROM {$table} WHERE {$where_sql}", ...$where_values )
		);
	}

	/**
	 * Get most common issues grouped by issue_code.
	 *
	 * @param int $feed_id Feed ID (0 for all).
	 * @param int $limit Maximum results.
	 * @return array<array{issue_code: string, field_name: string, severity: string, count: int, message: string}>
	 */
	public static function get_most_common_issues( int $feed_id = 0, int $limit = 5 ): array {
		global $wpdb;

		$table = Schema::get_issues_table();
		if ( $feed_id > 0 ) {
			$sql = $wpdb->prepare(
				"SELECT issue_code, field_name, severity, message, COUNT(id) as count 
				 FROM {$table} 
				 WHERE feed_id = %d 
				 GROUP BY issue_code, field_name, severity, message 
				 ORDER BY count DESC 
				 LIMIT %d",
				$feed_id,
				max( 1, $limit )
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT issue_code, field_name, severity, message, COUNT(id) as count 
				 FROM {$table} 
				 GROUP BY issue_code, field_name, severity, message 
				 ORDER BY count DESC 
				 LIMIT %d",
				max( 1, $limit )
			);
		}

		$rows = $wpdb->get_results( $sql, ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return [];
		}

		return array_map(
			static fn( $row ) => [
				'issue_code' => (string) $row['issue_code'],
				'field_name' => (string) $row['field_name'],
				'severity'   => (string) $row['severity'],
				'message'    => (string) $row['message'],
				'count'      => (int) $row['count'],
			],
			$rows
		);
	}

	/**
	 * Delete all issues for a feed.
	 *
	 * @param int $feed_id Feed ID.
	 * @return int
	 */
	public static function delete_by_feed( int $feed_id ): int {
		global $wpdb;
		$table = Schema::get_issues_table();
		$res = $wpdb->delete( $table, [ 'feed_id' => $feed_id ], [ '%d' ] );
		return false !== $res ? (int) $res : 0;
	}

	/**
	 * Prune old issues according to data retention days.
	 *
	 * @param int $days Retention threshold in days.
	 * @return int
	 */
	public static function prune_old_issues( int $days ): int {
		global $wpdb;
		$days = max( 1, $days );
		$cutoff_date = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		$table = Schema::get_issues_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff_date )
		);

		return false !== $deleted ? (int) $deleted : 0;
	}
}
