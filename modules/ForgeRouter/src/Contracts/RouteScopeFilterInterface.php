<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Contracts;

use ReflectionClass;
use ReflectionMethod;

/**
 * Interface for route scope filters that can control which routes
 * are registered based on scope (e.g., tenant vs central domain).
 * Modules implementing this interface will be automatically discovered
 * and used by the Router for route filtering.
 */
interface RouteScopeFilterInterface
{
  /**
   * Check if the current domain is a central domain.
   * This can be a static method for convenience.
   *
   * @return bool True if on central domain, false otherwise
   */
  public static function isCentralDomain(): bool;

  /**
   * Extract scope from a controller class or method reflection.
   *
   * @param ReflectionClass|ReflectionMethod $reflection The reflection to extract scope from
   * @return object|null The scope attribute instance, or null if not found
   */
  public function extractScope(ReflectionClass|ReflectionMethod $reflection): ?object;

  /**
   * Check if a scope is allowed in the current context.
   *
   * @param object $scope The scope attribute instance
   * @param bool $onCentral Whether we're on a central domain
   * @return bool True if the scope is allowed, false otherwise
   */
  public function allowedHere(object $scope, bool $onCentral): bool;
}
