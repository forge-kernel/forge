<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Commands;

use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

#[Cli(
    command: 'modules:forge-router:cleanup',
    description: 'Remove public/ entry point, root index.php, and config files scaffolded by forge-router:init',
    usage: 'modules:forge-router:cleanup [--force]',
    examples: [
        'modules:forge-router:cleanup',
        'modules:forge-router:cleanup --force'
    ]
)]
final class CleanupCommand extends Command
{
    use Wizard;

    private const array TARGETS = [
        '/public/index.php',
        '/public/.htaccess',
        '/public',
        '/index.php',
        '/config/middleware.php',
        '/config/forge_router.php',
    ];

    #[Arg(
        name: 'force',
        description: 'Skip confirmation prompt',
        default: false,
        required: false,
    )]
    private bool $force;

    public function execute(array $args): int
    {
        $this->wizard($args);

        if (!$this->force) {
            $this->warning('This will permanently delete public/, root index.php, and ForgeRouter config files.');
            $this->prompt("\033[1;36mAre you sure? (yes/no):\033[0m ");
            $input = trim(fgets(STDIN));
            if (!in_array(strtolower($input), ['yes', 'y'], true)) {
                $this->info('Aborted.');
                return 0;
            }
        }

        $anyRemoved = false;

        foreach (self::TARGETS as $target) {
            $path = BASE_PATH . $target;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                if (!is_dir($path)) {
                    $this->success("Removed directory: {$target}");
                    $anyRemoved = true;
                }
            } elseif (is_file($path)) {
                unlink($path);
                if (!is_file($path)) {
                    $this->success("Removed: {$target}");
                    $anyRemoved = true;
                }
            }
        }

        if (!$anyRemoved) {
            $this->info('Nothing to clean up.');
        }

        return 0;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }
}
