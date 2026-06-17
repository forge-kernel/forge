<?php

declare(strict_types=1);

namespace App\Modules\ForgeSaas;

use App\Modules\ForgeSaas\Contracts\SubscriptionManagerInterface;
use App\Modules\ForgeSaas\Services\SubscriptionManager;
use Forge\Core\Contracts\Database\CentralQueryBuilderInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\Structure;

#[Structure(structure: [
    'services' => 'src/Services',
    'migrations' => 'src/Database/Migrations',
    'commands' => 'src/Commands',
    'dto' => 'src/Dto',
    'seeders' => 'src/Database/Seeders',
    'middlewares' => 'src/Middlewares',
    'support' => 'src/Support',
])]
#[Service]
#[Module(
    name: 'ForgeSaas',
    version: '0.1.1',
    description: 'SaaS plans, subscriptions, and feature gating for Forge Kernel',
    order: 4,
    author: 'Forge Team',
    license: 'MIT',
    tags: ['saas', 'billing', 'plans', 'feature-flags', 'multi-tenant']
)]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
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
    }
}
