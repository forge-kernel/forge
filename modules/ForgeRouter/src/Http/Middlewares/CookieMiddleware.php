<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http\Middlewares;

use Modules\ForgeRouter\Http\Cookie;
use Modules\ForgeRouter\Http\Middleware as MiddlewareImpl;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\Middleware;

#[Middleware(group: 'api', order: 2, allowDuplicate: true, enabled: true)]
class CookieMiddleware extends MiddlewareImpl
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        foreach ($response->getCookies() as $cookie) {
            $this->setCookieHeader($response, $cookie);
        }

        return $response;
    }

    private function setCookieHeader(Response $response, Cookie $cookie): void
    {
        $header = sprintf('%s=%s', $cookie->name, $cookie->value);

        $fields = [
            'expires' => $cookie->expires,
            'path' => $cookie->path,
            'domain' => $cookie->domain,
            'secure' => $cookie->secure,
            'httponly' => $cookie->httponly,
            'samesite' => $cookie->samesite
        ];

        foreach ($fields as $key => $value) {
            if ($value !== null && $value !== '') {
                $header .= "; $key=$value";
            }
        }

        $response->setHeader('Set-Cookie', $header);
    }
}
