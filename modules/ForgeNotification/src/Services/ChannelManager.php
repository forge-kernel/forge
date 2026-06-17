<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Services;

use App\Modules\ForgeNotification\Channels\EmailChannel;
use App\Modules\ForgeNotification\Channels\PushChannel;
use App\Modules\ForgeNotification\Channels\SmsChannel;
use App\Modules\ForgeNotification\Contracts\ChannelInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use RuntimeException;

/**
 * Manages channel instances and routes to providers.
 * Caches channel instances for performance.
 */
#[Service]
final class ChannelManager
{
  /**
   * @var array<string, ChannelInterface> Cached channel instances
   */
  private array $channels = [];

  /**
   * Channel class mapping.
   * Format: ['channel_name' => ChannelClass::class]
   */
  private array $channelMap = [];

  public function __construct(
    private readonly ProviderResolver $providerResolver,
    private readonly Container $container
  ) {
    $this->initializeChannelMap();
  }

  /**
   * Initialize the channel class mapping.
   */
  private function initializeChannelMap(): void
  {
    $this->channelMap = [
      'email' => EmailChannel::class,
      'sms' => SmsChannel::class,
      'push' => PushChannel::class,
    ];
  }

  /**
   * Get a channel instance by name.
   *
   * @param string $channelName Channel name (email, sms, push)
   * @return ChannelInterface
   * @throws RuntimeException If channel is not found
   */
  public function getChannel(string $channelName): ChannelInterface
  {
    if (isset($this->channels[$channelName])) {
      return $this->channels[$channelName];
    }

    if (!isset($this->channelMap[$channelName])) {
      throw new RuntimeException(
        "Channel '{$channelName}' not found. " .
        "Available channels: " . implode(', ', array_keys($this->channelMap))
      );
    }

    $channelClass = $this->channelMap[$channelName];

    if (!class_exists($channelClass)) {
      throw new RuntimeException(
        "Channel class '{$channelClass}' not found for channel '{$channelName}'"
      );
    }

    try {
      $channel = $this->container->make($channelClass);
    } catch (\Exception $e) {
      throw new RuntimeException(
        "Failed to instantiate channel '{$channelName}': " . $e->getMessage(),
        0,
        $e
      );
    }

    if (!$channel instanceof ChannelInterface) {
      throw new RuntimeException(
        "Channel '{$channelClass}' must implement ChannelInterface"
      );
    }

    $this->channels[$channelName] = $channel;

    return $channel;
  }

  /**
   * Get the email channel.
   *
   * @return EmailChannel
   */
  public function email(): EmailChannel
  {
    return $this->getChannel('email');
  }

  /**
   * Get the SMS channel.
   *
   * @return SmsChannel
   */
  public function sms(): SmsChannel
  {
    return $this->getChannel('sms');
  }

  /**
   * Get the push notification channel.
   *
   * @return PushChannel
   */
  public function push(): PushChannel
  {
    return $this->getChannel('push');
  }

  /**
   * Check if a channel exists.
   *
   * @param string $channelName Channel name
   * @return bool
   */
  public function hasChannel(string $channelName): bool
  {
    return isset($this->channelMap[$channelName]);
  }

  /**
   * Get all available channel names.
   *
   * @return array<string> List of channel names
   */
  public function getAvailableChannels(): array
  {
    return array_keys($this->channelMap);
  }

  /**
   * Clear cached channel instances.
   * Useful for testing or when configuration changes.
   */
  public function clearCache(): void
  {
    $this->channels = [];
  }
}
