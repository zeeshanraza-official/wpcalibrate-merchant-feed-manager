<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Admin;

use WPCalibrate\MerchantFeedManager\Api\FeedDelivery;
use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Feed\FeedRepository;
use WPCalibrate\MerchantFeedManager\Generator\FeedGenerator;
use WPCalibrate\MerchantFeedManager\Scheduler\ActionSchedulerService;
use WPCalibrate\MerchantFeedManager\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Screen rendering the list of merchant feeds with actions.
 */
class FeedListScreen {

	/**
	 * Process list screen actions (delete, duplicate, toggle, generate).
	 */
	public static function process_actions(): void {
		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$action  = sanitize_key( wp_unslash( $_GET['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$feed_id = (int) wp_unslash( $_GET['id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce   = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );

		if ( ! Security::verify_nonce( $nonce, 'wpcmfm_feed_action_' . $feed_id ) ) {
			wp_die( esc_html__( 'Security check failed. Please refresh and try again.', 'wpcalibrate-merchant-feed-manager' ) );
		}

		Security::check_auth_or_die();

		switch ( $action ) {
			case 'delete':
				$success = FeedRepository::delete( $feed_id, true );
				if ( $success ) {
					ActionSchedulerService::unschedule_feed( $feed_id );
					AdminNotice::add( __( 'Feed deleted successfully.', 'wpcalibrate-merchant-feed-manager' ), 'success' );
				} else {
					AdminNotice::add( __( 'Failed to delete feed.', 'wpcalibrate-merchant-feed-manager' ), 'error' );
				}
				break;

			case 'duplicate':
				$new_id = FeedRepository::duplicate( $feed_id );
				if ( $new_id > 0 ) {
					AdminNotice::add( __( 'Feed duplicated successfully.', 'wpcalibrate-merchant-feed-manager' ), 'success' );
				} else {
					AdminNotice::add( __( 'Failed to duplicate feed.', 'wpcalibrate-merchant-feed-manager' ), 'error' );
				}
				break;

			case 'toggle':
				$feed = FeedRepository::get_by_id( $feed_id );
				if ( $feed ) {
					$feed->status = ( 'active' === $feed->status ) ? 'inactive' : 'active';
					FeedRepository::update( $feed );
					ActionSchedulerService::sync_feed_schedule( $feed );
					AdminNotice::add(
						'active' === $feed->status
							? __( 'Feed enabled successfully.', 'wpcalibrate-merchant-feed-manager' )
							: __( 'Feed disabled successfully.', 'wpcalibrate-merchant-feed-manager' ),
						'success'
					);
				}
				break;

			case 'generate':
				$result = FeedGenerator::start_generation( $feed_id, true );
				if ( $result['success'] ) {
					AdminNotice::add( $result['message'], 'success' );
				} else {
					AdminNotice::add( $result['message'], 'error' );
				}
				break;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=feeds' ) );
		exit;
	}

	/**
	 * Render the feeds table.
	 */
	public static function render(): void {
		$feeds = FeedRepository::get_all( [ 'limit' => 100 ] );
		?>
		<div class="wpcmfm-feeds-header wpcmfm-d-flex wpcmfm-justify-between wpcmfm-align-center wpcmfm-mb-3">
			<h2><?php esc_html_e( 'Merchant Feeds', 'wpcalibrate-merchant-feed-manager' ); ?></h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=feed-edit' ) ); ?>" class="button button-primary">
				<?php esc_html_e( '+ Add New Feed', 'wpcalibrate-merchant-feed-manager' ); ?>
			</a>
		</div>

		<?php if ( empty( $feeds ) ) : ?>
			<div class="wpcmfm-card wpcmfm-text-center wpcmfm-p-5">
				<h3><?php esc_html_e( 'No Feeds Created Yet', 'wpcalibrate-merchant-feed-manager' ); ?></h3>
				<p><?php esc_html_e( 'Generate standard-compliant product feeds for Google Merchant Center, Meta, TikTok, and more.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=feed-edit' ) ); ?>" class="button button-primary button-hero">
						<?php esc_html_e( 'Create Your First Feed', 'wpcalibrate-merchant-feed-manager' ); ?>
					</a>
				</p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped wpcmfm-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Feed Name', 'wpcalibrate-merchant-feed-manager' ); ?></th>
						<th><?php esc_html_e( 'Channel', 'wpcalibrate-merchant-feed-manager' ); ?></th>
						<th><?php esc_html_e( 'Format', 'wpcalibrate-merchant-feed-manager' ); ?></th>
						<th><?php esc_html_e( 'Status', 'wpcalibrate-merchant-feed-manager' ); ?></th>
						<th><?php esc_html_e( 'Products', 'wpcalibrate-merchant-feed-manager' ); ?></th>
						<th><?php esc_html_e( 'Last Generation', 'wpcalibrate-merchant-feed-manager' ); ?></th>
						<th><?php esc_html_e( 'Feed URL', 'wpcalibrate-merchant-feed-manager' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'wpcalibrate-merchant-feed-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $feeds as $feed ) :
						$feed_url     = FeedDelivery::get_feed_url( $feed );
						$action_nonce = Security::create_nonce( 'wpcmfm_feed_action_' . $feed->id );
						$edit_url     = admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=feed-edit&id=' . $feed->id );
						$del_url      = admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=feeds&action=delete&id=' . $feed->id . '&_wpnonce=' . $action_nonce );
						$dup_url      = admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=feeds&action=duplicate&id=' . $feed->id . '&_wpnonce=' . $action_nonce );
						$gen_url      = admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=feeds&action=generate&id=' . $feed->id . '&_wpnonce=' . $action_nonce );
						$toggle_url   = admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=feeds&action=toggle&id=' . $feed->id . '&_wpnonce=' . $action_nonce );
						?>
						<tr>
							<td>
								<strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $feed->name ); ?></a></strong>
								<div class="row-actions">
									<span><a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'wpcalibrate-merchant-feed-manager' ); ?></a> | </span>
									<span><a href="<?php echo esc_url( $gen_url ); ?>"><?php esc_html_e( 'Generate Now', 'wpcalibrate-merchant-feed-manager' ); ?></a> | </span>
									<span><a href="<?php echo esc_url( $dup_url ); ?>"><?php esc_html_e( 'Duplicate', 'wpcalibrate-merchant-feed-manager' ); ?></a> | </span>
									<span class="trash"><a href="<?php echo esc_url( $del_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this feed?', 'wpcalibrate-merchant-feed-manager' ) ); ?>');"><?php esc_html_e( 'Delete', 'wpcalibrate-merchant-feed-manager' ); ?></a></span>
								</div>
							</td>
							<td><span class="wpcmfm-channel-tag"><?php echo esc_html( strtoupper( $feed->channel ) ); ?></span></td>
							<td><?php echo esc_html( strtoupper( $feed->format ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( $toggle_url ); ?>" class="wpcmfm-status-toggle" title="<?php esc_attr_e( 'Click to toggle status', 'wpcalibrate-merchant-feed-manager' ); ?>">
									<span class="wpcmfm-badge wpcmfm-badge-<?php echo esc_attr( $feed->status ); ?>">
										<?php echo esc_html( ucfirst( $feed->status ) ); ?>
									</span>
								</a>
							</td>
							<td><?php echo esc_html( (string) $feed->item_count ); ?></td>
							<td>
								<div><?php echo esc_html( $feed->last_run_at ?: __( 'Never', 'wpcalibrate-merchant-feed-manager' ) ); ?></div>
								<small class="wpcmfm-text-muted">
									<span class="wpcmfm-dot wpcmfm-dot-<?php echo esc_attr( $feed->last_status ); ?>"></span>
									<?php echo esc_html( ucwords( str_replace( '_', ' ', $feed->last_status ) ) ); ?>
								</small>
							</td>
							<td>
								<?php if ( $feed->is_active() && ! empty( $feed->file_path ) ) : ?>
									<div class="wpcmfm-url-box">
										<input type="text" readonly value="<?php echo esc_url( $feed_url ); ?>" class="wpcmfm-feed-url-input" />
										<button type="button" class="button button-small wpcmfm-copy-url" data-url="<?php echo esc_url( $feed_url ); ?>" title="<?php esc_attr_e( 'Copy Feed URL', 'wpcalibrate-merchant-feed-manager' ); ?>">
											<?php esc_html_e( 'Copy', 'wpcalibrate-merchant-feed-manager' ); ?>
										</button>
									</div>
								<?php else : ?>
									<span class="wpcmfm-text-muted"><?php esc_html_e( 'Inactive or not generated', 'wpcalibrate-merchant-feed-manager' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( $gen_url ); ?>" class="button button-secondary button-small">
									<?php esc_html_e( 'Generate', 'wpcalibrate-merchant-feed-manager' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}
}
