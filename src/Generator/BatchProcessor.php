<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Generator;

use WPCalibrate\MerchantFeedManager\Channels\ChannelRegistry;
use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Feed\FeedRepository;
use WPCalibrate\MerchantFeedManager\Feed\FeedRunModel;
use WPCalibrate\MerchantFeedManager\Feed\FeedRunRepository;
use WPCalibrate\MerchantFeedManager\Feed\FeedStorage;
use WPCalibrate\MerchantFeedManager\Product\ProductNormalizer;
use WPCalibrate\MerchantFeedManager\Product\ProductQuery;
use WPCalibrate\MerchantFeedManager\Rules\RuleEngine;
use WPCalibrate\MerchantFeedManager\Scheduler\ActionSchedulerService;
use WPCalibrate\MerchantFeedManager\Support\Logger;
use WPCalibrate\MerchantFeedManager\Validation\IssueRepository;
use WPCalibrate\MerchantFeedManager\Validation\ValidationEngine;
use WPCalibrate\MerchantFeedManager\Validation\ValidationIssue;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles batch processing of feed products, writing streams and finalization.
 */
class BatchProcessor {

	/**
	 * Process a single batch chunk for a feed generation run.
	 *
	 * @param int    $feed_id Feed ID.
	 * @param string $run_token Unique run token.
	 * @return bool True if batch succeeded or generation completed.
	 */
	public static function process_batch( int $feed_id, string $run_token ): bool {
		$feed = FeedRepository::get_by_id( $feed_id );
		if ( ! $feed ) {
			Logger::error( 'Feed not found for batch processing', [ 'feed_id' => $feed_id ] );
			return false;
		}

		$run = FeedRunRepository::get_latest_for_feed( $feed_id );
		if ( ! $run || $run->run_token !== $run_token ) {
			Logger::error( 'Feed run token mismatch', [ 'feed_id' => $feed_id, 'token' => $run_token ] );
			return false;
		}

		$channel = ChannelRegistry::get( $feed->channel );
		if ( ! $channel ) {
			self::fail_run( $feed, $run, 'Invalid or unregistered channel adapter: ' . $feed->channel );
			return false;
		}

		$temp_file = FeedStorage::get_temp_file_path( $feed, $run_token );
		$is_csv    = 'csv' === strtolower( $feed->format );
		$batch_size = max( 10, $feed->batch_size );

		// 1. Initialize file on the first batch
		if ( 0 === $run->current_offset ) {
			if ( $is_csv ) {
				$writer = new CsvStreamWriter();
				if ( ! $writer->open( $temp_file, 'wb' ) ) {
					self::fail_run( $feed, $run, 'Failed to open CSV temp file for writing' );
					return false;
				}
				$headers = $channel->get_csv_headers( $feed );
				$writer->write_header( $headers );
				$writer->close();
			} else {
				$writer = new XmlStreamWriter();
				if ( ! $writer->open( $temp_file, 'wb' ) ) {
					self::fail_run( $feed, $run, 'Failed to open XML temp file for writing' );
					return false;
				}
				$header = $channel->get_xml_header( $feed );
				$open   = $channel->get_xml_open_tags( $feed );
				$writer->write_preamble( $header, $open );
				$writer->close();
			}
		}

		// 2. Fetch product IDs for this batch
		$product_ids = ProductQuery::get_product_ids_batch( $batch_size, $run->current_offset );

		// If no more products, finalize feed
		if ( empty( $product_ids ) ) {
			return self::finalize_feed( $feed, $run, $temp_file, $is_csv, $channel );
		}

		// 3. Process products in batch
		$issues_to_save = [];

		if ( $is_csv ) {
			$writer = new CsvStreamWriter();
			$writer->open( $temp_file, 'ab' );
		} else {
			$writer = new XmlStreamWriter();
			$writer->open( $temp_file, 'ab' );
		}

		foreach ( $product_ids as $pid ) {
			$run->processed_products++;

			$product = ProductQuery::get_product( $pid );
			if ( ! $product ) {
				// Product was deleted during generation
				$run->excluded_records++;
				$issues_to_save[] = new ValidationIssue(
					$pid,
					'product',
					ValidationIssue::SEVERITY_EXCLUDED,
					'product_deleted',
					__( 'Product was deleted or unavailable during feed generation.', 'wpcalibrate-merchant-feed-manager' )
				);
				continue;
			}

			// Rule evaluation
			if ( ! RuleEngine::is_product_eligible( $product, $feed->rules ) ) {
				$run->excluded_records++;
				$issues_to_save[] = new ValidationIssue(
					$pid,
					'product',
					ValidationIssue::SEVERITY_EXCLUDED,
					'excluded_by_rule',
					__( 'Product excluded by feed product-selection rules.', 'wpcalibrate-merchant-feed-manager' )
				);
				continue;
			}

			// Product normalization
			$normalized_items = ProductNormalizer::normalize( $product, $feed );

			foreach ( $normalized_items as $item ) {
				// Validate product
				$issues = ValidationEngine::validate_product( $item, $feed );
				$has_fatal_error = false;

				foreach ( $issues as $issue ) {
					$issue->feed_id = $feed->id;
					$issue->run_id  = $run->id;
					$issues_to_save[] = $issue;

					if ( ValidationIssue::SEVERITY_ERROR === $issue->severity ) {
						$run->error_count++;
						$has_fatal_error = true;
					} elseif ( ValidationIssue::SEVERITY_WARNING === $issue->severity ) {
						$run->warning_count++;
					}
				}

				// Skip emitting item if it has fatal errors (e.g. invalid price or missing required ID)
				if ( $has_fatal_error ) {
					$run->excluded_records++;
					continue;
				}

				// Stream formatted item
				if ( $is_csv ) {
					$row = $channel->format_csv_row( $item, $feed );
					$writer->write_row( $row );
				} else {
					$xml_item = $channel->format_xml_item( $item, $feed );
					$writer->write_item( $xml_item );
				}

				$run->included_records++;
			}
		}

		$writer->close();

		// Save collected issues
		if ( ! empty( $issues_to_save ) ) {
			foreach ( $issues_to_save as $issue ) {
				$issue->feed_id = $feed->id;
				$issue->run_id  = $run->id;
			}
			IssueRepository::insert_batch( $issues_to_save );
		}

		// Advance offset and update run record
		$run->current_offset += count( $product_ids );
		FeedRunRepository::update( $run );

		// Check if more products remain
		$total_candidates = $run->total_products ?: ProductQuery::get_total_product_count();
		if ( $run->current_offset < $total_candidates ) {
			// Enqueue next batch
			ActionSchedulerService::queue_generation( $feed->id, $run->run_token );
			return true;
		}

		// Otherwise finalize
		return self::finalize_feed( $feed, $run, $temp_file, $is_csv, $channel );
	}

	/**
	 * Finalize feed file generation and perform atomic replacement.
	 */
	private static function finalize_feed( FeedModel $feed, FeedRunModel $run, string $temp_file, bool $is_csv, $channel ): bool {
		// Close XML tags if XML
		if ( ! $is_csv ) {
			$writer = new XmlStreamWriter();
			if ( $writer->open( $temp_file, 'ab' ) ) {
				$close = $channel->get_xml_close_tags( $feed );
				$writer->write_epilogue( $close );
				$writer->close();
			}
		}

		$target_file = FeedStorage::get_feed_file_path( $feed );
		$success     = FeedStorage::finalize_temp_file( $temp_file, $target_file );

		if ( ! $success ) {
			self::fail_run( $feed, $run, 'Failed during atomic file finalization' );
			return false;
		}

		// Update run status
		$now               = current_time( 'mysql', true );
		$run->status       = ( $run->warning_count > 0 ) ? 'completed_with_warnings' : 'completed';
		$run->finished_at  = $now;
		FeedRunRepository::update( $run );

		// Update feed metadata
		$feed->last_run_at = $now;
		$feed->last_status = $run->status;
		$feed->item_count  = $run->included_records;
		$feed->file_path   = $target_file;
		$feed->file_size   = (int) @filesize( $target_file );
		FeedRepository::update( $feed );

		// Release lock
		GenerationLock::release( $feed->id, $run->run_token );

		/**
		 * Action fired when feed generation finishes successfully.
		 *
		 * @param FeedModel    $feed Feed entity.
		 * @param FeedRunModel $run Run entity.
		 */
		do_action( 'wpcmfm_feed_generation_completed', $feed, $run );

		Logger::info(
			'Feed generation finished successfully',
			[
				'feed_id'          => $feed->id,
				'included_records' => $feed->item_count,
				'file_size'        => $feed->file_size,
				'status'           => $run->status,
			]
		);

		return true;
	}

	/**
	 * Mark run as failed and release lock.
	 */
	private static function fail_run( FeedModel $feed, FeedRunModel $run, string $message ): void {
		$run->status      = 'failed';
		$run->message     = $message;
		$run->finished_at = current_time( 'mysql', true );
		FeedRunRepository::update( $run );

		$feed->last_status = 'failed';
		FeedRepository::update( $feed );

		GenerationLock::release( $feed->id, $run->run_token );

		Logger::error(
			'Feed generation failed',
			[
				'feed_id' => $feed->id,
				'message' => $message,
			]
		);
	}
}
