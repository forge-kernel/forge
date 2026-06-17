<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Drivers;

use App\Modules\ForgeStorage\Contracts\StorageDriverInterface;
use Forge\Core\Config\Config;

class S3Driver implements StorageDriverInterface
{
  public function __construct(
    private readonly Config $config
  ) {
  }

  public function put(string $path, $contents, array $options = []): bool
  {
    throw new \RuntimeException(
      'S3Driver::put() requires aws/aws-sdk-php. ' .
      'Install it with: composer require aws/aws-sdk-php'
    );
  }

  public function get(string $path)
  {
    throw new \RuntimeException(
      'S3Driver::get() requires aws/aws-sdk-php. ' .
      'Install it with: composer require aws/aws-sdk-php'
    );
  }

  public function delete(string $path): bool
  {
    throw new \RuntimeException(
      'S3Driver::delete() requires aws/aws-sdk-php. ' .
      'Install it with: composer require aws/aws-sdk-php'
    );
  }

  public function exists(string $path): bool
  {
    throw new \RuntimeException(
      'S3Driver::exists() requires aws/aws-sdk-php. ' .
      'Install it with: composer require aws/aws-sdk-php'
    );
  }

  public function getUrl(string $path): string
  {
    throw new \RuntimeException(
      'S3Driver::getUrl() requires aws/aws-sdk-php. ' .
      'Install it with: composer require aws/aws-sdk-php'
    );
  }

  public function signedUrl(string $path, int $expires): string
  {
    throw new \RuntimeException(
      'S3Driver::signedUrl() requires aws/aws-sdk-php. ' .
      'Install it with: composer require aws/aws-sdk-php'
    );
  }

  public function getMetadata(string $path): array
  {
    throw new \RuntimeException(
      'S3Driver::getMetadata() requires aws/aws-sdk-php. ' .
      'Install it with: composer require aws/aws-sdk-php'
    );
  }

  public function copy(string $sourcePath, string $destPath): bool
  {
    throw new \RuntimeException(
      'S3Driver::copy() requires aws/aws-sdk-php. ' .
      'Install it with: composer require aws/aws-sdk-php'
    );
  }

  public function list(string $prefix = '', int $maxKeys = 1000): array
  {
    throw new \RuntimeException(
      'S3Driver::list() requires aws/aws-sdk-php. ' .
      'Install it with: composer require aws/aws-sdk-php'
    );
  }
}
