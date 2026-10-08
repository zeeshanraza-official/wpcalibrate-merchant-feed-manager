<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoloader for WPCalibrate Merchant Feed Manager.
 *
 * Maps namespace WPCalibrate\MerchantFeedManager\ to plugin src/ directory.
 */
class Autoloader {

	/**
	 * Namespace prefix.
	 *
	 * @var string
	 */
	private const PREFIX = 'WPCalibrate\\MerchantFeedManager\\';

	/**
	 * Register the SPL autoloader.
	 */
	public static function register(): void {
		spl_autoload_register( [ self::class, 'autoload' ] );
	}

	/**
	 * Autoload a class by namespace.
	 *
	 * @param string $class Class name.
	 */
	public static function autoload( string $class ): void {
		if ( ! str_starts_with( $class, self::PREFIX ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( self::PREFIX ) );
		$file = dirname( __DIR__ ) . '/src/' . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
