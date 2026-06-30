<?php

declare(strict_types=1);

namespace Modules\ForgeComponents;

use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\Requires;

#[Module(
    name: 'ForgeComponents',
    version: '0.3.8',
    description: 'Primitive reusable UI components with vanilla CSS design system',
    order: 1,
    author: 'Forge Team',
    license: 'MIT',
    type: 'generic',
    tags: ['ui', 'component', 'design-system']
)]
#[Compatibility(framework: '>=4.15.13', php: '>=8.3')]
#[Requires(module: "forge-view")]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    "forge_components" => []
])]
final class ForgeComponentsModule
{
}
