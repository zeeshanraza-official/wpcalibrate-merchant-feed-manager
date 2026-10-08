<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Admin;

use WPCalibrate\MerchantFeedManager\Category\GoogleTaxonomy;
use WPCalibrate\MerchantFeedManager\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds merchant feed meta fields into WooCommerce product editing panels.
 */
class ProductMetabox {

	public const NONCE_ACTION = 'wpcmfm_product_meta_save';
	public const NONCE_NAME   = '_wpcmfm_product_meta_nonce';

	/**
	 * Register WooCommerce product data tab hooks.
	 */
	public static function register(): void {
		add_filter( 'woocommerce_product_data_tabs', [ self::class, 'add_product_tab' ] );
		add_action( 'woocommerce_product_data_panels', [ self::class, 'render_product_panel' ] );
		add_action( 'woocommerce_process_product_meta', [ self::class, 'save_product_meta' ] );

		// Variations support
		add_action( 'woocommerce_product_after_variable_attributes', [ self::class, 'render_variation_fields' ], 10, 3 );
		add_action( 'woocommerce_save_product_variation', [ self::class, 'save_variation_fields' ], 10, 2 );
	}

	/**
	 * Add "Merchant Feeds" tab to WooCommerce product data tabs.
	 *
	 * @param array<string, mixed> $tabs Product tabs.
	 * @return array<string, mixed>
	 */
	public static function add_product_tab( array $tabs ): array {
		$tabs['wpcmfm_feed_data'] = [
			'label'    => __( 'Merchant Feeds', 'wpcalibrate-merchant-feed-manager' ),
			'target'   => 'wpcmfm_feed_data_panel',
			'class'    => [ 'show_if_simple', 'show_if_variable', 'show_if_external' ],
			'priority' => 65,
		];
		return $tabs;
	}

	/**
	 * Render fields in product data panel.
	 */
	public static function render_product_panel(): void {
		global $post;
		if ( ! $post ) {
			return;
		}

		$product_id = $post->ID;
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		echo '<div id="wpcmfm_feed_data_panel" class="panel woocommerce_options_panel hidden">';
		echo '<div class="options_group">';

		woocommerce_wp_text_input(
			[
				'id'          => '_wpcmfm_brand',
				'label'       => __( 'Brand Override', 'wpcalibrate-merchant-feed-manager' ),
				'placeholder' => __( 'Leave empty to use store name or attribute', 'wpcalibrate-merchant-feed-manager' ),
				'desc_tip'    => true,
				'description' => __( 'Overrides brand name specifically for merchant feeds.', 'wpcalibrate-merchant-feed-manager' ),
				'value'       => get_post_meta( $product_id, '_wpcmfm_brand', true ),
			]
		);

		woocommerce_wp_text_input(
			[
				'id'          => '_wpcmfm_gtin',
				'label'       => __( 'GTIN / Barcode (UPC, EAN, ISBN)', 'wpcalibrate-merchant-feed-manager' ),
				'placeholder' => __( 'e.g. 012345678905', 'wpcalibrate-merchant-feed-manager' ),
				'desc_tip'    => true,
				'description' => __( 'Valid 8, 12, 13, or 14-digit Global Trade Item Number.', 'wpcalibrate-merchant-feed-manager' ),
				'value'       => get_post_meta( $product_id, '_wpcmfm_gtin', true ),
			]
		);

		woocommerce_wp_text_input(
			[
				'id'          => '_wpcmfm_mpn',
				'label'       => __( 'MPN (Manufacturer Part Number)', 'wpcalibrate-merchant-feed-manager' ),
				'placeholder' => __( 'Leave empty to use SKU', 'wpcalibrate-merchant-feed-manager' ),
				'desc_tip'    => true,
				'description' => __( 'Overrides manufacturer part number.', 'wpcalibrate-merchant-feed-manager' ),
				'value'       => get_post_meta( $product_id, '_wpcmfm_mpn', true ),
			]
		);

		woocommerce_wp_select(
			[
				'id'          => '_wpcmfm_condition',
				'label'       => __( 'Condition', 'wpcalibrate-merchant-feed-manager' ),
				'options'     => [
					'new'         => __( 'New', 'wpcalibrate-merchant-feed-manager' ),
					'refurbished' => __( 'Refurbished', 'wpcalibrate-merchant-feed-manager' ),
					'used'        => __( 'Used', 'wpcalibrate-merchant-feed-manager' ),
				],
				'value'       => get_post_meta( $product_id, '_wpcmfm_condition', true ) ?: 'new',
			]
		);

		woocommerce_wp_text_input(
			[
				'id'          => '_wpcmfm_google_category',
				'label'       => __( 'Google Category Override', 'wpcalibrate-merchant-feed-manager' ),
				'placeholder' => __( 'e.g. Apparel & Accessories > Clothing', 'wpcalibrate-merchant-feed-manager' ),
				'desc_tip'    => true,
				'description' => __( 'Overrides category mapping specifically for this product.', 'wpcalibrate-merchant-feed-manager' ),
				'value'       => get_post_meta( $product_id, '_wpcmfm_google_category', true ),
			]
		);

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Save product meta fields.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function save_product_meta( int $product_id ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $product_id ) ) {
			return;
		}

		$fields = [
			'_wpcmfm_brand'           => 'sanitize_text_field',
			'_wpcmfm_gtin'            => 'sanitize_text_field',
			'_wpcmfm_mpn'             => 'sanitize_text_field',
			'_wpcmfm_condition'       => 'sanitize_text_field',
			'_wpcmfm_google_category' => 'sanitize_text_field',
		];

		foreach ( $fields as $field_key => $sanitize_fn ) {
			if ( isset( $_POST[ $field_key ] ) ) {
				$val = call_user_func( $sanitize_fn, wp_unslash( $_POST[ $field_key ] ) );
				update_post_meta( $product_id, $field_key, $val );
			}
		}
	}

	/**
	 * Render fields for individual variations.
	 *
	 * @param int      $loop Loop index.
	 * @param array    $variation_data Variation data.
	 * @param \WP_Post $variation Variation post.
	 */
	public static function render_variation_fields( int $loop, array $variation_data, \WP_Post $variation ): void {
		$var_id = $variation->ID;
		?>
		<div class="form-row form-row-full wpcmfm-variation-fields">
			<p><strong><?php esc_html_e( 'Merchant Feed Overrides', 'wpcalibrate-merchant-feed-manager' ); ?></strong></p>
			<p class="form-row form-row-first">
				<label><?php esc_html_e( 'Variation GTIN / Barcode', 'wpcalibrate-merchant-feed-manager' ); ?></label>
				<input type="text" name="wpcmfm_var_gtin[<?php echo esc_attr( (string) $var_id ); ?>]" value="<?php echo esc_attr( (string) get_post_meta( $var_id, '_wpcmfm_gtin', true ) ); ?>" />
			</p>
			<p class="form-row form-row-last">
				<label><?php esc_html_e( 'Variation MPN', 'wpcalibrate-merchant-feed-manager' ); ?></label>
				<input type="text" name="wpcmfm_var_mpn[<?php echo esc_attr( (string) $var_id ); ?>]" value="<?php echo esc_attr( (string) get_post_meta( $var_id, '_wpcmfm_mpn', true ) ); ?>" />
			</p>
		</div>
		<?php
	}

	/**
	 * Save variation fields.
	 *
	 * @param int $variation_id Variation ID.
	 * @param int $i Loop index.
	 */
	public static function save_variation_fields( int $variation_id, int $i ): void {
		if ( ! current_user_can( 'edit_post', $variation_id ) ) {
			return;
		}

		if ( isset( $_POST['wpcmfm_var_gtin'][ $variation_id ] ) ) {
			$gtin = sanitize_text_field( wp_unslash( $_POST['wpcmfm_var_gtin'][ $variation_id ] ) );
			update_post_meta( $variation_id, '_wpcmfm_gtin', $gtin );
		}

		if ( isset( $_POST['wpcmfm_var_mpn'][ $variation_id ] ) ) {
			$mpn = sanitize_text_field( wp_unslash( $_POST['wpcmfm_var_mpn'][ $variation_id ] ) );
			update_post_meta( $variation_id, '_wpcmfm_mpn', $mpn );
		}
	}
}
