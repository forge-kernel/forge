<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Middlewares;

use App\Modules\ForgeMultiTenant\Services\CentralDomain;
use App\Modules\ForgeMultiTenant\Services\TenantConnectionFactory;
use App\Modules\ForgeMultiTenant\Services\TenantQueryRewriter;
use App\Modules\ForgeMultiTenant\Services\TenantSessionProxy;
use App\Modules\ForgeMultiTenant\Services\TenantCacheProxy;
use App\Modules\ForgeSqlOrm\ORM\QueryBuilder;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;
use App\Modules\ForgeRouter\Http\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;
use Forge\Core\Session\SessionInterface;
use Forge\Core\Cache\CacheManager;
use App\Modules\ForgeMultiTenant\Services\TenantManager;
use Forge\Exceptions\MissingServiceException;
use Forge\Exceptions\ResolveParameterException;
use PDO;
use ReflectionException;

#[RegisterMiddleware(group: "web", order: 1, allowDuplicate: false, overrideClass: null, enabled: true)]
final class TenantMiddleware extends Middleware
{

    public function __construct(private readonly TenantManager $tenantManager)
    {
    }

    /**
     * @throws ReflectionException
     * @throws MissingServiceException
     * @throws ResolveParameterException
     */
    public function handle(Request $request, callable $next): Response
    {
        $rawHost = $request->getHeader('Host') ?? $request->serverParams['HTTP_HOST'] ?? '';
        $host = CentralDomain::stripPort($rawHost);

        $tenant = $this->tenantManager->resolveByDomain($host) ?? null;

        if ($tenant !== null) {
            $request->setAttribute('tenant', $tenant);

            TenantQueryRewriter::setTenant($tenant);

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