<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\Tests;

use Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;

#[Group("forgedatabase-enums")]
final class ColumnTypeTest extends TestCase
{
    #[Test("withLength returns VARCHAR(n) for STRING type")]
    public function with_length_string(): void
    {
        $this->assertSame('VARCHAR(100)', ColumnType::STRING->withLength(100));
    }

    #[Test("withLength returns raw value for non-STRING type")]
    public function with_length_non_string(): void
    {
        $this->assertSame('INTEGER', ColumnType::INTEGER->withLength(50));
    }

    #[Test("withPrecisionScale returns DECIMAL(p,s) for DECIMAL type")]
    public function with_precision_scale_decimal(): void
    {
        $this->assertSame('DECIMAL(12, 4)', ColumnType::DECIMAL->withPrecisionScale(12, 4));
    }

    #[Test("withPrecisionScale returns raw value for non-DECIMAL type")]
    public function with_precision_scale_non_decimal(): void
    {
        $this->assertSame('FLOAT', ColumnType::FLOAT->withPrecisionScale(10, 2));
    }

    #[Test("defaultValue returns correct defaults per type")]
    public function default_values(): void
    {
        $this->assertSame(0, ColumnType::INTEGER->defaultValue());
        $this->assertSame('', ColumnType::STRING->defaultValue());
        $this->assertFalse(ColumnType::BOOLEAN->defaultValue());
        $this->assertSame(0.0, ColumnType::FLOAT->defaultValue());
        $this->assertSame('0.00', ColumnType::DECIMAL->defaultValue());
        $this->assertNull(ColumnType::DATE->defaultValue());
        $this->assertNull(ColumnType::DATETIME->defaultValue());
        $this->assertSame('CURRENT_TIMESTAMP', ColumnType::TIMESTAMP->defaultValue());
    }

    #[Test("has all expected column types")]
    public function all_types(): void
    {
        $expected = ['UUID', 'STRING', 'TEXT', 'INTEGER', 'BOOLEAN', 'FLOAT', 'DECIMAL', 'DATE', 'DATETIME', 'TIMESTAMP', 'ENUM', 'JSON'];
        $values = array_map(fn($c) => $c->value, ColumnType::cases());
        foreach ($expected as $type) {
            $this->assertContains($type, $values);
        }
    }
}
