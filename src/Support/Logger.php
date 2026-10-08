<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Standardized logging abstraction for WPCalibrate Merchant Feed Manager.
 * Uses WooCommerce Logger (WC_Logger) when available, with fallback to error_log.
 */
class Logger {

	public const SOURCE = 'wpcalibrate-merchant-feed-manager';

	/**
	 * Log an emergency message.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function emergency( string $message, array $context = [] ): void {
		self::log( 'emergency', $message, $context );
	}

	/**
	 * Log an alert message.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function alert( string $message, array $context = [] ): void {
		self::log( 'alert', $message, $context );
	}

	/**
	 * Log a critical message.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function critical( string $message, array $context = [] ): void {
		self::log( 'critical', $message, $context );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function error( string $message, array $context = [] ): void {
		self::log( 'error', $message, $context );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function warning( string $message, array $context = [] ): void {
		self::log( 'warning', $message, $context );
	}

	/**
	 * Log a notice message.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function notice( string $message, array $context = [] ): void {
		self::log( 'notice', $message, $context );
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function info( string $message, array $context = [] ): void {
		self::log( 'info', $message, $context );
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function debug( string $message, array $context = [] ): void {
		self::log( 'debug', $message, $context );
	}

	/**
	 * Write a log message with sanitized context.
	 *
	 * @param string $level Severity level.
	 * @param string $message Text message.
	 * @param array<string, mixed> $context Context metadata.
	 */
	public static function log( string $level, string $message, array $context = [] ): void {
		$sanitized_context = self::sanitize_context( $context );
		$sanitized_message = self::sanitize_message( $message );

		if ( function_exists( 'wc_get_logger' ) ) {
			$wc_logger = wc_get_logger();
			if ( $wc_logger ) {
				$sanitized_context['source'] = self::SOURCE;
				$wc_logger->log( $level, $sanitized_message, $sanitized_context );
				return;
			}
		}

		// Fallback when WC Logger is not initialized or in test environment
		$log_entry = sprintf(
			'[%s] [WPCalibrate Merchant Feed Manager] [%s] %s %s',
			gmdate( 'Y-m-d H:i:s' ),
			strtoupper( $level ),
			$sanitized_message,
			! empty( $sanitized_context ) ? wp_json_encode( $sanitized_context ) : ''
		);

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $log_entry );
	}

	/**
	 * Remove any sensitive information such as license keys, passwords, auth tokens, or absolute paths.
	 *
	 * @param array<string, mixed> $context Raw context.
	 * @return array<string, mixed> Sanitized context.
	 */
	public static function sanitize_context( array $context ): array {
		$sensitive_keys = [
			'license_key',
			'license',
			'password',
			'secret',
			'token',
			'auth',
			'authorization',
			'api_key',
			'credit_card',
			'email',
		];

		$sanitized = [];
		foreach ( $context as $key => $value ) {
			$lower_key = strtolower( (string) $key );
			$is_sensitive = false;
			foreach ( $sensitive_keys as $secret_key ) {
				if ( str_contains( $lower_key, $secret_key ) ) {
					$is_sensitive = true;
					break;
				}
			}

			if ( $is_sensitive ) {
				$sanitized[ $key ] = '[REDACTED]';
			} elseif ( is_array( $value ) ) {
				$sanitized[ $key ] = self::sanitize_context( $value );
			} elseif ( is_string( $value ) ) {
				// Strip file paths if present
				$sanitized[ $key ] = self::sanitize_message( $value );
			} else {
				$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Redact absolute paths from error messages to avoid path disclosure.
	 *
	 * @param string $message Raw message.
	 * @return string Sanitized message.
	 */
	public static function sanitize_message( string $message ): string {
		if ( defined( 'ABSPATH' ) && ABSPATH ) {
			$message = str_replace( ABSPATH, '[ABSPATH]/', $message );
		}
		if ( defined( 'WP_CONTENT_DIR' ) && WP_CONTENT_DIR ) {
			$message = str_replace( WP_CONTENT_DIR, '[WP_CONTENT]/', $message );
		}
		return $message;
	}
}
