<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

use App\Modules\ForgeDeployment\Contracts\ProviderInterface;
use App\Modules\ForgeDeployment\Dto\DeploymentConfig;
use App\Modules\ForgeDeployment\Dto\DeploymentState;
use App\Modules\ForgeDeployment\Dto\ProvisionConfig;
use App\Modules\ForgeDeployment\Dto\ServerConfig;
use App\Modules\ForgeDeployment\Providers\DigitalOceanProvider;
use App\Modules\ForgeDeployment\Services\DeploymentConfigReader;
use App\Modules\ForgeDeployment\Services\DeploymentService;
use App\Modules\ForgeDeployment\Services\DeploymentStateService;
use App\Modules\ForgeDeployment\Services\ForgeDeploymentService;
use App\Modules\ForgeDeployment\Services\GitDiffService;
use App\Modules\ForgeDeployment\Services\LetsEncryptService;
use App\Modules\ForgeDeployment\Services\SshKeyManager;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Config\Config;
use Forge\Core\Services\TemplateGenerator;

#[Cli(
  command: 'forge-deployment:deploy',
  description: 'Full wizard-driven deployment to cloud provider',
  usage: 'forge-deployment:deploy [--provider=digitalocean] [--ssh-key=path] [--config=path]',
  examples: [
    'forge-deployment:deploy',
    'forge-deployment:deploy --provider=digitalocean',
    'forge-deployment:deploy --config=./deployment.php',
  ]
)]
final class DeployCommand extends Command
{
  use Wizard;

  #[Arg(name: 'provider', description: 'Cloud provider', default: 'digitalocean')]
  private string $provider = 'digitalocean';

  #[Arg(name: 'ssh-key', description: 'SSH public key path', required: false)]
  private ?string $sshKey = null;

  #[Arg(name: 'config', description: 'Deployment config file path', required: false)]
  private ?string $config = null;

  public function __construct(
    private readonly Config $configService,
    private readonly TemplateGenerator $templateGenerator,
    private readonly SshKeyManager $sshKeyManager,
    private readonly ForgeDeploymentService $forgeDeploymentService,
    private readonly DeploymentService $deploymentService,
    private readonly LetsEncryptService $letsEncryptService,
    private readonly DeploymentConfigReader $configReader,
    private readonly DeploymentStateService $stateService,
    private readonly GitDiffService $gitDiffService
  ) {
  }

  public function execute(array $args): int
  {
    $this->wizard($args);

    try {
      $this->info('Starting deployment wizard...');

      $existingState = $this->stateService->load();
      $resume = false;

      if ($existingState !== null) {
        $this->warning('Found previous deployment state.');
        $this->info("Server IP: {$existingState->serverIp}");
        $this->info("Domain: {$existingState->domain}");
        $this->info("Last updated: {$existingState->lastUpdated}");
        $this->info("Completed steps: " . count($existingState->completedSteps));

        $resume = $this->templateGenerator->askQuestion('Resume previous deployment? (y/n)', 'y') === 'y';

        if (!$resume) {
          $this->stateService->clear();
          $existingState = null;
        } else {
          if (!$this->stateService->validate($existingState)) {
            $this->error('Cannot resume: Server is not accessible. Please check the server status.');
            return 1;
          }
        }
      }

      $serverId = null;
      $serverInfo = null;
      $state = $existingState;
      $provider = null;
      $serverConfig = null;
      $provisionConfig = null;
      $deploymentConfig = null;

      if ($resume && $existingState !== null) {
        $this->info('Resuming deployment...');
        $serverInfo = ['ipv4' => $existingState->serverIp];
        $serverId = $existingState->serverId;

        $fileConfig = $this->configReader->readConfig($this->config);
        $provisionConfigData = $fileConfig ? $this->configReader->getProvisionConfig($fileConfig) : null;
        if ($provisionConfigData !== null && !empty($provisionConfigData)) {
          $phpVersion = $provisionConfigData['php_version'] ?? $state->config['php_version'] ?? '8.4';
          $dbType = $provisionConfigData['database_type'] ?? $state->config['database_type'] ?? 'mysql';
          $dbVersion = $provisionConfigData['database_version'] ?? $state->config['database_version'] ?? '8.0';
          $dbName = $provisionConfigData['database_name'] ?? $state->config['database_name'] ?? null;
          $dbUser = $provisionConfigData['database_user'] ?? $state->config['database_user'] ?? null;
          $dbPass = $provisionConfigData['database_password'] ?? $state->config['database_password'] ?? null;
          $provisionConfig = new ProvisionConfig($phpVersion, $dbType, $dbVersion, $dbName, $dbUser, $dbPass);
        } else {
          $provisionConfig = new ProvisionConfig(
            $state->config['php_version'] ?? '8.4',
            $state->config['database_type'] ?? 'mysql',
            $state->config['database_version'] ?? '8.0',
            $state->config['database_name'] ?? null,
            $state->config['database_user'] ?? null,
            $state->config['database_password'] ?? null
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
      } else {
        $fileConfig = $this->configReader->readConfig($this->config);
        $hasConfig = $fileConfig !== null;

        if ($hasConfig) {
          $this->info('Found deployment configuration file');
        }

        $provider = $this->getProvider();
        $sshPublicKey = $this->getSshPublicKey();
        $serverConfig = $this->getServerConfig($provider, $fileConfig);
        $provisionConfig = $this->getProvisionConfig($fileConfig);
        $deploymentConfig = $this->getDeploymentConfig($fileConfig);

        $this->info('Creating server...');
        $serverId = $provider->createServer($serverConfig->toArray(), $sshPublicKey);

        $this->info('Waiting for server to be ready...');
        $serverInfo = $provider->waitForServer($serverId);
        $this->success("Server created: {$serverInfo['ipv4']}");

        $state = new DeploymentState(
          $serverInfo['ipv4'],
          (string) $serverId,
          $this->sshKey ? str_replace('.pub', '', $this->sshKey) : str_replace('.pub', '', $this->sshKeyManager->locatePublicKey() ?? '~/.ssh/id_rsa'),
          $deploymentConfig->domain,
          [],
          'server_created',
          date('c'),
          [
            'php_version' => $provisionConfig->phpVersion,
            'database_type' => $provisionConfig->databaseType,
            'database_version' => $provisionConfig->databaseVersion,
          ]
        );
        $this->stateService->save($state->markStepCompleted('server_created'));
      }

      $this->info('Waiting for SSH to be available...');
      $this->waitForSsh($serverInfo['ipv4'], 30);

      $sshPrivateKeyPath = $state->sshKeyPath ?? ($this->sshKey ? str_replace('.pub', '', $this->sshKey) : str_replace('.pub', '', $this->sshKeyManager->locatePublicKey() ?? '~/.ssh/id_rsa'));
      $sshPrivateKeyPath = $this->expandPath($sshPrivateKeyPath);

      $this->info('Connecting to server...');
      $sshService = new \App\Modules\ForgeDeployment\Services\SshService();
      $connected = $sshService->connect(
        $serverInfo['ipv4'],
        22,
        'root',
        $sshPrivateKeyPath,
        $sshPrivateKeyPath . '.pub'
      );

      if (!$connected) {
        throw new \RuntimeException("Failed to establish SSH connection to {$serverInfo['ipv4']}. Please check your SSH key and server accessibility.");
      }

      $this->info('Provisioning server...');
      $ramMb = $this->getRamFromSize($serverConfig->size ?? 's-1vcpu-1gb');

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

      if ($resume && $state !== null && $state->isStepCompleted('ssh_connected')) {
        $this->info('Reconnecting SSH for provisioning...');
        $sshService = new \App\Modules\ForgeDeployment\Services\SshService();
        $reconnected = $sshService->connect(
          $serverInfo['ipv4'],
          22,
          'root',
          $sshPrivateKeyPath,
          $sshPrivateKeyPath . '.pub'
        );
        if (!$reconnected) {
          throw new \RuntimeException("Failed to reconnect SSH to {$serverInfo['ipv4']}");
        }
      }

      $this->forgeDeploymentService->deployFull(
        $serverConfig,
        $provisionConfig,
        $deploymentConfig,
        $serverInfo['ipv4'],
        $sshPrivateKeyPath,
        $ramMb,
        fn(string $message) => $this->info($message),
        $outputCallback,
        $errorCallback,
        $state
      );

      $state = $this->stateService->load();

      $dnsConfigured = false;
      if ($state === null || !$state->isStepCompleted('dns_configured')) {
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
            $success = $cloudflareService->addDnsRecord($zoneId, $deploymentConfig->domain, $serverInfo['ipv4']);
            if ($success) {
              $this->success("DNS record added for {$deploymentConfig->domain}");
              $verified = $cloudflareService->verifyDnsRecord($zoneId, $deploymentConfig->domain, $serverInfo['ipv4']);
              if ($verified) {
                $this->info('Waiting for DNS propagation (30 seconds)...');
                sleep(30);
                $dnsConfigured = true;
                if ($state !== null) {
                  $state = $state->markStepCompleted('dns_configured');
                  $this->stateService->save($state);
                }
              } else {
                throw new \RuntimeException('Failed to verify DNS record creation');
              }
            } else {
              throw new \RuntimeException('Failed to create DNS record in Cloudflare');
            }
          } else {
            throw new \RuntimeException("Zone not found for domain: {$deploymentConfig->domain}. Please ensure the root domain is configured in Cloudflare.");
          }
        } else {
          $this->warning('DNS configuration skipped. SSL setup may fail without DNS.');
        }
      } else {
        $this->info('⏭ Skipping DNS configuration (already completed)');
        $dnsConfigured = true;
      }

      if ($state === null || !$state->isStepCompleted('ssl_configured')) {
        if (!$dnsConfigured) {
          throw new \RuntimeException('DNS must be configured before SSL setup. Please configure DNS in Cloudflare first.');
        }
        $this->info('Setting up SSL...');
        $email = $deploymentConfig->sslEmail ?? ('admin@' . $deploymentConfig->domain);

        $sshPrivateKeyPath = $this->expandPath($sshPrivateKeyPath);
        $connected = $this->letsEncryptService->connect(
          $serverInfo['ipv4'],
          22,
          'root',
          $sshPrivateKeyPath,
          $sshPrivateKeyPath . '.pub'
        );

        if (!$connected) {
          throw new \RuntimeException('Failed to connect to server for SSL setup');
        }

        $this->letsEncryptService->setupSsl($deploymentConfig->domain, $email, $outputCallback, $errorCallback);
        if ($state !== null) {
          $state = $state->markStepCompleted('ssl_configured');
          $this->stateService->save($state);
        }
      } else {
        $this->info('⏭ Skipping SSL setup (already completed)');
      }

      $remotePath = '/var/www/' . $deploymentConfig->domain;
      $this->letsEncryptService->updateNginxConfig($deploymentConfig->domain, $remotePath, $provisionConfig->phpVersion, $outputCallback);

      $this->info('Configuring environment...');
      $this->deploymentService->configureEnvironment(BASE_PATH, $remotePath, $deploymentConfig->envVars, function (string $message) use ($outputCallback) {
        if ($outputCallback !== null) {
          $outputCallback("      {$message}");
        }
      }, $provisionConfig->toArray(), $provisionConfig->phpVersion);

      if (!empty($deploymentConfig->postDeploymentCommands)) {
        if ($state === null || !$state->isStepCompleted('post_deployment_completed')) {
          $this->info('Running post-deployment commands...');
          $sshService = new \App\Modules\ForgeDeployment\Services\SshService();
          $connected = $sshService->connect(
            $serverInfo['ipv4'],
            22,
            'root',
            $sshPrivateKeyPath,
            $sshPrivateKeyPath . '.pub'
          );

          if ($connected) {
            $this->deploymentService->runPostDeploymentCommands($remotePath, $deploymentConfig->postDeploymentCommands, $provisionConfig->phpVersion, $outputCallback);
            if ($state !== null) {
              $state = $state->markStepCompleted('post_deployment_completed');
              $this->stateService->save($state);
            }
          } else {
            $this->warning('Failed to connect for post-deployment commands');
          }
        } else {
          $this->info('⏭ Skipping post-deployment commands (already completed)');
        }
      }

      // Save commit hash after successful deployment
      if ($state !== null && $this->gitDiffService->isGitRepository()) {
        $currentCommit = $this->gitDiffService->getCurrentCommitHash();
        if ($currentCommit !== null) {
          $state = $state->withLastDeployedCommit($currentCommit);
          $this->stateService->save($state);
        }
      }

      $this->success('Deployment completed successfully!');
      $this->line("Server IP: {$serverInfo['ipv4']}");
      $this->line("Domain: {$deploymentConfig->domain}");
      $this->line("URL: https://{$deploymentConfig->domain}");

      return 0;
    } catch (\Exception $e) {
      $this->error('Deployment failed: ' . $e->getMessage());
      return 1;
    }
  }

  private function getProvider(): ProviderInterface
  {
    $apiToken = $this->configService->get("forge_deployment.{$this->provider}.api_token");
    if (empty($apiToken)) {
      $apiToken = $this->templateGenerator->askQuestion("Enter {$this->provider} API token", '');
      if (empty($apiToken)) {
        throw new \RuntimeException('API token is required');
      }
      $this->configService->set("forge_deployment.{$this->provider}.api_token", $apiToken);
    }

    return new DigitalOceanProvider($apiToken);
  }

  private function getSshPublicKey(): ?string
  {
    $publicKey = $this->sshKeyManager->readPublicKey($this->sshKey);
    if ($publicKey === null) {
      $customPath = $this->templateGenerator->askQuestion('SSH public key path (leave empty for default)', '');
      $publicKey = $this->sshKeyManager->readPublicKey($customPath !== '' ? $customPath : null);
    }

    return $publicKey;
  }

  private function getServerConfig(ProviderInterface $provider, ?array $fileConfig): ServerConfig
  {
    $serverConfig = $fileConfig ? $this->configReader->getServerConfig($fileConfig) : null;

    if ($serverConfig !== null && !empty($serverConfig)) {
      $name = $serverConfig['name'] ?? 'forge-server-' . time();
      $region = $serverConfig['region'] ?? null;
      $size = $serverConfig['size'] ?? null;
      $image = $serverConfig['image'] ?? null;
      $sshKeyPath = $serverConfig['ssh_key_path'] ?? $this->sshKey;

      if ($region && $size && $image) {
        $this->info("Using server config from file: {$name}");
        return new ServerConfig($name, $region, $size, $image, $sshKeyPath);
      }
    }

    $regions = $provider->listRegions();
    $regionOptions = array_map(fn($r) => "{$r['name']} ({$r['slug']})", $regions);
    $selectedRegion = $this->templateGenerator->selectFromList('Select region', $regionOptions);
    $regionSlug = $regions[array_search($selectedRegion, $regionOptions)]['slug'];

    $sizes = $provider->listSizes();
    $sizeOptions = array_map(fn($s) => "{$s['slug']} - {$s['memory']}MB RAM, {$s['vcpus']} vCPU, \${$s['price_monthly']}/mo", $sizes);
    $selectedSize = $this->templateGenerator->selectFromList('Select server size', $sizeOptions);
    $sizeSlug = $sizes[array_search($selectedSize, $sizeOptions)]['slug'];

    $images = $provider->listImages();
    $imageOptions = array_map(fn($i) => "{$i['name']} ({$i['slug']})", $images);
    $selectedImage = $this->templateGenerator->selectFromList('Select OS image', $imageOptions);
    $imageSlug = $images[array_search($selectedImage, $imageOptions)]['slug'];

    $name = $this->templateGenerator->askQuestion('Server name', 'forge-server-' . time());

    return new ServerConfig($name, $regionSlug, $sizeSlug, $imageSlug, $this->sshKey);
  }

  private function getProvisionConfig(?array $fileConfig): ProvisionConfig
  {
    $provisionConfig = $fileConfig ? $this->configReader->getProvisionConfig($fileConfig) : null;

    if ($provisionConfig !== null && !empty($provisionConfig)) {
      $phpVersion = $provisionConfig['php_version'] ?? null;
      $dbType = $provisionConfig['database_type'] ?? null;
      $dbVersion = $provisionConfig['database_version'] ?? null;
      $dbName = $provisionConfig['database_name'] ?? null;
      $dbUser = $provisionConfig['database_user'] ?? null;
      $dbPass = $provisionConfig['database_password'] ?? null;

      if ($phpVersion && $dbType) {
        $this->info("Using provision config from file: PHP {$phpVersion}, {$dbType}");
        return new ProvisionConfig($phpVersion, $dbType, $dbVersion, $dbName, $dbUser, $dbPass);
      }
    }

    $phpVersions = ['8.0', '8.1', '8.2', '8.3', '8.4'];
    $phpVersion = $this->templateGenerator->selectFromList('Select PHP version', $phpVersions, '8.4');

    $dbTypes = ['mysql', 'postgresql', 'sqlite'];
    $dbType = $this->templateGenerator->selectFromList('Select database type', $dbTypes, 'mysql');

    $dbVersion = null;
    $dbName = null;
    $dbUser = null;
    $dbPass = null;

    if ($dbType === 'mysql') {
      $dbVersions = ['5.7', '8.0'];
      $dbVersion = $this->templateGenerator->selectFromList('Select MySQL version', $dbVersions, '8.0');
    } elseif ($dbType === 'postgresql') {
      $dbVersions = ['13', '14', '15', '16'];
      $dbVersion = $this->templateGenerator->selectFromList('Select PostgreSQL version', $dbVersions, '16');
    }

    if ($dbType !== 'sqlite') {
      $dbName = $this->templateGenerator->askQuestion('Database name', 'forge_app');
      $dbUser = $this->templateGenerator->askQuestion('Database user', 'forge_user');
      $dbPass = $this->templateGenerator->askQuestion('Database password', bin2hex(random_bytes(8)));
    }

    return new ProvisionConfig($phpVersion, $dbType, $dbVersion, $dbName, $dbUser, $dbPass);
  }

  private function getDeploymentConfig(?array $fileConfig): DeploymentConfig
  {
    $deploymentConfig = $fileConfig ? $this->configReader->getDeploymentConfig($fileConfig) : null;

    if ($deploymentConfig !== null && !empty($deploymentConfig)) {
      $domain = $deploymentConfig['domain'] ?? null;
      $commands = $deploymentConfig['commands'] ?? [];
      $postDeploymentCommands = $deploymentConfig['post_deployment_commands'] ?? [];
      $envVars = $deploymentConfig['env_vars'] ?? [];

      if ($domain) {
        $this->info("Using deployment config from file: {$domain}");
        return new DeploymentConfig($domain, $commands, $envVars, $postDeploymentCommands);
      }
    }

    $domain = $this->templateGenerator->askQuestion('Domain name', '');
    if (empty($domain)) {
      throw new \RuntimeException('Domain name is required');
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

    return new DeploymentConfig($domain, $commands, [], $postDeploymentCommands);
  }

  private function getRamFromSize(string $sizeSlug): int
  {
    if (preg_match('/(\d+)gb/i', $sizeSlug, $matches)) {
      return (int) $matches[1] * 1024;
    }
    return 1024;
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

  private function waitForSsh(string $host, int $maxAttempts = 30): void
  {
    $attempts = 0;
    $sshPrivateKeyPath = $this->sshKey ? str_replace('.pub', '', $this->sshKey) : str_replace('.pub', '', $this->sshKeyManager->locatePublicKey() ?? '~/.ssh/id_rsa');
    $sshPrivateKeyPath = $this->expandPath($sshPrivateKeyPath);

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
}
