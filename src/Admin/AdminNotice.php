<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages administrative flash notices.
 */
class AdminNotice {

	public const TRANSIENT_PREFIX = 'wpcmfm_notices_';

	/**
	 * Add a flash notice for current user.
	 *
	 * @param string $message Notice message.
	 * @param string $type Notice type ('success', 'error', 'warning', 'info').
	 */
	public static function add( string $message, string $type = 'success' ): void {
		$user_id = get_current_user_id();
		if ( 0 === $user_id ) {
			return;
		}

		$key     = self::TRANSIENT_PREFIX . $user_id;
		$notices = get_transient( $key );
		if ( ! is_array( $notices ) ) {
			$notices = [];
		}

		$notices[] = [
			'message' => $message,
			'type'    => in_array( $type, [ 'success', 'error', 'warning', 'info' ], true ) ? $type : 'info',
		];

		set_transient( $key, $notices, 60 );
	}

	/**
	 * Render and clear all pending notices for current user.
	 */
	public static function render(): void {
		$user_id = get_current_user_id();
		if ( 0 === $user_id ) {
			return;
		}

		$key     = self::TRANSIENT_PREFIX . $user_id;
		$notices = get_transient( $key );

		if ( ! is_array( $notices ) || empty( $notices ) ) {
			return;
		}

		delete_transient( $key );

		foreach ( $notices as $notice ) {
			$type = esc_attr( (string) $notice['type'] );
			$msg  = esc_html( (string) $notice['message'] );
			echo "<div class=\"notice notice-{$type} is-dismissible\"><p>{$msg}</p></div>";
		}
	}
}
