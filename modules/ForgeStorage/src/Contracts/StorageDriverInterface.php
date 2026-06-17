<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Contracts;

interface StorageDriverInterface
{
  public function put(string $path, $contents, array $options = []): bool;

  public function get(string $path);

  public function delete(string $path): bool;

  public function exists(string $path): bool;

  public function getUrl(string $path): string;

  public function signedUrl(string $path, int $expires): string;

  public function getMetadata(string $path): array;

  public function copy(string $sourcePath, string $destPath): bool;

  public function list(string $prefix = '', int $maxKeys = 1000): array;
}
