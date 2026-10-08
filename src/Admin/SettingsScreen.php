<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Admin;

use WPCalibrate\MerchantFeedManager\Security\Security;
use WPCalibrate\MerchantFeedManager\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Screen rendering global plugin settings and handling updates.
 */
class SettingsScreen {

	public const NONCE_ACTION = 'wpcmfm_save_settings_action';
	public const NONCE_NAME   = '_wpcmfm_settings_nonce';

	/**
	 * Process settings save.
	 */
	public static function process_save(): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		if ( ! Security::verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed. Please refresh and try again.', 'wpcalibrate-merchant-feed-manager' ) );
		}

		Security::check_auth_or_die();

		$data = [
			'default_batch_size'       => isset( $_POST['default_batch_size'] ) ? (int) wp_unslash( $_POST['default_batch_size'] ) : 100,
			'history_retention_days'   => isset( $_POST['history_retention_days'] ) ? (int) wp_unslash( $_POST['history_retention_days'] ) : 30,
			'auto_sanitize_csv'        => ! empty( $_POST['auto_sanitize_csv'] ),
			'lock_timeout_seconds'     => isset( $_POST['lock_timeout_seconds'] ) ? (int) wp_unslash( $_POST['lock_timeout_seconds'] ) : 900,
			'enable_logging'           => ! empty( $_POST['enable_logging'] ),
			'log_level'                => isset( $_POST['log_level'] ) ? sanitize_key( wp_unslash( $_POST['log_level'] ) ) : 'warning',
			'delete_data_on_uninstall' => ! empty( $_POST['delete_data_on_uninstall'] ),
		];

		$saved = SettingsManager::update_settings( $data );
		if ( $saved ) {
			AdminNotice::add( __( 'Plugin settings updated successfully.', 'wpcalibrate-merchant-feed-manager' ), 'success' );
		} else {
			AdminNotice::add( __( 'Failed to update plugin settings.', 'wpcalibrate-merchant-feed-manager' ), 'error' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=settings' ) );
		exit;
	}

	/**
	 * Render settings form HTML.
	 */
	public static function render(): void {
		$settings = SettingsManager::get_all();
		?>
		<div class="wpcmfm-settings-screen">
			<h2><?php esc_html_e( 'Global Feed Manager Settings', 'wpcalibrate-merchant-feed-manager' ); ?></h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=settings' ) ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

				<div class="wpcmfm-card wpcmfm-p-4 wpcmfm-mb-4">
					<h3><?php esc_html_e( 'Performance & Generation', 'wpcalibrate-merchant-feed-manager' ); ?></h3>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="default_batch_size"><?php esc_html_e( 'Default Batch Size', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<input type="number" id="default_batch_size" name="default_batch_size" min="10" max="1000" class="small-text" value="<?php echo esc_attr( (string) $settings['default_batch_size'] ); ?>" />
								<p class="description"><?php esc_html_e( 'Number of products processed per background chunk (10 - 1000).', 'wpcalibrate-merchant-feed-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="lock_timeout_seconds"><?php esc_html_e( 'Generation Lock Timeout (Seconds)', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<input type="number" id="lock_timeout_seconds" name="lock_timeout_seconds" min="60" max="3600" class="small-text" value="<?php echo esc_attr( (string) $settings['lock_timeout_seconds'] ); ?>" />
								<p class="description"><?php esc_html_e( 'Time after which a stuck generation lock is automatically released (default 900 seconds = 15 minutes).', 'wpcalibrate-merchant-feed-manager' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<div class="wpcmfm-card wpcmfm-p-4 wpcmfm-mb-4">
					<h3><?php esc_html_e( 'Security & Data Retention', 'wpcalibrate-merchant-feed-manager' ); ?></h3>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'CSV Formula Sanitization', 'wpcalibrate-merchant-feed-manager' ); ?></th>
							<td>
								<label for="auto_sanitize_csv">
									<input type="checkbox" id="auto_sanitize_csv" name="auto_sanitize_csv" value="1" <?php checked( $settings['auto_sanitize_csv'] ); ?> />
									<?php esc_html_e( 'Neutralize formula triggers (=, +, -, @) in CSV feeds (prevents CSV Injection / CWE-1236).', 'wpcalibrate-merchant-feed-manager' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="history_retention_days"><?php esc_html_e( 'History Retention (Days)', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<input type="number" id="history_retention_days" name="history_retention_days" min="1" max="365" class="small-text" value="<?php echo esc_attr( (string) $settings['history_retention_days'] ); ?>" />
								<p class="description"><?php esc_html_e( 'Automatically clean old generation runs and validation issue logs older than this number of days.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Uninstall Data Retention', 'wpcalibrate-merchant-feed-manager' ); ?></th>
							<td>
								<label for="delete_data_on_uninstall">
									<input type="checkbox" id="delete_data_on_uninstall" name="delete_data_on_uninstall" value="1" <?php checked( $settings['delete_data_on_uninstall'] ); ?> />
									<strong><?php esc_html_e( 'Delete all plugin data on uninstall', 'wpcalibrate-merchant-feed-manager' ); ?></strong>
								</label>
								<p class="description wpcmfm-text-danger">
									<?php esc_html_e( 'Warning: If checked, completely removes custom database tables, all feeds, and generated files upon deleting the plugin. By default, configuration is safely preserved.', 'wpcalibrate-merchant-feed-manager' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<div class="wpcmfm-card wpcmfm-p-4 wpcmfm-mb-4">
					<h3><?php esc_html_e( 'Logging & Diagnostics', 'wpcalibrate-merchant-feed-manager' ); ?></h3>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Logging', 'wpcalibrate-merchant-feed-manager' ); ?></th>
							<td>
								<label for="enable_logging">
									<input type="checkbox" id="enable_logging" name="enable_logging" value="1" <?php checked( $settings['enable_logging'] ); ?> />
									<?php esc_html_e( 'Record generation and error events in WooCommerce Logger.', 'wpcalibrate-merchant-feed-manager' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="log_level"><?php esc_html_e( 'Log Level', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<select id="log_level" name="log_level">
									<option value="error" <?php selected( $settings['log_level'], 'error' ); ?>><?php esc_html_e( 'Error Only', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="warning" <?php selected( $settings['log_level'], 'warning' ); ?>><?php esc_html_e( 'Warning & Error', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="info" <?php selected( $settings['log_level'], 'info' ); ?>><?php esc_html_e( 'Info, Warning, Error', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="debug" <?php selected( $settings['log_level'], 'debug' ); ?>><?php esc_html_e( 'Debug (Verbose)', 'wpcalibrate-merchant-feed-manager' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'wpcalibrate-merchant-feed-manager' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}
}
