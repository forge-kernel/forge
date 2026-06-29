<?php

namespace Modules\ForgeDebugBar\Listeners;

use Modules\ForgeRouter\Contracts\DebugBarInterface;
use Forge\Core\DI\Container;
use Modules\ForgeDebugBar\Collectors\RequestCollector;

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
