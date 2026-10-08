<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks system and plugin dependencies for WPCalibrate Merchant Feed Manager.
 */
class DependencyChecker {

	public const MIN_PHP_VERSION = '8.2.0';
	public const MIN_WP_VERSION  = '6.4.0';
	public const MIN_WC_VERSION  = '8.5.0';

	/**
	 * Check if all runtime dependencies are met.
	 *
	 * @return bool
	 */
	public static function are_dependencies_met(): bool {
		return self::is_php_supported() && self::is_wp_supported() && self::is_woocommerce_active();
	}

	/**
	 * Check PHP version.
	 *
	 * @return bool
	 */
	public static function is_php_supported(): bool {
		return version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '>=' );
	}

	/**
	 * Check WordPress version.
	 *
	 * @return bool
	 */
	public static function is_wp_supported(): bool {
		global $wp_version;
		return empty( $wp_version ) || version_compare( $wp_version, self::MIN_WP_VERSION, '>=' );
	}

	/**
	 * Check if WooCommerce is installed and active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active(): bool {
		if ( class_exists( 'WooCommerce' ) ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			$plugin_file = ABSPATH . 'wp-admin/includes/plugin.php';
			if ( file_exists( $plugin_file ) ) {
				require_once $plugin_file;
			}
		}

		return function_exists( 'is_plugin_active' ) && is_plugin_active( 'woocommerce/woocommerce.php' );
	}

	/**
	 * Check WooCommerce version compatibility.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_version_supported(): bool {
		if ( ! defined( 'WC_VERSION' ) ) {
			return false;
		}

		return version_compare( WC_VERSION, self::MIN_WC_VERSION, '>=' );
	}

	/**
	 * Render admin notices for unmet dependencies.
	 */
	public static function render_dependency_notices(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! self::is_php_supported() ) {
			printf(
				'<div class="notice notice-error"><p><strong>%1$s:</strong> %2$s</p></div>',
				esc_html__( 'WPCalibrate Merchant Feed Manager', 'wpcalibrate-merchant-feed-manager' ),
				sprintf(
					/* translators: 1: Required PHP version, 2: Current PHP version */
					esc_html__( 'Requires PHP version %1$s or higher. Your server is running PHP %2$s. Please upgrade your PHP version.', 'wpcalibrate-merchant-feed-manager' ),
					esc_html( self::MIN_PHP_VERSION ),
					esc_html( PHP_VERSION )
				)
			);
		}

		if ( ! self::is_woocommerce_active() ) {
			printf(
				'<div class="notice notice-error"><p><strong>%1$s:</strong> %2$s</p></div>',
				esc_html__( 'WPCalibrate Merchant Feed Manager', 'wpcalibrate-merchant-feed-manager' ),
				esc_html__( 'WooCommerce is required and must be active. Feed generation is paused until WooCommerce is active.', 'wpcalibrate-merchant-feed-manager' )
			);
		}
	}
}
