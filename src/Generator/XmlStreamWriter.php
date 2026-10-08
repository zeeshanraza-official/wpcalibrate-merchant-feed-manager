<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Generator;

use WPCalibrate\MerchantFeedManager\Support\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Memory-safe streaming writer for large XML feed files.
 */
class XmlStreamWriter {

	/**
	 * File resource handle.
	 *
	 * @var resource|null
	 */
	private $handle = null;

	/**
	 * File path.
	 *
	 * @var string
	 */
	private string $file_path = '';

	/**
	 * Open file for writing or appending.
	 *
	 * @param string $file_path Target file path.
	 * @param string $mode File mode ('wb' or 'ab').
	 * @return bool
	 */
	public function open( string $file_path, string $mode = 'wb' ): bool {
		$this->file_path = $file_path;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$this->handle = @fopen( $file_path, $mode );

		if ( ! is_resource( $this->handle ) ) {
			Logger::error( 'Unable to open XML feed file for writing', [ 'path' => $file_path ] );
			return false;
		}

		return true;
	}

	/**
	 * Write XML header and opening tags.
	 *
	 * @param string $header XML declaration.
	 * @param string $open_tags Opening RSS/Channel tags.
	 * @return bool
	 */
	public function write_preamble( string $header, string $open_tags ): bool {
		if ( ! is_resource( $this->handle ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		$res1 = fwrite( $this->handle, $header );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		$res2 = fwrite( $this->handle, $open_tags );

		return false !== $res1 && false !== $res2;
	}

	/**
	 * Write an individual item string.
	 *
	 * @param string $item_xml XML chunk.
	 * @return bool
	 */
	public function write_item( string $item_xml ): bool {
		if ( ! is_resource( $this->handle ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		$res = fwrite( $this->handle, $item_xml );
		return false !== $res;
	}

	/**
	 * Write closing tags and flush file handle.
	 *
	 * @param string $close_tags Closing RSS/Channel tags.
	 * @return bool
	 */
	public function write_epilogue( string $close_tags ): bool {
		if ( ! is_resource( $this->handle ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		$res = fwrite( $this->handle, $close_tags );
		fflush( $this->handle );

		return false !== $res;
	}

	/**
	 * Flush and close the file handle.
	 */
	public function close(): void {
		if ( is_resource( $this->handle ) ) {
			fflush( $this->handle );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			fclose( $this->handle );
			$this->handle = null;
		}
	}

	/**
	 * Destructor to ensure file handle is safely closed.
	 */
	public function __destruct() {
		$this->close();
	}
}
