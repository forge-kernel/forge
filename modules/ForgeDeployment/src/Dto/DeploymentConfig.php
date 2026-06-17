<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Dto;

final class DeploymentConfig
{
  public function __construct(
    public readonly string $domain,
    public readonly array $commands = [],
    public readonly array $envVars = [],
    public readonly array $postDeploymentCommands = [],
    public readonly ?string $sslEmail = null
  ) {
  }

  public function toArray(): array
  {
    return [
      'domain' => $this->domain,
      'commands' => $this->commands,
      'env_vars' => $this->envVars,
      'post_deployment_commands' => $this->postDeploymentCommands,
      'ssl_email' => $this->sslEmail,
    ];
  }

  public static function fromArray(array $data): self
  {
    return new self(
      $data['domain'] ?? 'example.com',
      $data['commands'] ?? [],
      $data['env_vars'] ?? $data['envVars'] ?? [],
      $data['post_deployment_commands'] ?? $data['postDeploymentCommands'] ?? [],
      $data['ssl_email'] ?? null
    );
  }
}
