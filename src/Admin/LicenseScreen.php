<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Admin;

use WPCalibrate\MerchantFeedManager\Licensing\LicenseManager;
use WPCalibrate\MerchantFeedManager\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Screen rendering License configuration and provider status.
 */
class LicenseScreen {

	public const NONCE_ACTION = 'wpcmfm_license_action';
	public const NONCE_NAME   = '_wpcmfm_license_nonce';

	/**
	 * Process license actions (save, activate, deactivate).
	 */
	public static function process_action(): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		if ( ! Security::verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed. Please refresh and try again.', 'wpcalibrate-merchant-feed-manager' ) );
		}

		Security::check_auth_or_die( Security::get_admin_capability() );

		$action = isset( $_POST['license_action'] ) ? sanitize_key( wp_unslash( $_POST['license_action'] ) ) : 'save';

		if ( 'save' === $action ) {
			$raw_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
			LicenseManager::save_key( $raw_key );
			AdminNotice::add( __( 'License key updated.', 'wpcalibrate-merchant-feed-manager' ), 'success' );
		} elseif ( 'activate' === $action ) {
			$res = LicenseManager::activate();
			AdminNotice::add( $res['message'], $res['success'] ? 'success' : 'warning' );
		} elseif ( 'deactivate' === $action ) {
			$res = LicenseManager::deactivate();
			AdminNotice::add( $res['message'], 'info' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=license' ) );
		exit;
	}

	/**
	 * Render license management UI HTML.
	 */
	public static function render(): void {
		$license_data = LicenseManager::get_license_data();
		$masked_key   = LicenseManager::get_masked_key();
		$provider     = LicenseManager::get_provider();

		?>
		<div class="wpcmfm-license-screen">
			<h2><?php esc_html_e( 'License Management', 'wpcalibrate-merchant-feed-manager' ); ?></h2>

			<div class="wpcmfm-card wpcmfm-p-4 wpcmfm-mb-4">
				<div class="wpcmfm-alert wpcmfm-alert-info wpcmfm-mb-3">
					<strong><?php esc_html_e( 'Integration Status: Not configured', 'wpcalibrate-merchant-feed-manager' ); ?></strong>
					<p>
						<?php esc_html_e( 'The pluggable licensing provider abstraction is implemented. Live remote activation is currently pending official WPCalibrate licensing endpoint and provider configuration.', 'wpcalibrate-merchant-feed-manager' ); ?>
					</p>
				</div>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=license' ) ); ?>">
					<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'License Provider', 'wpcalibrate-merchant-feed-manager' ); ?></th>
							<td>
								<code><?php echo esc_html( $provider->get_name() ); ?></code>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="license_key"><?php esc_html_e( 'License Key', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<input type="password" id="license_key" name="license_key" class="regular-text" value="<?php echo esc_attr( $masked_key ); ?>" placeholder="<?php esc_attr_e( 'Enter your license key', 'wpcalibrate-merchant-feed-manager' ); ?>" autocomplete="off" />
								<p class="description">
									<?php esc_html_e( 'For security, your stored license key is masked and never exposed in cleartext.', 'wpcalibrate-merchant-feed-manager' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'License Status', 'wpcalibrate-merchant-feed-manager' ); ?></th>
							<td>
								<span class="wpcmfm-badge wpcmfm-badge-<?php echo esc_attr( $license_data['status'] ); ?>">
									<?php echo esc_html( ucfirst( $license_data['status'] ) ); ?>
								</span>
							</td>
						</tr>
					</table>

					<p class="submit">
						<button type="submit" name="license_action" value="save" class="button button-primary">
							<?php esc_html_e( 'Save Key', 'wpcalibrate-merchant-feed-manager' ); ?>
						</button>
						<button type="submit" name="license_action" value="activate" class="button button-secondary">
							<?php esc_html_e( 'Activate License', 'wpcalibrate-merchant-feed-manager' ); ?>
						</button>
						<?php if ( ! empty( $license_data['key'] ) ) : ?>
							<button type="submit" name="license_action" value="deactivate" class="button button-secondary">
								<?php esc_html_e( 'Deactivate License', 'wpcalibrate-merchant-feed-manager' ); ?>
							</button>
						<?php endif; ?>
					</p>
				</form>
			</div>
		</div>
		<?php
	}
}
