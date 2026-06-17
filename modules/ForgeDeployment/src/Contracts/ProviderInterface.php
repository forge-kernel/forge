<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Contracts;

interface ProviderInterface
{
  public function createServer(array $config, ?string $sshPublicKey = null): string;

  public function waitForServer(string $serverId): array;

  public function getServerStatus(string $serverId): string;

  public function listRegions(): array;

  public function listSizes(): array;

  public function listImages(): array;

  public function deleteServer(string $serverId): bool;
}
