<?php
declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Services;

use Modules\ForgeMultiTenant\Attributes\TenantScope;
use Modules\ForgeRouter\Contracts\RouteScopeFilterInterface;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Provides;
use Forge\Exceptions\MissingServiceException;
use Forge\Exceptions\ResolveParameterException;
use ReflectionClass;
use ReflectionMethod;

#[Provides(RouteScopeFilterInterface::class, version: '1.0.0')]
final class RouteScopeFilter implements RouteScopeFilterInterface
{
    private static ?bool $isCentral = null;
    private static ?string $lastHost = null;

    public static function isCentralDomain(): bool
    {
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        if ($currentHost !== self::$lastHost) {
            self::$isCentral = null;
            self::$lastHost = $currentHost;
        }

        return self::$isCentral ??= (new TenantManager(Container::getInstance()->get(QueryBuilderInterface::class)))
            ->resolveByDomain($currentHost) === null;
    }

    public static function reset(): void
    {
        self::$isCentral = null;
        self::$lastHost = null;
    }

    /**
     * Extract scope from a controller class or method reflection.
     *
     * @param ReflectionClass|ReflectionMethod $reflection The reflection to extract scope from
     * @return TenantScope|null The scope attribute instance, or null if not found
     */
    public function extractScope(ReflectionClass|ReflectionMethod $reflection): ?TenantScope
    {
        $attrs = $reflection->getAttributes(TenantScope::class);
        return $attrs ? $attrs[0]->newInstance() : null;
    }

    /**
     * Check if a scope is allowed in the current context.
     *
     * @param object $scope The scope attribute instance (should be TenantScope)
     * @param bool $onCentral Whether we're on a central domain
     * @return bool True if the scope is allowed, false otherwise
     */
    public function allowedHere(object $scope, bool $onCentral): bool
    {
        if (!$scope instanceof TenantScope) {
            return true;
        }

        return match ($scope->value) {
            "central" => $onCentral,
            "tenant" => !$onCentral,
            "both" => true,
            default => true,
        };
    }
}
