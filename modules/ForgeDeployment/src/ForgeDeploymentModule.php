<?php

declare(strict_types=1);

namespace Modules\ForgeDeployment;

use Forge\Core\Config\Config;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Traits\RegistersCommands;
use Forge\CLI\Traits\OutputHelper;

#[Module(
    name: 'ForgeDeployment',
    version: '2.5.9',
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
    use RegistersCommands;

    public function register(Container $container): void
    {
        $this->setupConfigDefaults($container);
    }

    protected function commands(): array
    {
        return [
            \Modules\ForgeDeployment\Commands\ConfigureDnsCommand::class,
            \Modules\ForgeDeployment\Commands\CreateServerCommand::class,
            \Modules\ForgeDeployment\Commands\DeleteServerCommand::class,
            \Modules\ForgeDeployment\Commands\DeployAppCommand::class,
            \Modules\ForgeDeployment\Commands\DeployCommand::class,
            \Modules\ForgeDeployment\Commands\DeployEnvCommand::class,
            \Modules\ForgeDeployment\Commands\FixPermissionsCommand::class,
            \Modules\ForgeDeployment\Commands\InitDeploymentConfigCommand::class,
            \Modules\ForgeDeployment\Commands\ProvisionCommand::class,
            \Modules\ForgeDeployment\Commands\ResetCommand::class,
            \Modules\ForgeDeployment\Commands\ResumeCommand::class,
            \Modules\ForgeDeployment\Commands\RollbackCommand::class,
            \Modules\ForgeDeployment\Commands\SetupSslCommand::class,
            \Modules\ForgeDeployment\Commands\StatusCommand::class,
            \Modules\ForgeDeployment\Commands\UpdateCommand::class,
        ];
    }

    private function setupConfigDefaults(Container $container): void
    {
        /** @var Config $config */
        $config = $container->get(Config::class);
        $config->set('forge_deployment.digitalocean.api_token', env('FORGE_DEPLOYMENT_DIGITALOCEAN_API_TOKEN', 'your-do-token'));
        $config->set('forge_deployment.cloudflare.api_token', env('FORGE_DEPLOYMENT_CLOUDFLARE_API_TOKEN', 'your-cloudflare-token'));
    }
}
