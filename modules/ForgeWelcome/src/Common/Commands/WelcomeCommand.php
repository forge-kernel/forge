<?php
declare(strict_types=1);

namespace Modules\ForgeWelcome\Common\Commands;

use Forge\CLI\Command as CommandBase;
use Forge\CLI\Attributes\Command;
use Forge\Core\Config\Config;

#[Command(command: 'forge-welcome:greet', description: 'An example command to greet the user')]
class WelcomeCommand extends CommandBase
{
    public function __construct(private Config $config)
    {
        $settingOne = $config->get('forgewelcome.example');
    }
    public function execute(array $args): int
    {
        $name = $this->argument('name', $args) ?? 'Guest';
        $this->info("Hello, " . $name . " from the ForgeWelcome");
        return 0;
    }
}
