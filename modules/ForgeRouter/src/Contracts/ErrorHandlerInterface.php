<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Contracts;

use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use Throwable;

/**
 * Interface for error handlers that can be registered with the kernel.
 * Modules implementing this interface will be automatically discovered
 * and used for error handling.
 */
interface ErrorHandlerInterface
{
  /**
   * Handle an exception/error and return an appropriate response.
   *
   * @param Throwable $e The exception or error to handle
   * @param Request $request The current request
   * @return Response The response to send
   */
  public function handle(Throwable $e, Request $request): Response;
}
