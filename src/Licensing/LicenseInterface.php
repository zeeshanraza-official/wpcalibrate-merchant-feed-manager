<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for WPCalibrate licensing providers.
 */
interface LicenseInterface {

	/**
	 * Get provider name / identifier.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Is this provider configured and ready for remote calls?
	 *
	 * @return bool
	 */
	public function is_configured(): bool;

	/**
	 * Activate a license key with provider.
	 *
	 * @param string $license_key License key.
	 * @return array{success: bool, message: string, status: string, expires_at?: string}
	 */
	public function activate( string $license_key ): array;

	/**
	 * Deactivate a license key with provider.
	 *
	 * @param string $license_key License key.
	 * @return array{success: bool, message: string, status: string}
	 */
	public function deactivate( string $license_key ): array;

	/**
	 * Check status of a license key with provider.
	 *
	 * @param string $license_key License key.
	 * @return array{success: bool, message: string, status: string, expires_at?: string}
	 */
	public function check( string $license_key ): array;
}
