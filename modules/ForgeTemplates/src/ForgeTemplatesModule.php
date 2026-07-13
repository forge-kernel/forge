<?php

declare(strict_types=1);

namespace Modules\ForgeTemplates;

use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\Structure;

#[Module(
    name: 'ForgeTemplates',
    version: '0.1.0',
    description: 'Template engine for composing notification messages from PHP template files',
    order: 3,
    author: 'Forge Team',
    license: 'MIT',
    type: 'html',
    tags: ['templates', 'notifications', 'email', 'rendering']
)]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[Structure([
    "injectable" => ['src/Injectable'],
])]
final class ForgeTemplatesModule
{
}
