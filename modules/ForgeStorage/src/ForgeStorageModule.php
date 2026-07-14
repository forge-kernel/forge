<?php

declare(strict_types=1);

namespace Modules\ForgeStorage;

use Forge\Core\Module\Attributes\Requires;
use Modules\ForgeStorage\Contracts\StorageDriverInterface;
use Modules\ForgeStorage\Services\ProviderResolver;
use Forge\Core\Config\Config;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Traits\IncludesFiles;

#[Module(
    name: 'ForgeStorage',
    version: '1.3.8',
    description: 'Simple file upload storage module with multiple provider support',
    author: 'Forge Team',
    license: 'MIT',
    type: 'storage',
    tags: ['storage', 'file', 'upload']
)]
#[Compatibility(framework: '>=0.1.2', php: '>=8.3')]
#[Requires(module: "forge-router")]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    'forge_storage' => [
        'provider' => 'local',
        'root_path' => 'storage/files',
        'public_path' => 'public/storage',
        'drivers' => [
            'local' => [],
            's3' => [
                'key' => null,
                'secret' => null,
                'region' => 'us-east-1',
                'bucket' => null,
                'endpoint' => null,
            ],
        ],
        'signed_url' => [
            'default_expiration' => 3600,
            'max_expiration' => 86400,
        ],
        'hash_filenames' => true,
    ]
])]
#[PostInstall(command: 'db:migrate', args: ['--type=module', '--module=forge-storage'])]
#[PostUninstall(command: 'db:migrate:rollback', args: ['--type=module', '--module=forge-storage'])]
final class ForgeStorageModule
{
    use IncludesFiles;

    protected function includes(): array
    {
        return [
            __DIR__ . '/Support/helpers.php',
        ];
    }

    public function __construct(private Config $config)
    {
    }

    public function register(Container $container): void
    {
        $this->setupConfigDefaults();
        $container->singleton(StorageDriverInterface::class, function (Container $c) {
            $providerResolver = $c->make(ProviderResolver::class);
            return $providerResolver->resolve();
        });
    }

    private function setupConfigDefaults(): void
    {
        $this->config->set('forge_storage.provider', env('STORAGE_PROVIDER', env('FORGE_STORAGE_PROVIDER', 'local')));
        $this->config->set('forge_storage.root_path', env('FILE_STORAGE_PATH', env('FORGE_STORAGE_ROOT_PATH', 'storage/files')));
        $this->config->set('forge_storage.public_path', env('FORGE_STORAGE_PUBLIC_PATH', 'public/storage'));
        $this->config->set('forge_storage.drivers.s3.key', env('FORGE_STORAGE_AWS_ACCESS_KEY_ID'));
        $this->config->set('forge_storage.drivers.s3.secret', env('FORGE_STORAGE_AWS_SECRET_ACCESS_KEY'));
        $this->config->set('forge_storage.drivers.s3.region', env('FORGE_STORAGE_AWS_DEFAULT_REGION', 'us-east-1'));
        $this->config->set('forge_storage.drivers.s3.bucket', env('FORGE_STORAGE_AWS_BUCKET'));
        $this->config->set('forge_storage.drivers.s3.endpoint', env('FORGE_STORAGE_AWS_ENDPOINT'));
        $this->config->set('forge_storage.signed_url.default_expiration', env('FORGE_STORAGE_SIGNED_URL_DEFAULT_EXPIRATION', 3600));
        $this->config->set('forge_storage.signed_url.max_expiration', env('FORGE_STORAGE_SIGNED_URL_MAX_EXPIRATION', 86400));
        $this->config->set('forge_storage.hash_filenames', env('FORGE_STORAGE_HASH_FILENAMES', true));
    }
}
