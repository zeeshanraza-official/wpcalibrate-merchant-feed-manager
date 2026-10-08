<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Api;

use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Feed\FeedRepository;
use WPCalibrate\MerchantFeedManager\Feed\FeedStorage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public delivery handler for stable merchant feed URLs.
 */
class FeedDelivery {

	public const QUERY_VAR = 'wpcmfm_feed';

	/**
	 * Register delivery hooks.
	 */
	public static function register(): void {
		add_filter( 'query_vars', [ self::class, 'register_query_var' ] );
		add_action( 'template_redirect', [ self::class, 'handle_feed_request' ], 0 );
	}

	/**
	 * Register custom query variable with WordPress.
	 *
	 * @param array<string> $vars Query variables.
	 * @return array<string>
	 */
	public static function register_query_var( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Get the public stable delivery URL for a feed.
	 *
	 * @param FeedModel $feed Feed model.
	 * @return string Stable public URL.
	 */
	public static function get_feed_url( FeedModel $feed ): string {
		return add_query_arg(
			[ self::QUERY_VAR => $feed->token ],
			home_url( '/' )
		);
	}

	/**
	 * Handle incoming public request for feed delivery.
	 */
	public static function handle_feed_request(): void {
		$token = get_query_var( self::QUERY_VAR );
		if ( empty( $token ) && isset( $_GET[ self::QUERY_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$token = sanitize_text_field( wp_unslash( $_GET[ self::QUERY_VAR ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( empty( $token ) ) {
			return;
		}

		// Clean token parameter
		$token = preg_replace( '/[^a-zA-Z0-9]/', '', (string) $token );
		if ( empty( $token ) ) {
			status_header( 404 );
			nocache_headers();
			exit( 'Feed not found.' );
		}

		$feed = FeedRepository::get_by_token( $token );
		if ( ! $feed || ! $feed->is_active() ) {
			status_header( 404 );
			nocache_headers();
			exit( 'Feed not found or inactive.' );
		}

		$file_path = FeedStorage::get_feed_file_path( $feed );
		if ( ! file_exists( $file_path ) || 0 === filesize( $file_path ) ) {
			status_header( 404 );
			nocache_headers();
			exit( 'Feed file has not been generated yet.' );
		}

		// Content type headers
		$is_csv = 'csv' === strtolower( $feed->format );
		if ( $is_csv ) {
			header( 'Content-Type: text/csv; charset=UTF-8' );
		} else {
			header( 'Content-Type: application/xml; charset=UTF-8' );
		}

		// Cache & delivery headers
		$mtime = filemtime( $file_path );
		$size  = filesize( $file_path );

		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', (int) $mtime ) . ' GMT' );
		header( 'Content-Length: ' . (string) $size );
		header( 'Cache-Control: public, max-age=3600, must-revalidate' );
		header( 'X-Robots-Tag: noindex, nofollow', true );

		// Stream feed file directly to client
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
		readfile( $file_path );
		exit;
	}
}
