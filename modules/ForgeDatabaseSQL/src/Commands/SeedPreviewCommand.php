<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\Commands;

use Modules\ForgeDatabaseSQL\DB\Seeders\SeederManager;
use Exception;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\CLI\Attributes\CoreCommand;
use Forge\Traits\StringHelper;

#[CoreCommand]
#[Cli(
    command: 'db:seed:preview',
    description: 'Preview all available seeders and their status.',
    usage: 'seed:preview [--type=all|app|module] [--module=ModuleName]',
    examples: [
        'db:seed:preview --type=app',
        'db:seed:preview --type=module --module=Blog',
        'db:seed:preview   (starts wizard)',
    ]
)]
final class SeedPreviewCommand extends Command
{
    use StringHelper;
    use Wizard;

    #[Arg(name: 'type', description: 'Seeder type: all, app, module, tenants', validate: 'all|app|module|tenants')]
    private string $type = 'all';
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

        $type = $this->type ?? null;
        $module = $this->module ? $this->toPascalCase($this->module) : null;

        $allSeeders = $this->manager->discoverSeeders($type, $module);
        $ranSeeders = $this->manager->getAllRanSeedersWithDetails();

        if (empty($allSeeders)) {
            $this->info("No seeders found.");
            return 0;
        }

        $this->line("");
        $this->info("Seeder Preview:");
        $tableRows = [];

        foreach ($allSeeders as $seeder) {
            $nameForDisplay = $seeder['name'];
            $source = strtoupper($seeder['source']);

            $lookupKey = $nameForDisplay;
            $details = $ranSeeders[$lookupKey] ?? null;

            $batch = $details['batch'] ?? '-';
            $ranAt = $details['ran_at'] ?? '-';
            $status = $details
                ? "\033[1;32mRan\033[0m"
                : "\033[0;33mPending\033[0m";

            $tableRows[] = [
                'NAME' => $nameForDisplay,
                'SOURCE' => $source,
                'BATCH' => $batch,
                'RAN AT' => $ranAt,
                'STATUS' => $status,
            ];
        }

        $headers = ['NAME', 'SOURCE', 'BATCH', 'RAN AT', 'STATUS'];
        $this->table($headers, $tableRows);
        $this->line("");

        return 0;
    }
}
