<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declares WooCommerce High-Performance Order Storage (HPOS) and Blocks compatibility.
 */
class HposCompatibility {

	/**
	 * Register compatibility declaration on before_woocommerce_init hook.
	 */
	public static function register(): void {
		add_action( 'before_woocommerce_init', [ self::class, 'declare_compatibility' ] );
	}

	/**
	 * Declare HPOS and feature compatibility.
	 */
	public static function declare_compatibility(): void {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) && defined( 'WPCMFM_PLUGIN_FILE' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				WPCMFM_PLUGIN_FILE,
				true
			);
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				WPCMFM_PLUGIN_FILE,
				true
			);
		}
	}
}
