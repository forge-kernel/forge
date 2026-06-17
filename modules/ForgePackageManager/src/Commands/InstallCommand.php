<?php
declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Commands;

use App\Modules\ForgePackageManager\Services\PackageManagerService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\CLI\Attributes\CoreCommand;
use Throwable;

#[CoreCommand]
#[Cli(
    command: 'package:install-project',
    description: 'Install modules from forge-lock.json and scaffold app folder structure',
    usage: 'package:install-project',
    examples: [
        'package:install-project  # Install all modules from lock file and scaffold app structure'
    ]
)]
final class InstallCommand extends Command
{
    use Wizard;
    
    public function __construct(private readonly PackageManagerService $packageManagerService)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        try {
            $this->packageManagerService->installFromLock();
            $this->success("Modules installed successfully");
            
            $this->line('');
            $this->packageManagerService->scaffoldAppStructure();
            
            return 0;
        } catch (Throwable $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}