<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Generator;

use WPCalibrate\MerchantFeedManager\Security\CsvEscaper;
use WPCalibrate\MerchantFeedManager\Support\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Memory-safe streaming writer for large CSV feed files with formula injection protection.
 */
class CsvStreamWriter {

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
	 * Whether to sanitize formulas.
	 *
	 * @var bool
	 */
	private bool $sanitize_formulas = true;

	/**
	 * Open CSV file for writing or appending.
	 *
	 * @param string $file_path Target file path.
	 * @param string $mode File mode ('wb' or 'ab').
	 * @param bool   $sanitize_formulas Formula injection mitigation.
	 * @return bool
	 */
	public function open( string $file_path, string $mode = 'wb', bool $sanitize_formulas = true ): bool {
		$this->file_path         = $file_path;
		$this->sanitize_formulas = $sanitize_formulas;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$this->handle = @fopen( $file_path, $mode );

		if ( ! is_resource( $this->handle ) ) {
			Logger::error( 'Unable to open CSV feed file for writing', [ 'path' => $file_path ] );
			return false;
		}

		return true;
	}

	/**
	 * Write header row.
	 *
	 * @param array<string> $headers Column header names.
	 * @return bool
	 */
	public function write_header( array $headers ): bool {
		if ( ! is_resource( $this->handle ) ) {
			return false;
		}

		$line = CsvEscaper::format_row( $headers, ',', false );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		$res = fwrite( $this->handle, $line );
		return false !== $res;
	}

	/**
	 * Write data row.
	 *
	 * @param array<mixed> $row Row values.
	 * @return bool
	 */
	public function write_row( array $row ): bool {
		if ( ! is_resource( $this->handle ) ) {
			return false;
		}

		$line = CsvEscaper::format_row( $row, ',', $this->sanitize_formulas );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		$res = fwrite( $this->handle, $line );
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
	 * Destructor to ensure file handle is closed.
	 */
	public function __destruct() {
		$this->close();
	}
}
