<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use App\Modules\ForgeDeployment\Dto\DeploymentConfig;
use App\Modules\ForgeDeployment\Dto\DeploymentState;
use App\Modules\ForgeDeployment\Dto\ProvisionConfig;
use App\Modules\ForgeDeployment\Dto\ServerConfig;
use Forge\Core\DI\Attributes\Service;

#[Service]
final class ForgeDeploymentService
{
  public function __construct(
    private readonly SshService $sshService,
    private readonly SystemProvisioner $systemProvisioner,
    private readonly PhpProvisioner $phpProvisioner,
    private readonly DatabaseProvisioner $databaseProvisioner,
    private readonly NginxProvisioner $nginxProvisioner,
    private readonly DeploymentService $deploymentService,
    private readonly DeploymentStateService $stateService
  ) {
  }

  public function deployFull(
    ServerConfig $serverConfig,
    ProvisionConfig $provisionConfig,
    DeploymentConfig $deploymentConfig,
    string $serverIp,
    string $sshPrivateKeyPath,
    int $ramMb = 1024,
    ?callable $progressCallback = null,
    ?callable $outputCallback = null,
    ?callable $errorCallback = null,
    ?DeploymentState $state = null
  ): bool {
    $progress = function (string $message) use ($progressCallback) {
      if ($progressCallback !== null) {
        $progressCallback($message);
      }
    };

    $saveState = function (string $step) use (&$state, $serverIp, $serverConfig, $provisionConfig, $deploymentConfig, $sshPrivateKeyPath) {
      if ($state === null) {
        $existingState = $this->stateService->load();
        if ($existingState !== null) {
          $state = $existingState;
        } else {
        $state = new DeploymentState(
          $serverIp,
          $serverConfig->name ?? null,
          $sshPrivateKeyPath,
          $deploymentConfig->domain,
          [],
          $step,
          date('c'),
          [
            'php_version' => $provisionConfig->phpVersion,
            'database_type' => $provisionConfig->databaseType,
            'database_version' => $provisionConfig->databaseVersion,
          ]
        );
        }
      }
      $state = $state->markStepCompleted($step);
      $this->stateService->save($state);
    };

    if (!$this->sshService->isConnected()) {
      $connected = $this->sshService->connect(
        $serverIp,
        22,
        'root',
        $sshPrivateKeyPath,
        $sshPrivateKeyPath . '.pub'
      );

      if (!$connected) {
        throw new \RuntimeException("Failed to establish SSH connection to {$serverIp}. Please check your SSH key and server accessibility.");
      }

      if ($state === null || !$state->isStepCompleted('ssh_connected')) {
        $saveState('ssh_connected');
      }
    }

    if ($state === null || !$state->isStepCompleted('system_provisioned')) {
      $progress("  → Configuring system (swap, firewall, updates, kernel optimizations)...");
      $this->systemProvisioner->provision($ramMb, $progress, $outputCallback, $errorCallback);
      $progress("  ✓ System configured");
      $saveState('system_provisioned');
    } else {
      $progress("  ⏭ Skipping system provisioning (already completed)");
    }

    if ($state === null || !$state->isStepCompleted('php_installed')) {
      $progress("  → Installing PHP {$provisionConfig->phpVersion} with extensions...");
      $this->phpProvisioner->provision($provisionConfig->phpVersion, $ramMb, $progress, $outputCallback, $errorCallback);
      $progress("  ✓ PHP {$provisionConfig->phpVersion} installed and configured");
      $saveState('php_installed');
    } else {
      $progress("  ⏭ Skipping PHP installation (already completed)");
    }

    if ($state === null || !$state->isStepCompleted('database_installed')) {
      $progress("  → Installing {$provisionConfig->databaseType} " . ($provisionConfig->databaseVersion ?? 'latest') . "...");
      $this->databaseProvisioner->provision($provisionConfig->databaseType, $provisionConfig->databaseVersion, $ramMb, $progress, $outputCallback, $errorCallback);

      if ($provisionConfig->databaseName && $provisionConfig->databaseUser && $provisionConfig->databasePassword) {
        $progress("  → Creating database '{$provisionConfig->databaseName}' and user '{$provisionConfig->databaseUser}'...");
        $this->databaseProvisioner->createDatabase(
          $provisionConfig->databaseType,
          $provisionConfig->databaseName,
          $provisionConfig->databaseUser,
          $provisionConfig->databasePassword
        );
      }

      $progress("  ✓ {$provisionConfig->databaseType} installed and configured");
      $saveState('database_installed');
    } else {
      $progress("  ⏭ Skipping database installation (already completed)");
    }

    if ($state === null || !$state->isStepCompleted('nginx_installed')) {
      $progress("  → Installing and configuring Nginx...");
      $this->nginxProvisioner->provision($provisionConfig->phpVersion, $ramMb, $progress, $outputCallback, $errorCallback);
      $progress("  ✓ Nginx installed and configured");
      $saveState('nginx_installed');
    } else {
      $progress("  ⏭ Skipping Nginx installation (already completed)");
    }

    $remotePath = '/var/www/' . $deploymentConfig->domain;

    if ($state === null || !$state->isStepCompleted('project_uploaded')) {
      $progress("  → Uploading project files...");
      $this->deploymentService->deploy(
        BASE_PATH,
        $remotePath,
        $deploymentConfig->commands,
        $deploymentConfig->envVars,
        function (string $message) use ($outputCallback) {
          if ($outputCallback !== null) {
            $outputCallback("      {$message}");
          }
        }
      );
      $progress("  ✓ Project files uploaded");
      $saveState('project_uploaded');
    } else {
      $progress("  ⏭ Skipping project upload (already completed)");
    }

    if ($state === null || !$state->isStepCompleted('site_configured')) {
      $progress("  → Creating Nginx site configuration...");
      $this->nginxProvisioner->createSiteConfig(
        $deploymentConfig->domain,
        $remotePath,
        $provisionConfig->phpVersion,
        $outputCallback,
        $errorCallback
      );
      $progress("  ✓ Nginx site configuration created");
      $saveState('site_configured');
    } else {
      $progress("  ⏭ Skipping site configuration (already completed)");
    }

    return true;
  }
}
