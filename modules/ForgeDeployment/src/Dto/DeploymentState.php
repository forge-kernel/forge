<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Dto;

final class DeploymentState
{
  public function __construct(
    public readonly ?string $serverIp = null,
    public readonly ?string $serverId = null,
    public readonly ?string $sshKeyPath = null,
    public readonly ?string $domain = null,
    public readonly array $completedSteps = [],
    public readonly ?string $currentStep = null,
    public readonly ?string $lastUpdated = null,
    public readonly array $config = [],
    public readonly ?string $lastDeployedCommit = null
  ) {
  }

  public function isStepCompleted(string $step): bool
  {
    return in_array($step, $this->completedSteps, true);
  }

  public function markStepCompleted(string $step): self
  {
    $completedSteps = $this->completedSteps;
    if (!in_array($step, $completedSteps, true)) {
      $completedSteps[] = $step;
    }

    return new self(
      $this->serverIp,
      $this->serverId,
      $this->sshKeyPath,
      $this->domain,
      $completedSteps,
      $step,
      date('c'),
      $this->config,
      $this->lastDeployedCommit
    );
  }

  public function withLastDeployedCommit(string $commitHash): self
  {
    return new self(
      $this->serverIp,
      $this->serverId,
      $this->sshKeyPath,
      $this->domain,
      $this->completedSteps,
      $this->currentStep,
      $this->lastUpdated,
      $this->config,
      $commitHash
    );
  }

  public function toArray(): array
  {
    return [
      'server_ip' => $this->serverIp,
      'server_id' => $this->serverId,
      'ssh_key_path' => $this->sshKeyPath,
      'domain' => $this->domain,
      'completed_steps' => $this->completedSteps,
      'current_step' => $this->currentStep,
      'last_updated' => $this->lastUpdated,
      'config' => $this->config,
      'last_deployed_commit' => $this->lastDeployedCommit,
    ];
  }

  public static function fromArray(array $data): self
  {
    return new self(
      $data['server_ip'] ?? null,
      $data['server_id'] ?? null,
      $data['ssh_key_path'] ?? null,
      $data['domain'] ?? null,
      $data['completed_steps'] ?? [],
      $data['current_step'] ?? null,
      $data['last_updated'] ?? null,
      $data['config'] ?? [],
      $data['last_deployed_commit'] ?? null
    );
  }
}
