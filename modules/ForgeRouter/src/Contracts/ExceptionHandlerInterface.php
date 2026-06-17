<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Contracts;

use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use Throwable;

/**
 * Interface for exception handlers that can intercept exceptions
 * before they reach the error handler. Multiple handlers can be registered
 * and will be called in order until one handles the exception.
 */
interface ExceptionHandlerInterface
{
  /**
   * Handle an exception. Return a Response if handled, or null to pass to next handler.
   *
   * @param Throwable $e The exception to handle
   * @param Request $request The current request
   * @return Response|null The response if handled, or null to continue to next handler
   */
  public function handle(Throwable $e, Request $request): ?Response;
}
