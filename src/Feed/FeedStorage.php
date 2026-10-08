<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Feed;

use WPCalibrate\MerchantFeedManager\Support\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages secure file storage, atomic swapping, and cleanup for generated product feeds.
 */
class FeedStorage {

	public const UPLOAD_SUBDIR = 'wpcmfm-feeds';

	/**
	 * Get the absolute directory path where feeds are stored.
	 *
	 * @return string
	 */
	public static function get_storage_dir(): string {
		$upload_dir = wp_upload_dir();
		$base_dir = $upload_dir['basedir'] . '/' . self::UPLOAD_SUBDIR;

		if ( ! file_exists( $base_dir ) ) {
			wp_mkdir_p( $base_dir );
			self::secure_directory( $base_dir );
		}

		return $base_dir;
	}

	/**
	 * Get the public URL for the storage directory.
	 *
	 * @return string
	 */
	public static function get_storage_url(): string {
		$upload_dir = wp_upload_dir();
		return $upload_dir['baseurl'] . '/' . self::UPLOAD_SUBDIR;
	}

	/**
	 * Place security hardening files in the feed storage directory.
	 *
	 * @param string $dir Target directory.
	 */
	public static function secure_directory( string $dir ): void {
		// Place index.php to prevent directory indexing
		$index_file = $dir . '/index.php';
		if ( ! file_exists( $index_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
			file_put_contents( $index_file, "<?php\n// Silence is golden.\nexit;\n" );
		}

		// Place .htaccess to prevent PHP execution
		$htaccess_file = $dir . '/.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$htaccess_rules = "<Files *.php>\nDeny from all\n</Files>\nOptions -Indexes\n";
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
			file_put_contents( $htaccess_file, $htaccess_rules );
		}
	}

	/**
	 * Generate a deterministic yet opaque filename for a feed.
	 *
	 * @param FeedModel $feed Feed entity.
	 * @return string Filename (e.g. feed_1_a1b2c3d4.xml)
	 */
	public static function get_filename( FeedModel $feed ): string {
		$safe_token = preg_replace( '/[^a-zA-Z0-9]/', '', $feed->token );
		$ext = 'csv' === strtolower( $feed->format ) ? 'csv' : 'xml';
		return sprintf( 'feed_%d_%s.%s', $feed->id, substr( $safe_token, 0, 16 ), $ext );
	}

	/**
	 * Get target final file path for a feed.
	 *
	 * @param FeedModel $feed Feed entity.
	 * @return string Full path.
	 */
	public static function get_feed_file_path( FeedModel $feed ): string {
		return self::get_storage_dir() . '/' . self::get_filename( $feed );
	}

	/**
	 * Get temporary file path for in-progress generation.
	 *
	 * @param FeedModel $feed Feed entity.
	 * @param string    $run_token Run identifier.
	 * @return string Full path.
	 */
	public static function get_temp_file_path( FeedModel $feed, string $run_token ): string {
		$safe_run = preg_replace( '/[^a-zA-Z0-9]/', '', $run_token );
		return self::get_storage_dir() . '/' . sprintf( 'feed_%d_%s.tmp', $feed->id, substr( $safe_run, 0, 16 ) );
	}

	/**
	 * Atomically replace the existing feed file with the new temporary file.
	 *
	 * @param string $temp_path Temporary file path.
	 * @param string $target_path Final destination path.
	 * @return bool True if atomic rename succeeded.
	 */
	public static function finalize_temp_file( string $temp_path, string $target_path ): bool {
		if ( ! file_exists( $temp_path ) ) {
			Logger::error( 'Temp feed file not found for finalization', [ 'temp_path' => $temp_path ] );
			return false;
		}

		$file_size = filesize( $temp_path );
		if ( false === $file_size || 0 === $file_size ) {
			Logger::error( 'Temp feed file is empty or unreadable', [ 'temp_path' => $temp_path ] );
			return false;
		}

		// Keep a backup of the previous known-good file in case atomic replacement fails
		$backup_path = $target_path . '.bak';
		if ( file_exists( $target_path ) ) {
			@copy( $target_path, $backup_path );
		}

		// Atomic rename
		if ( @rename( $temp_path, $target_path ) ) {
			if ( file_exists( $backup_path ) ) {
				@unlink( $backup_path );
			}
			return true;
		}

		// Fallback copy if rename across mount fails
		if ( @copy( $temp_path, $target_path ) ) {
			@unlink( $temp_path );
			if ( file_exists( $backup_path ) ) {
				@unlink( $backup_path );
			}
			return true;
		}

		// Failed replacement: restore last-known-good
		if ( file_exists( $backup_path ) ) {
			@rename( $backup_path, $target_path );
		}

		Logger::critical( 'Failed to atomically replace feed file', [ 'target_path' => $target_path ] );
		return false;
	}

	/**
	 * Delete generated file and temporary files for a feed.
	 *
	 * @param FeedModel $feed Feed entity.
	 */
	public static function delete_feed_files( FeedModel $feed ): void {
		$path = self::get_feed_file_path( $feed );
		if ( file_exists( $path ) ) {
			@unlink( $path );
		}

		// Delete any lingering temp or backup files
		$pattern = self::get_storage_dir() . '/feed_' . $feed->id . '_*';
		$files = glob( $pattern );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					@unlink( $file );
				}
			}
		}
	}

	/**
	 * Clean stale temporary files older than 24 hours.
	 */
	public static function clean_stale_temp_files(): void {
		$dir = self::get_storage_dir();
		$files = glob( $dir . '/*.tmp' );
		if ( ! is_array( $files ) ) {
			return;
		}

		$cutoff = time() - 86400;
		foreach ( $files as $file ) {
			if ( is_file( $file ) && filemtime( $file ) < $cutoff ) {
				@unlink( $file );
			}
		}
	}

	/**
	 * Delete all files in storage directory (used on complete uninstall).
	 */
	public static function purge_all_storage(): void {
		$dir = self::get_storage_dir();
		$files = glob( $dir . '/*' );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					@unlink( $file );
				}
			}
		}
	}
}
