# WPCalibrate Merchant Feed Manager

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/zeeshanraza-official/wpcalibrate-merchant-feed-manager)
[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-21759b.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.5%2B-96588a.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-777bb4.svg)](https://www.php.net/)
[![HPOS](https://img.shields.io/badge/HPOS-Compatible-success.svg)](https://woocommerce.com/)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](LICENSE)

**WPCalibrate Merchant Feed Manager** is an enterprise-grade, memory-efficient WooCommerce product feed generator engineered to transform your catalog into high-converting, policy-compliant shopping feeds for **Google Merchant Center**, **Meta (Facebook & Instagram Shops)**, **TikTok Shop**, **Microsoft Shopping (Bing)**, **Pinterest Product Catalogs**, and **Custom XML/CSV** marketplaces.

---

## Table of Contents
- [About](#about)
- [Key Features](#key-features)
- [Architecture & Methods](#architecture--methods)
- [Supported Channels](#supported-channels)
- [Documentation](#documentation)
  - [System Requirements](#system-requirements)
  - [Installation](#installation)
  - [Automatic Dashboard Updates](#automatic-dashboard-updates)
  - [Quick Start Guide](#quick-start-guide)
  - [Field Mapping & Variation Inheritance](#field-mapping--variation-inheritance)
  - [Feed Health & Validation Engine](#feed-health--validation-engine)
- [Security Hardening](#security-hardening)
- [Changelog](#changelog)
- [License & Credits](#license--credits)

---

## About

Managing large WooCommerce product catalogs across multiple advertising platforms often leads to common points of failure: PHP memory exhaustion, timeout crashes during feed generation, disapproved products due to invalid GTINs, and crawlers receiving incomplete or corrupted files.

**WPCalibrate Merchant Feed Manager** solves these challenges through a streaming disk architecture, non-blocking asynchronous batching via WooCommerce Action Scheduler, atomic temporary file swaps, and built-in GS1 GTIN Modulo-10 checksum validation. Feeds are generated reliably in the background, served through high-speed cached endpoints with HTTP ETag headers, and maintained automatically.

---

## Key Features

- **Universal Channel Adapters**: Built-in, compliant adapters for Google Merchant Center (RSS 2.0 / Atom), Meta Catalog (Facebook/Instagram), TikTok Shop, Microsoft Shopping, Pinterest, and Custom XML/CSV.
- **GS1 GTIN Modulo-10 Checksum Engine**: Validates UPC-A, EAN-13, EAN-8, and ISBN-13 barcodes mathematically against the official GS1 standard, flagging invalid check digits before submitting to Google or Meta.
- **Direct Disk Streaming**: `XmlStreamWriter` and `CsvStreamWriter` flush product records in chunks directly to disk, keeping memory consumption consistently below 16MB even on 100,000+ item catalogs.
- **Atomic File Swapping**: Generation writes to isolated `.tmp` files. Only upon 100% completion is the file atomically swapped to the public path, guaranteeing external crawlers never encounter half-written feeds.
- **Concurrency Locks**: Mutual exclusion locks prevent overlapping batch executions or race conditions on high-traffic sites.
- **Variable Product Handling**: Smart variation resolution automatically inherits parent titles, descriptions, categories, and brand data when variation fields are omitted.
- **Conditional Rule Engine**: Rule-based product filtering (stock status, minimum/maximum price thresholds, category inclusion/exclusion, custom taxonomy matching).
- **Feed Health Reporting**: Real-time diagnostic audit recording validation errors, warnings, and missing required attributes with direct product edit links.
- **Dynamic HTTP Delivery**: Public feeds serve `ETag` and `Last-Modified` headers, returning `304 Not Modified` to crawler bots when the feed has not changed, conserving server bandwidth.
- **WooCommerce HPOS Compatible**: High-Performance Order Storage (HPOS) ready and strictly typed for PHP 8.2 & 8.3.

---

## Architecture & Methods

```
  WooCommerce Catalog
          │
          ▼
   Product Query ────► Rule Filtering Engine (Stock, Price, Category)
          │
          ▼
 Product Normalizer ──► Variation Inheritance (Parent Fallbacks)
          │
          ▼
  Field Resolver ─────► Channel Adapter (Google, Meta, TikTok, Custom)
          │
          ▼
  Validation Engine ──► GS1 GTIN Modulo-10 Checksum Audit
          │
          ▼
  Streaming Writer ───► Atomic .tmp Output Buffer
          │
          ▼
   Atomic Rename ─────► Live Public Feed Delivery (ETag / 304 Caching)
```

1. **Stream Processing**: Memory footprints remain flat regardless of store size.
2. **Action Scheduler Integration**: Background batch execution that respects shared hosting timeout boundaries.
3. **Isolation & Atomic Release**: Zero feed corruption during catalog syncs.

---

## Supported Channels

| Channel | Format | Key Specifications |
| :--- | :--- | :--- |
| **Google Merchant Center** | XML (RSS 2.0) / CSV | Full `g:` namespace, GTIN/MPN/Brand identifiers, Google Product Taxonomy |
| **Meta Catalog** | CSV / XML | Facebook & Instagram Shop, Dynamic Ads, availability, brand |
| **TikTok Shop** | CSV | Direct product catalog synchronization, SKU mapping |
| **Microsoft Shopping** | XML / CSV | Bing Product Ads specification, rich attributes |
| **Pinterest Catalog** | XML / CSV | Product pins, item group IDs, condition |
| **Custom XML / CSV** | Custom | Universal aggregator format with custom root and item tags |

---

## Documentation

### System Requirements
- **WordPress**: 6.4 or higher
- **WooCommerce**: 8.5 or higher
- **PHP**: 8.2 or 8.3
- **Extensions**: `curl`, `dom`, `libxml`, `json`

### Installation

#### Method 1: WordPress Admin Upload
1. Download the release archive: [`wpcalibrate-merchant-feed-manager-1.0.0.zip`](https://github.com/zeeshanraza-official/wpcalibrate-merchant-feed-manager/releases).
2. Go to **WordPress Admin &rarr; Plugins &rarr; Add New &rarr; Upload Plugin**.
3. Choose the ZIP file and click **Install Now**.
4. Click **Activate Plugin**.

#### Method 2: Git Clone
```bash
cd wp-content/plugins/
git clone https://github.com/zeeshanraza-official/wpcalibrate-merchant-feed-manager.git wpcalibrate-merchant-feed-manager
```

### Automatic Dashboard Updates

This repository is equipped with GitHub update headers (`GitHub Plugin URI`). To update this plugin directly from your WordPress dashboard when new versions are released on GitHub:

1. Install the free [Git Updater](https://git-updater.com/) plugin or [GitHub Updater](https://github.com/afragen/github-updater).
2. Once installed, **WPCalibrate Merchant Feed Manager** will automatically check this repository for new releases.
3. When a new version is published, an **Update Now** notification appears in your WordPress Plugins dashboard, enabling one-click in-dashboard updates.

---

### Quick Start Guide

1. **Set Global Standards**: Navigate to **WPCalibrate &rarr; Feed Manager &rarr; Settings** to define your default brand name, fallback availability, and Google product category taxonomy.
2. **Create a Feed**: Go to **Feeds &rarr; Add New Feed**. Select your channel (e.g. Google Merchant Center), format (XML/CSV), and refresh frequency (Hourly, Twice Daily, Daily).
3. **Map Attributes**: In the **Field Mapping** tab, match standard merchant attributes (`id`, `title`, `description`, `price`, `gtin`, `brand`, `link`) to WooCommerce fields.
4. **Define Rules**: Filter products dynamically by excluding out-of-stock items or restricting to specific categories.
5. **Inspect Health**: Run generation and visit **Feed Health** to verify GTIN check digits and policy compliance.
6. **Submit Feed**: Copy the public URL and schedule fetching in Google Merchant Center, Meta Commerce Manager, or TikTok Shop.

---

### Field Mapping & Variation Inheritance

Variable products automatically resolve attribute mappings through a hierarchical fallback chain:
1. Variation-specific value (e.g. variation SKU, price, color).
2. Parent product value (e.g. parent title, description, category, brand).
3. Global plugin default (defined in Settings).

---

### Feed Health & Validation Engine

The built-in validation engine inspects:
- **GS1 GTIN Modulo-10 Checksums**: Validates 8, 12, 13, and 14-digit barcodes.
- **Required Channel Fields**: Flags missing titles, prices, links, or image URLs.
- **Character Encoding**: Sanitizes control characters and invalid XML entity declarations.

---

## Security Hardening

- **Capability Verification**: All admin actions and REST endpoints enforce `manage_woocommerce` capability checks.
- **CSRF Protection**: Comprehensive WordPress nonce verification on all form submissions and AJAX calls.
- **CSV Formula Injection Defense**: All exported CSV cell values starting with `=,+,-,@,\t,\r` are escaped with leading single quotes to prevent spreadsheet execution vulnerabilities.
- **XML Entity Neutralization**: Strict entity decoding and CDATA wrapping preventing XXE injection.
- **Secret Redaction**: License keys and internal tokens are never logged or exposed in cleartext.

---

## Changelog

### [1.0.1] - 2026-10-08
- **Added**: Interactive Overview screen (`src/Admin/OverviewScreen.php`) detailing plugin Purpose, 6-Step Workflow Guide, Architecture, and Supported Channels.
- **Added**: GitHub update integration headers (`GitHub Plugin URI`) for one-click WordPress dashboard updates.
- **Added**: Persistent cURL FTP connection deployment engine (`deploy-ftp.php`).
- **Fixed**: Admin sidebar menu icon updated to official WPCalibrate branding logo (`branding/icon-white.png`) with crisp 20x20 high-DPI scaling.

### [1.0.0] - 2026-10-08
- Initial production release.
- Multi-channel adapters for Google Merchant Center, Meta, TikTok, Microsoft, Pinterest, and Custom XML/CSV.
- Memory-safe XML and CSV streaming writers.
- GS1 GTIN Modulo-10 checksum validation engine.
- Action Scheduler asynchronous batch engine with generation locks.
- Full HPOS and WordPress 6.4+ compatibility.

---

## License & Credits

- **Author**: [WPCalibrate](https://wpcalibrate.com)
- **License**: [GNU General Public License v2.0 or later](LICENSE)
- **Copyright**: &copy; 2026 WPCalibrate. All rights reserved.
