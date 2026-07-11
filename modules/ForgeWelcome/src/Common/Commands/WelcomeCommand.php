<?php
declare(strict_types=1);

namespace Modules\ForgeWelcome\Common\Commands;

use Forge\CLI\Command;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Traits\CliGenerator;
use Forge\Traits\StringHelper;

#[CLI(command: 'forge-welcome:greet', description: 'An example command to greet the user')]
final class WelcomeCommand extends Command
{
    use StringHelper;
    use CliGenerator;

    #[Arg(name: 'name', description: 'Whats your name')]
    private string $name = '';

    public function execute(array $args): int
    {
        $this->wizard($args);

        $this->log("Hi {$this->name} ", 'testingCommand');
        return 0;
    }
}
