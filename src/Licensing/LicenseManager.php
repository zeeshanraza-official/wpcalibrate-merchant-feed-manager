<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Licensing;

use WPCalibrate\MerchantFeedManager\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service to manage license storage, masking, and provider delegation.
 */
class LicenseManager {

	public const OPTION_KEY = 'wpcmfm_license_data';

	/**
	 * Active provider instance.
	 *
	 * @var LicenseInterface|null
	 */
	private static ?LicenseInterface $provider = null;

	/**
	 * Get active license provider.
	 *
	 * @return LicenseInterface
	 */
	public static function get_provider(): LicenseInterface {
		if ( null !== self::$provider ) {
			return self::$provider;
		}

		$default_provider = new NullLicenseProvider();

		/**
		 * Filter the license provider instance.
		 *
		 * @param LicenseInterface $provider Active provider.
		 */
		self::$provider = apply_filters( 'wpcmfm_license_provider', $default_provider );
		return self::$provider;
	}

	/**
	 * Get saved license data.
	 *
	 * @return array{key: string, status: string, expires_at: string, last_checked: string}
	 */
	public static function get_license_data(): array {
		$data = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $data ) ) {
			$data = [];
		}

		return [
			'key'          => (string) ( $data['key'] ?? '' ),
			'status'       => (string) ( $data['status'] ?? 'inactive' ),
			'expires_at'   => (string) ( $data['expires_at'] ?? '' ),
			'last_checked' => (string) ( $data['last_checked'] ?? '' ),
		];
	}

	/**
	 * Get masked version of license key for secure UI presentation.
	 *
	 * @return string
	 */
	public static function get_masked_key(): string {
		$data = self::get_license_data();
		$key  = trim( $data['key'] );

		if ( empty( $key ) ) {
			return '';
		}

		$length = strlen( $key );
		if ( $length <= 4 ) {
			return '****';
		}

		$visible_suffix = substr( $key, -4 );
		return str_repeat( '*', max( 8, $length - 4 ) ) . '-' . $visible_suffix;
	}

	/**
	 * Save or update license key.
	 *
	 * @param string $new_key Raw entered key.
	 * @return bool
	 */
	public static function save_key( string $new_key ): bool {
		Security::check_auth_or_die( Security::get_admin_capability() );

		$new_key = sanitize_text_field( trim( $new_key ) );
		$current = self::get_license_data();

		// If user did not change masked key, keep existing
		if ( str_contains( $new_key, '*' ) ) {
			return true;
		}

		$current['key']          = $new_key;
		$current['status']       = empty( $new_key ) ? 'inactive' : 'unconfigured';
		$current['last_checked'] = current_time( 'mysql', true );

		return update_option( self::OPTION_KEY, $current, false );
	}

	/**
	 * Attempt to activate license key.
	 *
	 * @return array{success: bool, message: string}
	 */
	public static function activate(): array {
		Security::check_auth_or_die( Security::get_admin_capability() );

		$data     = self::get_license_data();
		$provider = self::get_provider();

		if ( empty( $data['key'] ) ) {
			return [
				'success' => false,
				'message' => __( 'Please enter a license key first.', 'wpcalibrate-merchant-feed-manager' ),
			];
		}

		$res = $provider->activate( $data['key'] );
		$data['status']       = $res['status'];
		$data['last_checked'] = current_time( 'mysql', true );
		if ( ! empty( $res['expires_at'] ) ) {
			$data['expires_at'] = $res['expires_at'];
		}

		update_option( self::OPTION_KEY, $data, false );

		return [
			'success' => $res['success'],
			'message' => $res['message'],
		];
	}

	/**
	 * Deactivate license key.
	 *
	 * @return array{success: bool, message: string}
	 */
	public static function deactivate(): array {
		Security::check_auth_or_die( Security::get_admin_capability() );

		$data     = self::get_license_data();
		$provider = self::get_provider();

		$res            = $provider->deactivate( $data['key'] );
		$data['status'] = 'inactive';
		update_option( self::OPTION_KEY, $data, false );

		return [
			'success' => true,
			'message' => $res['message'],
		];
	}
}
