<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

use App\Modules\ForgeDeployment\Services\DeploymentConfigReader;
use App\Modules\ForgeDeployment\Services\DeploymentService;
use App\Modules\ForgeDeployment\Services\DeploymentStateService;
use App\Modules\ForgeDeployment\Services\GitDiffService;
use App\Modules\ForgeDeployment\Services\IncrementalUploadService;
use App\Modules\ForgeDeployment\Services\SshService;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Services\TemplateGenerator;

#[Cli(
  command: 'forge-deployment:rollback',
  description: 'Rollback the latest deployment to the previous commit',
  usage: 'forge-deployment:rollback [--skip-commands]',
  examples: [
    'forge-deployment:rollback',
    'forge-deployment:rollback --skip-commands',
  ]
)]
final class RollbackCommand extends Command
{
  use OutputHelper;
  use Wizard;

  #[Arg(name: 'skip-commands', description: 'Skip post-deployment commands', required: false)]
  private bool $skipCommands = false;

  #[Arg(name: 'skip-confirmation', description: 'Skip confirmation prompt (for non-interactive use)', required: false)]
  private bool $skipConfirmation = false;

  public function __construct(
    private readonly DeploymentStateService $stateService,
    private readonly DeploymentService $deploymentService,
    private readonly DeploymentConfigReader $configReader,
    private readonly SshService $sshService,
    private readonly GitDiffService $gitDiffService,
    private readonly IncrementalUploadService $incrementalUploadService,
    private readonly TemplateGenerator $templateGenerator
  ) {
  }

  public function execute(array $args): int
  {
    $this->wizard($args);

    try {
      $state = $this->stateService->load();

      if ($state === null) {
        $this->error('No deployment state found. Run forge-deployment:deploy to start a new deployment.');
        return 1;
      }

      if ($state->serverIp === null || $state->domain === null) {
        $this->error('Invalid deployment state: missing server IP or domain.');
        return 1;
      }

      if (!$this->stateService->validate($state)) {
        $this->error('Cannot rollback: Server is not accessible. Please check the server status.');
        return 1;
      }

      if (!$this->gitDiffService->isGitRepository()) {
        $this->error('Not a git repository. Rollback requires git history.');
        return 1;
      }

      if ($state->lastDeployedCommit === null) {
        $this->error('No previous deployment commit found. Cannot rollback.');
        return 1;
      }

      $this->info('Rolling back deployment...');
      $this->info("Server IP: {$state->serverIp}");
      $this->info("Domain: {$state->domain}");
      $this->info("Last deployed commit: {$state->lastDeployedCommit}");

      // Get parent commit
      $parentCommit = $this->gitDiffService->getParentCommit($state->lastDeployedCommit);

      if ($parentCommit === null) {
        $this->error('Cannot find parent commit. This might be the first commit.');
        return 1;
      }

      $parentCommitMessage = $this->gitDiffService->getCommitMessage($parentCommit);
      $currentCommitMessage = $this->gitDiffService->getCommitMessage($state->lastDeployedCommit);

      $this->info("Parent commit: {$parentCommit}");
      if ($parentCommitMessage !== null) {
        $this->info("Parent commit message: {$parentCommitMessage}");
      }
      if ($currentCommitMessage !== null) {
        $this->info("Current commit message: {$currentCommitMessage}");
      }

      // Confirm rollback (skip if --skip-confirmation is set)
      if (!$this->skipConfirmation) {
      $confirm = $this->templateGenerator->askQuestion(
        'Are you sure you want to rollback to the previous commit? (y/n)',
        'n'
      );

      if (!in_array(strtolower($confirm), ['y', 'yes', '1', 'true'], true)) {
        $this->info('Rollback cancelled.');
        return 0;
        }
      }

      // Get files that changed between parent and last deployed commit
      $changedFiles = $this->gitDiffService->getChangedFilesBetweenCommits($parentCommit, $state->lastDeployedCommit);

      if (empty($changedFiles)) {
        $this->warning('No files changed between commits. Nothing to rollback.');
      } else {
        $this->info('Found ' . count($changedFiles) . ' file(s) to rollback');

        $sshPrivateKeyPath = $this->expandPath($state->sshKeyPath ?? '~/.ssh/id_rsa');

        $this->info('Connecting to server...');
        $connected = $this->sshService->connect(
          $state->serverIp,
          22,
          'root',
          $sshPrivateKeyPath,
          $sshPrivateKeyPath . '.pub'
        );

        if (!$connected) {
          $this->error('Failed to connect to server. Please check your SSH key and server accessibility.');
          return 1;
        }

        $remotePath = '/var/www/' . $state->domain;

        $progressCallback = function (string $message) {
          $this->line('  ' . $message);
        };

        $this->info('Rolling back files...');
        $this->incrementalUploadService->uploadFilesFromCommit(
          $changedFiles,
          $parentCommit,
          $remotePath,
          $progressCallback
        );
        $this->success('Files rolled back successfully');
      }

      // Update deployment state with parent commit
      $state = $state->withLastDeployedCommit($parentCommit);
      $state = $state->markStepCompleted('project_uploaded');
      $this->stateService->save($state);

      // Run post-deployment commands if configured
      $fileConfig = $this->configReader->readConfig(null);
      $deploymentConfig = null;

      if ($fileConfig !== null && isset($fileConfig['deployment'])) {
        $deploymentConfig = \App\Modules\ForgeDeployment\Dto\DeploymentConfig::fromArray($fileConfig['deployment']);

        // Get PHP version from provision config or state
        $phpVersion = '8.4';
        if ($fileConfig !== null && isset($fileConfig['provision']['php_version'])) {
          $phpVersion = $fileConfig['provision']['php_version'];
        } elseif (isset($state->config['php_version'])) {
          $phpVersion = $state->config['php_version'];
        }

        if (!$this->skipCommands && !empty($deploymentConfig->postDeploymentCommands)) {
          $this->info('Running post-deployment commands...');
          $this->deploymentService->runPostDeploymentCommands($remotePath, $deploymentConfig->postDeploymentCommands, $phpVersion);
          $this->success('Post-deployment commands completed');
        } else {
          $this->info('Skipping post-deployment commands');
        }
      }

      $this->success('Rollback completed successfully!');
      $this->line("Server IP: {$state->serverIp}");
      $this->line("Domain: {$state->domain}");
      $this->line("Rolled back to commit: {$parentCommit}");

      return 0;
    } catch (\Exception $e) {
      $this->error('Rollback failed: ' . $e->getMessage());
      return 1;
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
