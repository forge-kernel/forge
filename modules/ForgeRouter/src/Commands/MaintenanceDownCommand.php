<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Commands;

use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\CoreCommand;
use Forge\CLI\Command;
use Modules\ForgeRouter\Traits\ManagesMaintenanceMode;

#[CoreCommand]
#[Cli(
    command: 'down',
    description: 'Put the application into maintenance mode',
    usage: 'down',
    examples: ['down']
)]
final class MaintenanceDownCommand extends Command
{
    use ManagesMaintenanceMode;

    public function execute(array $args): int
    {
        return $this->enableMaintenance();
    }
}
