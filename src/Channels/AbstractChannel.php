<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Channels;

use WPCalibrate\MerchantFeedManager\Feed\FeedModel;
use WPCalibrate\MerchantFeedManager\Security\XmlEscaper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base abstract channel class offering helper utilities for XML element rendering.
 */
abstract class AbstractChannel implements ChannelInterface {

	/**
	 * Helper to render an XML element tag safely.
	 *
	 * @param string      $tag Tag name (e.g. 'g:title', 'g:price').
	 * @param string|null $value Value to output.
	 * @param bool        $cdata Whether to wrap in CDATA.
	 * @return string Rendered tag or empty string if value is null/empty.
	 */
	protected function render_xml_element( string $tag, ?string $value, bool $cdata = false ): string {
		if ( null === $value || '' === trim( $value ) ) {
			return '';
		}

		$clean_tag = XmlEscaper::strip_invalid_xml_chars( $tag );
		if ( $cdata ) {
			$escaped_val = XmlEscaper::cdata( $value );
		} else {
			$escaped_val = XmlEscaper::escape( $value );
		}

		return sprintf( '    <%1$s>%2$s</%1$s>' . "\n", $clean_tag, $escaped_val );
	}

	/**
	 * Standard XML header.
	 *
	 * @param FeedModel $feed Feed model.
	 * @return string
	 */
	public function get_xml_header( FeedModel $feed ): string {
		return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	}

	/**
	 * Standard RSS 2.0 open tags for merchant feeds.
	 *
	 * @param FeedModel $feed Feed model.
	 * @return string
	 */
	public function get_xml_open_tags( FeedModel $feed ): string {
		$site_title = XmlEscaper::escape( get_bloginfo( 'name' ) ?: 'Store' );
		$feed_name  = XmlEscaper::escape( $feed->name );
		$site_url   = XmlEscaper::escape( home_url() );

		return "<rss version=\"2.0\" xmlns:g=\"http://base.google.com/ns/1.0\">\n" .
			"<channel>\n" .
			"  <title>{$feed_name}</title>\n" .
			"  <link>{$site_url}</link>\n" .
			"  <description>{$site_title} Product Feed</description>\n";
	}

	/**
	 * Standard RSS 2.0 closing tags.
	 *
	 * @param FeedModel $feed Feed model.
	 * @return string
	 */
	public function get_xml_close_tags( FeedModel $feed ): string {
		return "</channel>\n</rss>\n";
	}
}
