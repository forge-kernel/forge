<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Services;

use App\Modules\ForgeNotification\Contracts\ProviderInterface;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use RuntimeException;

/**
 * Resolves and instantiates notification providers from configuration.
 * Caches provider instances for performance.
 */
#[Service]
final class ProviderResolver
{
  /**
   * @var array<string, ProviderInterface> Cached provider instances
   */
  private array $providers = [];

  /**
   * Provider class mapping by channel and provider name.
   * Format: ['channel' => ['provider_name' => ProviderClass::class]]
   */
  private array $providerMap = [];

  public function __construct(
    private readonly Config $config,
    private readonly Container $container
  ) {
    $this->initializeProviderMap();
  }

  /**
   * Initialize the provider class mapping from configuration.
   */
  private function initializeProviderMap(): void
  {

    $this->providerMap = [
      'email' => [
        'smtp' => \App\Modules\ForgeNotification\Providers\Email\SmtpProvider::class,
        'sendgrid' => \App\Modules\ForgeNotification\Providers\Email\SendGridProvider::class,
        'mailgun' => \App\Modules\ForgeNotification\Providers\Email\MailgunProvider::class,
      ],
      'sms' => [
        'twilio' => \App\Modules\ForgeNotification\Providers\Sms\TwilioProvider::class,
        'vonage' => \App\Modules\ForgeNotification\Providers\Sms\VonageProvider::class,
      ],
      'push' => [
        'firebase' => \App\Modules\ForgeNotification\Providers\Push\FirebaseProvider::class,
        'onesignal' => \App\Modules\ForgeNotification\Providers\Push\OneSignalProvider::class,
      ],
    ];
  }

  /**
   * Resolve a provider instance for a given channel and provider name.
   *
   * @param string $channel Channel name (email, sms, push)
   * @param string $providerName Provider name (e.g., 'twilio', 'smtp')
   * @return ProviderInterface
   * @throws RuntimeException If provider is not found or cannot be instantiated
   */
  public function resolve(string $channel, string $providerName): ProviderInterface
  {
    $cacheKey = "{$channel}.{$providerName}";

    if (isset($this->providers[$cacheKey])) {
      return $this->providers[$cacheKey];
    }

    if (!isset($this->providerMap[$channel][$providerName])) {
      throw new RuntimeException(
        "Provider '{$providerName}' not found for channel '{$channel}'. " .
        "Available providers: " . implode(', ', array_keys($this->providerMap[$channel] ?? []))
      );
    }

    $providerClass = $this->providerMap[$channel][$providerName];

    if (!class_exists($providerClass)) {
      throw new RuntimeException(
        "Provider class '{$providerClass}' not found for provider '{$providerName}' in channel '{$channel}'"
      );
    }

    try {
      $provider = $this->container->make($providerClass);

      if (method_exists($provider, 'setConfig')) {
        $config = $this->getProviderConfig($channel, $providerName);
        $provider->setConfig($config);
      }
    } catch (\Exception $e) {
      throw new RuntimeException(
        "Failed to instantiate provider '{$providerName}' for channel '{$channel}': " . $e->getMessage(),
        0,
        $e
      );
    }

    if (!$provider instanceof ProviderInterface) {
      throw new RuntimeException(
        "Provider '{$providerClass}' must implement ProviderInterface"
      );
    }

    $this->providers[$cacheKey] = $provider;

    return $provider;
  }

  /**
   * Get configuration for a specific provider.
   *
   * @param string $channel Channel name
   * @param string $providerName Provider name
   * @return array Provider configuration
   */
  private function getProviderConfig(string $channel, string $providerName): array
  {
    $configPath = "forge_notification.channels.{$channel}.providers.{$providerName}";
    $config = $this->config->get($configPath, []);

    return is_array($config) ? $config : [];
  }

  /**
   * Get the default provider name for a channel.
   *
   * @param string $channel Channel name
   * @return string Default provider name
   * @throws RuntimeException If no default provider is configured
   */
  public function getDefaultProvider(string $channel): string
  {
    $defaultProvider = $this->config->get("forge_notification.channels.{$channel}.default_provider");

    if (empty($defaultProvider) || !is_string($defaultProvider)) {
      $availableProviders = array_keys($this->providerMap[$channel] ?? []);
      if (empty($availableProviders)) {
        throw new RuntimeException("No providers available for channel '{$channel}'");
      }
      return $availableProviders[0];
    }

    return $defaultProvider;
  }

  /**
   * Check if a provider exists for a channel.
   *
   * @param string $channel Channel name
   * @param string $providerName Provider name
   * @return bool
   */
  public function hasProvider(string $channel, string $providerName): bool
  {
    return isset($this->providerMap[$channel][$providerName]);
  }

  /**
   * Get all available providers for a channel.
   *
   * @param string $channel Channel name
   * @return array<string> List of provider names
   */
  public function getAvailableProviders(string $channel): array
  {
    return array_keys($this->providerMap[$channel] ?? []);
  }

  /**
   * Clear cached provider instances.
   * Useful for testing or when configuration changes.
   */
  public function clearCache(): void
  {
    $this->providers = [];
  }
}
