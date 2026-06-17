<?php

declare(strict_types=1);

namespace App\Modules\ForgeTesting;

use App\Modules\ForgeTesting\Attributes\AfterEach;
use App\Modules\ForgeTesting\Attributes\BeforeEach;
use App\Modules\ForgeTesting\Traits\Assertions;
use App\Modules\ForgeTesting\Traits\CacheTesting;
use App\Modules\ForgeTesting\Traits\DatabaseTesting;
// use App\Modules\ForgeTesting\Traits\HttpTesting; // Temporarily disabled
use App\Modules\ForgeTesting\Traits\PerformanceTesting;
use RuntimeException;

abstract class TestCase
{
  use Assertions;

  use DatabaseTesting;
  use PerformanceTesting;
  // use HttpTesting; // Temporarily disabled
  use CacheTesting;

  #[BeforeEach]
  public function setup(): void
  {
    // Setup logic can be added here if needed
  }

  #[AfterEach]
  public function tearDown(): void
  {
  }

  protected function markTestIncomplete(string $message = ""): void
  {
    throw new RuntimeException("TEST_INCOMPLETE: $message");
  }

  protected function markTestSkipped(string $message = ""): void
  {
    throw new RuntimeException("TEST_SKIPPED: $message");
  }
}
