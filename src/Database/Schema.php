<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database schema definitions for WPCalibrate Merchant Feed Manager.
 */
class Schema {

	public const DB_VERSION = '1.0.0';
	public const OPTION_DB_VERSION = 'wpcmfm_db_version';

	/**
	 * Get feeds table name.
	 *
	 * @return string
	 */
	public static function get_feeds_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpcmfm_feeds';
	}

	/**
	 * Get feed runs table name.
	 *
	 * @return string
	 */
	public static function get_runs_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpcmfm_feed_runs';
	}

	/**
	 * Get issues table name.
	 *
	 * @return string
	 */
	public static function get_issues_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpcmfm_issues';
	}

	/**
	 * Get category mappings table name.
	 *
	 * @return string
	 */
	public static function get_category_mappings_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpcmfm_category_mappings';
	}

	/**
	 * Return SQL queries required for table creation via dbDelta.
	 *
	 * @return array<string>
	 */
	public static function get_schema_sql(): array {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$feeds_table = self::get_feeds_table();
		$runs_table  = self::get_runs_table();
		$issues_table = self::get_issues_table();
		$cat_table   = self::get_category_mappings_table();

		return [
			"CREATE TABLE {$feeds_table} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(255) NOT NULL,
				slug VARCHAR(255) NOT NULL,
				token VARCHAR(64) NOT NULL,
				channel VARCHAR(64) NOT NULL,
				format VARCHAR(16) NOT NULL,
				status VARCHAR(32) NOT NULL DEFAULT 'active',
				target_country VARCHAR(8) NOT NULL DEFAULT '',
				target_language VARCHAR(12) NOT NULL DEFAULT '',
				target_currency VARCHAR(8) NOT NULL DEFAULT '',
				schedule VARCHAR(32) NOT NULL DEFAULT 'daily',
				batch_size INT(11) NOT NULL DEFAULT 100,
				rules_json LONGTEXT NULL,
				mappings_json LONGTEXT NULL,
				overrides_json LONGTEXT NULL,
				category_mappings_json LONGTEXT NULL,
				last_run_at DATETIME NULL,
				next_run_at DATETIME NULL,
				last_status VARCHAR(32) NOT NULL DEFAULT 'never_run',
				item_count INT(11) NOT NULL DEFAULT 0,
				file_path VARCHAR(255) NOT NULL DEFAULT '',
				file_size BIGINT(20) NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY slug (slug),
				KEY token (token),
				KEY status (status)
			) {$charset_collate};",

			"CREATE TABLE {$runs_table} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				feed_id BIGINT(20) UNSIGNED NOT NULL,
				run_token VARCHAR(64) NOT NULL,
				status VARCHAR(32) NOT NULL DEFAULT 'queued',
				current_offset INT(11) NOT NULL DEFAULT 0,
				total_products INT(11) NOT NULL DEFAULT 0,
				processed_products INT(11) NOT NULL DEFAULT 0,
				included_records INT(11) NOT NULL DEFAULT 0,
				excluded_records INT(11) NOT NULL DEFAULT 0,
				warning_count INT(11) NOT NULL DEFAULT 0,
				error_count INT(11) NOT NULL DEFAULT 0,
				message TEXT NULL,
				started_at DATETIME NULL,
				finished_at DATETIME NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY feed_id (feed_id),
				KEY status (status),
				KEY run_token (run_token)
			) {$charset_collate};",

			"CREATE TABLE {$issues_table} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				feed_id BIGINT(20) UNSIGNED NOT NULL,
				run_id BIGINT(20) UNSIGNED NOT NULL,
				product_id BIGINT(20) UNSIGNED NOT NULL,
				variation_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				severity VARCHAR(16) NOT NULL,
				field_name VARCHAR(64) NOT NULL,
				issue_code VARCHAR(64) NOT NULL,
				message TEXT NOT NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY feed_id (feed_id),
				KEY run_id (run_id),
				KEY product_id (product_id),
				KEY severity (severity),
				KEY issue_code (issue_code)
			) {$charset_collate};",

			"CREATE TABLE {$cat_table} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				feed_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				wc_category_id BIGINT(20) UNSIGNED NOT NULL,
				channel VARCHAR(64) NOT NULL,
				mapped_category_id VARCHAR(128) NOT NULL,
				mapped_category_name VARCHAR(255) NOT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY feed_wc_channel (feed_id, wc_category_id, channel)
			) {$charset_collate};"
		];
	}
}
