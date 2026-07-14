<?php

declare(strict_types=1);

namespace Modules\ForgeUi;

use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Traits\IncludesFiles;

#[Module(
    name: 'ForgeUi',
    version: '1.1.8',
    description: 'A UI component module by forge.',
    order: 99,
    author: 'Forge Team',
    license: 'MIT',
    type: 'ui',
    tags: ['ui', 'component', 'library'])]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[PostInstall(command: 'asset:link', args: ['--type=module', '--module=forge-ui'])]
#[PostInstall(command: 'asset:unlink', args: ['--type=module', '--module=forge-ui'])]
final class ForgeUIModule
{
    use IncludesFiles;

    protected function includes(): array
    {
        return [
            __DIR__ . '/Support/helpers.php',
        ];
    }
}
