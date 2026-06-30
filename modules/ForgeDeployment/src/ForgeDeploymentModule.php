<?php

declare(strict_types=1);

namespace Modules\ForgeDeployment;

use Forge\Core\Config\Config;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\CLI\Traits\OutputHelper;

#[Module(
    name: 'ForgeDeployment',
    version: '2.5.7',
    description: 'Deploy applications to cloud providers with automated provisioning',
    order: 99,
    author: 'Forge Team',
    license: 'MIT',
    type: 'deployment',
    tags: ['deployment', 'cloud', 'provider', 'automated', 'provisioning', 'deployment-system', 'deployment-library', 'deployment-framework']
)]
#[Compatibility(framework: '>=4.15.11', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    'forge_deployment' => [
        'digitalocean' => [
            'api_token' => '',
        ],
        'cloudflare' => [
            'api_token' => '',
        ],
    ]
])]
final class ForgeDeploymentModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $this->setupConfigDefaults($container);
    }

    private function setupConfigDefaults(Container $container): void
    {
        /** @var Config $config */
        $config = $container->get(Config::class);
        $config->set('forge_deployment.digitalocean.api_token', env('FORGE_DEPLOYMENT_DIGITALOCEAN_API_TOKEN', 'your-do-token'));
        $config->set('forge_deployment.cloudflare.api_token', env('FORGE_DEPLOYMENT_CLOUDFLARE_API_TOKEN', 'your-cloudflare-token'));
    }
}
