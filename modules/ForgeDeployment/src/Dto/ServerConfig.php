<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Dto;

final class ServerConfig
{
  public function __construct(
    public readonly string $name,
    public readonly string $region,
    public readonly string $size,
    public readonly string $image,
    public readonly ?string $sshKeyPath = null
  ) {
  }

  public function toArray(): array
  {
    return [
      'name' => $this->name,
      'region' => $this->region,
      'size' => $this->size,
      'image' => $this->image,
      'ssh_key_path' => $this->sshKeyPath,
    ];
  }
}
