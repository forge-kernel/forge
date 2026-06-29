<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\Tests;

use Modules\ForgeDatabaseSQL\DB\Enums\ActiveStatus;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;

#[Group("forgedatabase-enums")]
final class ActiveStatusTest extends TestCase
{
    #[Test("enum has expected cases")]
    public function has_expected_cases(): void
    {
        $this->assertSame('ACTIVE', ActiveStatus::ACTIVE->value);
        $this->assertSame('COMPLETED', ActiveStatus::COMPLETED->value);
        $this->assertSame('PENDING_DELETION', ActiveStatus::PENDING_DELETION->value);
        $this->assertSame('ARCHIVED', ActiveStatus::ARCHIVED->value);
    }

    #[Test("enum can be instantiated from string value")]
    public function from_string(): void
    {
        $this->assertSame(ActiveStatus::ACTIVE, ActiveStatus::from('ACTIVE'));
        $this->assertSame(ActiveStatus::COMPLETED, ActiveStatus::from('COMPLETED'));
    }

    #[Test("enum has exactly 4 cases")]
    public function count_cases(): void
    {
        $this->assertCount(4, ActiveStatus::cases());
    }
}
