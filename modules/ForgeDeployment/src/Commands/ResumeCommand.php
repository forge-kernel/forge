<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

use App\Modules\ForgeDeployment\Dto\DeploymentConfig;
use App\Modules\ForgeDeployment\Dto\ProvisionConfig;
use App\Modules\ForgeDeployment\Dto\ServerConfig;
use App\Modules\ForgeDeployment\Services\DeploymentConfigReader;
use App\Modules\ForgeDeployment\Services\DeploymentService;
use App\Modules\ForgeDeployment\Services\DeploymentStateService;
use App\Modules\ForgeDeployment\Services\ForgeDeploymentService;
use App\Modules\ForgeDeployment\Services\LetsEncryptService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Config\Config;
use Forge\Core\Services\TemplateGenerator;

#[Cli(
  command: 'forge-deployment:resume',
  description: 'Resume a failed deployment from the last checkpoint',
  usage: 'forge-deployment:resume',
  examples: [
    'forge-deployment:resume',
  ]
)]
final class ResumeCommand extends Command
{
  use OutputHelper;

  public function __construct(
    private readonly DeploymentStateService $stateService,
    private readonly ForgeDeploymentService $forgeDeploymentService,
    private readonly DeploymentService $deploymentService,
    private readonly LetsEncryptService $letsEncryptService,
    private readonly DeploymentConfigReader $configReader,
    private readonly Config $configService,
    private readonly TemplateGenerator $templateGenerator
  ) {
  }

  public function execute(array $args): int
  {
    try {
      $state = $this->stateService->load();

      if ($state === null) {
        $this->error('No deployment state found. Run forge-deployment:deploy to start a new deployment.');
        return 1;
      }

      $this->info('Resuming deployment...');
      $this->info("Server IP: {$state->serverIp}");
      $this->info("Domain: {$state->domain}");
      $this->info("Completed steps: " . count($state->completedSteps));

      if (!$this->stateService->validate($state)) {
        $this->error('Cannot resume: Server is not accessible. Please check the server status.');
        return 1;
      }

      $fileConfig = $this->configReader->readConfig(null);

      $provisionConfigData = $fileConfig ? $this->configReader->getProvisionConfig($fileConfig) : null;
      if ($provisionConfigData !== null && !empty($provisionConfigData)) {
        $phpVersion = $provisionConfigData['php_version'] ?? $state->config['php_version'] ?? '8.4';
        $dbType = $provisionConfigData['database_type'] ?? $state->config['database_type'] ?? 'mysql';
        $dbVersion = $provisionConfigData['database_version'] ?? $state->config['database_version'] ?? '8.0';
        $provisionConfig = new ProvisionConfig($phpVersion, $dbType, $dbVersion);
      } else {
        $provisionConfig = new ProvisionConfig(
          $state->config['php_version'] ?? '8.4',
          $state->config['database_type'] ?? 'mysql',
          $state->config['database_version'] ?? '8.0'
        );
      }

      $deploymentConfigData = $fileConfig ? $this->configReader->getDeploymentConfig($fileConfig) : null;
      if ($deploymentConfigData !== null && !empty($deploymentConfigData)) {
        $domain = $deploymentConfigData['domain'] ?? $state->domain ?? 'example.com';
        $commands = $deploymentConfigData['commands'] ?? [];
        $postDeploymentCommands = $deploymentConfigData['post_deployment_commands'] ?? [];
        $envVars = $deploymentConfigData['env_vars'] ?? [];
        $deploymentConfig = new DeploymentConfig($domain, $commands, $envVars, $postDeploymentCommands);
      } else {
        $deploymentConfig = new DeploymentConfig(
          $state->domain ?? 'example.com',
          [],
          [],
          []
        );
      }

      $serverConfig = new ServerConfig(
        $state->serverId ?? 'resumed-server',
        'nyc1',
        's-1vcpu-1gb',
        'ubuntu-22-04-x64',
        null
      );

      $sshPrivateKeyPath = $this->expandPath($state->sshKeyPath ?? '~/.ssh/id_rsa');

      $this->info('Waiting for SSH to be available...');
      $this->waitForSsh($state->serverIp, 30);

      $ramMb = 1024;
      $outputCallback = function (string $line) {
        if (trim($line) !== '') {
          $this->line('      ' . trim($line));
        }
      };

      $errorCallback = function (string $line) {
        if (trim($line) !== '') {
          $this->error('      ' . trim($line));
        }
      };

      $this->forgeDeploymentService->deployFull(
        $serverConfig,
        $provisionConfig,
        $deploymentConfig,
        $state->serverIp,
        $sshPrivateKeyPath,
        $ramMb,
        fn(string $message) => $this->info($message),
        $outputCallback,
        $errorCallback,
        $state
      );

      $state = $this->stateService->load();

      if ($state->isStepCompleted('dns_configured') && $state->isStepCompleted('ssl_configured') && $state->isStepCompleted('post_deployment_completed')) {
        $this->success('Deployment resumed and completed successfully!');
      } else {
        $this->info('Continuing with remaining steps...');

        if (!$state->isStepCompleted('dns_configured')) {
          $this->info('Configuring DNS...');
          $cloudflareToken = $this->configService->get('forge_deployment.cloudflare.api_token');
          if (empty($cloudflareToken)) {
            $cloudflareToken = $this->templateGenerator->askQuestion('Cloudflare API token (leave empty to skip DNS)', '');
            if (!empty($cloudflareToken)) {
              $this->configService->set('forge_deployment.cloudflare.api_token', $cloudflareToken);
            }
          }

          if (!empty($cloudflareToken)) {
            $cloudflareService = new \App\Modules\ForgeDeployment\Services\CloudflareService($cloudflareToken);
            $zoneId = $cloudflareService->getZoneId($deploymentConfig->domain);
            if ($zoneId !== null) {
              $cloudflareService->addDnsRecord($zoneId, $deploymentConfig->domain, $state->serverIp);
              $this->success("DNS record added for {$deploymentConfig->domain}");
              $state = $state->markStepCompleted('dns_configured');
              $this->stateService->save($state);
            }
          }
        }

        if (!$state->isStepCompleted('ssl_configured')) {
          $this->info('Setting up SSL...');
          $email = $this->templateGenerator->askQuestion('Email for Let\'s Encrypt', 'admin@' . $deploymentConfig->domain);
          $sshPrivateKeyPath = $this->expandPath($sshPrivateKeyPath);
          $connected = $this->letsEncryptService->connect(
            $state->serverIp,
            22,
            'root',
            $sshPrivateKeyPath,
            $sshPrivateKeyPath . '.pub'
          );

          if ($connected) {
            $this->letsEncryptService->setupSsl($deploymentConfig->domain, $email, $outputCallback, $errorCallback);
            $remotePath = '/var/www/' . $deploymentConfig->domain;
            $this->letsEncryptService->updateNginxConfig($deploymentConfig->domain, $remotePath, $provisionConfig->phpVersion, $outputCallback);
            $state = $state->markStepCompleted('ssl_configured');
            $this->stateService->save($state);
          }
        }

        if (!empty($deploymentConfig->postDeploymentCommands) && !$state->isStepCompleted('post_deployment_completed')) {
          $this->info('Running post-deployment commands...');
          $sshService = new \App\Modules\ForgeDeployment\Services\SshService();
          $connected = $sshService->connect(
            $state->serverIp,
            22,
            'root',
            $sshPrivateKeyPath,
            $sshPrivateKeyPath . '.pub'
          );

          if ($connected) {
            $remotePath = '/var/www/' . $deploymentConfig->domain;

            $this->info('Configuring environment...');
            $this->deploymentService->configureEnvironment(BASE_PATH, $remotePath, $deploymentConfig->envVars, function (string $message) use ($outputCallback) {
              if ($outputCallback !== null) {
                $outputCallback("      {$message}");
              }
            }, $provisionConfig->toArray(), $provisionConfig->phpVersion);

            $this->deploymentService->runPostDeploymentCommands($remotePath, $deploymentConfig->postDeploymentCommands, $provisionConfig->phpVersion, $outputCallback);
            $state = $state->markStepCompleted('post_deployment_completed');
            $this->stateService->save($state);
          }
        }
      }

      $this->success('Deployment completed successfully!');
      $this->line("Server IP: {$state->serverIp}");
      $this->line("Domain: {$deploymentConfig->domain}");

      return 0;
    } catch (\Exception $e) {
      $this->error('Deployment failed: ' . $e->getMessage());
      return 1;
    }
  }

  private function waitForSsh(string $host, int $maxAttempts = 30): void
  {
    $attempts = 0;
    while ($attempts < $maxAttempts) {
      $connection = @fsockopen($host, 22, $errno, $errstr, 2);
      if ($connection) {
        fclose($connection);
        sleep(2);
        break;
      }
      $attempts++;
      sleep(2);
    }

    if ($attempts >= $maxAttempts) {
      throw new \RuntimeException("SSH port 22 not accessible on {$host} after " . ($maxAttempts * 2) . " seconds");
    }
  }

  private function expandPath(string $path): string
  {
    if (str_starts_with($path, '~/')) {
      $home = $_SERVER['HOME'] ?? getenv('HOME') ?? '';
      if ($home !== '') {
        return $home . substr($path, 1);
      }
    }
    return $path;
  }
}
