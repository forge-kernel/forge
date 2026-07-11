<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http\Middlewares;

use Modules\ForgeRouter\Http\Middleware as MiddlewareImpl;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Forge\Core\Security\AssetRegistry;

final class RelaxSecurityHeadersMiddleware extends MiddlewareImpl
{
    public function handle(Request $request, callable $next): Response
    {
        AssetRegistry::reset();

        $response = $next($request);

        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'DENY');

        $csp = $this->buildCspHeader();

        if ($csp !== '') {
            $response->setHeader('Content-Security-Policy', $csp);
        }

        return $response;
    }

    private function buildCspHeader(): string
    {
        $config = config('forge_router.csp') ?? config('security.csp');

        if (!$config || !($config['enabled'] ?? false)) {
            return '';
        }

        $directives = $config['directives'] ?? [];

        $externalSources = AssetRegistry::getCspSources();

        foreach ($externalSources as $directive => $origins) {
            if (!isset($directives[$directive])) {
                $directives[$directive] = ["'self'"];
            }

            $directives[$directive] = array_merge(
                $directives[$directive],
                $origins
            );
        }

        $parts = [];

        foreach ($directives as $directive => $values) {
            $values = array_unique($values);
            $parts[] = $directive . ' ' . implode(' ', $values);
        }

        return implode('; ', $parts);
    }
}
