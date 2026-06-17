<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

use App\Modules\ForgeDeployment\Services\DeploymentConfigReader;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;

#[Cli(
  command: 'forge-deployment:init',
  description: 'Initialize deployment configuration file',
  usage: 'forge-deployment:init [--file=forge-deployment.php]',
  examples: [
    'forge-deployment:init',
    'forge-deployment:init --file=./deployment.php',
  ]
)]
final class InitDeploymentConfigCommand extends Command
{
  use Wizard;

  #[Arg(name: 'file', description: 'Config file name', default: 'forge-deployment.php')]
  private string $file = 'forge-deployment.php';

  public function __construct(
    private readonly DeploymentConfigReader $configReader
  ) {
  }

  public function execute(array $args): int
  {
    $this->wizard($args);

    $configPath = BASE_PATH . '/' . $this->file;

    if (file_exists($configPath)) {
      $overwrite = $this->askYesNo("Configuration file {$this->file} already exists. Overwrite? (y/n)", 'y');
      if (!$overwrite) {
        $this->info('Cancelled.');
        return 0;
      }
    }

    $template = $this->configReader->generateConfigTemplate();
    file_put_contents($configPath, $template);

    $this->success("Deployment configuration file created: {$configPath}");
    $this->info("Edit this file to customize your deployment settings.");
    $this->info("All settings are optional - the wizard will prompt for missing values.");

    return 0;
  }

  private function askYesNo(string $question, string $expected): bool
  {
    $this->prompt($question . ': ');
    $answer = trim(fgets(STDIN));
    return strtolower($answer) === strtolower($expected);
  }
}
