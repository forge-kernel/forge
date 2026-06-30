<?php
declare(strict_types=1);

namespace Modules\ForgeLanding;

use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\Requires;

#[Module(
    name: 'ForgeLanding',
    version: '0.1.4',
    description: 'Public-facing landing page with navigation to auth flows',
    order: 50,
    author: 'Forge Team',
    license: 'MIT',
    type: 'generic',
    tags: ['ui', 'landing', 'public'],
)]
#[Compatibility(framework: '>=4.15.13', php: '>=8.3')]
#[Requires(module: "forge-components")]
#[Requires(module: "forge-router")]
#[Requires(module: "forge-view")]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    "forge_landing" => [
        "brand" => "Forge",
        "show_auth_buttons" => true,
    ],
])]
#[PostInstall(command: 'asset:link', args: ['--type=module', '--module=forge-landing'])]
#[PostUninstall(command: 'asset:unlink', args: ['--type=module', '--module=forge-landing'])]
final class ForgeLandingModule
{
}
