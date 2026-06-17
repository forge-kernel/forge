<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

  use App\Modules\ForgeDeployment\Contracts\ProviderInterface;
use App\Modules\ForgeDeployment\Dto\DeploymentState;
use App\Modules\ForgeDeployment\Providers\DigitalOceanProvider;
use App\Modules\ForgeDeployment\Services\CloudflareService;
use App\Modules\ForgeDeployment\Services\DeploymentStateService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Config\Config;
use Forge\Core\Services\TemplateGenerator;

#[Cli(
  command: 'forge-deployment:delete-server',
  description: 'Delete deployed server and all related resources',
  usage: 'forge-deployment:delete-server [--skip-confirmation]',
  examples: [
    'forge-deployment:delete-server',
    'forge-deployment:delete-server --skip-confirmation'
  ]
)]
final class DeleteServerCommand extends Command
{
  use OutputHelper;
  use Wizard;

  public function __construct(
    private readonly DeploymentStateService $stateService,
    private readonly TemplateGenerator $templateGenerator,
    private readonly Config $config
  ) {
  }

  public function execute(array $args): int
  {
    $state = $this->stateService->load();

    if ($state === null) {
      $this->error('No deployment state found. Nothing to delete.');
      return 1;
    }

    if (!in_array('--skip-confirmation', $args)) {
      $this->line('');
      $this->line('⚠️  WARNING: This will permanently delete:');
      $this->line('   • The server (' . $state->serverIp . ')');
      $this->line('   • DNS records from Cloudflare');
      $this->line('   • Deployment state file');
      $this->line('   • All deployment logs');
      $this->line('');
      $this->line('This action cannot be undone.');
      $this->line('');

      $confirm = $this->templateGenerator->askQuestion('Type "delete" to confirm deletion:', '');
      if ($confirm !== 'delete') {
        $this->info('Operation cancelled.');
        return 0;
      }
    }

    try {
      $this->info('Starting server deletion process...');
      
      $progress = function (string $message) {
        $this->line($message);
      };

      $this->deleteServerResources($state, $progress);

      $this->line('');
      $this->success('Server and all related resources have been deleted successfully.');
      return 0;
    } catch (\Exception $e) {
      $this->error('Failed to delete server: ' . $e->getMessage());
      return 1;
    }
  }

  private function deleteServerResources(DeploymentState $state, callable $progress): void
  {
    $progress('→ Initializing deletion process...');
    $this->saveDeletionStep('initializing');

    if ($state->serverId !== null) {
      $progress('→ Deleting server from cloud provider...');
      $provider = $this->getProvider();
      $provider->deleteServer($state->serverId);
      $progress('✓ Server deleted successfully');
      $this->saveDeletionStep('server_deleted');
    }

    if ($state->domain !== null && $state->domain !== '') {
      $progress('→ Removing DNS records from Cloudflare...');
      try {
        $cloudflareService = $this->getCloudflareService();
        $cloudflareService->deleteDnsRecords($state->domain);
        $progress('✓ DNS records removed successfully');
        $this->saveDeletionStep('dns_removed');
      } catch (\Exception $e) {
        $progress('⚠ Failed to remove DNS records: ' . $e->getMessage());
        $this->saveDeletionStep('dns_failed');
      }
    }

    $progress('→ Removing deployment state...');
    $this->stateService->clear();
    $progress('✓ Deployment state cleared');
    $this->saveDeletionStep('state_cleared');

    $progress('→ Removing deployment logs...');
    $this->removeDeploymentLogs();
    $progress('✓ Deployment logs removed');
    $this->saveDeletionStep('logs_removed');

    $progress('→ Finalizing cleanup...');
    $this->saveDeletionStep('completed');

    $progress('→ Completely clearing deployment state...');
    $this->stateService->clear();
  }

  private function saveDeletionStep(string $step): void
  {
    $existingState = $this->stateService->load();
    if ($existingState !== null) {
      $completedSteps = $existingState->completedSteps;
      if (!in_array('deletion_' . $step, $completedSteps)) {
        $completedSteps[] = 'deletion_' . $step;
        $updatedState = new DeploymentState(
          $existingState->serverIp,
          $existingState->serverId,
          $existingState->sshKeyPath,
          $existingState->domain,
          $completedSteps,
          'Deleting server: ' . $step,
          date('c'),
          $existingState->config,
          $existingState->lastDeployedCommit
        );
        $this->stateService->save($updatedState);
      }
    }
  }

  private function getProvider(): ProviderInterface
  {
    $apiToken = $this->config->get("forge_deployment.digitalocean.api_token");
    if (empty($apiToken)) {
      $apiToken = $this->templateGenerator->askQuestion("Enter DigitalOcean API token", '');
      if (empty($apiToken)) {
        throw new \RuntimeException('API token is required');
      }
    }

    return new DigitalOceanProvider($apiToken);
  }

  private function getCloudflareService(): CloudflareService
  {
    $cloudflareToken = $this->config->get("forge_deployment.cloudflare.api_token");
    if (empty($cloudflareToken)) {
      $cloudflareToken = $this->templateGenerator->askQuestion("Enter Cloudflare API token", '');
      if (empty($cloudflareToken)) {
        throw new \RuntimeException('Cloudflare API token is required');
      }
    }

    return new CloudflareService($cloudflareToken);
  }

  private function removeDeploymentLogs(): void
  {
    $logsDir = BASE_PATH . '/storage/framework/deployments';
    
    if (is_dir($logsDir)) {
      $files = glob($logsDir . '/deploy-*.log');
      foreach ($files as $file) {
        if (is_file($file)) {
          unlink($file);
        }
      }
    }
  }
}