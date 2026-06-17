<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;

#[Cli(
  command: 'forge-deployment:fix-permissions',
  description: 'Fix file permissions and ownership in the current project root',
  usage: 'forge-deployment:fix-permissions',
  examples: [
    'forge-deployment:fix-permissions',
  ]
)]
final class FixPermissionsCommand extends Command
{
  use OutputHelper;

  public function execute(array $args): int
  {
    $projectRoot = defined('BASE_PATH') ? BASE_PATH : getcwd();

    $this->info("Fixing permissions in {$projectRoot}...");

    $this->info("Setting ownership to www-data:www-data...");
    exec("chown -R www-data:www-data " . escapeshellarg($projectRoot), $output, $resultCode);
    if ($resultCode !== 0) {
      $this->warning("Failed to set ownership to www-data:www-data. You might need to run this with sudo.");
    }

    $this->info("Setting directory permissions to 755...");
    exec("find " . escapeshellarg($projectRoot) . " -type d -exec chmod 755 {} \\;", $output, $resultCode);
    if ($resultCode !== 0) {
      $this->error("Failed to set directory permissions.");
    }

    $this->info("Setting file permissions to 644...");
    exec("find " . escapeshellarg($projectRoot) . " -type f -exec chmod 644 {} \\;", $output, $resultCode);
    if ($resultCode !== 0) {
      $this->error("Failed to set file permissions.");
    }

    $this->info("Setting storage and cache permissions to 775...");
    $storagePath = $projectRoot . "/storage";
    if (is_dir($storagePath)) {
      exec("chmod -R 775 " . escapeshellarg($storagePath), $output, $resultCode);
    }

    $cachePath = $projectRoot . "/bootstrap/cache";
    if (is_dir($cachePath)) {
      exec("chmod -R 775 " . escapeshellarg($cachePath), $output, $resultCode);
    }

    $this->success("Permissions fixed successfully!");

    return 0;
  }
}
