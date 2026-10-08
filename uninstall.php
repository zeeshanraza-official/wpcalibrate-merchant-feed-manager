<?php
/**
 * Uninstallation handler for WPCalibrate Merchant Feed Manager.
 *
 * @package WPCalibrate\MerchantFeedManager
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check user setting for data deletion on uninstall
$settings                 = get_option( 'wpcmfm_settings', [] );
$delete_data_on_uninstall = ! empty( $settings['delete_data_on_uninstall'] );

if ( ! $delete_data_on_uninstall ) {
	// Preserve user feeds and settings according to default retention policy
	return;
}

global $wpdb;

// 1. Drop custom plugin tables safely
$tables = [
	$wpdb->prefix . 'wpcmfm_issues',
	$wpdb->prefix . 'wpcmfm_feed_runs',
	$wpdb->prefix . 'wpcmfm_category_mappings',
	$wpdb->prefix . 'wpcmfm_feeds',
];

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// 2. Delete plugin options
$options_to_delete = [
	'wpcmfm_settings',
	'wpcmfm_db_version',
	'wpcmfm_license_data',
];

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

// 3. Clear plugin transients
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_wpcmfm_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_wpcmfm_' ) . '%'
	)
);

// 4. Remove generated feed files
$upload_dir = wp_upload_dir();
$feed_dir   = $upload_dir['basedir'] . '/wpcmfm-feeds';

if ( is_dir( $feed_dir ) ) {
	$files = glob( $feed_dir . '/*' );
	if ( is_array( $files ) ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				@unlink( $file );
			}
		}
	}
	@rmdir( $feed_dir );
}
