<?php

declare(strict_types=1);

namespace Modules\ForgeLogger\Commands;

use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\CoreCommand;
use Forge\CLI\Command;
use Forge\Core\Config\Config;

#[CoreCommand]
#[Cli(
    command: 'log:clear',
    description: 'Clear application logs including rotated files',
    usage: 'log:clear',
    examples: [
        'log:clear  (clears the application logs and rotated backups)'
    ]
)]
final class ForgeLoggerCommand extends Command
{
    public function __construct(private readonly Config $config)
    {
    }

    public function execute(array $args): int
    {
        $path = $this->config->get('forge_logger.path', BASE_PATH . '/storage/logs/forge.log');
        $cleared = 0;

        $files = [$path];
        for ($i = 1; $i <= 9; $i++) {
            $files[] = $path . '.' . $i;
        }

        foreach ($files as $file) {
            if (file_exists($file)) {
                if (unlink($file)) {
                    $cleared++;
                } else {
                    $this->error("Failed to clear log file at {$file}");
                    return 1;
                }
            }
        }

        if ($cleared > 0) {
            $this->success("Cleared {$cleared} log file(s) successfully");
            return 0;
        }

        $this->info("No log files found at {$path}");
        return 0;
    }
}
