<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant;

use Forge\Core\Config\Config;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\Module\Attributes\Requires;
use Forge\Core\ResetManager;
use Modules\ForgeMultiTenant\Services\RouteScopeFilter;
use Modules\ForgeMultiTenant\Services\TenantManager;
use Modules\ForgeRouter\Contracts\RouteScopeFilterInterface;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use Forge\CLI\Traits\OutputHelper;

#[Module(
    name: 'ForgeMultiTenant',
    version: '0.3.16',
    description: 'A Multi Tenant Module by Forge',
    order: 2,
    author: 'Forge Team',
    license: 'MIT',
    type: 'multi-tenant',
    tags: ['multi-tenant', 'tenant', 'management', 'database', 'multi-tenant', 'multi-tenant-management']
)]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Requires(module: "forge-database-sql")]
#[Requires(module: "forge-router")]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    "forge_multi_tenant" => [
        'central_domain' => 'forge.localhost',
    ]
])]
#[PostInstall(command: 'db:migrate', args: ['--type=module', '--module=ForgeMultiTenant'])]
#[PostInstall(command: 'db:seed', args: ['--type=module', '--module=ForgeMultiTenant'])]
#[PostInstall(command: 'tenant:migrate', args: [''])]
#[PostInstall(command: 'tenant:seed', args: [''])]
#[PostUninstall(command: 'db:migrate:rollback', args: ['--type=module', '--module=ForgeMultiTenant', '--group=tenant'])]
#[PostUninstall(command: 'db:migrate:rollback', args: ['--type=module', '--module=ForgeMultiTenant'])]
#[PostUninstall(command: 'db:seed:rollback', args: ['--type=module', '--module=ForgeMultiTenant'])]
final class ForgeMultiTenantModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $this->setupConfigDefaults($container);

        $container->bind(TenantManager::class, function (Container $container) {
            return new TenantManager($container->get(QueryBuilderInterface::class));
        });

        $container->bind(RouteScopeFilterInterface::class, RouteScopeFilter::class);

        ResetManager::onBefore([RouteScopeFilter::class, 'reset']);
    }

    private function setupConfigDefaults(Container $container): void
    {
        $config = $container->get(Config::class);
        $config->set('forge_multi_tenant.central_domain', env('FORGE_MULTI_TENANT_CENTRAL_DOMAIN', 'forge.localhost'));
    }
}
