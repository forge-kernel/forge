<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use App\Modules\ForgeDeployment\Dto\DeploymentState;
use Forge\Core\DI\Attributes\Service;

#[Service]
final class DeploymentStateService
{
  private const STATE_FILE = '.forge-deployment-state.json';

  public function getStateFilePath(): string
  {
    return BASE_PATH . '/' . self::STATE_FILE;
  }

  public function save(DeploymentState $state): bool
  {
    $filePath = $this->getStateFilePath();
    $data = json_encode($state->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    return file_put_contents($filePath, $data) !== false;
  }

  public function load(): ?DeploymentState
  {
    $filePath = $this->getStateFilePath();

    if (!file_exists($filePath)) {
      return null;
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
      return null;
    }

    $data = json_decode($content, true);
    if (!is_array($data)) {
      return null;
    }

    return DeploymentState::fromArray($data);
  }

  public function clear(): bool
  {
    $filePath = $this->getStateFilePath();

    if (file_exists($filePath)) {
      return unlink($filePath);
    }

    return true;
  }

  public function exists(): bool
  {
    return file_exists($this->getStateFilePath());
  }

  public function validate(DeploymentState $state): bool
  {
    if ($state->serverIp === null) {
      return false;
    }

    $connection = @fsockopen($state->serverIp, 22, $errno, $errstr, 2);
    if ($connection) {
      fclose($connection);
      return true;
    }

    return false;
  }

  public function isStepCompleted(DeploymentState $state, string $step): bool
  {
    return $state->isStepCompleted($step);
  }
}
