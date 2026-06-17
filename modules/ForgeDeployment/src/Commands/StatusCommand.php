<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

use App\Modules\ForgeDeployment\Services\DeploymentStateService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;

#[Cli(
  command: 'forge-deployment:status',
  description: 'Show current deployment state',
  usage: 'forge-deployment:status',
  examples: [
    'forge-deployment:status',
  ]
)]
final class StatusCommand extends Command
{
  use OutputHelper;

  public function __construct(
    private readonly DeploymentStateService $stateService
  ) {
  }

  public function execute(array $args): int
  {
    $state = $this->stateService->load();

    if ($state === null) {
      $this->info('No deployment state found.');
      $this->info('Run forge-deployment:deploy to start a new deployment.');
      return 0;
    }

    $this->info('Deployment Status');
    $this->line('─────────────────');
    $this->line('');

    if ($state->serverIp !== null) {
      $this->line("Server IP: {$state->serverIp}");
    }

    if ($state->serverId !== null) {
      $this->line("Server ID: {$state->serverId}");
    }

    if ($state->domain !== null) {
      $this->line("Domain: {$state->domain}");
    }

    if ($state->lastUpdated !== null) {
      $this->line("Last Updated: {$state->lastUpdated}");
    }

    $this->line('');
    $this->info('Completed Steps: ' . count($state->completedSteps));

    if (!empty($state->completedSteps)) {
      foreach ($state->completedSteps as $step) {
        $this->success("  ✓ {$step}");
      }
    }

    $allSteps = [
      'server_created',
      'ssh_connected',
      'system_provisioned',
      'php_installed',
      'database_installed',
      'nginx_installed',
      'project_uploaded',
      'site_configured',
      'dns_configured',
      'ssl_configured',
      'post_deployment_completed',
    ];

    $remainingSteps = array_diff($allSteps, $state->completedSteps);
    if (!empty($remainingSteps)) {
      $this->line('');
      $this->warning('Remaining Steps: ' . count($remainingSteps));
      foreach ($remainingSteps as $step) {
        $this->comment("  ⏳ {$step}");
      }
    }

    if ($state->currentStep !== null) {
      $this->line('');
      $this->info("Current Step: {$state->currentStep}");
    }

    if (!empty($state->config)) {
      $this->line('');
      $this->info('Configuration:');
      foreach ($state->config as $key => $value) {
        $this->line("  {$key}: {$value}");
      }
    }

    $isAccessible = $this->stateService->validate($state);
    $this->line('');
    if ($isAccessible) {
      $this->success('Server Status: Accessible');
    } else {
      $this->error('Server Status: Not Accessible');
    }

    return 0;
  }
}
