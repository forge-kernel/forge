<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Contracts;

use ReflectionMethod;

/**
 * Interface for route modifiers that can modify routes during registration.
 * Modules implementing this interface will be called when routes are being registered.
 */
interface RouteModifierInterface
{
  /**
   * Modify route data before it's registered.
   *
   * @param array $routeData The route data array containing:
   *   - controller: string
   *   - method: string
   *   - handler: array
   *   - params: array
   *   - middleware: array
   *   - permissions: array
   * @param ReflectionMethod $method The reflection of the controller method
   * @return array The modified route data
   */
  public function modifyRoute(array $routeData, ReflectionMethod $method): array;
}
