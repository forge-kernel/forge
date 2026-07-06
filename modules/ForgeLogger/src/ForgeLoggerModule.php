<?php

declare(strict_types=1);

namespace Modules\ForgeLogger;

use Forge\Core\Config\Config;
use Forge\Core\Contracts\LoggerInterface;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Structure;
use Forge\Core\Module\Attributes\Repository;
use Modules\ForgeLogger\Services\ForgeLoggerService;
use Forge\CLI\Traits\OutputHelper;

#[Module(
    name: 'ForgeLogger',
    version: '0.5.8',
    description: 'A logger by Forge.',
    order: 90,
    author: 'Forge Team',
    license: 'MIT',
    type: 'logging',
    tags: ['logging', 'logger', 'log', 'logging-system', 'logging-library', 'logging-framework']
)]
#[Structure(structure: [
    'services' => 'src/Services',
    'commands' => 'src/Commands',
    'events' => 'src/Events',
    'tests' => 'src/tests',
])]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    'forge_logger' => [
        'driver' => 'file',
        'path' => '/storage/logs/forge.log',
        'min_level' => 'DEBUG',
        'max_file_size' => 0,
    ]
])]
final class ForgeLoggerModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $this->setupConfigDefaults($container);
        $container->bind(LoggerInterface::class, ForgeLoggerService::class);
    }

    private function setupConfigDefaults(Container $container): void
    {
        /** @var Config $config */
        $config = $container->get(Config::class);
        $config->set('forge_logger.driver', env('LOG_DRIVER', env('FORGE_LOGGER_DRIVER', 'file')));
        $config->set('forge_logger.path', env('LOG_PATH', env('FORGE_LOGGER_PATH', BASE_PATH . '/storage/logs/forge.log')));
        $config->set('forge_logger.min_level', env('FORGE_LOGGER_MIN_LEVEL', 'DEBUG'));
        $config->set('forge_logger.max_file_size', env('FORGE_LOGGER_MAX_FILE_SIZE', '0'));
    }
}
