<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Admin;

use WPCalibrate\MerchantFeedManager\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages WordPress admin menu registration, script enqueuing, and page rendering.
 */
class Menu {

	public const PARENT_SLUG = 'wpcalibrate';
	public const PAGE_SLUG   = 'wpcalibrate-merchant-feed-manager';

	/**
	 * Register admin menu hooks.
	 */
	public static function register(): void {
		add_action( 'admin_menu', [ self::class, 'register_menu' ], 9 );
		add_action( 'admin_init', [ self::class, 'handle_submissions' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
		add_action( 'admin_head', [ self::class, 'render_admin_menu_styles' ] );
	}

	/**
	 * Register or attach to the WPCalibrate top-level menu safely.
	 */
	public static function register_menu(): void {
		global $admin_page_hooks, $menu;

		$capability = Security::get_capability();
		$plugin_url = defined( 'WPCMFM_PLUGIN_URL' ) ? WPCMFM_PLUGIN_URL : plugin_dir_url( dirname( __DIR__ ) . '/wpcalibrate-merchant-feed-manager.php' );
		$brand_icon = $plugin_url . 'branding/icon-white.png';

		// Safely create parent menu if not already registered by another WPCalibrate plugin
		if ( ! isset( $admin_page_hooks[ self::PARENT_SLUG ] ) ) {
			add_menu_page(
				'WPCalibrate',
				'WPCalibrate',
				$capability,
				self::PARENT_SLUG,
				[ self::class, 'render_main_page' ],
				$brand_icon,
				58
			);
		} else {
			// Ensure parent menu icon uses the official branding logo
			if ( is_array( $menu ) ) {
				foreach ( $menu as $k => $item ) {
					if ( isset( $item[2] ) && self::PARENT_SLUG === $item[2] ) {
						$menu[ $k ][6] = $brand_icon;
						break;
					}
				}
			}
		}

		// Add this plugin as its own dedicated isolated submenu
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'WPCalibrate Merchant Feed Manager', 'wpcalibrate-merchant-feed-manager' ),
			__( 'Feed Manager', 'wpcalibrate-merchant-feed-manager' ),
			$capability,
			self::PAGE_SLUG,
			[ self::class, 'render_main_page' ]
		);
	}

	/**
	 * Output inline styles for the admin menu branding logo.
	 */
	public static function render_admin_menu_styles(): void {
		?>
		<style>
			#adminmenu .toplevel_page_wpcalibrate .wp-menu-image img {
				max-width: 20px;
				max-height: 20px;
				padding: 7px 0 0 0;
				object-fit: contain;
				opacity: 0.85;
				transition: opacity 0.2s ease;
			}
			#adminmenu .toplevel_page_wpcalibrate:hover .wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.current .wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.wp-has-current-submenu .wp-menu-image img {
				opacity: 1;
			}
		</style>
		<?php
	}

	/**
	 * Handle POST form submissions before headers are sent.
	 */
	public static function handle_submissions(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = sanitize_key( wp_unslash( $_GET['page'] ?? '' ) );
		if ( self::PAGE_SLUG !== $page && self::PARENT_SLUG !== $page ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = sanitize_key( wp_unslash( $_GET['tab'] ?? 'dashboard' ) );

		if ( 'feed-edit' === $tab ) {
			FeedEditScreen::process_save();
		} elseif ( 'feeds' === $tab ) {
			FeedListScreen::process_actions();
		} elseif ( 'settings' === $tab ) {
			SettingsScreen::process_save();
		} elseif ( 'license' === $tab ) {
			LicenseScreen::process_action();
		}
	}

	/**
	 * Enqueue styles and scripts ONLY on this plugin's admin screens.
	 *
	 * @param string $hook_suffix Admin page hook.
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		// Strictly verify this is our plugin page
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = sanitize_key( wp_unslash( $_GET['page'] ?? '' ) );
		$is_our_page = ( self::PAGE_SLUG === $page ) || ( self::PARENT_SLUG === $page && ! isset( $_GET['tab'] ) );

		if ( ! $is_our_page ) {
			return;
		}

		$plugin_url = defined( 'WPCMFM_PLUGIN_URL' ) ? WPCMFM_PLUGIN_URL : plugin_dir_url( dirname( __DIR__ ) . '/wpcalibrate-merchant-feed-manager.php' );
		$version    = defined( 'WPCMFM_VERSION' ) ? WPCMFM_VERSION : '1.0.0';

		wp_enqueue_style(
			'wpcmfm-admin-css',
			$plugin_url . 'assets/css/admin.css',
			[],
			$version
		);

		if ( is_rtl() ) {
			wp_enqueue_style(
				'wpcmfm-admin-rtl',
				$plugin_url . 'assets/css/admin-rtl.css',
				[ 'wpcmfm-admin-css' ],
				$version
			);
		}

		wp_enqueue_script(
			'wpcmfm-admin-js',
			$plugin_url . 'assets/js/admin.js',
			[ 'jquery' ],
			$version,
			true
		);

		wp_localize_script(
			'wpcmfm-admin-js',
			'wpcmfm_vars',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => Security::create_nonce( Security::NONCE_ACTION ),
				'strings'  => [
					'copied'        => __( 'Copied!', 'wpcalibrate-merchant-feed-manager' ),
					'copy_failed'   => __( 'Failed to copy', 'wpcalibrate-merchant-feed-manager' ),
					'confirm_del'   => __( 'Are you sure you want to delete this feed?', 'wpcalibrate-merchant-feed-manager' ),
					'generating'    => __( 'Queuing feed generation...', 'wpcalibrate-merchant-feed-manager' ),
				],
			]
		);
	}

	/**
	 * Render the main plugin interface with navigation tabs.
	 */
	public static function render_main_page(): void {
		Security::check_auth_or_die();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_tab = sanitize_key( wp_unslash( $_GET['tab'] ?? 'dashboard' ) );

		$tabs = [
			'dashboard' => [ 'label' => __( 'Dashboard', 'wpcalibrate-merchant-feed-manager' ), 'icon' => 'dashicons-chart-pie' ],
			'overview'  => [ 'label' => __( 'Overview', 'wpcalibrate-merchant-feed-manager' ), 'icon' => 'dashicons-welcome-learn-more' ],
			'feeds'     => [ 'label' => __( 'Feeds', 'wpcalibrate-merchant-feed-manager' ), 'icon' => 'dashicons-list-view' ],
			'health'    => [ 'label' => __( 'Feed Health', 'wpcalibrate-merchant-feed-manager' ), 'icon' => 'dashicons-heart' ],
			'products'  => [ 'label' => __( 'Product Data', 'wpcalibrate-merchant-feed-manager' ), 'icon' => 'dashicons-products' ],
			'settings'  => [ 'label' => __( 'Settings', 'wpcalibrate-merchant-feed-manager' ), 'icon' => 'dashicons-admin-settings' ],
			'license'   => [ 'label' => __( 'License', 'wpcalibrate-merchant-feed-manager' ), 'icon' => 'dashicons-admin-network' ],
			'support'   => [ 'label' => __( 'Support', 'wpcalibrate-merchant-feed-manager' ), 'icon' => 'dashicons-sos' ],
		];

		// If editing a feed, add active Edit tab
		if ( 'feed-edit' === $current_tab ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$feed_id = isset( $_GET['id'] ) ? (int) wp_unslash( $_GET['id'] ) : 0;
			$tabs['feed-edit'] = [
				'label' => $feed_id > 0 ? __( 'Edit Feed', 'wpcalibrate-merchant-feed-manager' ) : __( 'Add New Feed', 'wpcalibrate-merchant-feed-manager' ),
				'icon'  => 'dashicons-edit',
			];
		}

		$brand_icon_url = ( defined( 'WPCMFM_PLUGIN_URL' ) ? WPCMFM_PLUGIN_URL : plugin_dir_url( dirname( __DIR__ ) . '/wpcalibrate-merchant-feed-manager.php' ) ) . 'branding/icon-dark.png';

		?>
		<div class="wrap wpcmfm-wrap">
			<!-- Header Banner -->
			<div class="wpcmfm-header-banner wpcmfm-d-flex wpcmfm-justify-between wpcmfm-align-center">
				<div class="wpcmfm-brand-info wpcmfm-d-flex wpcmfm-align-center">
					<img src="<?php echo esc_url( $brand_icon_url ); ?>" alt="<?php esc_attr_e( 'WPCalibrate', 'wpcalibrate-merchant-feed-manager' ); ?>" class="wpcmfm-brand-logo" />
					<div>
						<h1 class="wpcmfm-title"><?php esc_html_e( 'WPCalibrate Merchant Feed Manager', 'wpcalibrate-merchant-feed-manager' ); ?></h1>
						<p class="wpcmfm-subtitle"><?php esc_html_e( 'Reliable shopping feeds for Google, Meta, TikTok, Microsoft, Pinterest, and custom XML/CSV', 'wpcalibrate-merchant-feed-manager' ); ?></p>
					</div>
				</div>
				<div class="wpcmfm-version-tag">
					v1.0.0
				</div>
			</div>

			<!-- Navigation Bar -->
			<nav class="nav-tab-wrapper wpcmfm-nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Feed Manager Screens', 'wpcalibrate-merchant-feed-manager' ); ?>">
				<?php foreach ( $tabs as $tab_key => $tab_meta ) :
					$tab_url   = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=' . $tab_key );
					$is_active = ( $current_tab === $tab_key );
					?>
					<a href="<?php echo esc_url( $tab_url ); ?>" class="nav-tab <?php echo $is_active ? 'nav-tab-active' : ''; ?>">
						<span class="dashicons <?php echo esc_attr( $tab_meta['icon'] ); ?>"></span>
						<?php echo esc_html( $tab_meta['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<!-- Flash Notices -->
			<div class="wpcmfm-notices-container wpcmfm-my-3">
				<?php AdminNotice::render(); ?>
			</div>

			<!-- Tab Content Screen -->
			<div class="wpcmfm-tab-content">
				<?php
				switch ( $current_tab ) {
					case 'overview':
						OverviewScreen::render();
						break;
					case 'feeds':
						FeedListScreen::render();
						break;
					case 'feed-edit':
						FeedEditScreen::render();
						break;
					case 'health':
						FeedHealthScreen::render();
						break;
					case 'products':
						ProductDataScreen::render();
						break;
					case 'settings':
						SettingsScreen::render();
						break;
					case 'license':
						LicenseScreen::render();
						break;
					case 'support':
						SupportScreen::render();
						break;
					case 'dashboard':
					default:
						DashboardScreen::render();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}
}
