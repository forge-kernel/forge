<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http\Middlewares;

use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Config\Config;
use Forge\Core\Config\Environment;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Modules\ForgeRouter\Http\Middleware as MiddlewareImpl;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Traits\ResponseHelper;

class RateLimitMiddleware extends MiddlewareImpl
{
    use OutputHelper;
    use ResponseHelper;

    public function __construct(
        private readonly Config $config,
        private readonly ?QueryBuilderInterface $queryBuilder = null
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->queryBuilder === null) {
            return $next($request);
        }

        $enabled = $this->config->get('forge_router.rate_limit.enabled', true);
        if (!$enabled) {
            return $next($request);
        }

        $isDev = Environment::getInstance()->isDevelopment();
        $env = Environment::getInstance();
        $path = $request->getPath();

        if ($isDev && str_contains($path, 'tailwind-watch.php')) {
            return $next($request);
        }

        $disableInDev = $this->config->get('forge_router.rate_limit.disable_in_dev', $env->get('RATE_LIMIT_DISABLE_IN_DEV', true));
        if ($isDev && $disableInDev) {
            return $next($request);
        }

        if (($env->get('RATE_LIMIT_ENABLED', 'true')) === 'false') {
            return $next($request);
        }

        if (in_array($path, ['/health', '/healthz', '/ping', '/status'], true)) {
            return $next($request);
        }

        $clientIp = $request->getClientIp();

        $bypassIps = $this->config->get('forge_router.rate_limit.bypass_ips', ['127.0.0.1', '::1', 'localhost']);
        if (in_array($clientIp, $bypassIps, true)) {
            return $next($request);
        }

        $queryBuilder = clone $this->queryBuilder;

        $maxRequests = $this->config->get('forge_router.rate_limit.max_requests', $env->get('RATE_LIMIT_MAX_REQUESTS', 100));
        $timeWindow = $this->config->get('forge_router.rate_limit.time_window', $env->get('RATE_LIMIT_TIME_WINDOW', 60));
        $table = 'rate_limits';
        $now = time();
        $nowFormatted = date('Y-m-d H:i:s');

        $updated = $this->tryAtomicUpdate($queryBuilder, $table, $clientIp, $maxRequests, $timeWindow, $now, $nowFormatted, $clientIp);

        if ($updated === 'rate_limited') {
            return $this->createErrorResponse($request);
        }

        if ($updated === false) {
            try {
                $this->createNewRateLimitRecord($clientIp, $queryBuilder, $nowFormatted);
            } catch (\Throwable $e) {
                $updated = $this->tryAtomicUpdate($queryBuilder, $table, $clientIp, $maxRequests, $timeWindow, $now, $nowFormatted, $clientIp);
                if ($updated === 'rate_limited') {
                    return $this->createErrorResponse($request);
                }
            }
        }

        return $next($request);
    }

    /**
     * Attempts to atomically update the rate limit record.
     * Returns 'rate_limited' if limit exceeded, true if updated, false if no record found.
     */
    private function tryAtomicUpdate(
        QueryBuilderInterface $queryBuilder,
        string $table,
        string $clientIp,
        int $maxRequests,
        int $timeWindow,
        int $now,
        string $nowFormatted,
        string $logIp
    ): string|bool {
        $record = $queryBuilder->reset()
            ->setTable($table)
            ->select('*')
            ->where('ip_address', '=', $clientIp)
            ->first();

        if (!$record) {
            return false;
        }

        $timeDiff = $now - strtotime($record['last_request']);

        if ($timeDiff >= $timeWindow) {
            $queryBuilder->reset()
                ->setTable($table)
                ->where('id', '=', $record['id'])
                ->update([
                    'request_count' => 1,
                    'last_request' => $nowFormatted,
                ]);
            return true;
        }

        if ($record['request_count'] >= $maxRequests) {
            if (function_exists('error_log')) {
                error_log(sprintf(
                    '[RateLimit] IP %s exceeded limit: %d/%d requests in %d seconds',
                    $logIp,
                    $record['request_count'],
                    $maxRequests,
                    $timeWindow
                ));
            }
            return 'rate_limited';
        }

        $queryBuilder->reset()
            ->setTable($table)
            ->where('id', '=', $record['id'])
            ->where('request_count', '<', $maxRequests)
            ->update([
                'request_count' => $record['request_count'] + 1,
                'last_request' => $nowFormatted,
            ]);

        return true;
    }

    private function createNewRateLimitRecord(string $clientIp, QueryBuilderInterface $queryBuilder, string $nowFormatted): void
    {
        $queryBuilder->reset()
            ->setTable('rate_limits')
            ->insert([
                'ip_address' => $clientIp,
                'request_count' => 1,
                'last_request' => $nowFormatted,
            ]);
    }
}
