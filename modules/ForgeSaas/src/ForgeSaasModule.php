<?php

declare(strict_types=1);

namespace Modules\ForgeSaas;

use Forge\Core\Module\Attributes\Requires;
use Modules\ForgeSaas\Contracts\SubscriptionManagerInterface;
use Modules\ForgeSaas\Services\SubscriptionManager;
use Forge\Core\Contracts\Database\CentralQueryBuilderInterface;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\Structure;
use Modules\ForgeRouter\ForgeRouterModule;

#[Structure(structure: [
    'services' => 'src/Services',
    'migrations' => 'src/Database/Migrations',
    'commands' => 'src/Commands',
    'dto' => 'src/Dto',
    'seeders' => 'src/Database/Seeders',
    'middlewares' => 'src/Middlewares',
    'support' => 'src/Support',
])]
#[Module(
    name: 'ForgeSaas',
    version: '0.1.8',
    description: 'SaaS plans, subscriptions, and feature gating for Forge Kernel',
    order: 4,
    author: 'Forge Team',
    license: 'MIT',
    tags: ['saas', 'billing', 'plans', 'feature-flags', 'multi-tenant']
)]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Requires(module: "forge-router")]
#[Requires(module: "forge-database-sql")]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: ['forge_saas' => []])]
#[PostInstall(command: 'db:migrate', args: ['--type=', 'module', '--module=', 'ForgeSaas'])]
#[PostInstall(command: 'db:seed', args: ['--type=', 'module', '--module=', 'ForgeSaas'])]
#[PostUninstall(command: 'db:migrate:rollback', args: ['--type=module', '--module=ForgeSaas'])]
final class ForgeSaasModule
{
    public function register(Container $container): void
    {
        $container->bind(
            SubscriptionManagerInterface::class,
            fn() => new SubscriptionManager($container->get(CentralQueryBuilderInterface::class)),
            singleton: true,
        );

        ForgeRouterModule::registerMiddleware(\Modules\ForgeSaas\Middlewares\SaasMiddleware::class, 'web', 5);
        ForgeRouterModule::registerMiddleware(\Modules\ForgeSaas\Middlewares\FeatureGateMiddleware::class, 'web', 6);
    }
}
