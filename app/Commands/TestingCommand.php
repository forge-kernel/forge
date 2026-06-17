<?php

declare(strict_types=1);

namespace App\Commands;

use Exception;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\CliGenerator;
use Forge\Traits\StringHelper;

#[Cli(
  command: 'testing:greet',
  description: 'This is just a test',
  usage: 'testing:greet [--name=Example]',
  examples: [
    'testing:greet --name=Example',
    'testing:greet   (starts wizard)',
  ]
)]
final class testingCommand extends Command
{
  use StringHelper;
  use CliGenerator;

  #[Arg(name: 'name', description: 'Whats your name')]
  private string $name = '';

  /**
   * @throws Exception
   */
  public function execute(array $args): int
  {
    $this->wizard($args);

    $this->log("Hi {$this->name} ", 'testingCommand');
    return 0;
  }
}
