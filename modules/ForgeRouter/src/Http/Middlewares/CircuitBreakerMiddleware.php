<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Http\Middlewares;

use Forge\Core\Config\Config;
use Forge\Core\Contracts\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use App\Modules\ForgeRouter\Http\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use Throwable;

#[Service]
#[RegisterMiddleware(group: 'global', order: 1, allowDuplicate: true, enabled: true)]
class CircuitBreakerMiddleware extends Middleware
{
    use ResponseHelper;

    public function __construct(private readonly Config $config, private readonly ?QueryBuilderInterface $queryBuilder = null)
    {
    }

    /**
     * @throws Throwable
     */
    public function handle(Request $request, callable $next): Response
    {
        if ($this->queryBuilder === null) {
            return $next($request);
        }

        $isDev = \Forge\Core\Config\Environment::getInstance()->isDevelopment();
        $disableInDev = $this->config->get('forge_router.circuit_breaker.disable_in_dev', true);
        if ($isDev && $disableInDev) {
            return $next($request);
        }

        if (($_ENV['CIRCUIT_BREAKER_ENABLED'] ?? 'true') === 'false') {
            return $next($request);
        }

        if (Container::getInstance()->has(DatabaseConnectionInterface::class)) {
            $maintenancePage = file_get_contents(BASE_PATH . "/kernel/Core/Http/ErrorPages/maintenance.html");
            $queryBuilder = clone $this->queryBuilder;

            $maxFailures = $this->config->get('forge_router.circuit_breaker.max_failures', 5);
            $resetTime = $this->config->get('forge_router.circuit_breaker.reset_time', 300);

            $clientIp = $request->getClientIp();
            $now = time();
            $table = 'circuit_breaker';

            $record = $queryBuilder->reset()->setTable($table)
                ->select('*')
                ->where('ip_address', '=', $clientIp)
                ->first();

            if ($record) {
                $failCount = $record['fail_count'];
                $firstFailureTime = strtotime($record['first_failure']);

                if ($failCount >= $maxFailures && ($now - $firstFailureTime) < $resetTime) {
                    return $this->createErrorResponse($request, $maintenancePage, 503);
                }

                if (($now - $firstFailureTime) >= $resetTime) {
                    $this->resetFailureCount($record['id'], $queryBuilder);
                }
            }

            try {
                /*** @var Response $response */
                $response = $next($request);
            } catch (Throwable $exception) {
                $statusCode = $exception->getCode();

                if ($statusCode >= 500) {
                    if ($record) {
                        $this->incrementFailureCount($record['id'], $record, $queryBuilder);
                    } else {
                        $this->createNewFailureRecord($clientIp, $queryBuilder);
                    }
                }
                throw $exception;
            }
        } else {
            return $next($request);
        }

        return $response;
    }

    private function resetFailureCount(int $recordId, QueryBuilderInterface $queryBuilder): void
    {
        $queryBuilder->reset()->setTable('circuit_breaker')
            ->where('id', '=', $recordId)
            ->delete();
    }

    private function incrementFailureCount(int $recordId, object|array $record, QueryBuilderInterface $queryBuilder): void
    {
        $queryBuilder->reset()->setTable('circuit_breaker')
            ->where('id', '=', $recordId)
            ->update([
                'fail_count' => $record['fail_count'] + 1,
                'first_failure' => date('Y-m-d H:i:s'),
            ]);
    }

    private function createNewFailureRecord(string $clientIp, QueryBuilderInterface $queryBuilder): void
    {
        $queryBuilder->reset()->setTable('circuit_breaker')
            ->insert([
                'ip_address' => $clientIp,
                'fail_count' => 1,
                'first_failure' => date('Y-m-d H:i:s'),
            ]);
    }
}
