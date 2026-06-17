<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Http\Middlewares;

use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;
use Forge\Core\Services\TokenManager;
use App\Modules\ForgeRouter\Traits\ResponseHelper;

#[Service]
#[RegisterMiddleware(group: 'web', order: 1, allowDuplicate: true, enabled: true)]
final class CsrfMiddleware extends Middleware
{
    use ResponseHelper;

    public function __construct(private readonly TokenManager $csrf)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $request->setAttribute('_csrf_token', $this->csrf->getToken('web'));

        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return $next($request);
        }

        if ($request->getPath() === '/__wire') {
            if (strtoupper($request->getMethod()) !== 'POST') {
                return new Response('Method Not Allowed', 405);
            }
            $ct = $request->getHeader('Content-Type', '');
            if (stripos($ct, 'application/json') === false) {
                return new Response('Unsupported Media Type', 415);
            }

            if (!$this->isSameOrigin($request)) {
                return new Response('Forbidden (origin)', 403);
            }
        }

        if (in_array(strtoupper($request->getMethod()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $provided = $request->getHeader('X-CSRF-TOKEN')
                ?? $request->input('_token')
                ?? null;

            if (!$this->csrf->isValid($provided, 'web')) {
                return $this->createResponse($request, 'CSRF token mismatch', 419);
            }
        }

        return $next($request);
    }

    private function isSameOrigin(Request $request): bool
    {
        $trusted = filter_var(env('TRUST_PROXIES', false), FILTER_VALIDATE_BOOLEAN);

        $scheme = $trusted
            ? ($request->getHeader('X-Forwarded-Proto') ?: $request->getScheme())
            : $request->getScheme();

        $hostHeader = $trusted
            ? ($request->getHeader('X-Forwarded-Host') ?: $request->getHeader('Host'))
            : $request->getHeader('Host');

        $host = $this->normalizeHost($hostHeader);

        $strictScheme = filter_var(env('CSRF_STRICT_SCHEME', true), FILTER_VALIDATE_BOOLEAN);

        if (env('APP_ENV', 'production') !== 'production') {
            $strictScheme = false;
        }

        $origin = $request->getHeader('Origin');
        if ($origin && !$this->matchUrl($origin, $host, $scheme, $strictScheme)) {
            return false;
        }

        $referer = $request->getHeader('Referer');
        if ($referer && !$this->matchUrl($referer, $host, $scheme, $strictScheme)) {
            return false;
        }

        return true;
    }

    private function normalizeHost(?string $h): ?string
    {
        if (!$h) {
            return null;
        }
        $h = strtolower($h);
        return preg_replace('/:\d+$/', '', $h);
    }

    private function hostsEqual(?string $a, ?string $b): bool
    {
        if (!$a || !$b) {
            return false;
        }
        if (strcasecmp($a, $b) === 0) {
            return true;
        }

        $dev = ['localhost', '127.0.0.1', '::1'];
        if (in_array($a, $dev, true) && in_array($b, $dev, true)) {
            return true;
        }

        return false;
    }

    private function matchUrl(string $url, ?string $expectedHost, string $expectedScheme, bool $strictScheme): bool
    {
        $uHost = $this->normalizeHost(parse_url($url, PHP_URL_HOST) ?: '');
        $uScheme = (string)(parse_url($url, PHP_URL_SCHEME) ?: 'http');

        if (!$uHost || !$expectedHost) {
            return false;
        }
        if (!$this->hostsEqual($uHost, $expectedHost)) {
            return false;
        }

        if ($strictScheme && strcasecmp($uScheme, $expectedScheme) !== 0) {
            return false;
        }

        return true;
    }
}
