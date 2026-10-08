<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Database;

use WPCalibrate\MerchantFeedManager\Support\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migration manager for WPCalibrate Merchant Feed Manager.
 */
class Migrations {

	/**
	 * Run required database migrations.
	 */
	public static function migrate(): void {
		$installed_version = get_option( Schema::OPTION_DB_VERSION, '0.0.0' );

		if ( version_compare( (string) $installed_version, Schema::DB_VERSION, '>=' ) ) {
			return;
		}

		self::execute_schema_update();
		update_option( Schema::OPTION_DB_VERSION, Schema::DB_VERSION, false );

		Logger::info(
			'Database migration completed successfully',
			[
				'previous_version' => $installed_version,
				'current_version'  => Schema::DB_VERSION,
			]
		);
	}

	/**
	 * Execute dbDelta schema updates safely.
	 */
	public static function execute_schema_update(): void {
		if ( ! function_exists( 'dbDelta' ) ) {
			$upgrade_file = ABSPATH . 'wp-admin/includes/upgrade.php';
			if ( file_exists( $upgrade_file ) ) {
				require_once $upgrade_file;
			}
		}

		$sqls = Schema::get_schema_sql();
		foreach ( $sqls as $sql ) {
			dbDelta( $sql );
		}
	}

	/**
	 * Drop all custom tables (Used ONLY upon complete uninstallation when configured by user).
	 */
	public static function drop_tables(): void {
		global $wpdb;

		$tables = [
			Schema::get_issues_table(),
			Schema::get_runs_table(),
			Schema::get_category_mappings_table(),
			Schema::get_feeds_table(),
		];

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		delete_option( Schema::OPTION_DB_VERSION );
	}
}
