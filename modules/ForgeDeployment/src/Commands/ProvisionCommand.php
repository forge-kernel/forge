<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

use App\Modules\ForgeDeployment\Dto\ProvisionConfig;
use App\Modules\ForgeDeployment\Services\DatabaseProvisioner;
use App\Modules\ForgeDeployment\Services\NginxProvisioner;
use App\Modules\ForgeDeployment\Services\PhpProvisioner;
use App\Modules\ForgeDeployment\Services\SshKeyManager;
use App\Modules\ForgeDeployment\Services\SshService;
use App\Modules\ForgeDeployment\Services\SystemProvisioner;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Services\TemplateGenerator;

#[Cli(
  command: 'forge-deployment:provision',
  description: 'Provision an existing server',
  usage: 'forge-deployment:provision [--host=ip] [--ssh-key=path] [--ram=1024]',
  examples: [
    'forge-deployment:provision --host=1.2.3.4',
  ]
)]
final class ProvisionCommand extends Command
{
  use Wizard;

  #[Arg(name: 'host', description: 'Server IP address')]
  private string $host = '';

  #[Arg(name: 'ssh-key', description: 'SSH private key path', required: false)]
  private ?string $sshKey = null;

  #[Arg(name: 'ram', description: 'Server RAM in MB', default: '1024')]
  private string $ram = '1024';

  public function __construct(
    private readonly TemplateGenerator $templateGenerator,
    private readonly SshKeyManager $sshKeyManager,
    private readonly SshService $sshService,
    private readonly SystemProvisioner $systemProvisioner,
    private readonly PhpProvisioner $phpProvisioner,
    private readonly DatabaseProvisioner $databaseProvisioner,
    private readonly NginxProvisioner $nginxProvisioner
  ) {
  }

  public function execute(array $args): int
  {
    $this->wizard($args);

    try {
      if (empty($this->host)) {
        $this->host = $this->templateGenerator->askQuestion('Server IP address', '');
      }

      $provisionConfig = $this->getProvisionConfig();
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

      $ramMb = (int) $this->ram;

      $this->info('Provisioning system...');
      $this->systemProvisioner->provision($ramMb);

      $this->info('Provisioning PHP...');
      $this->phpProvisioner->provision($provisionConfig->phpVersion, $ramMb);

      $this->info('Provisioning database...');
      $this->databaseProvisioner->provision($provisionConfig->databaseType, $provisionConfig->databaseVersion, $ramMb);

      $this->info('Provisioning Nginx...');
      $this->nginxProvisioner->provision($provisionConfig->phpVersion, $ramMb);

      $this->success('Server provisioned successfully!');

      return 0;
    } catch (\Exception $e) {
      $this->error('Provisioning failed: ' . $e->getMessage());
      return 1;
    }
  }

  private function getProvisionConfig(): ProvisionConfig
  {
    $phpVersions = ['8.0', '8.1', '8.2', '8.3', '8.4'];
    $phpVersion = $this->templateGenerator->selectFromList('Select PHP version', $phpVersions, '8.4');

    $dbTypes = ['mysql', 'postgresql'];
    $dbType = $this->templateGenerator->selectFromList('Select database type', $dbTypes, 'mysql');

    $dbVersion = null;
    if ($dbType === 'mysql') {
      $dbVersions = ['5.7', '8.0'];
      $dbVersion = $this->templateGenerator->selectFromList('Select MySQL version', $dbVersions, '8.0');
    } elseif ($dbType === 'postgresql') {
      $dbVersions = ['13', '14', '15', '16'];
      $dbVersion = $this->templateGenerator->selectFromList('Select PostgreSQL version', $dbVersions, '16');
    }

    return new ProvisionConfig($phpVersion, $dbType, $dbVersion);
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
