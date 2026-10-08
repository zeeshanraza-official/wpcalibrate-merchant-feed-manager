<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Generator;

use WPCalibrate\MerchantFeedManager\Settings\SettingsManager;
use WPCalibrate\MerchantFeedManager\Support\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages distributed locks to prevent concurrent feed generation runs.
 */
class GenerationLock {

	public const LOCK_PREFIX = 'wpcmfm_lock_feed_';

	/**
	 * Attempt to acquire an exclusive lock for a feed.
	 *
	 * @param int    $feed_id Feed ID.
	 * @param string $run_token Unique run identifier.
	 * @return bool True if lock was acquired, false if feed is currently locked.
	 */
	public static function acquire( int $feed_id, string $run_token ): bool {
		$lock_key = self::LOCK_PREFIX . $feed_id;
		$timeout  = (int) SettingsManager::get( 'lock_timeout_seconds', 900 );
		$now      = time();

		$current_lock = get_option( $lock_key, false );

		if ( is_array( $current_lock ) ) {
			$acquired_at = (int) ( $current_lock['acquired_at'] ?? 0 );
			$is_expired  = ( $now - $acquired_at ) > $timeout;

			// If lock is active and belongs to another run, reject
			if ( ! $is_expired && ( $current_lock['run_token'] ?? '' ) !== $run_token ) {
				Logger::warning(
					'Generation lock is currently active for feed',
					[
						'feed_id'     => $feed_id,
						'locked_by'   => $current_lock['run_token'] ?? 'unknown',
						'acquired_at' => $acquired_at,
					]
				);
				return false;
			}

			if ( $is_expired ) {
				Logger::notice(
					'Recovering stale generation lock for feed',
					[
						'feed_id'   => $feed_id,
						'stale_run' => $current_lock['run_token'] ?? 'unknown',
					]
				);
			}
		}

		$lock_data = [
			'run_token'   => $run_token,
			'acquired_at' => $now,
		];

		update_option( $lock_key, $lock_data, false );
		return true;
	}

	/**
	 * Release lock for a feed.
	 *
	 * @param int    $feed_id Feed ID.
	 * @param string $run_token Run token verifying ownership of the lock.
	 * @return bool
	 */
	public static function release( int $feed_id, string $run_token ): bool {
		$lock_key     = self::LOCK_PREFIX . $feed_id;
		$current_lock = get_option( $lock_key, false );

		if ( ! is_array( $current_lock ) ) {
			return true;
		}

		// Only the lock owner (or an expired lock) can release
		if ( ( $current_lock['run_token'] ?? '' ) === $run_token ) {
			delete_option( $lock_key );
			return true;
		}

		$timeout    = (int) SettingsManager::get( 'lock_timeout_seconds', 900 );
		$is_expired = ( time() - (int) ( $current_lock['acquired_at'] ?? 0 ) ) > $timeout;

		if ( $is_expired ) {
			delete_option( $lock_key );
			return true;
		}

		return false;
	}

	/**
	 * Force release lock regardless of owner (e.g. administrator reset).
	 *
	 * @param int $feed_id Feed ID.
	 */
	public static function force_release( int $feed_id ): void {
		delete_option( self::LOCK_PREFIX . $feed_id );
	}

	/**
	 * Check if a feed currently has an active lock.
	 *
	 * @param int $feed_id Feed ID.
	 * @return bool
	 */
	public static function is_locked( int $feed_id ): bool {
		$lock_key     = self::LOCK_PREFIX . $feed_id;
		$current_lock = get_option( $lock_key, false );

		if ( ! is_array( $current_lock ) ) {
			return false;
		}

		$timeout = (int) SettingsManager::get( 'lock_timeout_seconds', 900 );
		return ( time() - (int) ( $current_lock['acquired_at'] ?? 0 ) ) <= $timeout;
	}
}
