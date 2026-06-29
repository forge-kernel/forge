<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\Commands;

use Modules\ForgeDatabaseSQL\DB\Migrator;
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
    command: "db:migrate",
    description: "Run database migrations",
    usage: "db:migrate [--type=app|module|all] [--module=ModuleName] [--group=group_name] [--preview]",
    examples: [
        "db:migrate --type=all",
        "db:migrate --type=app",
        "db:migrate --type=module --module=Blog",
        "db:migrate --type=module --module=Blog --group=users",
        "db:migrate --preview",
        "db:migrate   (starts wizard)",
    ],
),
]
final class MigrateCommand extends Command
{
    use StringHelper;
    use Wizard;

    #[
        Arg(
        name: "type",
        description: "Migration type: app, module, all",
        required: true,
        validate: "app|module|all",
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
        name: "preview",
        description: "Preview migrations without running them",
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

        $type = strtolower($this->type ?? "app");
        $module = $this->module ? $this->toPascalCase($this->module) : null;
        $group = $this->group;

        $migratorScope = match ($type) {
            "app" => "app",
            "module" => "module",
            default => "all",
        };

        $infoMessage = "Processing migrations for scope '{$migratorScope}'";
        if ($module) {
            $infoMessage .= " on module '{$module}'";
        }
        if ($group) {
            $infoMessage .= " in group '{$group}'";
        }
        $this->info($infoMessage . "...");

        if ($this->preview) {
            $migrations = $this->migrator->previewRun(
                $migratorScope,
                $module,
                $group,
            );

            if (empty($migrations)) {
                $this->info(
                    "No migrations are currently PENDING matching the specified criteria.",
                );
            } else {
                $this->info(
                    "The following migrations are PENDING and would be run:",
                );
                foreach ($migrations as $migrationPath) {
                    $this->info("- " . basename($migrationPath));
                }
            }

            return 0;
        }

        $this->migrator->run($migratorScope, $module, $group);
        $this->success("Migrations completed successfully");
        return 0;
    }
}
