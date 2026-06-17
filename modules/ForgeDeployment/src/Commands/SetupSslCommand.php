<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

use App\Modules\ForgeDeployment\Services\LetsEncryptService;
use App\Modules\ForgeDeployment\Services\SshKeyManager;
use App\Modules\ForgeDeployment\Services\SshService;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Services\TemplateGenerator;

#[Cli(
  command: 'forge-deployment:setup-ssl',
  description: 'Setup SSL certificate with Let\'s Encrypt',
  usage: 'forge-deployment:setup-ssl [--host=ip] [--domain=example.com] [--ssh-key=path]',
  examples: [
    'forge-deployment:setup-ssl --host=1.2.3.4 --domain=example.com',
  ]
)]
final class SetupSslCommand extends Command
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

  public function __construct(
    private readonly TemplateGenerator $templateGenerator,
    private readonly SshKeyManager $sshKeyManager,
    private readonly SshService $sshService,
    private readonly LetsEncryptService $letsEncryptService
  ) {
  }

  public function execute(array $args): int
  {
    $this->wizard($args);

    try {
      if (empty($this->host)) {
        $this->host = $this->templateGenerator->askQuestion('Server IP address', '');
      }

      if (empty($this->domain)) {
        $this->domain = $this->templateGenerator->askQuestion('Domain name', '');
      }

      $email = $this->templateGenerator->askQuestion('Email for Let\'s Encrypt', 'admin@' . $this->domain);
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

      $this->info('Setting up SSL certificate...');
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

      $this->letsEncryptService->setupSsl($this->domain, $email, $outputCallback, $errorCallback);

      $remotePath = '/var/www/' . $this->domain;
      $this->info('Updating Nginx configuration...');
      $this->letsEncryptService->updateNginxConfig($this->domain, $remotePath, $this->phpVersion, $outputCallback);

      $this->success('SSL certificate configured successfully!');
      $this->line("Domain: {$this->domain}");
      $this->line("URL: https://{$this->domain}");

      return 0;
    } catch (\Exception $e) {
      $this->error('SSL setup failed: ' . $e->getMessage());
      return 1;
    }
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
