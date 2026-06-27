<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Tests;

use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use App\Modules\ForgeRouter\Collectors\TimelineCollector;
use App\Modules\ForgeRouter\Http\Request;

#[Group('collectors')]
final class TimelineCollectorTest extends TestCase
{
    private function getRequest(): Request
    {
        return new Request([], [], ['REQUEST_URI' => '/'], 'GET', []);
    }

    #[Test('collector initializes with start time and empty events')]
    public function initializes_cleanly(): void
    {
        $collector = new TimelineCollector();
        $this->assertNotNull($collector->getStartTime());
        $this->assertEquals([], $collector->getEvents());
        $this->assertEquals([], $collector->collect($this->getRequest()));
    }

    #[Test('addEvent stores event with relative time')]
    public function add_event_stores_data(): void
    {
        $collector = new TimelineCollector();
        usleep(1000);
        $collector->addEvent('db.query', 'Query executed', ['sql' => 'SELECT 1']);

        $events = $collector->getEvents();
        $this->assertEquals(1, count($events));
        $this->assertEquals('db.query', $events[0]['name']);
        $this->assertEquals('Query executed', $events[0]['label']);
        $this->assertEquals('SELECT 1', $events[0]['data']['sql']);
        $this->assertTrue($events[0]['relative_time'] > 0);
        $this->assertNotNull($events[0]['origin']);
    }

    #[Test('reset clears events and sets new start time')]
    public function reset_clears_state(): void
    {
        $collector = new TimelineCollector();
        $oldStart = $collector->getStartTime();

        $collector->addEvent('test');
        $this->assertEquals(1, count($collector->getEvents()));

        usleep(1000);
        $collector->reset();

        $this->assertEquals([], $collector->getEvents());
        $this->assertTrue($collector->getStartTime() > $oldStart);
    }

    #[Test('setStartTime recalculates relative times')]
    public function set_start_time_recalculates(): void
    {
        $collector = new TimelineCollector();
        $pastStart = microtime(true) - 1.0;

        $collector->addEvent('event1');
        $collector->setStartTime($pastStart);

        $events = $collector->getEvents();
        $this->assertTrue($events[0]['relative_time'] >= 1000);
        $this->assertEquals($pastStart, $collector->getStartTime());
    }
}
