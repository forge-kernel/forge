<?php

declare(strict_types=1);

namespace Modules\ForgeView\tests;

use Modules\ForgeTesting\Attributes\BeforeEach;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Modules\ForgeView\ViewState;

#[Group('view')]
final class ViewStateTest extends TestCase
{
    private ViewState $state;

    #[BeforeEach]
    public function setup(): void
    {
        $this->state = new ViewState();
    }

    #[Test('set and get layout info')]
    public function layout_info(): void
    {
        $this->assertNull($this->state->getLayout());

        $layoutInfo = ['name' => 'app', 'useModulePath' => false, 'moduleName' => null];
        $this->state->setLayout($layoutInfo);

        $this->assertEquals($layoutInfo, $this->state->getLayout());
    }

    #[Test('shouldSuppressLayout controls layout rendering flag')]
    public function suppress_layout(): void
    {
        $this->assertFalse($this->state->shouldSuppressLayout());

        $this->state->setShouldSuppressLayout(true);
        $this->assertTrue($this->state->shouldSuppressLayout());
    }

    #[Test('startSection and endSection capture output buffer')]
    public function sections_capture_output(): void
    {
        $this->state->startSection('content');
        echo "Hello Layout";
        $this->state->endSection();

        $this->assertEquals("Hello Layout", $this->state->getSection('content'));
    }

    #[Test('getSection returns empty string for unknown section')]
    public function unknown_section_returns_empty(): void
    {
        $this->assertEquals("", $this->state->getSection('missing'));
    }

    #[Test('getSlot handles numeric strings and callables')]
    public function slot_resolution(): void
    {
        $this->state->setSlots([
            'static' => 'Text',
            'dynamic' => fn() => 'Computed'
        ]);

        $this->assertEquals('Text', $this->state->getSlot('static'));
        $this->assertEquals('Computed', $this->state->getSlot('dynamic'));
        $this->assertEquals('Fallback', $this->state->getSlot('missing', 'Fallback'));
    }

    #[Test('reset clears all state properties')]
    public function reset_clears_state(): void
    {
        $this->state->setLayout(['name' => 'base']);
        $this->state->setShouldSuppressLayout(true);
        $this->state->startSection('a');
        echo "a";
        $this->state->endSection();
        $this->state->setSlots(['b' => 'c']);

        $this->state->reset();

        $this->assertNull($this->state->getLayout());
        $this->assertFalse($this->state->shouldSuppressLayout());
        $this->assertEquals([], $this->state->getSections());
        $this->assertEquals([], $this->state->getSlots());
    }
}
