<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Admin;

use WPCalibrate\MerchantFeedManager\Category\GoogleTaxonomy;
use WPCalibrate\MerchantFeedManager\Channels\ChannelRegistry;
use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Feed\FeedRepository;
use WPCalibrate\MerchantFeedManager\Mapping\SourceRegistry;
use WPCalibrate\MerchantFeedManager\Scheduler\ActionSchedulerService;
use WPCalibrate\MerchantFeedManager\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Screen rendering the Add / Edit Feed interface and handling feed save requests.
 */
class FeedEditScreen {

	public const NONCE_ACTION = 'wpcmfm_save_feed_action';
	public const NONCE_NAME   = '_wpcmfm_feed_nonce';

	/**
	 * Process form submissions.
	 */
	public static function process_save(): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		if ( ! Security::verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed. Please refresh and try again.', 'wpcalibrate-merchant-feed-manager' ) );
		}

		Security::check_auth_or_die();

		$feed_id = isset( $_POST['feed_id'] ) ? (int) wp_unslash( $_POST['feed_id'] ) : 0;
		$feed    = $feed_id > 0 ? FeedRepository::get_by_id( $feed_id ) : new FeedModel();

		if ( ! $feed ) {
			$feed = new FeedModel();
		}

		// Sanitize primary attributes
		$feed->name            = sanitize_text_field( wp_unslash( $_POST['feed_name'] ?? 'Merchant Feed' ) );
		$feed->channel         = sanitize_key( wp_unslash( $_POST['channel'] ?? 'google' ) );
		$feed->format          = sanitize_key( wp_unslash( $_POST['format'] ?? 'xml' ) );
		$feed->status          = sanitize_key( wp_unslash( $_POST['status'] ?? 'active' ) );
		$feed->target_country  = sanitize_text_field( wp_unslash( $_POST['target_country'] ?? 'US' ) );
		$feed->target_language = sanitize_text_field( wp_unslash( $_POST['target_language'] ?? 'en' ) );
		$feed->target_currency = sanitize_text_field( wp_unslash( $_POST['target_currency'] ?? 'USD' ) );
		$feed->schedule        = sanitize_key( wp_unslash( $_POST['schedule'] ?? 'daily' ) );
		$feed->batch_size      = max( 10, min( 1000, (int) wp_unslash( $_POST['batch_size'] ?? 100 ) ) );

		// Process Rules
		$raw_rules = isset( $_POST['rules'] ) && is_array( $_POST['rules'] ) ? wp_unslash( $_POST['rules'] ) : [];
		$clean_rules = [];
		foreach ( $raw_rules as $rule ) {
			if ( ! is_array( $rule ) || empty( $rule['field'] ) ) {
				continue;
			}
			$clean_rules[] = [
				'type'     => ( $rule['type'] ?? 'include' ) === 'exclude' ? 'exclude' : 'include',
				'field'    => sanitize_key( (string) $rule['field'] ),
				'operator' => sanitize_key( (string) ( $rule['operator'] ?? 'equals' ) ),
				'value'    => sanitize_text_field( (string) ( $rule['value'] ?? '' ) ),
			];
		}
		$feed->rules = $clean_rules;

		// Process Field Mappings
		$raw_mappings = isset( $_POST['mappings'] ) && is_array( $_POST['mappings'] ) ? wp_unslash( $_POST['mappings'] ) : [];
		$clean_mappings = [];
		foreach ( $raw_mappings as $field_key => $mapping ) {
			$clean_field = sanitize_key( (string) $field_key );
			if ( is_array( $mapping ) ) {
				$clean_mappings[ $clean_field ] = [
					'source'        => sanitize_text_field( (string) ( $mapping['source'] ?? '' ) ),
					'meta_key'      => sanitize_key( (string) ( $mapping['meta_key'] ?? '' ) ),
					'static_value'  => sanitize_text_field( (string) ( $mapping['static_value'] ?? '' ) ),
					'custom_tag'    => Security::sanitize_xml_tag( (string) ( $mapping['custom_tag'] ?? '' ) ),
					'custom_header' => sanitize_text_field( (string) ( $mapping['custom_header'] ?? '' ) ),
				];
			}
		}
		$feed->mappings = $clean_mappings;

		// Process Overrides
		$raw_overrides = isset( $_POST['overrides'] ) && is_array( $_POST['overrides'] ) ? wp_unslash( $_POST['overrides'] ) : [];
		$clean_overrides = [];
		foreach ( $raw_overrides as $k => $v ) {
			$clean_overrides[ sanitize_key( (string) $k ) ] = sanitize_text_field( (string) $v );
		}
		$feed->overrides = $clean_overrides;

		// Save Feed
		if ( $feed->id > 0 ) {
			$saved = FeedRepository::update( $feed );
			$id    = $feed->id;
		} else {
			$id    = FeedRepository::insert( $feed );
			$saved = $id > 0;
		}

		if ( $saved ) {
			ActionSchedulerService::sync_feed_schedule( $feed );
			AdminNotice::add( __( 'Feed configuration saved successfully.', 'wpcalibrate-merchant-feed-manager' ), 'success' );
			wp_safe_redirect( admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=feed-edit&id=' . $id ) );
			exit;
		}

		AdminNotice::add( __( 'Failed to save feed configuration.', 'wpcalibrate-merchant-feed-manager' ), 'error' );
	}

	/**
	 * Render the Add/Edit form HTML.
	 */
	public static function render(): void {
		$feed_id = isset( $_GET['id'] ) ? (int) wp_unslash( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$feed    = $feed_id > 0 ? FeedRepository::get_by_id( $feed_id ) : null;
		$is_edit = ( null !== $feed );

		if ( ! $feed ) {
			$feed                 = new FeedModel();
			$feed->target_currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD';
		}

		$channels = ChannelRegistry::get_all();
		$sources  = SourceRegistry::get_grouped_sources();

		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=feed-edit' . ( $is_edit ? '&id=' . $feed->id : '' ) ) ); ?>">
			<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
			<input type="hidden" name="feed_id" value="<?php echo esc_attr( (string) $feed->id ); ?>" />

			<div class="wpcmfm-edit-header wpcmfm-d-flex wpcmfm-justify-between wpcmfm-align-center wpcmfm-mb-3">
				<h2>
					<?php echo $is_edit ? esc_html( sprintf( __( 'Edit Feed: %s', 'wpcalibrate-merchant-feed-manager' ), $feed->name ) ) : esc_html__( 'Add New Feed', 'wpcalibrate-merchant-feed-manager' ); ?>
				</h2>
				<div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-merchant-feed-manager&tab=feeds' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Back to Feeds', 'wpcalibrate-merchant-feed-manager' ); ?>
					</a>
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Save Feed', 'wpcalibrate-merchant-feed-manager' ); ?>
					</button>
				</div>
			</div>

			<div class="wpcmfm-tabs-container">
				<div class="wpcmfm-tabs-nav">
					<button type="button" class="wpcmfm-tab-btn active" data-target="wpcmfm-tab-general"><?php esc_html_e( 'General Settings', 'wpcalibrate-merchant-feed-manager' ); ?></button>
					<button type="button" class="wpcmfm-tab-btn" data-target="wpcmfm-tab-rules"><?php esc_html_e( 'Product Selection Rules', 'wpcalibrate-merchant-feed-manager' ); ?></button>
					<button type="button" class="wpcmfm-tab-btn" data-target="wpcmfm-tab-mappings"><?php esc_html_e( 'Field Mappings', 'wpcalibrate-merchant-feed-manager' ); ?></button>
					<button type="button" class="wpcmfm-tab-btn" data-target="wpcmfm-tab-overrides"><?php esc_html_e( 'Feed Overrides', 'wpcalibrate-merchant-feed-manager' ); ?></button>
				</div>

				<!-- General Settings Tab -->
				<div id="wpcmfm-tab-general" class="wpcmfm-tab-pane active wpcmfm-card wpcmfm-p-4">
					<table class="form-table">
						<tr>
							<th scope="row"><label for="feed_name"><?php esc_html_e( 'Feed Name', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<input type="text" id="feed_name" name="feed_name" class="regular-text" value="<?php echo esc_attr( $feed->name ); ?>" required />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="channel"><?php esc_html_e( 'Channel', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<select id="channel" name="channel">
									<?php foreach ( $channels as $ch_id => $ch_obj ) : ?>
										<option value="<?php echo esc_attr( $ch_id ); ?>" <?php selected( $feed->channel, $ch_id ); ?>>
											<?php echo esc_html( $ch_obj->get_name() ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="format"><?php esc_html_e( 'Output Format', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<select id="format" name="format">
									<option value="xml" <?php selected( $feed->format, 'xml' ); ?>><?php esc_html_e( 'XML', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="csv" <?php selected( $feed->format, 'csv' ); ?>><?php esc_html_e( 'CSV', 'wpcalibrate-merchant-feed-manager' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="status"><?php esc_html_e( 'Status', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<select id="status" name="status">
									<option value="active" <?php selected( $feed->status, 'active' ); ?>><?php esc_html_e( 'Active', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="inactive" <?php selected( $feed->status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'wpcalibrate-merchant-feed-manager' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="schedule"><?php esc_html_e( 'Generation Schedule', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<select id="schedule" name="schedule">
									<option value="manual" <?php selected( $feed->schedule, 'manual' ); ?>><?php esc_html_e( 'Manual Only', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="hourly" <?php selected( $feed->schedule, 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="twicedaily" <?php selected( $feed->schedule, 'twicedaily' ); ?>><?php esc_html_e( 'Twice Daily (Every 12h)', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="daily" <?php selected( $feed->schedule, 'daily' ); ?>><?php esc_html_e( 'Daily', 'wpcalibrate-merchant-feed-manager' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Managed reliably via WooCommerce Action Scheduler.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="target_currency"><?php esc_html_e( 'Target Currency', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<input type="text" id="target_currency" name="target_currency" class="small-text" value="<?php echo esc_attr( $feed->target_currency ); ?>" />
								<p class="description"><?php esc_html_e( 'Ensure this matches your store currency. Live currency conversion is planned for future releases.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="batch_size"><?php esc_html_e( 'Batch Size', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<input type="number" id="batch_size" name="batch_size" min="10" max="1000" class="small-text" value="<?php echo esc_attr( (string) $feed->batch_size ); ?>" />
								<p class="description"><?php esc_html_e( 'Number of products loaded into memory per generation chunk (default 100).', 'wpcalibrate-merchant-feed-manager' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<!-- Selection Rules Tab -->
				<div id="wpcmfm-tab-rules" class="wpcmfm-tab-pane wpcmfm-card wpcmfm-p-4">
					<p><?php esc_html_e( 'Define criteria to include or exclude specific products from this feed.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
					<div id="wpcmfm-rules-wrapper">
						<?php
						$rules = ! empty( $feed->rules ) ? $feed->rules : [ [ 'type' => 'include', 'field' => 'stock_status', 'operator' => 'equals', 'value' => 'instock' ] ];
						foreach ( $rules as $idx => $rule ) :
							?>
							<div class="wpcmfm-rule-row wpcmfm-d-flex wpcmfm-align-center wpcmfm-mb-2">
								<select name="rules[<?php echo esc_attr( (string) $idx ); ?>][type]">
									<option value="include" <?php selected( $rule['type'] ?? '', 'include' ); ?>><?php esc_html_e( 'Include', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="exclude" <?php selected( $rule['type'] ?? '', 'exclude' ); ?>><?php esc_html_e( 'Exclude', 'wpcalibrate-merchant-feed-manager' ); ?></option>
								</select>
								<select name="rules[<?php echo esc_attr( (string) $idx ); ?>][field]">
									<option value="stock_status" <?php selected( $rule['field'] ?? '', 'stock_status' ); ?>><?php esc_html_e( 'Stock Status', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="category" <?php selected( $rule['field'] ?? '', 'category' ); ?>><?php esc_html_e( 'Category ID', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="product_type" <?php selected( $rule['field'] ?? '', 'product_type' ); ?>><?php esc_html_e( 'Product Type', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="price" <?php selected( $rule['field'] ?? '', 'price' ); ?>><?php esc_html_e( 'Price', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="sku" <?php selected( $rule['field'] ?? '', 'sku' ); ?>><?php esc_html_e( 'SKU', 'wpcalibrate-merchant-feed-manager' ); ?></option>
								</select>
								<select name="rules[<?php echo esc_attr( (string) $idx ); ?>][operator]">
									<option value="equals" <?php selected( $rule['operator'] ?? '', 'equals' ); ?>><?php esc_html_e( 'Equals', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="not_equals" <?php selected( $rule['operator'] ?? '', 'not_equals' ); ?>><?php esc_html_e( 'Not Equals', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="contains" <?php selected( $rule['operator'] ?? '', 'contains' ); ?>><?php esc_html_e( 'Contains', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="greater_than" <?php selected( $rule['operator'] ?? '', 'greater_than' ); ?>><?php esc_html_e( 'Greater Than', 'wpcalibrate-merchant-feed-manager' ); ?></option>
									<option value="less_than" <?php selected( $rule['operator'] ?? '', 'less_than' ); ?>><?php esc_html_e( 'Less Than', 'wpcalibrate-merchant-feed-manager' ); ?></option>
								</select>
								<input type="text" name="rules[<?php echo esc_attr( (string) $idx ); ?>][value]" value="<?php echo esc_attr( (string) ( $rule['value'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Value', 'wpcalibrate-merchant-feed-manager' ); ?>" />
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Field Mappings Tab -->
				<div id="wpcmfm-tab-mappings" class="wpcmfm-tab-pane wpcmfm-card wpcmfm-p-4">
					<p><?php esc_html_e( 'Map merchant feed attributes to WooCommerce product sources or static values.', 'wpcalibrate-merchant-feed-manager' ); ?></p>
					<table class="wp-list-table widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Merchant Field', 'wpcalibrate-merchant-feed-manager' ); ?></th>
								<th><?php esc_html_e( 'Data Source', 'wpcalibrate-merchant-feed-manager' ); ?></th>
								<th><?php esc_html_e( 'Custom Meta / Value', 'wpcalibrate-merchant-feed-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$merchant_fields = [
								'id'          => __( 'ID', 'wpcalibrate-merchant-feed-manager' ),
								'title'       => __( 'Title', 'wpcalibrate-merchant-feed-manager' ),
								'description' => __( 'Description', 'wpcalibrate-merchant-feed-manager' ),
								'link'        => __( 'Link (URL)', 'wpcalibrate-merchant-feed-manager' ),
								'image_link'  => __( 'Image Link', 'wpcalibrate-merchant-feed-manager' ),
								'price'       => __( 'Price', 'wpcalibrate-merchant-feed-manager' ),
								'brand'       => __( 'Brand', 'wpcalibrate-merchant-feed-manager' ),
								'gtin'        => __( 'GTIN / Barcode', 'wpcalibrate-merchant-feed-manager' ),
								'mpn'         => __( 'MPN', 'wpcalibrate-merchant-feed-manager' ),
								'condition'   => __( 'Condition', 'wpcalibrate-merchant-feed-manager' ),
							];

							foreach ( $merchant_fields as $f_key => $f_label ) :
								$map_conf = $feed->mappings[ $f_key ] ?? [];
								$src_val  = is_array( $map_conf ) ? ( $map_conf['source'] ?? $f_key ) : (string) $map_conf;
								$meta_val = is_array( $map_conf ) ? ( $map_conf['meta_key'] ?? '' ) : '';
								?>
								<tr>
									<td><strong><?php echo esc_html( $f_label ); ?></strong> (<code><?php echo esc_html( $f_key ); ?></code>)</td>
									<td>
										<select name="mappings[<?php echo esc_attr( $f_key ); ?>][source]">
											<?php foreach ( $sources as $grp_key => $group ) : ?>
												<optgroup label="<?php echo esc_attr( $group['label'] ); ?>">
													<?php foreach ( $group['sources'] as $src_id => $src_name ) : ?>
														<option value="<?php echo esc_attr( $src_id ); ?>" <?php selected( $src_val, $src_id ); ?>>
															<?php echo esc_html( $src_name ); ?>
														</option>
													<?php endforeach; ?>
												</optgroup>
											<?php endforeach; ?>
										</select>
									</td>
									<td>
										<input type="text" name="mappings[<?php echo esc_attr( $f_key ); ?>][meta_key]" value="<?php echo esc_attr( $meta_val ); ?>" placeholder="<?php esc_attr_e( 'Meta key if Custom Meta selected', 'wpcalibrate-merchant-feed-manager' ); ?>" />
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<!-- Overrides Tab -->
				<div id="wpcmfm-tab-overrides" class="wpcmfm-tab-pane wpcmfm-card wpcmfm-p-4">
					<p class="description">
						<?php esc_html_e( 'Note: Feed-specific overrides apply only to output feeds and DO NOT modify underlying WooCommerce products.', 'wpcalibrate-merchant-feed-manager' ); ?>
					</p>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="override_brand"><?php esc_html_e( 'Feed-Level Brand Override', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<input type="text" id="override_brand" name="overrides[brand]" class="regular-text" value="<?php echo esc_attr( $feed->overrides['brand'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Leave empty to use store name/attribute', 'wpcalibrate-merchant-feed-manager' ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="override_google_cat"><?php esc_html_e( 'Default Google Product Category', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<input type="text" id="override_google_cat" name="overrides[default_google_category]" class="regular-text" value="<?php echo esc_attr( $feed->overrides['default_google_category'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Apparel & Accessories > Clothing', 'wpcalibrate-merchant-feed-manager' ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="override_cl0"><?php esc_html_e( 'Custom Label 0', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<input type="text" id="override_cl0" name="overrides[custom_label_0]" class="regular-text" value="<?php echo esc_attr( $feed->overrides['custom_label_0'] ?? '' ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="override_cl1"><?php esc_html_e( 'Custom Label 1', 'wpcalibrate-merchant-feed-manager' ); ?></label></th>
							<td>
								<input type="text" id="override_cl1" name="overrides[custom_label_1]" class="regular-text" value="<?php echo esc_attr( $feed->overrides['custom_label_1'] ?? '' ); ?>" />
							</td>
						</tr>
					</table>
				</div>
			</div>
		</form>
		<?php
	}
}
