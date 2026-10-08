<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Admin;

use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Product\ProductNormalizer;
use WPCalibrate\MerchantFeedManager\Product\ProductQuery;
use WPCalibrate\MerchantFeedManager\Validation\ValidationEngine;
use WPCalibrate\MerchantFeedManager\Validation\ValidationIssue;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Screen rendering product readiness overview and merchant field diagnostics.
 */
class ProductDataScreen {

	/**
	 * Render Product Data readiness screen HTML.
	 */
	public static function render(): void {
		$paged    = isset( $_GET['paged'] ) ? max( 1, (int) wp_unslash( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = 20;
		$offset   = ( $paged - 1 ) * $per_page;

		$dummy_feed = new FeedModel();
		$dummy_feed->channel = 'google';

		$product_ids = ProductQuery::get_product_ids_batch( $per_page, $offset );
		$total       = ProductQuery::get_total_product_count();
		$total_pages = ceil( $total / $per_page );

		?>
		<div class="wpcmfm-product-data-screen">
			<div class="wpcmfm-d-flex wpcmfm-justify-between wpcmfm-align-center wpcmfm-mb-3">
				<h2><?php esc_html_e( 'Product Readiness Inspector', 'wpcalibrate-merchant-feed-manager' ); ?></h2>
				<span class="wpcmfm-text-muted">
					<?php echo esc_html( sprintf( __( 'Reviewing %d products in your WooCommerce catalog', 'wpcalibrate-merchant-feed-manager' ), $total ) ); ?>
				</span>
			</div>

			<div class="wpcmfm-card">
				<div class="wpcmfm-card-body">
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Product', 'wpcalibrate-merchant-feed-manager' ); ?></th>
								<th><?php esc_html_e( 'SKU', 'wpcalibrate-merchant-feed-manager' ); ?></th>
								<th><?php esc_html_e( 'Price', 'wpcalibrate-merchant-feed-manager' ); ?></th>
								<th><?php esc_html_e( 'Stock', 'wpcalibrate-merchant-feed-manager' ); ?></th>
								<th><?php esc_html_e( 'Brand', 'wpcalibrate-merchant-feed-manager' ); ?></th>
								<th><?php esc_html_e( 'GTIN / Barcode', 'wpcalibrate-merchant-feed-manager' ); ?></th>
								<th><?php esc_html_e( 'Readiness Status', 'wpcalibrate-merchant-feed-manager' ); ?></th>
								<th><?php esc_html_e( 'Action', 'wpcalibrate-merchant-feed-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $product_ids ) ) : ?>
								<tr>
									<td colspan="8" class="wpcmfm-text-center"><?php esc_html_e( 'No published products found in WooCommerce.', 'wpcalibrate-merchant-feed-manager' ); ?></td>
								</tr>
							<?php else :
								foreach ( $product_ids as $pid ) :
									$prod = ProductQuery::get_product( $pid );
									if ( ! $prod ) {
										continue;
									}

									$norm_list = ProductNormalizer::normalize( $prod, $dummy_feed );
									$first_norm = $norm_list[0] ?? null;
									$issues = $first_norm ? ValidationEngine::validate_product( $first_norm, $dummy_feed ) : [];

									$has_error = false;
									$has_warn  = false;
									foreach ( $issues as $iss ) {
										if ( ValidationIssue::SEVERITY_ERROR === $iss->severity ) {
											$has_error = true;
										} elseif ( ValidationIssue::SEVERITY_WARNING === $iss->severity ) {
											$has_warn = true;
										}
									}

									$edit_url = admin_url( 'post.php?post=' . $pid . '&action=edit' );
									?>
									<tr>
										<td><strong><?php echo esc_html( $prod->get_name() ); ?></strong></td>
										<td><?php echo esc_html( $prod->get_sku() ?: '—' ); ?></td>
										<td><?php echo esc_html( $first_norm ? $first_norm->format_price( $first_norm->price ) : '—' ); ?></td>
										<td><?php echo esc_html( ucfirst( $prod->get_stock_status() ) ); ?></td>
										<td><?php echo esc_html( $first_norm && $first_norm->brand ? $first_norm->brand : '—' ); ?></td>
										<td><?php echo esc_html( $first_norm && $first_norm->gtin ? $first_norm->gtin : '—' ); ?></td>
										<td>
											<?php if ( $has_error ) : ?>
												<span class="wpcmfm-badge wpcmfm-badge-error"><?php esc_html_e( 'Missing Required Fields', 'wpcalibrate-merchant-feed-manager' ); ?></span>
											<?php elseif ( $has_warn ) : ?>
												<span class="wpcmfm-badge wpcmfm-badge-warning"><?php esc_html_e( 'Has Warnings', 'wpcalibrate-merchant-feed-manager' ); ?></span>
											<?php else : ?>
												<span class="wpcmfm-badge wpcmfm-badge-success"><?php esc_html_e( 'Ready', 'wpcalibrate-merchant-feed-manager' ); ?></span>
											<?php endif; ?>
										</td>
										<td>
											<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small" target="_blank" rel="noopener noreferrer">
												<?php esc_html_e( 'Edit', 'wpcalibrate-merchant-feed-manager' ); ?>
											</a>
										</td>
									</tr>
								<?php endforeach;
							endif; ?>
						</tbody>
					</table>

					<?php if ( $total_pages > 1 ) : ?>
						<div class="wpcmfm-pagination wpcmfm-d-flex wpcmfm-justify-between wpcmfm-align-center wpcmfm-mt-3">
							<span><?php echo esc_html( sprintf( __( 'Showing page %1$d of %2$d (%3$d total products)', 'wpcalibrate-merchant-feed-manager' ), $paged, $total_pages, $total ) ); ?></span>
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
				</div>
			</div>
		</div>
		<?php
	}
}
