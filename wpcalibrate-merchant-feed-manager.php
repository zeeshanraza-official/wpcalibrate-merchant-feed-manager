<?php
/**
 * Plugin Name: WPCalibrate Merchant Feed Manager
 * Plugin URI: https://marketplace.wpcalibrate.com/
 * Description: Production-grade WooCommerce product feed manager for Google Merchant Center, Meta, TikTok, Microsoft, Pinterest, and custom XML/CSV feeds.
 * Version: 1.0.0
 * Author: WPCalibrate
 * Author URI: https://wpcalibrate.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpcalibrate-merchant-feed-manager
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.2
 * WC requires at least: 8.5
 * WC tested up to: 11.2
 * GitHub Plugin URI: zeeshanraza-official/wpcalibrate-merchant-feed-manager
 * Primary Branch: main
 * Release Asset: true
 *
 * @package WPCalibrate\MerchantFeedManager
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin Constants
if ( ! defined( 'WPCMFM_VERSION' ) ) {
	define( 'WPCMFM_VERSION', '1.0.0' );
}
if ( ! defined( 'WPCMFM_PLUGIN_FILE' ) ) {
	define( 'WPCMFM_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'WPCMFM_PLUGIN_DIR' ) ) {
	define( 'WPCMFM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WPCMFM_PLUGIN_URL' ) ) {
	define( 'WPCMFM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Load PSR-4 Autoloader
require_once WPCMFM_PLUGIN_DIR . 'src/Autoloader.php';
\WPCalibrate\MerchantFeedManager\Autoloader::register();

// Register Activation & Deactivation Hooks
register_activation_hook( __FILE__, [ \WPCalibrate\MerchantFeedManager\Plugin::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \WPCalibrate\MerchantFeedManager\Plugin::class, 'deactivate' ] );

// Initialize Application Coordinator
add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain(
			'wpcalibrate-merchant-feed-manager',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		\WPCalibrate\MerchantFeedManager\Plugin::get_instance()->init();
	},
	10
);
