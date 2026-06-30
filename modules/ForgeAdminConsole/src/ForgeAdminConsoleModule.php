<?php
declare(strict_types=1);

namespace Modules\ForgeAdminConsole;

use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\Requires;

#[Module(
    name: 'ForgeAdminConsole',
    version: '0.1.3',
    description: 'Protected admin console with dashboard, account, profile, and user management',
    order: 55,
    author: 'Forge Team',
    license: 'MIT',
    type: 'generic',
    tags: ['ui', 'admin', 'console'],
)]
#[Requires(module: "forge-router")]
#[Requires(module: "forge-view")]
#[Requires(module: "forge-components")]
#[Requires(module: "forge-auth")]
#[Compatibility(framework: '>=4.15.13', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    "forge_admin_console" => [
        "brand" => "Admin",
        "items_per_page" => 10,
    ],
])]
#[PostInstall(command: 'asset:link', args: ['--type=module', '--module=forge-admin-console'])]
#[PostUninstall(command: 'asset:unlink', args: ['--type=module', '--module=forge-admin-console'])]
final class ForgeAdminConsoleModule
{
}
