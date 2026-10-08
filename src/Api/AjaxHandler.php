<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Api;

use WPCalibrate\MerchantFeedManager\Category\GoogleTaxonomy;
use WPCalibrate\MerchantFeedManager\Feed\FeedRunRepository;
use WPCalibrate\MerchantFeedManager\Generator\FeedGenerator;
use WPCalibrate\MerchantFeedManager\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles authenticated WordPress AJAX requests for admin UI interactions.
 */
class AjaxHandler {

	/**
	 * Register AJAX hooks.
	 */
	public static function register(): void {
		add_action( 'wp_ajax_wpcmfm_search_categories', [ self::class, 'search_categories' ] );
		add_action( 'wp_ajax_wpcmfm_start_generation', [ self::class, 'start_generation' ] );
		add_action( 'wp_ajax_wpcmfm_check_run_status', [ self::class, 'check_run_status' ] );
	}

	/**
	 * AJAX endpoint to search Google Product Categories.
	 */
	public static function search_categories(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! Security::current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized access.', 'wpcalibrate-merchant-feed-manager' ) ], 403 );
		}

		$query   = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
		$results = GoogleTaxonomy::search( $query, 25 );

		wp_send_json_success( [ 'results' => $results ] );
	}

	/**
	 * AJAX endpoint to trigger feed generation.
	 */
	public static function start_generation(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! Security::current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized access.', 'wpcalibrate-merchant-feed-manager' ) ], 403 );
		}

		$feed_id = isset( $_POST['feed_id'] ) ? (int) wp_unslash( $_POST['feed_id'] ) : 0;
		if ( $feed_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid feed ID.', 'wpcalibrate-merchant-feed-manager' ) ], 400 );
		}

		$res = FeedGenerator::start_generation( $feed_id, true );
		if ( $res['success'] ) {
			wp_send_json_success( $res );
		} else {
			wp_send_json_error( $res, 400 );
		}
	}

	/**
	 * AJAX endpoint to check status of a generation run.
	 */
	public static function check_run_status(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! Security::current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized access.', 'wpcalibrate-merchant-feed-manager' ) ], 403 );
		}

		$run_id = isset( $_POST['run_id'] ) ? (int) wp_unslash( $_POST['run_id'] ) : 0;
		$run    = $run_id > 0 ? FeedRunRepository::get_by_id( $run_id ) : null;

		if ( ! $run ) {
			wp_send_json_error( [ 'message' => __( 'Run not found.', 'wpcalibrate-merchant-feed-manager' ) ], 404 );
		}

		wp_send_json_success( [ 'run' => $run->to_array() ] );
	}
}
