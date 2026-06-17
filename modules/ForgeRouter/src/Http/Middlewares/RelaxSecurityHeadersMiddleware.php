<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Http\Middlewares;

use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;
use Forge\Core\Security\AssetRegistry;

#[Service]
#[RegisterMiddleware(group: 'web', order: 3, allowDuplicate: true, enabled: true)]
final class RelaxSecurityHeadersMiddleware extends Middleware
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