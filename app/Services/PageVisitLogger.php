<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\TestPageVisitedEvent;
use App\Modules\ForgeEvents\Attributes\EventListener;
use Forge\Core\DI\Attributes\Service;

#[Service]
class PageVisitLogger
{
    #[EventListener(TestPageVisitedEvent::class)]
    public function handlePageVisit(TestPageVisitedEvent $event): void
    {
        // Implement your logging logic here
        $logEntry = sprintf(
            "User %d visited test page at %s",
            $event->userId,
            $event->visitedAt
        );

        // Example: Write to log file
        file_put_contents(
            BASE_PATH . '/storage/logs/page_visits.log',
            $logEntry . PHP_EOL,
            FILE_APPEND
        );
    }
}
