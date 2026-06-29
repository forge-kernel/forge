<?php
declare(strict_types=1);

namespace Modules\ForgeSqlOrm\Tests;

use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use DateTimeImmutable;
use function Modules\ForgeSqlOrm\ORM\Values\cast;
use Modules\ForgeSqlOrm\ORM\Values\Cast;

#[Group("forgesql-cast")]
final class CastTest extends TestCase
{
    #[Test("cast int")]
    public function cast_int(): void
    {
        $this->assertSame(42, cast('42', Cast::INT));
        $this->assertSame(0, cast('abc', Cast::INT));
        $this->assertSame(null, cast(null, Cast::INT));
    }

    #[Test("cast float")]
    public function cast_float(): void
    {
        $this->assertSame(3.14, cast('3.14', Cast::FLOAT));
        $this->assertSame(null, cast(null, Cast::FLOAT));
    }

    #[Test("cast bool")]
    public function cast_bool(): void
    {
        $this->assertTrue(cast('1', Cast::BOOL));
        $this->assertTrue(cast(1, Cast::BOOL));
        $this->assertTrue(cast('true', Cast::BOOL));
        $this->assertFalse(cast('0', Cast::BOOL));
        $this->assertFalse(cast(0, Cast::BOOL));
        $this->assertFalse(cast('false', Cast::BOOL));
        $this->assertFalse(cast('random', Cast::BOOL));
        $this->assertSame(null, cast(null, Cast::BOOL));
    }

    #[Test("cast string")]
    public function cast_string(): void
    {
        $this->assertSame('42', cast(42, Cast::STRING));
        $this->assertSame('hello', cast('hello', Cast::STRING));
        $this->assertSame(null, cast(null, Cast::STRING));
    }

    #[Test("cast json to array")]
    public function cast_json_to_array(): void
    {
        $result = cast('{"a":1,"b":2}', Cast::JSON);
        $this->assertSame(['a' => 1, 'b' => 2], $result);
    }

    #[Test("cast json from array")]
    public function cast_json_from_array(): void
    {
        $result = cast(['a' => 1], Cast::JSON);
        $this->assertSame(['a' => 1], $result);
    }

    #[Test("cast date")]
    public function cast_date(): void
    {
        $result = cast('2024-01-15', Cast::DATE);
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('2024-01-15', $result->format('Y-m-d'));
    }

    #[Test("cast datetime")]
    public function cast_datetime(): void
    {
        $result = cast('2024-01-15 10:30:00', Cast::DATETIME);
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('2024-01-15 10:30:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test("cast null returns null")]
    public function cast_null(): void
    {
        $this->assertSame(null, cast(null, Cast::STRING));
        $this->assertSame(null, cast(null, Cast::INT));
        $this->assertSame(null, cast(null, Cast::JSON));
    }
}
