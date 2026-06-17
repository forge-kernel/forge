<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

use App\Modules\ForgeDeployment\Services\CloudflareService;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Config\Config;
use Forge\Core\Services\TemplateGenerator;

#[Cli(
  command: 'forge-deployment:configure-dns',
  description: 'Configure DNS records via Cloudflare',
  usage: 'forge-deployment:configure-dns [--domain=example.com] [--ip=1.2.3.4]',
  examples: [
    'forge-deployment:configure-dns --domain=example.com --ip=1.2.3.4',
  ]
)]
final class ConfigureDnsCommand extends Command
{
  use Wizard;

  #[Arg(name: 'domain', description: 'Domain name')]
  private string $domain = '';

  #[Arg(name: 'ip', description: 'Server IP address')]
  private string $ip = '';

  public function __construct(
    private readonly Config $config,
    private readonly TemplateGenerator $templateGenerator
  ) {
  }

  public function execute(array $args): int
  {
    $this->wizard($args);

    try {
      if (empty($this->domain)) {
        $this->domain = $this->templateGenerator->askQuestion('Domain name', '');
      }

      if (empty($this->ip)) {
        $this->ip = $this->templateGenerator->askQuestion('Server IP address', '');
      }

      $apiToken = $this->config->get('forge_deployment.cloudflare.api_token');
      if (empty($apiToken)) {
        $apiToken = $this->templateGenerator->askQuestion('Cloudflare API token', '');
        if (empty($apiToken)) {
          throw new \RuntimeException('Cloudflare API token is required');
        }
        $this->config->set('forge_deployment.cloudflare.api_token', $apiToken);
      }

      $cloudflareService = new CloudflareService($apiToken);

      $this->info('Finding Cloudflare zone...');
      $zoneId = $cloudflareService->getZoneId($this->domain);
      if ($zoneId === null) {
        throw new \RuntimeException("Zone not found for domain: {$this->domain}");
      }

      $this->info('Adding DNS record...');
      $success = $cloudflareService->addDnsRecord($zoneId, $this->domain, $this->ip);

      if ($success) {
        $this->success("DNS record added successfully for {$this->domain} -> {$this->ip}");
      } else {
        throw new \RuntimeException('Failed to add DNS record');
      }

      return 0;
    } catch (\Exception $e) {
      $this->error('DNS configuration failed: ' . $e->getMessage());
      return 1;
    }
  }
}
