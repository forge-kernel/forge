<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant;

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
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;

#[Module(
    name: 'ForgeMultiTenant',
    version: '0.3.5',
    description: 'A Multi Tenant Module by Forge',
    order: 2,
    author: 'Forge Team',
    license: 'MIT',
    type: 'multi-tenant',
    tags: ['multi-tenant', 'tenant', 'management', 'database', 'multi-tenant', 'multi-tenant-management']
)]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    "forge_multi_tenant" => []
])]
#[PostInstall(command: 'migrate', args: ['--type=', 'module', '--module=', 'ForgeMultiTenant'])]
#[PostInstall(command: 'seed', args: ['--type=', 'module', '--module=', 'ForgeMultiTenant'])]
#[PostInstall(command: 'tenant:migrate', args: [''])]
#[PostInstall(command: 'tenant:seed', args: [''])]
#[PostUninstall(command: 'migrate:rollback', args: ['--type=module', '--module=ForgeMultiTenant', '--group=tenant'])]
#[PostUninstall(command: 'migrate:rollback', args: ['--type=module', '--module=ForgeMultiTenant'])]
#[PostUninstall(command: 'seed:rollback', args: ['--type=module', '--module=ForgeMultiTenant'])]
final class ForgeMultiTenantModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $container->bind(TenantManager::class, function (Container $container) {
            return new TenantManager($container);
        });

        $container->bind(RouteScopeFilterInterface::class, RouteScopeFilter::class);
    }
}
