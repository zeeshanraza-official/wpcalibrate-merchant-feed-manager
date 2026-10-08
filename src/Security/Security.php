<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security helper and authorization manager for WPCalibrate Merchant Feed Manager.
 */
class Security {

	public const DEFAULT_CAPABILITY = 'manage_woocommerce';
	public const ADMIN_CAPABILITY   = 'manage_options';
	public const NONCE_ACTION        = 'wpcmfm_admin_action';
	public const NONCE_NAME          = '_wpcmfm_nonce';

	/**
	 * Get standard feed management capability.
	 *
	 * @return string
	 */
	public static function get_capability(): string {
		/**
		 * Filters the capability required to manage merchant feeds.
		 *
		 * @param string $capability Default capability.
		 */
		return (string) apply_filters( 'wpcmfm_management_capability', self::DEFAULT_CAPABILITY );
	}

	/**
	 * Get administrative capability for sensitive settings (e.g. license, dangerous data resets).
	 *
	 * @return string
	 */
	public static function get_admin_capability(): string {
		/**
		 * Filters the capability required for sensitive plugin administration.
		 *
		 * @param string $capability Default admin capability.
		 */
		return (string) apply_filters( 'wpcmfm_admin_capability', self::ADMIN_CAPABILITY );
	}

	/**
	 * Verify if current user can manage feeds.
	 *
	 * @return bool
	 */
	public static function current_user_can_manage(): bool {
		return current_user_can( self::get_capability() );
	}

	/**
	 * Verify if current user can manage administrative settings.
	 *
	 * @return bool
	 */
	public static function current_user_can_admin(): bool {
		return current_user_can( self::get_admin_capability() );
	}

	/**
	 * Verify standard admin nonce.
	 *
	 * @param string $nonce Nonce value from request.
	 * @param string $action Optional action name.
	 * @return bool
	 */
	public static function verify_nonce( string $nonce, string $action = self::NONCE_ACTION ): bool {
		return (bool) wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Create standard admin nonce.
	 *
	 * @param string $action Optional action name.
	 * @return string
	 */
	public static function create_nonce( string $action = self::NONCE_ACTION ): string {
		return wp_create_nonce( $action );
	}

	/**
	 * Check capability and die if unauthorized.
	 *
	 * @param string|null $capability Specific capability or null for default.
	 */
	public static function check_auth_or_die( ?string $capability = null ): void {
		$cap = $capability ?? self::get_capability();
		if ( ! current_user_can( $cap ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to access this page or perform this action.', 'wpcalibrate-merchant-feed-manager' ),
				esc_html__( 'Unauthorized Access', 'wpcalibrate-merchant-feed-manager' ),
				[ 'response' => 403 ]
			);
		}
	}

	/**
	 * Validate and sanitize XML element name according to W3C XML specifications.
	 * Allowed: starts with letter or underscore, followed by letters, digits, hyphens, underscores, dots, or colons.
	 *
	 * @param string $name Raw element name.
	 * @return string Sanitized element name or empty string if invalid.
	 */
	public static function sanitize_xml_tag( string $name ): string {
		$cleaned = preg_replace( '/[^a-zA-Z0-9_\-\.:]/', '', $name );
		if ( empty( $cleaned ) ) {
			return 'item';
		}
		// First character must be a letter or underscore
		if ( ! preg_match( '/^[a-zA-Z_]/', $cleaned ) ) {
			$cleaned = '_' . $cleaned;
		}
		return $cleaned;
	}

	/**
	 * Validate safe orderby column from an allowlist.
	 *
	 * @param string $column Raw orderby column.
	 * @param array<string> $allowlist Allowed column names.
	 * @param string $default Default column.
	 * @return string
	 */
	public static function sanitize_orderby( string $column, array $allowlist, string $default ): string {
		return in_array( $column, $allowlist, true ) ? $column : $default;
	}

	/**
	 * Validate order direction (ASC or DESC).
	 *
	 * @param string $order Raw order string.
	 * @return string 'ASC' or 'DESC'
	 */
	public static function sanitize_order_dir( string $order ): string {
		return strtoupper( trim( $order ) ) === 'ASC' ? 'ASC' : 'DESC';
	}
}
