<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Commands;

use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\CoreCommand;
use Forge\CLI\Command;
use Modules\ForgeRouter\Traits\ManagesMaintenanceMode;

#[CoreCommand]
#[Cli(
    command: 'up',
    description: 'Disable maintenance mode',
    usage: 'up',
    examples: ['up']
)]
final class MaintenanceUpCommand extends Command
{
    use ManagesMaintenanceMode;

    public function execute(array $args): int
    {
        return $this->disableMaintenance();
    }
}
