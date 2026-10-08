<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Escapes strings for XML output, preventing XML injection and stripping invalid characters.
 */
class XmlEscaper {

	/**
	 * Strip XML-incompatible control characters.
	 *
	 * @param string $string Raw string.
	 * @return string Cleaned UTF-8 string.
	 */
	public static function strip_invalid_xml_chars( string $string ): string {
		// Valid XML 1.0 chars: #x9 | #xA | #xD | [#x20-#xD7FF] | [#xE000-#xFFFD] | [#x10000-#x10FFFF]
		return (string) preg_replace(
			'/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u',
			'',
			$string
		);
	}

	/**
	 * Escape text for safe XML element or attribute content.
	 *
	 * @param string $text Text to escape.
	 * @return string Escaped XML string.
	 */
	public static function escape( string $text ): string {
		$clean = self::strip_invalid_xml_chars( $text );
		return htmlspecialchars( $clean, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Wrap string in CDATA block, safely escaping any internal ]]> sequences.
	 *
	 * @param string $text Content to wrap.
	 * @return string CDATA block.
	 */
	public static function cdata( string $text ): string {
		$clean = self::strip_invalid_xml_chars( $text );
		$escaped = str_replace( ']]>', ']]]]><![CDATA[>', $clean );
		return '<![CDATA[' . $escaped . ']]>';
	}
}
