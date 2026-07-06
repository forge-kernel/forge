<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Commands;

use Modules\ForgeRouter\Events\RouterHookManager;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\CoreCommand;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Bootstrap\ModuleSetup;

#[CoreCommand]
#[Cli(
    command: 'serve',
    description: 'Start the PHP Development Server',
    usage: 'serve [--host=localhost] [--port=8000]',
    examples: [
        'serve',
        'serve --host=127.0.0.1 --port=8080'
    ]
)]
final class ServeCommand extends Command
{
    use Wizard;

    #[Arg(
        name: 'host',
        description: 'Server host (default: localhost)',
        default: 'localhost',
        required: false
    )]
    private string $host;
    #[Arg(
        name: 'port',
        description: 'Server port (default: 8000)',
        default: '8000',
        required: false,
        validate: '/^\d{2,5}$/'
    )]
    private string $port;

    public function execute(array $args): int
    {
        $this->wizard($args);

        if (PHP_SAPI === 'cli') {
            ModuleSetup::compileHooks();
            RouterHookManager::discover();
            RouterHookManager::compile();
        }

        $publicDir = BASE_PATH . "/public";
        $this->info("Server running on http://{$this->host}:{$this->port}");
        passthru("php -S {$this->host}:{$this->port} -t $publicDir");

        return 0;
    }
}
