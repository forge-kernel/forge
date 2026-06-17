<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

use App\Modules\ForgeDeployment\Services\DeploymentConfigReader;
use App\Modules\ForgeDeployment\Services\DeploymentService;
use App\Modules\ForgeDeployment\Services\DeploymentStateService;
use App\Modules\ForgeDeployment\Services\SshService;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Traits\Wizard;

#[Cli(
  command: 'forge-deployment:deploy-env',
  description: 'Deploy .env file to the server with merged environment variables',
  usage: 'forge-deployment:deploy-env [--host=ip] [--domain=example.com] [--ssh-key=path]',
  examples: [
    'forge-deployment:deploy-env',
    'forge-deployment:deploy-env --host=1.2.3.4 --domain=example.com',
  ]
)]
final class DeployEnvCommand extends Command
{
  use OutputHelper;
  use Wizard;

  #[Arg(name: 'host', description: 'Server IP address', required: false)]
  private ?string $host = null;

  #[Arg(name: 'domain', description: 'Domain name', required: false)]
  private ?string $domain = null;

  #[Arg(name: 'ssh-key', description: 'SSH private key path', required: false)]
  private ?string $sshKey = null;

  public function __construct(
    private readonly DeploymentStateService $stateService,
    private readonly DeploymentService $deploymentService,
    private readonly DeploymentConfigReader $configReader,
    private readonly SshService $sshService
  ) {
  }

  public function execute(array $args): int
  {
    $this->wizard($args);

    try {
      $state = null;
      $serverIp = null;
      $domain = null;
      $sshPrivateKeyPath = null;
      $phpVersion = '8.4';

      if ($this->host !== null && $this->host !== '' && $this->domain !== null && $this->domain !== '') {
        $serverIp = $this->host;
        $domain = $this->domain;
        $sshPrivateKeyPath = $this->sshKey ? $this->expandPath($this->sshKey) : $this->expandPath('~/.ssh/id_rsa');
      } else {
        $state = $this->stateService->load();

        if ($state === null) {
          $this->error('No deployment state found. Run forge-deployment:deploy to start a new deployment, or provide --host and --domain arguments.');
          return 1;
        }

        if ($state->serverIp === null || $state->domain === null) {
          $this->error('Invalid deployment state: missing server IP or domain.');
          return 1;
        }

        if (!$this->stateService->validate($state)) {
          $this->error('Cannot deploy env: Server is not accessible. Please check the server status.');
          return 1;
        }

        $serverIp = $state->serverIp;
        $domain = $state->domain;
        $sshPrivateKeyPath = $this->expandPath($state->sshKeyPath ?? '~/.ssh/id_rsa');
        $phpVersion = $state->config['php_version'] ?? '8.4';
      }

      $this->info('Deploying environment file...');
      $this->info("Server IP: {$serverIp}");
      $this->info("Domain: {$domain}");

      $fileConfig = $this->configReader->readConfig(null);
      if ($fileConfig === null || !isset($fileConfig['deployment'])) {
        $this->error('No deployment configuration found. Please create a forge-deployment.php file.');
        return 1;
      }

      $deploymentConfig = $this->configReader->getDeploymentConfig($fileConfig);
      $envVars = $deploymentConfig['env_vars'] ?? [];

      if (isset($fileConfig['provision']['php_version'])) {
        $phpVersion = $fileConfig['provision']['php_version'];
      }

      $this->info('Connecting to server...');
      $connected = $this->sshService->connect(
        $serverIp,
        22,
        'root',
        $sshPrivateKeyPath,
        $sshPrivateKeyPath . '.pub'
      );

      if (!$connected) {
        $this->error('Failed to connect to server. Please check your SSH key and server accessibility.');
        return 1;
      }

      $remotePath = '/var/www/' . $domain;

      $outputCallback = function (string $line) {
        if (trim($line) !== '') {
          $this->line('      ' . trim($line));
        }
      };

      $this->info('Configuring environment...');
      $this->deploymentService->configureEnvironment(
        BASE_PATH,
        $remotePath,
        $envVars,
        $outputCallback,
        [],
        $phpVersion
      );

      $this->success('Environment file deployed successfully!');
      $this->line("Server IP: {$serverIp}");
      $this->line("Domain: {$domain}");

      return 0;
    } catch (\Exception $e) {
      $this->error('Deployment failed: ' . $e->getMessage());
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
