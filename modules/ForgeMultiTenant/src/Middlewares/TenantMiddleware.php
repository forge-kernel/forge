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
use Forge\Core\Config\Config;
use Modules\ForgeRouter\Http\Middleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Forge\Core\Session\SessionInterface;
use Forge\Core\Cache\CacheManager;
use Modules\ForgeMultiTenant\Services\TenantManager;
use Modules\ForgeMultiTenant\Services\RouteScopeFilter;
use Forge\Exceptions\MissingServiceException;
use Forge\Exceptions\ResolveParameterException;
use PDO;
use ReflectionException;

final class TenantMiddleware extends Middleware
{
    private const UNKNOWN_TENANT_HTML = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:system-ui,-apple-system,sans-serif;background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;color:#1e293b}
        .container{text-align:center;padding:2rem}
        h1{font-size:4rem;font-weight:700;color:#e2e8f0;margin-bottom:.5rem}
        p{font-size:1.125rem;color:#64748b;margin-bottom:1.5rem}
        a{color:#3b82f6;text-decoration:none;font-weight:500}
        a:hover{text-decoration:underline}
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <p>This workspace could not be found.</p>
        <a href="/">Go to homepage</a>
    </div>
</body>
</html>
HTML;

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

            return $next($request);
        }

        if (!CentralDomain::isLocal($host) && $host !== CentralDomain::get()) {
            return $this->handleUnknownTenant($host, $request);
        }

        return $next($request);
    }

    private function handleUnknownTenant(string $host, Request $request): Response
    {
        $config = Container::getInstance()->get(Config::class);

        $redirectUrl = $config->get('forge_multi_tenant.unknown_tenant_page');
        if ($redirectUrl !== null) {
            return new Response('', 302, ['Location' => $redirectUrl]);
        }

        $viewFile = $config->get('forge_multi_tenant.unknown_tenant_view');
        if ($viewFile !== null && is_file($viewFile)) {
            ob_start();
            include $viewFile;
            $content = ob_get_clean();
            return (new Response($content, 404))->setHeader('Content-Type', 'text/html');
        }

        return new Response(self::UNKNOWN_TENANT_HTML, 404, ['Content-Type' => 'text/html']);
    }
}