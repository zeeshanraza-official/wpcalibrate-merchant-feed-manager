<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Scheduler;

use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Support\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service to manage Action Scheduler recurring and async jobs.
 */
class ActionSchedulerService {

	public const HOOK_RECURRING = 'wpcmfm_scheduled_feed_run';
	public const HOOK_BATCH     = 'wpcmfm_process_feed_batch';
	public const GROUP          = 'wpcalibrate-merchant-feed-manager';

	/**
	 * Check if Action Scheduler APIs are available.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return function_exists( 'as_enqueue_async_action' ) && function_exists( 'as_schedule_recurring_action' );
	}

	/**
	 * Queue an immediate asynchronous background feed generation run.
	 *
	 * @param int    $feed_id Feed ID.
	 * @param string $run_token Unique run token.
	 * @return int|bool Action ID or false.
	 */
	public static function queue_generation( int $feed_id, string $run_token ): int|bool {
		if ( ! self::is_available() ) {
			Logger::warning( 'Action Scheduler not available; feed generation queued synchronously fallback.' );
			return false;
		}

		return as_enqueue_async_action(
			self::HOOK_BATCH,
			[
				'feed_id'   => $feed_id,
				'run_token' => $run_token,
			],
			self::GROUP
		);
	}

	/**
	 * Schedule a recurring feed generation according to feed schedule setting.
	 *
	 * @param FeedModel $feed Feed model.
	 */
	public static function sync_feed_schedule( FeedModel $feed ): void {
		if ( ! self::is_available() ) {
			return;
		}

		self::unschedule_feed( $feed->id );

		if ( ! $feed->is_active() || 'manual' === $feed->schedule ) {
			return;
		}

		$interval = match ( $feed->schedule ) {
			'hourly'     => HOUR_IN_SECONDS,
			'twicedaily' => 12 * HOUR_IN_SECONDS,
			'daily'      => DAY_IN_SECONDS,
			default      => DAY_IN_SECONDS,
		};

		as_schedule_recurring_action(
			time() + $interval,
			$interval,
			self::HOOK_RECURRING,
			[ 'feed_id' => $feed->id ],
			self::GROUP
		);

		Logger::info(
			'Scheduled recurring feed generation',
			[
				'feed_id'  => $feed->id,
				'schedule' => $feed->schedule,
				'interval' => $interval,
			]
		);
	}

	/**
	 * Unschedule any recurring actions for a feed.
	 *
	 * @param int $feed_id Feed ID.
	 */
	public static function unschedule_feed( int $feed_id ): void {
		if ( ! self::is_available() ) {
			return;
		}

		as_unschedule_action( self::HOOK_RECURRING, [ 'feed_id' => $feed_id ], self::GROUP );
	}

	/**
	 * Unschedule all plugin-owned jobs (used upon deactivation).
	 */
	public static function unschedule_all(): void {
		if ( ! self::is_available() ) {
			return;
		}

		as_unschedule_all_actions( '', [], self::GROUP );
	}
}
