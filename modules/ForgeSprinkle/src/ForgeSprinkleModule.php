<?php

declare(strict_types=1);

namespace Modules\ForgeSprinkle;

use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\Requires;
use Forge\Traits\InjectsAssets;
use Modules\ForgeRouter\Events\RouterHookAttribute;
use Modules\ForgeRouter\Events\RouterHookName;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;

#[Module(
    name: 'ForgeSprinkle',
    version: '0.1.0',
    description: 'Tiny HTML attribute enhancements — sprinkle optional UX onto elements without breaking native behavior',
    order: 90,
    author: 'Forge Team',
    license: 'MIT',
    type: 'tool',
    tags: ['enhance', 'sprinkle', 'ux', 'progressive-enhancement'],
)]
#[Compatibility(framework: '>=6.0.23', php: '>=8.3')]
#[Requires(module: 'forge-router')]
#[Requires(module: 'forge-view')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[PostInstall(command: 'asset:link', args: ['--type=module', '--module=forge-sprinkle'])]
#[PostUninstall(command: 'asset:unlink', args: ['--type=module', '--module=forge-sprinkle'])]
final class ForgeSprinkleModule
{
    use InjectsAssets;

    #[RouterHookAttribute(RouterHookName::AFTER_REQUEST)]
    public function onAfterRequest(Request $request, Response $response): void
    {
        $this->registerSprinkleAssets();
        $this->injectAssets($response);
    }

    private function registerSprinkleAssets(): void
    {
        $this->registerAsset(
            assetHtml: '<link rel="stylesheet" href="/assets/modules/forge-sprinkle/css/sprinkle.css">',
            beforeTag: '</head>',
        );

        $this->registerAsset(
            assetHtml: '<script src="/assets/modules/forge-sprinkle/js/sprinkle.js" defer></script>',
            beforeTag: '</body>',
        );
    }
}
