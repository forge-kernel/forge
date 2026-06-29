<?php

namespace Modules\ForgeTesting\Traits;

use Forge\Core\Helpers\FileExistenceCache;
use Modules\ForgeRouter\Http\Response;
use RuntimeException;

trait Assertions
{
    protected function shouldFail(
        callable $callback,
        ?string  $expectedException = null,
        string   $message = "",
    ): void
    {
        try {
            $callback();
            $this->fail(
                $message ?:
                    "Expected code to throw an exception, but none was thrown.",
            );
        } catch (\Throwable $e) {
            if ($expectedException && !($e instanceof $expectedException)) {
                throw new RuntimeException(
                    $message ?:
                        sprintf(
                            'Expected exception of type "%s", got "%s"',
                            $expectedException,
                            get_class($e),
                        ),
                    0,
                    $e,
                );
            }
        }
    }

    protected function fail(string $message): void
    {
        throw new RuntimeException($message);
    }

    protected function assertNotEquals(
        $expected,
        $actual,
        string $message = "",
    ): void
    {
        if ($expected == $actual) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that %s is not equal to %s.",
                        var_export($actual, true),
                        var_export($expected, true),
                    ),
            );
        }
    }

    protected function assertSame(
        $expected,
        $actual,
        string $message = "",
    ): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that two variables are identical.\n--- Expected\n+++ Actual\n@@ @@\n-%s (%s)\n+%s (%s)\n",
                        var_export($expected, true),
                        gettype($expected),
                        var_export($actual, true),
                        gettype($actual),
                    ),
            );
        }
    }

    protected function assertNotSame(
        $expected,
        $actual,
        string $message = "",
    ): void
    {
        if ($expected === $actual) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that %s is not identical to %s.",
                        var_export($actual, true),
                        var_export($expected, true),
                    ),
            );
        }
    }

    protected function assertInstanceOf(
        string $expected,
               $actual,
        string $message = "",
    ): void
    {
        if (!($actual instanceof $expected)) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Expected instance of %s, got %s",
                        $expected,
                        is_object($actual) ? get_class($actual) : gettype($actual),
                    ),
            );
        }
    }

    protected function assertNotInstanceOf(
        string $expected,
        object $actual,
        string $message = "",
    ): void
    {
        if ($actual instanceof $expected) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that %s is not an instance of class '%s'.",
                        get_class($actual),
                        $expected,
                    ),
            );
        }
    }

    protected function assertCount(
        int      $expected,
        iterable $actual,
        string   $message = "",
    ): void
    {
        $count = is_array($actual) ? count($actual) : iterator_count($actual);
        if ($count !== $expected) {
            throw new RuntimeException(
                $message ?: "Expected $expected items, got $count",
            );
        }
    }

    protected function assertArrayNotHasKey(
        $key,
        array $array,
        string $message = "",
    ): void
    {
        if (array_key_exists($key, $array)) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that an array does not have the key '%s'.",
                        $key,
                    ),
            );
        }
    }

    protected function assertArrayHasKey(
        $key,
        array $array,
        string $message = "",
    ): void
    {
        if (!array_key_exists($key, $array)) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that an array has the key '%s'.",
                        $key,
                    ),
            );
        }
    }

    protected function assertFalse($actual, string $message = ""): void
    {
        $this->assertTrue(
            $actual === false,
            $message ?: "Expected false, got " . var_export($actual, true),
        );
    }

    protected function assertTrue($actual, string $message = ""): void
    {
        if ($actual !== true) {
            throw new RuntimeException(
                $message ?: "Expected true, got " . var_export($actual, true),
            );
        }
    }

    protected function assertGreaterThan(
        $expected,
        $actual,
        string $message = "",
    ): void
    {
        $this->assertTrue(
            $actual > $expected,
            $message ?:
                sprintf(
                    "Failed asserting that %s is greater than %s.",
                    var_export($actual, true),
                    var_export($expected, true),
                ),
        );
    }

    protected function assertLessThan(
        $expected,
        $actual,
        string $message = "",
    ): void
    {
        $this->assertTrue(
            $actual < $expected,
            $message ?:
                sprintf(
                    "Failed asserting that %s is less than %s.",
                    var_export($actual, true),
                    var_export($expected, true),
                ),
        );
    }

    protected function assertLessThanOrEqual(
        $expected,
        $actual,
        string $message = "",
    ): void
    {
        $this->assertTrue(
            $actual <= $expected,
            $message ?:
                sprintf(
                    "Failed asserting that %s is less than or equal to %s.",
                    var_export($actual, true),
                    var_export($expected, true),
                ),
        );
    }

    protected function assertNull($value, string $message = ""): void
    {
        if (!is_null($value)) {
            throw new RuntimeException(
                $message ?: "Expected null, got " . var_export($value, true),
            );
        }
    }

    protected function assertNotNull($actual, string $message = ""): void
    {
        if (is_null($actual)) {
            throw new RuntimeException(
                $message ?: "Failed asserting that a value is not null.",
            );
        }
    }

    protected function assertNotEmpty($actual, string $message = ""): void
    {
        if (empty($actual)) {
            throw new RuntimeException(
                $message ?: "Failed asserting that a value is not empty.",
            );
        }
    }

    protected function assertEmpty($actual, string $message = ""): void
    {
        if (!empty($actual)) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that %s is empty.",
                        var_export($actual, true),
                    ),
            );
        }
    }

    protected function assertStringContainsString(
        string $needle,
        string $haystack,
        string $message = "",
    ): void
    {
        if (!str_contains($haystack, $needle)) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that '%s' contains '%s'.",
                        $haystack,
                        $needle,
                    ),
            );
        }
    }

    protected function assertStringNotContainsString(
        string $needle,
        string $haystack,
        string $message = "",
    ): void
    {
        if (str_contains($haystack, $needle)) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that '%s' does not contain '%s'.",
                        $haystack,
                        $needle,
                    ),
            );
        }
    }

    protected function assertMatchesRegularExpression(
        string $pattern,
        string $string,
        string $message = "",
    ): void
    {
        if (!preg_match($pattern, $string)) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that '%s' matches pattern '%s'.",
                        $string,
                        $pattern,
                    ),
            );
        }
    }

    protected function assertDoesNotMatchRegularExpression(
        string $pattern,
        string $string,
        string $message = "",
    ): void
    {
        if (preg_match($pattern, $string)) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that '%s' does not match pattern '%s'.",
                        $string,
                        $pattern,
                    ),
            );
        }
    }

    protected function assertJsonStringEqualsJsonString(
        string $expectedJson,
        string $actualJson,
        string $message = "",
    ): void
    {
        try {
            $expectedData = json_decode(
                $expectedJson,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            $actualData = json_decode(
                $actualJson,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            throw new RuntimeException(
                "Invalid JSON provided: " . $e->getMessage(),
            );
        }

        $this->assertEquals(
            $expectedData,
            $actualData,
            $message ?: "JSON structures do not match",
        );
    }

    protected function assertEquals(
        $expected,
        $actual,
        string $message = "",
    ): void
    {
        if ($expected != $actual) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that %s is equal to %s.\n--- Expected\n+++ Actual\n@@ @@\n-%s\n+%s\n",
                        var_export($actual, true),
                        var_export($expected, true),
                        var_export($expected, true),
                        var_export($actual, true),
                    ),
            );
        }
    }

    protected function assertContains(
        $needle,
        iterable $haystack,
        string $message = "",
    ): void
    {
        $found = false;
        foreach ($haystack as $item) {
            if ($item === $needle) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that an iterable contains %s.",
                        var_export($needle, true),
                    ),
            );
        }
    }

    protected function assertNotContains(
        $needle,
        iterable $haystack,
        string $message = "",
    ): void
    {
        $found = false;
        foreach ($haystack as $item) {
            if ($item === $needle) {
                $found = true;
                break;
            }
        }
        if ($found) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that an iterable does not contain %s.",
                        var_export($needle, true),
                    ),
            );
        }
    }

    protected function assertHttpStatus(int $expected, Response $response): void
    {
        $actual = $response->getStatusCode();
        if ($actual !== $expected) {
            throw new RuntimeException(
                "Expected HTTP status $expected but got $actual",
            );
        }
    }


    protected function assertGreaterThanOrEqual(
        $expected,
        $actual,
        string $message = "",
    ): void
    {
        $this->assertTrue(
            $actual >= $expected,
            $message ?:
                sprintf(
                    "Failed asserting that %s is greater than or equal to %s.",
                    var_export($actual, true),
                    var_export($expected, true),
                ),
        );
    }

    protected function assertFileExists(
        string $filename,
        string $message = "",
    ): void
    {
        if (!FileExistenceCache::exists($filename)) {
            throw new RuntimeException(
                $message ?:
                    sprintf("Failed asserting that file '%s' exists.", $filename),
            );
        }
        if (!is_file($filename)) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that '%s' is a file (it exists but is not a file).",
                        $filename,
                    ),
            );
        }
    }

    protected function assertFileDoesNotExist(
        string $filename,
        string $message = "",
    ): void
    {
        if (file_exists($filename) && is_file($filename)) {
            throw new RuntimeException(
                $message ?:
                    sprintf(
                        "Failed asserting that file '%s' does not exist.",
                        $filename,
                    ),
            );
        }
    }
}
