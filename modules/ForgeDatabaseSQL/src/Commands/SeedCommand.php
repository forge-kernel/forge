<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\Commands;

use Modules\ForgeDatabaseSQL\DB\Seeders\SeederManager;
use Exception;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Attributes\CoreCommand;
use Forge\CLI\Traits\Wizard;

#[CoreCommand]
#[Cli(
    command: 'db:seed',
    description: 'Run database seeders',
    usage: 'db:seed [--type=app|module] [--module=ModuleName]',
    examples: [
        'db:seed --type=app',
        'db:seed --type=module --module=Blog',
        'db:seed (starts wizard)',
    ]
)]
final class SeedCommand extends Command
{
    use Wizard;

    #[Arg(name: 'type', description: 'Seeder type: app, module', required: true, validate: 'app|module')]
    private ?string $type = null;
    #[Arg(name: 'module', description: 'Module name if type=module', required: false)]
    private ?string $module = null;

    public function __construct(private readonly SeederManager $manager)
    {
    }

    /**
     * @throws Exception
     */
    public function execute(array $args): int
    {
        $this->wizard($args);
        $type = $this->type ?? 'app';

        $managerType = match ($type) {
            'app' => 'app',
            'module' => 'module',
            default => 'all',
        };

        if ($managerType === 'module' && !$this->module) {
            $this->error('--module=Name required when --type=module');
            return 1;
        }

        $this->info("Running seeders...");
        $this->manager->run($managerType, $this->module);
        $this->success("Seeding completed successfully");

        return 0;
    }
}
