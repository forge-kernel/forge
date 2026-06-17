<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Collectors;

use App\Modules\ForgeRouter\Contracts\RequestCollectorInterface;
use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Request;
use Forge\Core\Module\Attributes\Provides;
use Throwable;

/**
 * Exception collector that tracks all exceptions thrown during a request.
 * This collector is independent of any specific module and can be used by any module.
 */
#[Service]
#[Provides(RequestCollectorInterface::class, version: '1.0.0')]
final class ExceptionCollector implements RequestCollectorInterface
{
  private array $exceptions = [];

  /**
   * Collect exception data for the request.
   * This is called by the Kernel during request handling.
   *
   * @param Request $request The current request
   * @return array The collected exception data
   */
  public function collect(Request $request): array
  {
    return $this->exceptions;
  }

  /**
   * Add an exception to the collection.
   *
   * @param Throwable $exception The exception to collect
   * @return void
   */
  public function addException(Throwable $exception): void
  {
    $file = $exception->getFile();
    $line = $exception->getLine();

    if (str_starts_with($file, BASE_PATH)) {
      $file = substr($file, strlen(BASE_PATH));
    }

    $this->exceptions[] = [
      'type' => get_class($exception),
      'message' => $exception->getMessage(),
      'code' => $exception->getCode(),
      'file' => $file . ':' . $line,
      'trace' => $exception->getTraceAsString(),
    ];
  }

  /**
   * Get all collected exceptions.
   *
   * @return array
   */
  public function getExceptions(): array
  {
    return $this->exceptions;
  }

  /**
   * Reset the collector (clear all exceptions).
   *
   * @return void
   */
  public function reset(): void
  {
    $this->exceptions = [];
  }
}
