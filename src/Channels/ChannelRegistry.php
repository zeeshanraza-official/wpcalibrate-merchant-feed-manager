<?php
declare(strict_types=1);

namespace WPCalibrate\MerchantFeedManager\Channels;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry of available merchant channel adapters.
 */
class ChannelRegistry {

	/**
	 * Cached map of identifier => ChannelInterface instance.
	 *
	 * @var array<string, ChannelInterface>|null
	 */
	private static ?array $channels = null;

	/**
	 * Get all registered channel adapters.
	 *
	 * @return array<string, ChannelInterface> Map of [ id => instance ]
	 */
	public static function get_all(): array {
		if ( null !== self::$channels ) {
			return self::$channels;
		}

		$adapters = [
			new GoogleMerchantCenter(),
			new MetaCatalog(),
			new MicrosoftShopping(),
			new PinterestCatalog(),
			new TikTokCatalog(),
			new CustomXmlChannel(),
			new CustomCsvChannel(),
		];

		$map = [];
		foreach ( $adapters as $adapter ) {
			$map[ $adapter->get_identifier() ] = $adapter;
		}

		/**
		 * Filter registered merchant channel adapters.
		 *
		 * @param array<string, ChannelInterface> $map Map of registered channels.
		 */
		self::$channels = (array) apply_filters( 'wpcmfm_registered_channels', $map );
		return self::$channels;
	}

	/**
	 * Get a channel adapter by identifier.
	 *
	 * @param string $identifier Channel ID.
	 * @return ChannelInterface|null
	 */
	public static function get( string $identifier ): ?ChannelInterface {
		$all = self::get_all();
		return $all[ $identifier ] ?? null;
	}

	/**
	 * Check if a channel identifier is valid and supported.
	 *
	 * @param string $identifier Channel ID.
	 * @return bool
	 */
	public static function is_supported( string $identifier ): bool {
		return null !== self::get( $identifier );
	}

	/**
	 * Reset cached channels (useful for tests).
	 */
	public static function reset(): void {
		self::$channels = null;
	}
}
