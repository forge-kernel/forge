<?php

namespace Modules\ForgeRouter\Http\Middlewares;

use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Modules\ForgeRouter\Http\Middleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;
use Forge\Exceptions\InvalidMiddlewareResponse;
use Modules\ForgeRouter\Traits\ResponseHelper;

#[Service]
#[RegisterMiddleware(group: 'global', order: 2, allowDuplicate: true, enabled: true)]
class CorsMiddleware extends Middleware
{
    use ResponseHelper;

    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @throws InvalidMiddlewareResponse
     */
    public function handle(Request $request, callable $next): Response
    {
        $allowedOrigins = $this->normalizeArray($this->config->get('forge_router.cors.allowed_origins'));
        $allowedMethods = $this->normalizeArray($this->config->get('forge_router.cors.allowed_methods'));
        $allowedHeaders = $this->normalizeArray($this->config->get('forge_router.cors.allowed_headers'));

        $origin = $request->getHeader("Origin");
        $requestMethod = $request->getMethod();
        $requestHeaders = $request->getHeader("Access-Control-Request-Headers");

        if ($origin !== null) {
            $isAllowed = false;

            if (in_array($origin, $allowedOrigins)) {
                $isAllowed = true;
            } else {
                foreach ($allowedOrigins as $allowedOrigin) {
                    if ($allowedOrigin === '*') {
                        $isAllowed = true;
                        break;
                    }

                    $allowedOriginLower = strtolower($allowedOrigin);
                    $originLower = strtolower($origin);

                    if ($allowedOriginLower === $originLower) {
                        $isAllowed = true;
                        break;
                    }

                    if (!str_starts_with($allowedOriginLower, 'http://') && !str_starts_with($allowedOriginLower, 'https://')) {
                        $originDomain = parse_url($originLower, PHP_URL_HOST) ?: $originLower;
                        if ($originDomain === $allowedOriginLower || $originLower === 'https://' . $allowedOriginLower || $originLower === 'http://' . $allowedOriginLower) {
                            $isAllowed = true;
                            break;
                        }
                    }
                }
            }

            if (!$isAllowed) {
                return $this->createResponse($request, 'Origin not allowed', 403);
            }
        }

        if (!in_array($requestMethod, $allowedMethods)) {
            return $this->createResponse($request, 'Method not allowed', 403);
        }

        if ($requestMethod === "OPTIONS") {
            if ($requestHeaders !== null) {
                $requestedHeaders = array_map('trim', explode(',', $requestHeaders));
                foreach ($requestedHeaders as $header) {
                    if (!in_array($header, $allowedHeaders)) {
                        return $this->createResponse($request, 'Header not allowed', 403);
                    }
                }
            }
        }

        $response = $next($request);

        if (!$response instanceof Response) {
            throw new InvalidMiddlewareResponse();
        }

        if ($origin === null) {
            $response->setHeader("Access-Control-Allow-Origin", $allowedOrigins[0] ?? "*");
        } else {
            $response->setHeader("Access-Control-Allow-Origin", $origin);
        }

        $response->setHeader("Access-Control-Allow-Methods", implode(", ", $allowedMethods));
        $response->setHeader("Access-Control-Allow-Headers", implode(", ", $allowedHeaders));
        $response->setHeader("Access-Control-Allow-Credentials", "true");
        $response->setHeader("Access-Control-Max-Age", "86400");

        return $response;
    }

    private function normalizeArray(mixed $value): array
    {
        if (is_string($value)) {
            return array_map('trim', explode(',', $value));
        }
        if (is_array($value) && count($value) === 1 && is_string($value[0]) && str_contains($value[0], ',')) {
            return array_map('trim', explode(',', $value[0]));
        }
        return is_array($value) ? $value : (array) $value;
    }
}
