<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http\Middlewares;

use Forge\Core\Observability\ObservabilityManager;
use Modules\ForgeRouter\Http\Middleware as MiddlewareImpl;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\Middleware;

#[Middleware(group: "global", order: -1, allowDuplicate: false, overrideClass: null, enabled: true)]
final class ObservabilityMiddleware extends MiddlewareImpl
{
    public function handle(Request $request, callable $next): Response
    {
        $manager = ObservabilityManager::getInstance();
        if ($manager === null) {
            return $next($request);
        }

        $manager->beginRequest($request->getMethod() . ' ' . $request->getPath(), [
            'request_method' => $request->getMethod(),
            'request_path' => $request->getPath(),
        ]);

        try {
            $response = $next($request);
        } catch (\Throwable $exception) {
            $manager->endRequest([
                'status' => 'error',
                'status_code' => 500,
            ]);
            throw $exception;
        }

        $manager->endRequest([
            'status_code' => $this->extractStatusCode($response),
            'status' => $this->extractStatus($response),
        ]);

        return $response;
    }

    private function extractStatusCode(Response $response): int
    {
        return $response->getStatusCode();
    }

    private function extractStatus(Response $response): string
    {
        $code = $this->extractStatusCode($response);

        if ($code >= 500) {
            return 'error';
        }

        if ($code >= 400) {
            return 'warning';
        }

        return 'ok';
    }
}
