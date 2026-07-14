<?php

declare(strict_types=1);

namespace Modules\ForgeTailwind;

use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Traits\IncludesFiles;
use Forge\Core\Module\Traits\RegistersCommands;

#[Module(
    name: 'ForgeTailwind',
    version: '0.2.7',
    description: 'A tailwind module by forge',
    order: 99,
    isCli: true,
    author: 'Forge Team',
    license: 'MIT',
    type: 'tailwind',
    tags: ['tailwind', 'css', 'framework']
)]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
final class ForgeTailwindModule
{
    use IncludesFiles;
    use RegistersCommands;

    protected function includes(): array
    {
        return [
            __DIR__ . '/Support/helpers.php',
        ];
    }

    protected function commands(): array
    {
        return [
            \Modules\ForgeTailwind\Commands\BuildTailwindCommand::class,
            \Modules\ForgeTailwind\Commands\WatchTailwindCommand::class,
        ];
    }
}
