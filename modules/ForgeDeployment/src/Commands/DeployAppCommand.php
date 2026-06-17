<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

use App\Modules\ForgeDeployment\Dto\DeploymentConfig;
use App\Modules\ForgeDeployment\Services\DeploymentService;
use App\Modules\ForgeDeployment\Services\DeploymentConfigReader;
use App\Modules\ForgeDeployment\Services\GitDiffService;
use App\Modules\ForgeDeployment\Services\NginxProvisioner;
use App\Modules\ForgeDeployment\Services\SshKeyManager;
use App\Modules\ForgeDeployment\Services\SshService;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Services\TemplateGenerator;

#[Cli(
  command: 'forge-deployment:deploy-app',
  description: 'Deploy application to provisioned server',
  usage: 'forge-deployment:deploy-app [--host=ip] [--domain=example.com] [--ssh-key=path]',
  examples: [
    'forge-deployment:deploy-app --host=1.2.3.4 --domain=example.com',
  ]
)]
final class DeployAppCommand extends Command
{
  use Wizard;

  #[Arg(name: 'host', description: 'Server IP address')]
  private string $host = '';

  #[Arg(name: 'domain', description: 'Domain name')]
  private string $domain = '';

  #[Arg(name: 'ssh-key', description: 'SSH private key path', required: false)]
  private ?string $sshKey = null;

  #[Arg(name: 'php-version', description: 'PHP version', default: '8.3')]
  private string $phpVersion = '8.3';

  #[Arg(name: 'config', description: 'Deployment config file path', required: false)]
  private ?string $config = null;

  public function __construct(
    private readonly TemplateGenerator $templateGenerator,
    private readonly SshKeyManager $sshKeyManager,
    private readonly SshService $sshService,
    private readonly DeploymentService $deploymentService,
    private readonly NginxProvisioner $nginxProvisioner,
    private readonly DeploymentConfigReader $configReader,
    private readonly GitDiffService $gitDiffService
  ) {
  }

  public function execute(array $args): int
  {
    $this->wizard($args);

    try {
      if (empty($this->host)) {
        $this->host = $this->templateGenerator->askQuestion('Server IP address', '');
      }

      $fileConfig = $this->configReader->readConfig($this->config);
      $hasConfig = $fileConfig !== null;

      if ($hasConfig) {
        $this->info('Found deployment configuration file');
        $deploymentFileConfig = $this->configReader->getDeploymentConfig($fileConfig);
        if ($deploymentFileConfig && !empty($deploymentFileConfig['domain'])) {
          $this->domain = $deploymentFileConfig['domain'];
        }
        if ($deploymentFileConfig && !empty($deploymentFileConfig['php_version'])) {
          $this->phpVersion = $deploymentFileConfig['php_version'];
        }
      }

      if (empty($this->domain)) {
        $this->domain = $this->templateGenerator->askQuestion('Domain name', '');
      }

      $deploymentConfig = $this->getDeploymentConfig($fileConfig);
      $sshPrivateKeyPath = $this->getSshPrivateKeyPath();

      $this->info('Connecting to server...');
      $connected = $this->sshService->connect(
        $this->host,
        22,
        'root',
        $sshPrivateKeyPath,
        $sshPrivateKeyPath . '.pub'
      );

      if (!$connected) {
        throw new \RuntimeException('Failed to connect to server');
      }

      $remotePath = '/var/www/' . $this->domain;

      $this->info('Deploying application...');
      $this->deploymentService->deploy(
        BASE_PATH,
        $remotePath,
        $deploymentConfig->commands,
        $deploymentConfig->envVars
      );

      $this->info('Configuring Nginx...');
      $this->nginxProvisioner->createSiteConfig($this->domain, $remotePath, $this->phpVersion);

      if (!empty($deploymentConfig->postDeploymentCommands)) {
        $this->info('Running post-deployment commands...');
        $this->deploymentService->runPostDeploymentCommands($remotePath, $deploymentConfig->postDeploymentCommands, $this->phpVersion);
      }

      // Save commit hash after successful deployment (if using deployment state)
      // Note: DeployAppCommand doesn't use DeploymentState, so we skip this for now
      // If needed in the future, we can add state tracking to this command

      $this->success('Application deployed successfully!');

      return 0;
    } catch (\Exception $e) {
      $this->error('Deployment failed: ' . $e->getMessage());
      return 1;
    }
  }

  private function getDeploymentConfig(?array $fileConfig): DeploymentConfig
  {
    $deploymentFileConfig = $fileConfig ? $this->configReader->getDeploymentConfig($fileConfig) : null;

    if ($deploymentFileConfig !== null && !empty($deploymentFileConfig)) {
      $commands = $deploymentFileConfig['commands'] ?? [];
      $postDeploymentCommands = $deploymentFileConfig['post_deployment_commands'] ?? [];
      $envVars = $deploymentFileConfig['env_vars'] ?? [];

      if (!empty($commands) || !empty($postDeploymentCommands)) {
        $this->info("Using deployment commands from config file");
        return new DeploymentConfig($this->domain, $commands, $envVars, $postDeploymentCommands);
      }
    }

    $commands = [];
    $addCommand = $this->templateGenerator->askQuestion('Add deployment command? (y/n)', 'n');
    while (strtolower($addCommand) === 'y') {
      $command = $this->templateGenerator->askQuestion('Enter command', '');
      if (!empty($command)) {
        $commands[] = $command;
      }
      $addCommand = $this->templateGenerator->askQuestion('Add another command? (y/n)', 'n');
    }

    $this->info('Post-deployment commands run after everything is ready (project uploaded, configured, etc.)');
    $this->info('Examples: cache:flush, migrate, seed, etc.');
    $postDeploymentCommands = [];
    $addPostCommand = $this->templateGenerator->askQuestion('Add post-deployment command? (y/n)', 'n');
    while (strtolower($addPostCommand) === 'y') {
      $command = $this->templateGenerator->askQuestion('Enter command (e.g., cache:flush or php forge.php cache:flush)', '');
      if (!empty($command)) {
        $postDeploymentCommands[] = $command;
      }
      $addPostCommand = $this->templateGenerator->askQuestion('Add another post-deployment command? (y/n)', 'n');
    }

    return new DeploymentConfig($this->domain, $commands, [], $postDeploymentCommands);
  }

  private function getSshPrivateKeyPath(): string
  {
    if ($this->sshKey !== null) {
      return $this->expandPath($this->sshKey);
    }

    $publicKeyPath = $this->sshKeyManager->locatePublicKey();
    if ($publicKeyPath !== null) {
      return str_replace('.pub', '', $publicKeyPath);
    }

    return $this->expandPath('~/.ssh/id_rsa');
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
