<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Settings;

use WPCalibrate\MerchantFeedManager\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages global settings for WPCalibrate Merchant Feed Manager.
 */
class SettingsManager {

	public const OPTION_NAME = 'wpcmfm_settings';

	/**
	 * Default plugin settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		return [
			'default_batch_size'       => 100,
			'history_retention_days'   => 30,
			'delete_data_on_uninstall' => false,
			'enable_logging'           => true,
			'log_level'                => 'warning',
			'auto_sanitize_csv'        => true,
			'lock_timeout_seconds'     => 900,
		];
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_all(): array {
		$saved = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}

		return array_merge( self::get_defaults(), $saved );
	}

	/**
	 * Get a single setting by key.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$settings = self::get_all();
		return $settings[ $key ] ?? $default;
	}

	/**
	 * Update settings after validating and sanitizing.
	 *
	 * @param array<string, mixed> $input Raw input settings.
	 * @return bool
	 */
	public static function update_settings( array $input ): bool {
		Security::check_auth_or_die( Security::get_capability() );

		$sanitized = [
			'default_batch_size'       => max( 10, min( 1000, (int) ( $input['default_batch_size'] ?? 100 ) ) ),
			'history_retention_days'   => max( 1, min( 365, (int) ( $input['history_retention_days'] ?? 30 ) ) ),
			'delete_data_on_uninstall' => ! empty( $input['delete_data_on_uninstall'] ),
			'enable_logging'           => ! empty( $input['enable_logging'] ),
			'log_level'                => in_array( $input['log_level'] ?? '', [ 'debug', 'info', 'warning', 'error' ], true ) ? $input['log_level'] : 'warning',
			'auto_sanitize_csv'        => ! empty( $input['auto_sanitize_csv'] ),
			'lock_timeout_seconds'     => max( 60, min( 3600, (int) ( $input['lock_timeout_seconds'] ?? 900 ) ) ),
		];

		return update_option( self::OPTION_NAME, $sanitized, false );
	}
}
