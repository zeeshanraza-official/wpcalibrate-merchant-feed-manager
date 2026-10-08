<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Admin;

use WPCalibrate\MerchantFeedManager\Feed\FeedRepository;
use WPCalibrate\MerchantFeedManager\Feed\FeedRunRepository;
use WPCalibrate\MerchantFeedManager\Validation\IssueRepository;
use WPCalibrate\MerchantFeedManager\Validation\ValidationIssue;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Screen rendering detailed Feed Health diagnostics and issue reports.
 */
class FeedHealthScreen {

	/**
	 * Render Feed Health screen HTML.
	 */
	public static function render(): void {
		$feed_id  = isset( $_GET['feed_id'] ) ? (int) wp_unslash( $_GET['feed_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$severity = isset( $_GET['severity'] ) ? sanitize_key( wp_unslash( $_GET['severity'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged    = isset( $_GET['paged'] ) ? max( 1, (int) wp_unslash( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = 25;
		$offset   = ( $paged - 1 ) * $per_page;

		$feeds = FeedRepository::get_all( [ 'limit' => 100 ] );

		// Selected feed run stats
		$run = $feed_id > 0 ? FeedRunRepository::get_latest_for_feed( $feed_id ) : null;

		// Fetch filtered issues
		$filter_args = [
			'feed_id'  => $feed_id,
			'severity' => $severity,
			'limit'    => $per_page,
			'offset'   => $offset,
		];

		$issues        = IssueRepository::get_issues( $filter_args );
		$total_issues  = IssueRepository::count_issues( [ 'feed_id' => $feed_id, 'severity' => $severity ] );
		$total_pages   = ceil( $total_issues / $per_page );
		$common_issues = IssueRepository::get_most_common_issues( $feed_id, 5 );

		?>
		<div class="wpcmfm-health-screen">
			<div class="wpcmfm-health-header wpcmfm-d-flex wpcmfm-justify-between wpcmfm-align-center wpcmfm-mb-3">
				<h2><?php esc_html_e( 'Feed Health & Diagnostics', 'wpcalibrate-merchant-feed-manager' ); ?></h2>
				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="wpcmfm-d-flex wpcmfm-gap-2">
					<input type="hidden" name="page" value="wpcalibrate-merchant-feed-manager" />
					<input type="hidden" name="tab" value="health" />
					<select name="feed_id" onchange="this.form.submit()">
						<option value="0"><?php esc_html_e( 'All Feeds', 'wpcalibrate-merchant-feed-manager' ); ?></option>
						<?php foreach ( $feeds as $f ) : ?>
							<option value="<?php echo esc_attr( (string) $f->id ); ?>" <?php selected( $feed_id, $f->id ); ?>>
								<?php echo esc_html( $f->name . ' (' . strtoupper( $f->channel ) . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<select name="severity" onchange="this.form.submit()">
						<option value=""><?php esc_html_e( 'All Severities', 'wpcalibrate-merchant-feed-manager' ); ?></option>
						<option value="error" <?php selected( $severity, 'error' ); ?>><?php esc_html_e( 'Errors', 'wpcalibrate-merchant-feed-manager' ); ?></option>
						<option value="warning" <?php selected( $severity, 'warning' ); ?>><?php esc_html_e( 'Warnings', 'wpcalibrate-merchant-feed-manager' ); ?></option>
						<option value="missing_data" <?php selected( $severity, 'missing_data' ); ?>><?php esc_html_e( 'Missing Data', 'wpcalibrate-merchant-feed-manager' ); ?></option>
						<option value="excluded" <?php selected( $severity, 'excluded' ); ?>><?php esc_html_e( 'Excluded', 'wpcalibrate-merchant-feed-manager' ); ?></option>
					</select>
				</form>
			</div>

			<?php if ( $run ) : ?>
				<div class="wpcmfm-stat-grid wpcmfm-mb-4">
					<div class="wpcmfm-stat-card">
						<div class="wpcmfm-stat-value"><?php echo esc_html( (string) $run->total_products ); ?></div>
						<div class="wpcmfm-stat-label"><?php esc_html_e( 'Candidate Products', 'wpcalibrate-merchant-feed-manager' ); ?></div>
					</div>
					<div class="wpcmfm-stat-card wpcmfm-stat-success">
						<div class="wpcmfm-stat-value"><?php echo esc_html( (string) $run->included_records ); ?></div>
						<div class="wpcmfm-stat-label"><?php esc_html_e( 'Included Records', 'wpcalibrate-merchant-feed-manager' ); ?></div>
					</div>
					<div class="wpcmfm-stat-card <?php echo $run->error_count > 0 ? 'wpcmfm-stat-danger' : ''; ?>">
						<div class="wpcmfm-stat-value"><?php echo esc_html( (string) $run->error_count ); ?></div>
						<div class="wpcmfm-stat-label"><?php esc_html_e( 'Errors', 'wpcalibrate-merchant-feed-manager' ); ?></div>
					</div>
					<div class="wpcmfm-stat-card">
						<div class="wpcmfm-stat-value"><?php echo esc_html( (string) $run->warning_count ); ?></div>
						<div class="wpcmfm-stat-label"><?php esc_html_e( 'Warnings', 'wpcalibrate-merchant-feed-manager' ); ?></div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $common_issues ) ) : ?>
				<div class="wpcmfm-card wpcmfm-mb-4">
					<div class="wpcmfm-card-header">
						<h3><?php esc_html_e( 'Top Issues Detected', 'wpcalibrate-merchant-feed-manager' ); ?></h3>
					</div>
					<div class="wpcmfm-card-body">
						<ul class="wpcmfm-common-issues-list">
							<?php foreach ( $common_issues as $c_issue ) : ?>
								<li class="wpcmfm-d-flex wpcmfm-justify-between wpcmfm-align-center wpcmfm-py-1">
									<div>
										<span class="wpcmfm-badge wpcmfm-badge-<?php echo esc_attr( $c_issue['severity'] ); ?>">
											<?php echo esc_html( strtoupper( $c_issue['severity'] ) ); ?>
										</span>
										<strong><?php echo esc_html( $c_issue['message'] ); ?></strong>
										<span class="wpcmfm-text-muted">(<code><?php echo esc_html( $c_issue['field_name'] ); ?></code>)</span>
									</div>
									<span class="wpcmfm-issue-count"><?php echo esc_html( sprintf( __( '%d products', 'wpcalibrate-merchant-feed-manager' ), $c_issue['count'] ) ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</div>
			<?php endif; ?>

			<div class="wpcmfm-card">
				<div class="wpcmfm-card-header">
					<h3><?php esc_html_e( 'Product Validation Log', 'wpcalibrate-merchant-feed-manager' ); ?></h3>
				</div>
				<div class="wpcmfm-card-body">
					<?php if ( empty( $issues ) ) : ?>
						<p class="wpcmfm-empty-state"><?php esc_html_e( 'No issues found matching the selected criteria. Validation passed for the rules checked by this plugin.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Product', 'wpcalibrate-merchant-feed-manager' ); ?></th>
									<th><?php esc_html_e( 'Severity', 'wpcalibrate-merchant-feed-manager' ); ?></th>
									<th><?php esc_html_e( 'Field', 'wpcalibrate-merchant-feed-manager' ); ?></th>
									<th><?php esc_html_e( 'Issue Code', 'wpcalibrate-merchant-feed-manager' ); ?></th>
									<th><?php esc_html_e( 'Message', 'wpcalibrate-merchant-feed-manager' ); ?></th>
									<th><?php esc_html_e( 'Action', 'wpcalibrate-merchant-feed-manager' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $issues as $issue ) :
									$prod_title = sprintf( 'Product #%d', $issue->product_id );
									if ( function_exists( 'wc_get_product' ) ) {
										$p = wc_get_product( $issue->product_id );
										if ( $p ) {
											$prod_title = $p->get_name();
										}
									}
									$edit_link = admin_url( 'post.php?post=' . $issue->product_id . '&action=edit' );
									?>
									<tr>
										<td>
											<strong><?php echo esc_html( $prod_title ); ?></strong>
											<?php if ( $issue->variation_id > 0 ) : ?>
												<span class="wpcmfm-text-muted">(<?php echo esc_html( sprintf( __( 'Variation #%d', 'wpcalibrate-merchant-feed-manager' ), $issue->variation_id ) ); ?>)</span>
											<?php endif; ?>
										</td>
										<td>
											<span class="wpcmfm-badge wpcmfm-badge-<?php echo esc_attr( $issue->severity ); ?>">
												<?php echo esc_html( strtoupper( $issue->severity ) ); ?>
											</span>
										</td>
										<td><code><?php echo esc_html( $issue->field_name ); ?></code></td>
										<td><?php echo esc_html( $issue->issue_code ); ?></td>
										<td><?php echo esc_html( $issue->message ); ?></td>
										<td>
											<a href="<?php echo esc_url( $edit_link ); ?>" class="button button-small" target="_blank" rel="noopener noreferrer">
												<?php esc_html_e( 'Edit Product', 'wpcalibrate-merchant-feed-manager' ); ?>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<?php if ( $total_pages > 1 ) : ?>
							<div class="wpcmfm-pagination wpcmfm-d-flex wpcmfm-justify-between wpcmfm-align-center wpcmfm-mt-3">
								<span><?php echo esc_html( sprintf( __( 'Showing page %1$d of %2$d (%3$d total issues)', 'wpcalibrate-merchant-feed-manager' ), $paged, $total_pages, $total_issues ) ); ?></span>
								<div class="wpcmfm-page-links">
									<?php if ( $paged > 1 ) : ?>
										<a href="<?php echo esc_url( add_query_arg( 'paged', $paged - 1 ) ); ?>" class="button button-small">&laquo; <?php esc_html_e( 'Previous', 'wpcalibrate-merchant-feed-manager' ); ?></a>
									<?php endif; ?>
									<?php if ( $paged < $total_pages ) : ?>
										<a href="<?php echo esc_url( add_query_arg( 'paged', $paged + 1 ) ); ?>" class="button button-small"><?php esc_html_e( 'Next', 'wpcalibrate-merchant-feed-manager' ); ?> &raquo;</a>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}
}
