<?php

declare(strict_types=1);

namespace App\Modules\ForgeLogger\Commands;

use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\Core\Config\Config;

#[Cli(
  command: 'log:clear',
  description: 'Clear application logs',
  usage: 'log:clear',
  examples: [
    'log:clear  (clears the application logs)'
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

    if (file_exists($path)) {
      if (unlink($path)) {
        $this->success("Logs cleared successfully");
        return 0;
      }
      $this->error("Failed to clear the log file at {$path}");
      return 1;
    }

    $this->info("No log file found at {$path}");
    return 0;
  }
}
