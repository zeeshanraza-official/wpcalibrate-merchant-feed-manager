<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Admin;

use WPCalibrate\MerchantFeedManager\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Screen rendering official WPCalibrate support channels and system diagnostics.
 */
class SupportScreen {

	/**
	 * Render Support screen HTML.
	 */
	public static function render(): void {
		global $wp_version;

		$wc_version = defined( 'WC_VERSION' ) ? WC_VERSION : __( 'Not Active', 'wpcalibrate-merchant-feed-manager' );
		$db_version = get_option( Schema::OPTION_DB_VERSION, __( 'Not installed', 'wpcalibrate-merchant-feed-manager' ) );

		?>
		<div class="wpcmfm-support-screen">
			<h2><?php esc_html_e( 'WPCalibrate Support & Contact', 'wpcalibrate-merchant-feed-manager' ); ?></h2>

			<div class="wpcmfm-row">
				<div class="wpcmfm-col-6">
					<div class="wpcmfm-card wpcmfm-p-4 wpcmfm-mb-4">
						<h3><?php esc_html_e( 'Official Contact Channels', 'wpcalibrate-merchant-feed-manager' ); ?></h3>
						<p><?php esc_html_e( 'Need help configuring product feeds, custom mapping, or reporting an issue? Reach out through our official channels:', 'wpcalibrate-merchant-feed-manager' ); ?></p>

						<table class="wp-list-table widefat striped">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Email Support', 'wpcalibrate-merchant-feed-manager' ); ?></th>
									<td>
										<a href="<?php echo esc_url( 'mailto:support@wpcalibrate.com' ); ?>">
											support@wpcalibrate.com
										</a>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Official Website', 'wpcalibrate-merchant-feed-manager' ); ?></th>
									<td>
										<a href="<?php echo esc_url( 'https://wpcalibrate.com' ); ?>" target="_blank" rel="noopener noreferrer">
											https://wpcalibrate.com &nearr;
										</a>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'WPCalibrate Marketplace', 'wpcalibrate-merchant-feed-manager' ); ?></th>
									<td>
										<a href="<?php echo esc_url( 'https://marketplace.wpcalibrate.com/' ); ?>" target="_blank" rel="noopener noreferrer">
											https://marketplace.wpcalibrate.com/ &nearr;
										</a>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Phone & WhatsApp', 'wpcalibrate-merchant-feed-manager' ); ?></th>
									<td>
										<a href="<?php echo esc_url( 'https://wa.me/447474795976' ); ?>" target="_blank" rel="noopener noreferrer">
											+447474795976 (WhatsApp) &nearr;
										</a>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<div class="wpcmfm-col-6">
					<div class="wpcmfm-card wpcmfm-p-4 wpcmfm-mb-4">
						<h3><?php esc_html_e( 'Environment & Diagnostic Snapshot', 'wpcalibrate-merchant-feed-manager' ); ?></h3>
						<table class="wp-list-table widefat striped">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Plugin Version', 'wpcalibrate-merchant-feed-manager' ); ?></th>
									<td><code>1.0.0</code></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Database Schema Version', 'wpcalibrate-merchant-feed-manager' ); ?></th>
									<td><code><?php echo esc_html( (string) $db_version ); ?></code></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'PHP Version', 'wpcalibrate-merchant-feed-manager' ); ?></th>
									<td><code><?php echo esc_html( PHP_VERSION ); ?></code></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'WordPress Version', 'wpcalibrate-merchant-feed-manager' ); ?></th>
									<td><code><?php echo esc_html( (string) $wp_version ); ?></code></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'WooCommerce Version', 'wpcalibrate-merchant-feed-manager' ); ?></th>
									<td><code><?php echo esc_html( (string) $wc_version ); ?></code></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Action Scheduler', 'wpcalibrate-merchant-feed-manager' ); ?></th>
									<td>
										<code>
											<?php echo function_exists( 'as_schedule_recurring_action' ) ? esc_html__( 'Active', 'wpcalibrate-merchant-feed-manager' ) : esc_html__( 'Unavailable', 'wpcalibrate-merchant-feed-manager' ); ?>
										</code>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
