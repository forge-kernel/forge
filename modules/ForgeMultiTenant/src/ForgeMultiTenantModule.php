<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant;

use Forge\Core\Config\Config;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\Module\Attributes\Requires;
use Forge\Core\ResetManager;
use Modules\ForgeMultiTenant\Services\RouteScopeFilter;
use Modules\ForgeMultiTenant\Services\TenantManager;
use Modules\ForgeMultiTenant\Services\TenantQueryRewriter;
use Modules\ForgeRouter\Contracts\RouteScopeFilterInterface;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Traits\IncludesFiles;
use Forge\Core\Module\Traits\RegistersCommands;
use Forge\CLI\Traits\OutputHelper;
use Modules\ForgeRouter\ForgeRouterModule;
use Modules\ForgeRouter\Http\Middlewares\RateLimitMiddleware;
use Modules\ForgeRouter\Http\Middlewares\CircuitBreakerMiddleware;
use Modules\ForgeRouter\Http\Middlewares\ApiKeyMiddleware;

#[Module(
    name: 'ForgeMultiTenant',
    version: '0.4.9',
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
        'unknown_tenant_page' => null,
        'unknown_tenant_view' => null,
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
    use IncludesFiles;
    use RegistersCommands;
    use OutputHelper;

    protected function includes(): array
    {
        return [
            __DIR__ . '/Support/helpers.php',
        ];
    }

    protected function commands(): array
    {
        return [
            \Modules\ForgeMultiTenant\Commands\TenantListCommand::class,
            \Modules\ForgeMultiTenant\Commands\TenantMigrateCommand::class,
            \Modules\ForgeMultiTenant\Commands\TenantSeedCommand::class,
        ];
    }

    public function register(Container $container): void
    {
        $this->setupConfigDefaults($container);

        $container->singleton(TenantManager::class, function (Container $container) {
            return new TenantManager($container->get(QueryBuilderInterface::class));
        });

        $container->singleton(TenantQueryRewriter::class, function () {
            return new TenantQueryRewriter();
        });

        $container->bind(RouteScopeFilterInterface::class, RouteScopeFilter::class);

        ResetManager::onBefore([RouteScopeFilter::class, 'reset']);

        ForgeRouterModule::registerMiddleware(\Modules\ForgeMultiTenant\Middlewares\TenantMiddleware::class, 'web', 1);
        ForgeRouterModule::registerMiddleware(\Modules\ForgeMultiTenant\Middlewares\ScopeMiddleware::class, 'web', 2);

        ForgeRouterModule::registerMiddleware(
            \Modules\ForgeMultiTenant\Middlewares\TenantAwareRateLimitMiddleware::class,
            'global',
            1,
            RateLimitMiddleware::class
        );
        ForgeRouterModule::registerMiddleware(
            \Modules\ForgeMultiTenant\Middlewares\TenantAwareCircuitBreakerMiddleware::class,
            'global',
            2,
            CircuitBreakerMiddleware::class
        );
        ForgeRouterModule::registerMiddleware(
            \Modules\ForgeMultiTenant\Middlewares\TenantAwareApiKeyMiddleware::class,
            'api',
            100,
            ApiKeyMiddleware::class
        );
    }

    private function setupConfigDefaults(Container $container): void
    {
        $config = $container->get(Config::class);
        $config->set('forge_multi_tenant.central_domain', env('FORGE_MULTI_TENANT_CENTRAL_DOMAIN', 'forge.localhost'));
        $config->set('forge_multi_tenant.unknown_tenant_page', env('FORGE_MULTI_TENANT_UNKNOWN_PAGE', null));
        $config->set('forge_multi_tenant.unknown_tenant_view', env('FORGE_MULTI_TENANT_UNKNOWN_VIEW', null));
    }
}
