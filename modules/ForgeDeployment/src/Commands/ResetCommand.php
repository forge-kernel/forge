<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

use App\Modules\ForgeDeployment\Services\DeploymentStateService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Services\TemplateGenerator;

#[Cli(
  command: 'forge-deployment:reset',
  description: 'Clear deployment state to start fresh',
  usage: 'forge-deployment:reset [--force]',
  examples: [
    'forge-deployment:reset',
    'forge-deployment:reset --force',
  ]
)]
final class ResetCommand extends Command
{
  use OutputHelper;

  #[Arg(name: 'force', description: 'Skip confirmation prompt', required: false)]
  private bool $force = false;

  public function __construct(
    private readonly DeploymentStateService $stateService,
    private readonly TemplateGenerator $templateGenerator
  ) {
  }

  public function execute(array $args): int
  {
    if (!$this->stateService->exists()) {
      $this->info('No deployment state found. Nothing to reset.');
      return 0;
    }

    $state = $this->stateService->load();

    if ($state !== null) {
      $this->warning('Current deployment state:');
      $this->line("  Server IP: {$state->serverIp}");
      $this->line("  Domain: {$state->domain}");
      $this->line("  Completed steps: " . count($state->completedSteps));
    }

    if (!$this->force) {
      $confirm = $this->templateGenerator->askQuestion('Are you sure you want to clear the deployment state? (y/n)', 'n');
      if (strtolower($confirm) !== 'y') {
        $this->info('Operation cancelled.');
        return 0;
      }
    }

    if ($this->stateService->clear()) {
      $this->success('Deployment state cleared successfully.');
      $this->info('You can now run forge-deployment:deploy to start a new deployment.');
      return 0;
    } else {
      $this->error('Failed to clear deployment state.');
      return 1;
    }
  }
}
