<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default fallback provider when no live licensing API endpoint is configured.
 * Clearly states that integration is unconfigured rather than faking activation.
 */
class NullLicenseProvider implements LicenseInterface {

	public function get_name(): string {
		return 'Standard WPCalibrate License Engine (Pending Configuration)';
	}

	public function is_configured(): bool {
		return false;
	}

	public function activate( string $license_key ): array {
		return [
			'success' => false,
			'status'  => 'unconfigured',
			'message' => __( 'Remote license server is not yet configured. Live remote activation is currently blocked pending official licensing API details.', 'wpcalibrate-merchant-feed-manager' ),
		];
	}

	public function deactivate( string $license_key ): array {
		return [
			'success' => false,
			'status'  => 'unconfigured',
			'message' => __( 'Remote license server is not configured.', 'wpcalibrate-merchant-feed-manager' ),
		];
	}

	public function check( string $license_key ): array {
		return [
			'success' => false,
			'status'  => 'unconfigured',
			'message' => __( 'Remote license server is not configured.', 'wpcalibrate-merchant-feed-manager' ),
		];
	}
}
