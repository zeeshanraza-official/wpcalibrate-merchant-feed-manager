<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Admin;

use WPCalibrate\MerchantFeedManager\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Overview tab explaining plugin Purpose, How to Use, and Core Methods.
 */
class OverviewScreen {

	/**
	 * Render the Overview screen.
	 */
	public static function render(): void {
		Security::check_auth_or_die();

		$plugin_url   = defined( 'WPCMFM_PLUGIN_URL' ) ? WPCMFM_PLUGIN_URL : plugin_dir_url( dirname( __DIR__ ) . '/wpcalibrate-merchant-feed-manager.php' );
		$brand_logo   = $plugin_url . 'branding/icon-dark.png';
		$add_feed_url = admin_url( 'admin.php?page=' . Menu::PAGE_SLUG . '&tab=feed-edit' );
		$feeds_url    = admin_url( 'admin.php?page=' . Menu::PAGE_SLUG . '&tab=feeds' );
		$health_url   = admin_url( 'admin.php?page=' . Menu::PAGE_SLUG . '&tab=health' );
		$settings_url = admin_url( 'admin.php?page=' . Menu::PAGE_SLUG . '&tab=settings' );
		?>
		<div class="wpcmfm-overview-container">

			<!-- Hero / Purpose Banner -->
			<div class="wpcmfm-overview-hero">
				<div class="wpcmfm-overview-hero-content">
					<div class="wpcmfm-hero-badge">
						<span class="dashicons dashicons-superhero"></span>
						<?php esc_html_e( 'Official Documentation & Guide', 'wpcalibrate-merchant-feed-manager' ); ?>
					</div>
					<h2><?php esc_html_e( 'Welcome to WPCalibrate Merchant Feed Manager', 'wpcalibrate-merchant-feed-manager' ); ?></h2>
					<p class="wpcmfm-hero-lead">
						<?php esc_html_e( 'The enterprise-grade, memory-efficient solution designed to transform your WooCommerce catalog into high-converting, policy-compliant product feeds for Google, Meta, TikTok, Microsoft, Pinterest, and custom marketplaces.', 'wpcalibrate-merchant-feed-manager' ); ?>
					</p>
					<div class="wpcmfm-hero-actions">
						<a href="<?php echo esc_url( $add_feed_url ); ?>" class="button button-primary button-hero">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php esc_html_e( 'Create Your First Feed', 'wpcalibrate-merchant-feed-manager' ); ?>
						</a>
						<a href="<?php echo esc_url( $health_url ); ?>" class="button button-secondary button-hero">
							<span class="dashicons dashicons-heart"></span>
							<?php esc_html_e( 'Inspect Catalog Health', 'wpcalibrate-merchant-feed-manager' ); ?>
						</a>
					</div>
				</div>
			</div>

			<!-- Core Pillars / Purpose Cards -->
			<div class="wpcmfm-section-title">
				<h3><span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Plugin Purpose & Key Capabilities', 'wpcalibrate-merchant-feed-manager' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Built from the ground up to solve the real challenges store owners face when syncing products to advertising platforms.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
			</div>

			<div class="wpcmfm-overview-grid wpcmfm-cols-3">
				<div class="wpcmfm-card wpcmfm-feature-card">
					<div class="wpcmfm-feature-icon wpcmfm-icon-channels">
						<span class="dashicons dashicons-share"></span>
					</div>
					<h4><?php esc_html_e( 'Universal Channel Adapters', 'wpcalibrate-merchant-feed-manager' ); ?></h4>
					<p><?php esc_html_e( 'Native channel specifications tailored for Google Merchant Center (RSS 2.0/Atom), Meta Catalog (Facebook/Instagram), TikTok Shop, Microsoft Shopping, Pinterest, and Custom XML/CSV structures.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
				</div>

				<div class="wpcmfm-card wpcmfm-feature-card">
					<div class="wpcmfm-feature-icon wpcmfm-icon-quality">
						<span class="dashicons dashicons-yes-alt"></span>
					</div>
					<h4><?php esc_html_e( 'GS1 GTIN & Policy Validation', 'wpcalibrate-merchant-feed-manager' ); ?></h4>
					<p><?php esc_html_e( 'Stop account suspensions before they occur. Built-in Modulo-10 checksum validation mathematically verifies UPC, EAN, and ISBN codes, flagging invalid barcodes and missing required fields in real-time.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
				</div>

				<div class="wpcmfm-card wpcmfm-feature-card">
					<div class="wpcmfm-feature-icon wpcmfm-icon-perf">
						<span class="dashicons dashicons-performance"></span>
					</div>
					<h4><?php esc_html_e( 'Infinite Scale & Zero Timeouts', 'wpcalibrate-merchant-feed-manager' ); ?></h4>
					<p><?php esc_html_e( 'Utilizes high-speed XML & CSV streaming writers paired with non-blocking Action Scheduler batch processing. Never hits PHP execution timeouts or memory limits, even on catalogs with 100,000+ items.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
				</div>
			</div>

			<!-- Step-by-Step Workflow Guide (How to Use) -->
			<div class="wpcmfm-section-title wpcmfm-mt-5">
				<h3><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'How to Use: The 6-Step Workflow Guide', 'wpcalibrate-merchant-feed-manager' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Follow this straightforward workflow to publish and maintain reliable product feeds.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
			</div>

			<div class="wpcmfm-workflow-timeline">
				<!-- Step 1 -->
				<div class="wpcmfm-step-card">
					<div class="wpcmfm-step-number">1</div>
					<div class="wpcmfm-step-body">
						<h4><?php esc_html_e( 'Configure Global Settings & Store Defaults', 'wpcalibrate-merchant-feed-manager' ); ?></h4>
						<p><?php esc_html_e( 'Navigate to the Settings tab to set your default brand name, fallback availability, price formatting, batch processing sizes, and Google taxonomy defaults. These provide safe fallbacks whenever individual products lack specific values.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
						<a href="<?php echo esc_url( $settings_url ); ?>" class="wpcmfm-step-link"><?php esc_html_e( 'Open Settings &rarr;', 'wpcalibrate-merchant-feed-manager' ); ?></a>
					</div>
				</div>

				<!-- Step 2 -->
				<div class="wpcmfm-step-card">
					<div class="wpcmfm-step-number">2</div>
					<div class="wpcmfm-step-body">
						<h4><?php esc_html_e( 'Create a Feed Channel Profile', 'wpcalibrate-merchant-feed-manager' ); ?></h4>
						<p><?php esc_html_e( 'Go to Feeds &rarr; Add New Feed. Choose your target channel (Google Merchant Center, Meta, TikTok, etc.), output format (XML or CSV), target market currency, and desired refresh interval (hourly, twice daily, or daily).', 'wpcalibrate-merchant-feed-manager' ); ?></p>
						<a href="<?php echo esc_url( $add_feed_url ); ?>" class="wpcmfm-step-link"><?php esc_html_e( 'Create New Feed &rarr;', 'wpcalibrate-merchant-feed-manager' ); ?></a>
					</div>
				</div>

				<!-- Step 3 -->
				<div class="wpcmfm-step-card">
					<div class="wpcmfm-step-number">3</div>
					<div class="wpcmfm-step-body">
						<h4><?php esc_html_e( 'Map Product Attributes & Variation Rules', 'wpcalibrate-merchant-feed-manager' ); ?></h4>
						<p><?php esc_html_e( 'Under the Field Mapping section, match standard channel tags (e.g. g:brand, g:gtin, g:color) to your WooCommerce attributes, custom meta fields, or product taxonomies. For variable products, child variations automatically inherit parent titles, descriptions, and categories seamlessly.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
					</div>
				</div>

				<!-- Step 4 -->
				<div class="wpcmfm-step-card">
					<div class="wpcmfm-step-number">4</div>
					<div class="wpcmfm-step-body">
						<h4><?php esc_html_e( 'Apply Smart Filtering & Exclusion Rules', 'wpcalibrate-merchant-feed-manager' ); ?></h4>
						<p><?php esc_html_e( 'Filter products dynamically using custom rules. Exclude out-of-stock items, filter by minimum price thresholds, exclude specific categories, or restrict feeds to specific product tags without modifying product data.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
					</div>
				</div>

				<!-- Step 5 -->
				<div class="wpcmfm-step-card">
					<div class="wpcmfm-step-number">5</div>
					<div class="wpcmfm-step-body">
						<h4><?php esc_html_e( 'Generate & Inspect Feed Health', 'wpcalibrate-merchant-feed-manager' ); ?></h4>
						<p><?php esc_html_e( 'Click "Generate Feed" to trigger the background batch engine. Visit the Feed Health tab to view real-time validation reports, checking for invalid GTIN barcodes, missing images, or empty descriptions before platforms crawl your file.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
						<a href="<?php echo esc_url( $health_url ); ?>" class="wpcmfm-step-link"><?php esc_html_e( 'Check Feed Health &rarr;', 'wpcalibrate-merchant-feed-manager' ); ?></a>
					</div>
				</div>

				<!-- Step 6 -->
				<div class="wpcmfm-step-card">
					<div class="wpcmfm-step-number">6</div>
					<div class="wpcmfm-step-body">
						<h4><?php esc_html_e( 'Connect Stable Public Feed URL to Merchant Platforms', 'wpcalibrate-merchant-feed-manager' ); ?></h4>
						<p><?php esc_html_e( 'Copy the permanent Feed URL from the Feeds tab and paste it into Google Merchant Center, Meta Commerce Manager, or TikTok Shop as a Scheduled Fetch feed. Your store will automatically keep remote catalogs refreshed and in sync.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
						<a href="<?php echo esc_url( $feeds_url ); ?>" class="wpcmfm-step-link"><?php esc_html_e( 'View All Feeds &rarr;', 'wpcalibrate-merchant-feed-manager' ); ?></a>
					</div>
				</div>
			</div>

			<!-- Method & Technical Architecture (Under the Hood) -->
			<div class="wpcmfm-section-title wpcmfm-mt-5">
				<h3><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'The Method & Architecture: How It Works Under the Hood', 'wpcalibrate-merchant-feed-manager' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Engineering design patterns implemented to guarantee reliability, data integrity, and high performance.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
			</div>

			<div class="wpcmfm-overview-grid wpcmfm-cols-2">
				<div class="wpcmfm-card wpcmfm-method-card">
					<div class="wpcmfm-method-badge"><?php esc_html_e( 'Stream Processing', 'wpcalibrate-merchant-feed-manager' ); ?></div>
					<h4><?php esc_html_e( 'Direct Disk Streaming Architecture', 'wpcalibrate-merchant-feed-manager' ); ?></h4>
					<p><?php esc_html_e( 'Standard WordPress feed plugins build giant XML strings or DOM structures in PHP RAM, leading to Fatal Memory Exhausted errors. WPCalibrate uses XmlStreamWriter and CsvStreamWriter to flush chunks directly to the filesystem, keeping RAM consumption consistently below 16MB.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
				</div>

				<div class="wpcmfm-card wpcmfm-method-card">
					<div class="wpcmfm-method-badge"><?php esc_html_e( 'Atomic Storage', 'wpcalibrate-merchant-feed-manager' ); ?></div>
					<h4><?php esc_html_e( 'Atomic Swapping & Generation Locks', 'wpcalibrate-merchant-feed-manager' ); ?></h4>
					<p><?php esc_html_e( 'Feeds are generated inside isolated temporary files (.tmp). When generation finishes with 100% integrity, the temporary file is atomically renamed into the live public feed. Merchant crawlers never receive half-written, broken, or empty feeds. Concurrency locks prevent duplicate simultaneous generation runs.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
				</div>

				<div class="wpcmfm-card wpcmfm-method-card">
					<div class="wpcmfm-method-badge"><?php esc_html_e( 'Background Scheduler', 'wpcalibrate-merchant-feed-manager' ); ?></div>
					<h4><?php esc_html_e( 'Action Scheduler Chunking', 'wpcalibrate-merchant-feed-manager' ); ?></h4>
					<p><?php esc_html_e( 'Large stores with thousands of products are processed in asynchronous batches via WooCommerce Action Scheduler. Each chunk processes a configurable batch of products, records incremental progress, and triggers the next step without blocking browser requests or hitting server timeouts.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
				</div>

				<div class="wpcmfm-card wpcmfm-method-card">
					<div class="wpcmfm-method-badge"><?php esc_html_e( 'Security & Compliance', 'wpcalibrate-merchant-feed-manager' ); ?></div>
					<h4><?php esc_html_e( 'OWASP Hardening & CSV Formula Neutralization', 'wpcalibrate-merchant-feed-manager' ); ?></h4>
					<p><?php esc_html_e( 'All XML outputs are sanitized against control characters and invalid entity declarations. CSV feeds automatically prefix formula-trigger characters (=, +, -, @, TAB) to prevent CSV injection vulnerabilities. Access is strictly guarded with nonces, capability checks, and public feed access tokens.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
				</div>
			</div>

			<!-- Supported Channels Showcase -->
			<div class="wpcmfm-section-title wpcmfm-mt-5">
				<h3><span class="dashicons dashicons-networking"></span> <?php esc_html_e( 'Supported Channels & Feed Formats', 'wpcalibrate-merchant-feed-manager' ); ?></h3>
			</div>

			<div class="wpcmfm-channels-showcase">
				<div class="wpcmfm-channel-pill">
					<span class="dashicons dashicons-google"></span>
					<strong><?php esc_html_e( 'Google Merchant Center', 'wpcalibrate-merchant-feed-manager' ); ?></strong>
					<span class="wpcmfm-badge-format">XML / CSV</span>
				</div>
				<div class="wpcmfm-channel-pill">
					<span class="dashicons dashicons-facebook"></span>
					<strong><?php esc_html_e( 'Meta / Facebook / Instagram', 'wpcalibrate-merchant-feed-manager' ); ?></strong>
					<span class="wpcmfm-badge-format">CSV / XML</span>
				</div>
				<div class="wpcmfm-channel-pill">
					<span class="dashicons dashicons-video-alt3"></span>
					<strong><?php esc_html_e( 'TikTok Shop Catalog', 'wpcalibrate-merchant-feed-manager' ); ?></strong>
					<span class="wpcmfm-badge-format">CSV</span>
				</div>
				<div class="wpcmfm-channel-pill">
					<span class="dashicons dashicons-windows"></span>
					<strong><?php esc_html_e( 'Microsoft / Bing Shopping', 'wpcalibrate-merchant-feed-manager' ); ?></strong>
					<span class="wpcmfm-badge-format">XML / CSV</span>
				</div>
				<div class="wpcmfm-channel-pill">
					<span class="dashicons dashicons-camera"></span>
					<strong><?php esc_html_e( 'Pinterest Product Catalog', 'wpcalibrate-merchant-feed-manager' ); ?></strong>
					<span class="wpcmfm-badge-format">XML / CSV</span>
				</div>
				<div class="wpcmfm-channel-pill">
					<span class="dashicons dashicons-code-standards"></span>
					<strong><?php esc_html_e( 'Custom XML & CSV Feeds', 'wpcalibrate-merchant-feed-manager' ); ?></strong>
					<span class="wpcmfm-badge-format">Universal</span>
				</div>
			</div>

			<!-- Quick Jump CTA Footer -->
			<div class="wpcmfm-overview-footer wpcmfm-card wpcmfm-mt-5">
				<div class="wpcmfm-d-flex wpcmfm-justify-between wpcmfm-align-center">
					<div>
						<h4 class="wpcmfm-mb-1"><?php esc_html_e( 'Ready to sync your products across major marketplaces?', 'wpcalibrate-merchant-feed-manager' ); ?></h4>
						<p class="wpcmfm-text-muted wpcmfm-mb-0"><?php esc_html_e( 'Set up your first automated feed in less than two minutes.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
					</div>
					<div>
						<a href="<?php echo esc_url( $add_feed_url ); ?>" class="button button-primary">
							<span class="dashicons dashicons-plus-alt"></span>
							<?php esc_html_e( 'Create Feed Now', 'wpcalibrate-merchant-feed-manager' ); ?>
						</a>
					</div>
				</div>
			</div>

		</div>
		<?php
	}
}
