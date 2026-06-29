<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager;

use App\Modules\ForgePackageManager\Contracts\PackageManagerInterface;
use App\Modules\ForgePackageManager\Services\PackageManagerService;
use Forge\Core\Config\Config;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;

#[Module(
    name: 'ForgePackageManager',
    version: '3.3.18',
    description: 'A Package Manager By Forge',
    order: 1,
    isCli: true,
    author: 'Forge Team',
    license: 'MIT',
    type: 'management',
    tags: ['management', 'package', 'dependency', 'installer']
)]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    'source_list' => [
        'registry' => [
        ],
        'cache_ttl' => 3600,
    ],
])]
final class ForgePackageManager
{
    public function register(Container $container): void
    {
        if (PHP_SAPI === 'cli') {
            $container->bind(PackageManagerInterface::class, PackageManagerService::class);
        }

        $this->setupConfigDefaults($container);
    }

    private function setupConfigDefaults(Container $container): void
    {
        if (file_exists(BASE_PATH . '/config/source_list.php')) {
            return;
        }

        /** @var Config $config */
        $config = $container->get(Config::class);

        $config->set('source_list.registry', [
            [
                'name' => 'kernel-module-registry',
                'type' => 'git',
                'url' => 'https://github.com/forge-kernel/kernel-module-registry',
                'branch' => 'main',
                'private' => false,
                'personal_token' => env('GITHUB_TOKEN', ''),
                'description' => 'Forge Kernel Official Modules'
            ],
        ]);
        $config->set('source_list.cache_ttl', env('SOURCE_LIST_CACHE_TTL', 3600));
    }
}
