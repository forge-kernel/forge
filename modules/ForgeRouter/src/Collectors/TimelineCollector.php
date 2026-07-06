<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Collectors;

use Modules\ForgeRouter\Contracts\RequestCollectorInterface;
use Forge\Core\Helpers\Debuger;
use Modules\ForgeRouter\Http\Request;
use Forge\Core\Module\Attributes\Provides;

/**
 * Timeline collector that tracks events during a request lifecycle.
 * This collector is independent of any specific module and can be used by any module.
 */
#[Provides(RequestCollectorInterface::class, version: '1.0.0')]
final class TimelineCollector implements RequestCollectorInterface
{
    private array $events = [];
    private ?float $startTime = null;

    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    /**
     * Collect timeline events for the request.
     * This is called by the Kernel during request handling.
     *
     * @param Request $request The current request
     * @return array The collected timeline events
     */
    public function collect(Request $request): array
    {
        return $this->events;
    }

    /**
     * Add a timeline event.
     *
     * @param string $name The event name
     * @param string $label The event label
     * @param array $data Additional event data
     * @return void
     */
    public function addEvent(string $name, string $label = 'event', array $data = []): void
    {
        $this->events[] = [
            'name' => $name,
            'label' => $label,
            'time' => microtime(true),
            'relative_time' => $this->startTime ? (microtime(true) - $this->startTime) * 1000 : 0,
            'data' => $data,
            'origin' => Debuger::backtraceOrigin(),
        ];
    }

    /**
     * Get all collected timeline events.
     *
     * @return array
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * Get the start time of the timeline.
     *
     * @return float|null
     */
    public function getStartTime(): ?float
    {
        return $this->startTime;
    }

    /**
     * Reset the collector (clear all events).
     *
     * @return void
     */
    public function reset(): void
    {
        $this->events = [];
        $this->startTime = microtime(true);
    }

    /**
     * Set a new start time and recalculate relative times for all events.
     * This is useful when you want to reset the timeline for a new request
     * but keep events that were added before the request started.
     *
     * @param float $newStartTime The new start time (microtime)
     * @return void
     */
    public function setStartTime(float $newStartTime): void
    {
        $oldStartTime = $this->startTime ?? $newStartTime;
        $this->startTime = $newStartTime;

        foreach ($this->events as &$event) {
            $event['relative_time'] = ($event['time'] - $newStartTime) * 1000;
        }
        unset($event);
    }
}
