<?php
declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Middlewares;

use Modules\ForgeMultiTenant\Services\CentralDomain;
use Modules\ForgeMultiTenant\Services\TenantConnectionFactory;
use Modules\ForgeMultiTenant\Services\TenantQueryRewriter;
use Modules\ForgeMultiTenant\Services\TenantSessionProxy;
use Modules\ForgeMultiTenant\Services\TenantCacheProxy;
use Modules\ForgeSqlOrm\ORM\QueryBuilder;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;
use Modules\ForgeRouter\Http\Middleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;
use Forge\Core\Session\SessionInterface;
use Forge\Core\Cache\CacheManager;
use Modules\ForgeMultiTenant\Services\TenantManager;
use Modules\ForgeMultiTenant\Services\RouteScopeFilter;
use Forge\Exceptions\MissingServiceException;
use Forge\Exceptions\ResolveParameterException;
use PDO;
use ReflectionException;

#[RegisterMiddleware(group: "web", order: 1, allowDuplicate: false, overrideClass: null, enabled: true)]
final class TenantMiddleware extends Middleware
{

    public function __construct(
        private readonly TenantManager $tenantManager,
        private readonly TenantQueryRewriter $queryRewriter,
    ) {
    }

    /**
     * @throws ReflectionException
     * @throws MissingServiceException
     * @throws ResolveParameterException
     */
    public function handle(Request $request, callable $next): Response
    {
        $this->tenantManager->clearCache();
        RouteScopeFilter::reset();
        $this->queryRewriter->reset();

        $rawHost = $request->getHeader('Host') ?? $request->serverParams['HTTP_HOST'] ?? '';
        $host = CentralDomain::stripPort($rawHost);

        $tenant = $this->tenantManager->resolveByDomain($host) ?? null;

        if ($tenant !== null) {
            $request->setAttribute('tenant', $tenant);

            $this->queryRewriter->setTenant($tenant);

            $container = Container::getInstance();
            $newConn = $container->get(TenantConnectionFactory::class)->forTenant($tenant);

            $container->setInstance(DatabaseConnectionInterface::class, $newConn);
            $container->setInstance(PDO::class, $newConn->getPdo());
            $container->setInstance(QueryBuilderInterface::class, new QueryBuilder($newConn));

            if ($container->has(SessionInterface::class)) {
                $originalSession = $container->get(SessionInterface::class);
                if (!$originalSession instanceof TenantSessionProxy) {
                    $container->setInstance(SessionInterface::class, new TenantSessionProxy($originalSession, $tenant));
                }
            }

            if ($container->has(CacheManager::class)) {
                $originalCache = $container->get(CacheManager::class);
                if (!$originalCache instanceof TenantCacheProxy) {
                    $container->setInstance(CacheManager::class, new TenantCacheProxy($originalCache, $tenant));
                }
            }
        }

        return $next($request);
    }
}