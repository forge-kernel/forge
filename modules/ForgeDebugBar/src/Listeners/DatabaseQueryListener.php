<?php

namespace App\Modules\ForgeDebugBar\Listeners;

use App\Modules\ForgeRouter\Contracts\DebugBarInterface;

class DatabaseQueryListener
{
    private DebugBarInterface $debugBar;

    public function __construct(DebugBarInterface $debugBar)
    {
        $this->debugBar = $debugBar;
    }

    public function handle(mixed $event): void
    {
        // if ($event instanceof DatabaseQueryExecuted) {
        //     /** @var DatabaseCollector $databaseCollectorInstance */
        //     $databaseCollectorInstance = Container::getInstance()->get(DatabaseCollector::class);
        //     $databaseCollectorInstance::instance()->addQuery(
        //         $event->query,
        //         $event->bindings,
        //         $event->timeInMilliseconds,
        //         $event->connectionName,
        //         $event->origin
        //     );
        // }
    }
}
