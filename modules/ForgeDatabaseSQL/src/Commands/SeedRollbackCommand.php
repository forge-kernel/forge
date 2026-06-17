<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\Commands;

use App\Modules\ForgeDatabaseSQL\DB\Seeders\SeederManager;
use Exception;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Traits\StringHelper;
use Forge\CLI\Attributes\CoreCommand;
use Throwable;

#[CoreCommand]
#[Cli(
    command: 'db:seed:rollback',
    description: 'Rollback database seeders',
    usage: 'db:seed:rollback [--type=app|kernel|module] [--module=ModuleName] [--group=GroupName] [--steps=N]',
    examples: [
        'db:seed:rollback --type=app --steps=1',
        'db:seed:rollback --type=module --module=Blog --group=Users --steps=2',
        'db:seed:rollback   (starts wizard)',
    ]
)]
final class SeedRollbackCommand extends Command
{
    use StringHelper;
    use Wizard;

    #[Arg(name: 'type', description: 'Seeder type: app, kernel, module', required: false, validate: 'app|kernel|module')]
    private ?string $type = null;
    #[Arg(name: 'module', description: 'Module name if type=module', required: false)]
    private ?string $module = null;
    #[Arg(name: 'group', description: 'Group name for specific seeders', required: false)]
    private ?string $group = null;
    #[Arg(name: 'steps', description: 'Number of batches to rollback', default: '1', validate: '/^\d+$/')]
    private int $steps = 1;

    public function __construct(private readonly SeederManager $manager)
    {
    }

    /**
     * @throws Exception|Throwable
     */
    public function execute(array $args): int
    {
        $this->wizard($args);

        $type = $this->type ?? 'app';
        $module = $this->module ? $this->toPascalCase($this->module) : null;
        $group = $this->group;

        $this->info("Rolling back seeders...");
        $this->manager->rollback($this->steps, $type, $module, $group);

        $this->success("Rolled back {$this->steps} batch(es) successfully");
        return 0;
    }
}
