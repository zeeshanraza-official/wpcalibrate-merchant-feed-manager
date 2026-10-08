=== WPCalibrate Merchant Feed Manager ===
Contributors: wpcalibrate
Donate link: https://wpcalibrate.com
Tags: woocommerce, product feed, google shopping, google merchant center, facebook catalog
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.2
WC requires at least: 8.5
WC tested up to: 11.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Production-grade WooCommerce product feed manager for Google Merchant Center, Meta, TikTok, Microsoft, Pinterest, and custom XML/CSV feeds.

== Description ==

**WPCalibrate Merchant Feed Manager** is an enterprise-grade WooCommerce product feed management solution built for reliability, data accuracy, and scale. Generate standards-compliant product feeds and stable URLs for all major shopping and advertising channels.

The core product advantage is not merely exporting products to XML. The core value is reliable merchant feeds, strong product-data validation, channel-aware field mapping, granular variation handling, feed-specific overrides, rule-based product selection, scalable background generation, and clear feed-health reporting.

### Supported Channels (V1)

* **Google Merchant Center** - Full XML (RSS 2.0 with Google Base schema) and CSV formats. Deep validation for GTIN (UPC/EAN check digit), MPN, Brand, and Google Product Category taxonomy.
* **Meta / Facebook & Instagram Catalogs** - Native catalog CSV and XML feeds with Meta-specific availability mapping.
* **Microsoft / Bing Shopping** - Dedicated XML and CSV feed generation for Microsoft Advertising campaigns.
* **Pinterest Catalog** - Optimized catalog feeds formatted to Pinterest specifications.
* **TikTok Catalog** - Fast and compliant catalog feeds for TikTok Shop and TikTok Ads manager.
* **Custom XML** - Fully customizable XML feeds with administrator-defined root tags, element tags, and field mappings.
* **Custom CSV** - Customizable CSV export with configurable headers and automated formula injection mitigation.

### Key Features

* **High Performance Background Processing**: Powered by WooCommerce Action Scheduler. Chunks large catalogs into memory-safe batches without blocking your browser or causing memory exhaustion.
* **Pre-Publication Validation**: Evaluates missing titles, descriptions, prices, image URLs, and identifier requirements. Categorized into Errors, Warnings, Missing Data, and Excluded items.
* **GS1 Modulo-10 GTIN Checksum Verification**: Validates 8, 12, 13, and 14-digit GTIN barcodes before submitting to shopping channels.
* **Feed-Specific Overrides**: Override brands, titles, categories, or custom labels per feed without modifying your live WooCommerce product data.
* **Stable Delivery URLs**: Provides permanent, cache-friendly URLs for channel crawlers with atomic file replacement to prevent corrupt partial downloads.
* **High-Performance Order Storage (HPOS)**: Fully compatible with WooCommerce HPOS (custom order tables) and block cart/checkout features.

== Installation ==

1. Upload the `wpcalibrate-merchant-feed-manager` folder to your `/wp-content/plugins/` directory, or install the ZIP file via **Plugins > Add New > Upload Plugin**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Ensure WooCommerce (version 8.5 or higher) is installed and active.
4. Navigate to **WPCalibrate > Feed Manager** in your WordPress admin menu.
5. Click **+ Add New Feed**, configure your target channel, mappings, and schedule, then click **Save Feed** and **Generate Now**.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =
Yes. WPCalibrate Merchant Feed Manager is designed specifically for WooCommerce stores and leverages WooCommerce public CRUD APIs and Action Scheduler.

= Will generating large feeds slow down my site or timeout? =
No. Feed generation runs in background chunks via Action Scheduler. Large catalogs are streamed in batches directly to safe temporary files and finalized atomically.

= Does this plugin modify my WooCommerce products when applying overrides? =
No. All feed-specific overrides are stored in plugin-controlled feed configuration and applied during export without altering your WooCommerce database records.

= Can I keep my feeds when deactivating or updating? =
Yes. Deactivation and routine upgrades safely preserve all feeds, runs, and settings. Complete data removal occurs only if you explicitly enable "Delete all plugin data on uninstall" under Settings.

== Screenshots ==

1. Dashboard overview displaying feed status, catalog health metrics, and recent events.
2. Feeds management screen with stable feed URLs and generation controls.
3. Feed configuration showing rules, mappings, and overrides.
4. Feed Health diagnostics with paginated validation issues.
5. Product readiness inspector.

== Changelog ==

= 1.0.0 =
* Initial production release.
* Support for Google Merchant Center, Meta, Microsoft Shopping, Pinterest, TikTok, Custom XML, and Custom CSV.
* Integrated Action Scheduler background generation pipeline.
* Full GS1 GTIN Modulo-10 checksum verification.
* Comprehensive rule engine and channel-aware field mapping.
* WooCommerce HPOS compatibility declared.
