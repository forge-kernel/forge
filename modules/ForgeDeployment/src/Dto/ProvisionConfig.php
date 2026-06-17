<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Dto;

final class ProvisionConfig
{
  public function __construct(
    public readonly string $phpVersion,
    public readonly string $databaseType,
    public readonly ?string $databaseVersion = null,
    public readonly ?string $databaseName = null,
    public readonly ?string $databaseUser = null,
    public readonly ?string $databasePassword = null
  ) {
  }

  public function toArray(): array
  {
    return [
      'php_version' => $this->phpVersion,
      'database_type' => $this->databaseType,
      'database_version' => $this->databaseVersion,
      'database_name' => $this->databaseName,
      'database_user' => $this->databaseUser,
      'database_password' => $this->databasePassword,
    ];
  }
}
