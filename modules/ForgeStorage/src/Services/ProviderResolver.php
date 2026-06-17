<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Services;

use App\Modules\ForgeStorage\Contracts\StorageDriverInterface;
use App\Modules\ForgeStorage\Drivers\LocalDriver;
use App\Modules\ForgeStorage\Drivers\S3Driver;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use RuntimeException;

#[Service]
final class ProviderResolver
{
  private array $providers = [];

  private array $providerMap = [];

  public function __construct(
    private readonly Config $config,
    private readonly Container $container
  ) {
    $this->initializeProviderMap();
  }

  private function initializeProviderMap(): void
  {
    $this->providerMap = [
      'local' => LocalDriver::class,
      's3' => S3Driver::class,
    ];
  }

  public function resolve(?string $providerName = null): StorageDriverInterface
  {
    if ($providerName === null) {
      $providerName = $this->config->get('forge_storage.provider', 'local');
    }

    if (is_array($providerName)) {
      $providerName = $providerName[0] ?? 'local';
    }

    $providerName = (string) $providerName;

    if (isset($this->providers[$providerName])) {
      return $this->providers[$providerName];
    }

    if (!isset($this->providerMap[$providerName])) {
      throw new RuntimeException(
        "Storage provider '{$providerName}' not found. " .
        "Available providers: " . implode(', ', array_keys($this->providerMap))
      );
    }

    $providerClass = $this->providerMap[$providerName];

    if (!class_exists($providerClass)) {
      throw new RuntimeException(
        "Storage provider class '{$providerClass}' not found for provider '{$providerName}'"
      );
    }

    try {
      $provider = $this->container->make($providerClass);
    } catch (\Exception $e) {
      throw new RuntimeException(
        "Failed to instantiate storage provider '{$providerName}': " . $e->getMessage(),
        0,
        $e
      );
    }

    if (!$provider instanceof StorageDriverInterface) {
      throw new RuntimeException(
        "Storage provider '{$providerClass}' must implement StorageDriverInterface"
      );
    }

    $this->providers[$providerName] = $provider;

    return $provider;
  }
}
