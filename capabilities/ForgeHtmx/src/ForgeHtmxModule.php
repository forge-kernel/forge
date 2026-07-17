<?php

declare(strict_types=1);

namespace Capability\ForgeHtmx;

use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\Requires;
use Forge\Core\DI\Container;
use Forge\Traits\InjectsAssets;
use Modules\ForgeRouter\Events\RouterHookAttribute;
use Modules\ForgeRouter\Events\RouterHookName;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\ForgeRouterModule;

#[Module(
    name: 'ForgeHtmx',
    version: '1.0.3',
    description: 'HTMX integration for Forge router',
    order: 80,
    author: 'Forge Team',
    license: 'MIT',
    type: 'tool',
    tags: ['htmx', 'ajax', 'partial', 'tool'],
)]
#[Compatibility(framework: '>=6.0.23', php: '>=8.3')]
#[Requires(module: 'forge-router')]
#[Requires(module: 'forge-view')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[PostInstall(command: 'asset:link', args: ['--type=module', '--module=forge-htmx'])]
#[PostUninstall(command: 'asset:unlink', args: ['--type=module', '--module=forge-htmx'])]

final class ForgeHtmxModule
{
    use InjectsAssets;

    public function register(Container $container): void
    {
        ForgeRouterModule::registerMiddleware(\Capability\ForgeHtmx\Middlewares\ForgeHtmxMiddleware::class, 'web', 2);
    }

    #[RouterHookAttribute(RouterHookName::AFTER_REQUEST)]
    public function onAfterRequest(Request $request, Response $response): void
    {
        $this->registerHtmxAssets();
        $this->injectAssets($response);
    }

    private function registerHtmxAssets(): void
    {
        $assetHtml = '<script src="/assets/modules/forge-htmx/js/htmx.min.js" defer></script>';
        $this->registerAsset(assetHtml: $assetHtml, beforeTag: '</head>');
    }
}
