<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Admin;

use WPCalibrate\MerchantFeedManager\Feed\FeedRepository;
use WPCalibrate\MerchantFeedManager\Feed\FeedRunRepository;
use WPCalibrate\MerchantFeedManager\Product\ProductQuery;
use WPCalibrate\MerchantFeedManager\Validation\IssueRepository;
use WPCalibrate\MerchantFeedManager\Validation\ValidationIssue;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the dashboard overview screen.
 */
class DashboardScreen {

	/**
	 * Render the dashboard HTML.
	 */
	public static function render(): void {
		$total_feeds     = FeedRepository::count_feeds();
		$active_feeds    = FeedRepository::count_feeds( 'active' );
		$error_feeds     = FeedRepository::count_error_feeds();
		$total_products  = ProductQuery::get_total_product_count();
		$total_errors    = IssueRepository::count_issues( [ 'severity' => ValidationIssue::SEVERITY_ERROR ] );
		$total_warnings  = IssueRepository::count_issues( [ 'severity' => ValidationIssue::SEVERITY_WARNING ] );
		$total_missing   = IssueRepository::count_issues( [ 'severity' => ValidationIssue::SEVERITY_MISSING_DATA ] );
		$total_excluded  = IssueRepository::count_issues( [ 'severity' => ValidationIssue::SEVERITY_EXCLUDED ] );
		$recent_runs     = FeedRunRepository::get_recent_runs( 5 );
		$common_issues   = IssueRepository::get_most_common_issues( 0, 5 );

		?>
		<div class="wpcmfm-dashboard">
			<div class="wpcmfm-stat-grid">
				<div class="wpcmfm-stat-card">
					<div class="wpcmfm-stat-value"><?php echo esc_html( (string) $total_feeds ); ?></div>
					<div class="wpcmfm-stat-label"><?php esc_html_e( 'Total Feeds', 'wpcalibrate-merchant-feed-manager' ); ?></div>
				</div>
				<div class="wpcmfm-stat-card wpcmfm-stat-success">
					<div class="wpcmfm-stat-value"><?php echo esc_html( (string) $active_feeds ); ?></div>
					<div class="wpcmfm-stat-label"><?php esc_html_e( 'Active Feeds', 'wpcalibrate-merchant-feed-manager' ); ?></div>
				</div>
				<div class="wpcmfm-stat-card <?php echo $error_feeds > 0 ? 'wpcmfm-stat-danger' : ''; ?>">
					<div class="wpcmfm-stat-value"><?php echo esc_html( (string) $error_feeds ); ?></div>
					<div class="wpcmfm-stat-label"><?php esc_html_e( 'Feeds with Errors', 'wpcalibrate-merchant-feed-manager' ); ?></div>
				</div>
				<div class="wpcmfm-stat-card">
					<div class="wpcmfm-stat-value"><?php echo esc_html( (string) $total_products ); ?></div>
					<div class="wpcmfm-stat-label"><?php esc_html_e( 'Store Products', 'wpcalibrate-merchant-feed-manager' ); ?></div>
				</div>
			</div>

			<div class="wpcmfm-row wpcmfm-mt-4">
				<div class="wpcmfm-col-8">
					<div class="wpcmfm-card">
						<div class="wpcmfm-card-header">
							<h2><?php esc_html_e( 'Recent Feed Generation Events', 'wpcalibrate-merchant-feed-manager' ); ?></h2>
						</div>
						<div class="wpcmfm-card-body">
							<?php if ( empty( $recent_runs ) ) : ?>
								<p class="wpcmfm-empty-state"><?php esc_html_e( 'No feed generation events recorded yet. Create and run your first feed.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
							<?php else : ?>
								<table class="wp-list-table widefat fixed striped">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Feed', 'wpcalibrate-merchant-feed-manager' ); ?></th>
											<th><?php esc_html_e( 'Status', 'wpcalibrate-merchant-feed-manager' ); ?></th>
											<th><?php esc_html_e( 'Items Included', 'wpcalibrate-merchant-feed-manager' ); ?></th>
											<th><?php esc_html_e( 'Errors / Warnings', 'wpcalibrate-merchant-feed-manager' ); ?></th>
											<th><?php esc_html_e( 'Date & Time', 'wpcalibrate-merchant-feed-manager' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $recent_runs as $run ) :
											$feed = FeedRepository::get_by_id( $run->feed_id );
											$feed_name = $feed ? $feed->name : sprintf( '#%d', $run->feed_id );
											?>
											<tr>
												<td><strong><?php echo esc_html( $feed_name ); ?></strong></td>
												<td>
													<span class="wpcmfm-badge wpcmfm-badge-<?php echo esc_attr( $run->status ); ?>">
														<?php echo esc_html( ucwords( str_replace( '_', ' ', $run->status ) ) ); ?>
													</span>
												</td>
												<td><?php echo esc_html( (string) $run->included_records ); ?></td>
												<td>
													<span class="wpcmfm-text-danger"><?php echo esc_html( (string) $run->error_count ); ?></span> /
													<span class="wpcmfm-text-warning"><?php echo esc_html( (string) $run->warning_count ); ?></span>
												</td>
												<td><?php echo esc_html( $run->started_at ?: $run->created_at ); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div class="wpcmfm-col-4">
					<div class="wpcmfm-card">
						<div class="wpcmfm-card-header">
							<h2><?php esc_html_e( 'Catalog Health Summary', 'wpcalibrate-merchant-feed-manager' ); ?></h2>
						</div>
						<div class="wpcmfm-card-body">
							<ul class="wpcmfm-health-metrics">
								<li>
									<span class="wpcmfm-health-bullet wpcmfm-bullet-danger"></span>
									<span><?php esc_html_e( 'Validation Errors:', 'wpcalibrate-merchant-feed-manager' ); ?></span>
									<strong><?php echo esc_html( (string) $total_errors ); ?></strong>
								</li>
								<li>
									<span class="wpcmfm-health-bullet wpcmfm-bullet-warning"></span>
									<span><?php esc_html_e( 'Validation Warnings:', 'wpcalibrate-merchant-feed-manager' ); ?></span>
									<strong><?php echo esc_html( (string) $total_warnings ); ?></strong>
								</li>
								<li>
									<span class="wpcmfm-health-bullet wpcmfm-bullet-info"></span>
									<span><?php esc_html_e( 'Missing Data Notices:', 'wpcalibrate-merchant-feed-manager' ); ?></span>
									<strong><?php echo esc_html( (string) $total_missing ); ?></strong>
								</li>
								<li>
									<span class="wpcmfm-health-bullet wpcmfm-bullet-muted"></span>
									<span><?php esc_html_e( 'Rule Exclusions:', 'wpcalibrate-merchant-feed-manager' ); ?></span>
									<strong><?php echo esc_html( (string) $total_excluded ); ?></strong>
								</li>
							</ul>

							<div class="wpcmfm-mt-4">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=health' ) ); ?>" class="button button-secondary wpcmfm-w-100">
									<?php esc_html_e( 'View Detailed Issue Reports', 'wpcalibrate-merchant-feed-manager' ); ?>
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
