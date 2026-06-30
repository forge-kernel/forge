<?php

declare(strict_types=1);

namespace Modules\ForgeErrorHandler;

use Modules\ForgeRouter\Contracts\ErrorHandlerInterface;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Modules\ForgeErrorHandler\Services\ForgeErrorHandlerService;
use Forge\CLI\Traits\OutputHelper;

#[Module(
    name: 'ForgeErrorHandler',
    version: '1.2.5',
    description: 'An error handler by Forge',
    order: 2,
    core: true,
    author: 'Forge Team',
    license: 'MIT',
    type: 'core',
    tags: ['error', 'handler', 'error-handler', 'error-management', 'error-logging', 'error-logging-system', 'error-logging-library', 'error-logging-framework']
)]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [])]
final class ForgeErrorHandlerModule
{
    use OutputHelper;
    public function register(Container $container): void
    {
        $container->bind(ErrorHandlerInterface::class, ForgeErrorHandlerService::class);
    }
}
