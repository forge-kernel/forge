<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

use App\Modules\ForgeDeployment\Contracts\ProviderInterface;
use App\Modules\ForgeDeployment\Dto\ServerConfig;
use App\Modules\ForgeDeployment\Providers\DigitalOceanProvider;
use App\Modules\ForgeDeployment\Services\SshKeyManager;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Config\Config;
use Forge\Core\Services\TemplateGenerator;

#[Cli(
  command: 'forge-deployment:create-server',
  description: 'Create a VPS server',
  usage: 'forge-deployment:create-server [--provider=digitalocean] [--ssh-key=path]',
  examples: [
    'forge-deployment:create-server',
  ]
)]
final class CreateServerCommand extends Command
{
  use Wizard;

  #[Arg(name: 'provider', description: 'Cloud provider', default: 'digitalocean')]
  private string $provider = 'digitalocean';

  #[Arg(name: 'ssh-key', description: 'SSH public key path', required: false)]
  private ?string $sshKey = null;

  public function __construct(
    private readonly Config $config,
    private readonly TemplateGenerator $templateGenerator,
    private readonly SshKeyManager $sshKeyManager
  ) {
  }

  public function execute(array $args): int
  {
    $this->wizard($args);

    try {
      $provider = $this->getProvider();
      $sshPublicKey = $this->getSshPublicKey();
      $serverConfig = $this->getServerConfig($provider);

      $this->info('Creating server...');
      $serverId = $provider->createServer($serverConfig->toArray(), $sshPublicKey);

      $this->info('Waiting for server to be ready...');
      $serverInfo = $provider->waitForServer($serverId);

      $this->success("Server created successfully!");
      $this->line("Server ID: {$serverInfo['id']}");
      $this->line("Server IP: {$serverInfo['ipv4']}");
      $this->line("Status: {$serverInfo['status']}");

      return 0;
    } catch (\Exception $e) {
      $this->error('Failed to create server: ' . $e->getMessage());
      return 1;
    }
  }

  private function getProvider(): ProviderInterface
  {
    $apiToken = $this->config->get("forge_deployment.{$this->provider}.api_token");
    if (empty($apiToken)) {
      $apiToken = $this->templateGenerator->askQuestion("Enter {$this->provider} API token", '');
      if (empty($apiToken)) {
        throw new \RuntimeException('API token is required');
      }
    }

    return new DigitalOceanProvider($apiToken);
  }

  private function getSshPublicKey(): ?string
  {
    return $this->sshKeyManager->readPublicKey($this->sshKey);
  }

  private function getServerConfig(ProviderInterface $provider): ServerConfig
  {
    $regions = $provider->listRegions();
    $regionOptions = array_map(fn($r) => "{$r['name']} ({$r['slug']})", $regions);
    $selectedRegion = $this->templateGenerator->selectFromList('Select region', $regionOptions);
    $regionSlug = $regions[array_search($selectedRegion, $regionOptions)]['slug'];

    $sizes = $provider->listSizes();
    $sizeOptions = array_map(fn($s) => "{$s['slug']} - {$s['memory']}MB RAM, {$s['vcpus']} vCPU", $sizes);
    $selectedSize = $this->templateGenerator->selectFromList('Select server size', $sizeOptions);
    $sizeSlug = $sizes[array_search($selectedSize, $sizeOptions)]['slug'];

    $images = $provider->listImages();
    $imageOptions = array_map(fn($i) => "{$i['name']} ({$i['slug']})", $images);
    $selectedImage = $this->templateGenerator->selectFromList('Select OS image', $imageOptions);
    $imageSlug = $images[array_search($selectedImage, $imageOptions)]['slug'];

    $name = $this->templateGenerator->askQuestion('Server name', 'forge-server-' . time());

    return new ServerConfig($name, $regionSlug, $sizeSlug, $imageSlug, $this->sshKey);
  }
}
