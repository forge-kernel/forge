<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\Commands;

use App\Modules\ForgeDatabaseSQL\DB\Migrator;
use Exception;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Traits\StringHelper;
use Forge\CLI\Attributes\CoreCommand;
use Throwable;

#[CoreCommand]
#[
    Cli(
    command: "db:migrate:rollback",
    description: "Rollback database migrations",
    usage: "migrate:rollback [--type=app|kernel|module|all] [--module=ModuleName] [--group=GroupName] [--steps=N] [--batch=N] [--preview]",
    examples: [
        "migrate:rollback --type=all --steps=1",
        "migrate:rollback --type=app --steps=1",
        "migrate:rollback --type=module --module=Blog --group=Users --steps=2",
        "migrate:rollback --batch=3 --preview",
        "migrate:rollback   (starts wizard)",
    ],
),
]
final class MigrateRollbackCommand extends Command
{
    use StringHelper;
    use Wizard;

    #[
        Arg(
        name: "type",
        description: "Migration type: app, kernel, module, all",
        required: true,
        validate: "app|kernel|module|all",
    ),
    ]
    private ?string $type = null;
    #[
        Arg(
        name: "module",
        description: "Module name if type=module",
        required: false,
    ),
    ]
    private ?string $module = null;
    #[
        Arg(
        name: "group",
        description: "Group name for specific migrations",
        required: false,
    ),
    ]
    private ?string $group = null;
    #[
        Arg(
        name: "steps",
        description: "Number of batches to rollback",
        default: "1",
        validate: '/^\d+$/',
    ),
    ]
    private int $steps = 1;
    #[
        Arg(
        name: "batch",
        description: "Specific batch to rollback",
        required: false,
        validate: '/^\d+$/',
    ),
    ]
    private ?int $batch = null;
    #[
        Arg(
        name: "preview",
        description: "Preview migrations without rolling back",
        required: false,
    ),
    ]
    private bool $preview = false;

    public function __construct(private readonly Migrator $migrator)
    {
    }

    /**
     * @throws Exception|Throwable
     */
    public function execute(array $args): int
    {
        $this->wizard($args);

        $type = strtolower($this->type ?? "");
        $migrationType = match ($type) {
            "app" => "app",
            "module" => "module",
            "kernel" => "core",
            default => "all",
        };

        $module = $this->module ? $this->toPascalCase($this->module) : null;
        $group = $this->group;
        $steps = $this->steps;
        $batch = $this->batch;

        if ($batch !== null) {
            $steps = 1;
        }

        $infoMessage = "Processing rollback";
        if ($migrationType) {
            $infoMessage .= " (Type: {$migrationType})";
        }
        if ($module) {
            $infoMessage .= " (Module: {$module})";
        }
        if ($group) {
            $infoMessage .= " (Group: {$group})";
        }
        if ($batch !== null) {
            $infoMessage .= " targeting batch {$batch}";
        } else {
            $infoMessage .= " for {$steps} batch(es)";
        }
        $this->info($infoMessage . "...");

        if ($this->preview) {
            $migrations = $this->migrator->getRanMigrations(
                $steps,
                $migrationType,
                $module,
                $group,
                $batch,
            );

            if (empty($migrations)) {
                $this->info("No migrations to rollback matching the criteria.");
            } else {
                $this->info("The following migrations would be rolled back:");
                foreach ($migrations as $migration) {
                    $this->info("- {$migration}");
                }
            }
            return 0;
        }

        $this->migrator->rollback(
            $steps,
            $migrationType,
            $module,
            $group,
            $batch,
        );
        $this->success("Rollback completed successfully.");
        return 0;
    }
}
