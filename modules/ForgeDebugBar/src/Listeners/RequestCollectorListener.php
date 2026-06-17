<?php

namespace App\Modules\ForgeDebugBar\Listeners;

use App\Modules\ForgeRouter\Contracts\DebugBarInterface;
use Forge\Core\DI\Container;
use App\Modules\ForgeDebugBar\Collectors\RequestCollector;

class RequestCollectorListener
{
    public function handle(array $event): void
    {
        $container = Container::getInstance();
        /** @var DebugBarInterface $debugBar */
        $debugBar = $container->get(DebugBarInterface::class);
        $debugBar->addCollector('request', function () use ($event) {
            return RequestCollector::collect($event['request']);
        });
    }
}
