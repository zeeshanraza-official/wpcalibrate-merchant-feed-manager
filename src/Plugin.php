<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager;

use WPCalibrate\MerchantFeedManager\Admin\Menu;
use WPCalibrate\MerchantFeedManager\Admin\ProductMetabox;
use WPCalibrate\MerchantFeedManager\Api\AjaxHandler;
use WPCalibrate\MerchantFeedManager\Api\FeedDelivery;
use WPCalibrate\MerchantFeedManager\Compatibility\DependencyChecker;
use WPCalibrate\MerchantFeedManager\Compatibility\HposCompatibility;
use WPCalibrate\MerchantFeedManager\Database\Migrations;
use WPCalibrate\MerchantFeedManager\Feed\FeedRunRepository;
use WPCalibrate\MerchantFeedManager\Feed\FeedStorage;
use WPCalibrate\MerchantFeedManager\Generator\BatchProcessor;
use WPCalibrate\MerchantFeedManager\Generator\FeedGenerator;
use WPCalibrate\MerchantFeedManager\Scheduler\ActionSchedulerService;
use WPCalibrate\MerchantFeedManager\Settings\SettingsManager;
use WPCalibrate\MerchantFeedManager\Support\Logger;
use WPCalibrate\MerchantFeedManager\Validation\IssueRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main application coordinator and lifecycle manager.
 */
class Plugin {

	public const VERSION = '1.0.0';

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {}

	/**
	 * Initialize the plugin.
	 */
	public function init(): void {
		// 1. Declare WooCommerce HPOS compatibility early
		HposCompatibility::register();

		// 2. Check dependencies
		if ( ! DependencyChecker::is_php_supported() || ! DependencyChecker::is_woocommerce_active() ) {
			add_action( 'admin_notices', [ DependencyChecker::class, 'render_dependency_notices' ] );
			return;
		}

		// 3. Database migrations
		add_action( 'plugins_loaded', [ self::class, 'run_migrations' ], 15 );

		// 4. Action Scheduler job handlers
		add_action( ActionSchedulerService::HOOK_BATCH, [ BatchProcessor::class, 'process_batch' ], 10, 2 );
		add_action( ActionSchedulerService::HOOK_RECURRING, [ self::class, 'handle_recurring_trigger' ], 10, 1 );

		// 5. Daily maintenance cleanup event
		add_action( 'wpcmfm_daily_cleanup', [ self::class, 'run_daily_cleanup' ] );
		if ( ! wp_next_scheduled( 'wpcmfm_daily_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'wpcmfm_daily_cleanup' );
		}

		// 6. Public feed delivery
		FeedDelivery::register();

		// 7. Admin UI and AJAX (only in admin)
		if ( is_admin() ) {
			Menu::register();
			AjaxHandler::register();
			ProductMetabox::register();
		}
	}

	/**
	 * Run migrations if needed.
	 */
	public static function run_migrations(): void {
		Migrations::migrate();
	}

	/**
	 * Handle Action Scheduler recurring feed run trigger.
	 *
	 * @param int $feed_id Feed ID.
	 */
	public static function handle_recurring_trigger( int $feed_id ): void {
		FeedGenerator::start_generation( $feed_id, true );
	}

	/**
	 * Run daily maintenance and data retention pruning.
	 */
	public static function run_daily_cleanup(): void {
		$days = (int) SettingsManager::get( 'history_retention_days', 30 );
		FeedRunRepository::prune_old_runs( $days );
		IssueRepository::prune_old_issues( $days );
		FeedStorage::clean_stale_temp_files();
	}

	/**
	 * Plugin activation lifecycle callback.
	 */
	public static function activate(): void {
		// Run database migration
		Migrations::migrate();

		// Ensure storage folder is created and hardened
		FeedStorage::get_storage_dir();

		// Schedule daily maintenance cron
		if ( ! wp_next_scheduled( 'wpcmfm_daily_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'wpcmfm_daily_cleanup' );
		}
	}

	/**
	 * Plugin deactivation lifecycle callback.
	 */
	public static function deactivate(): void {
		// Unschedule maintenance cron
		$timestamp = wp_next_scheduled( 'wpcmfm_daily_cleanup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'wpcmfm_daily_cleanup' );
		}

		// Unschedule Action Scheduler background jobs
		ActionSchedulerService::unschedule_all();
	}
}
