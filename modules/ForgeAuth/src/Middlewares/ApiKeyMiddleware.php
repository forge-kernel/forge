<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Middlewares;

use App\Modules\ForgeAuth\Services\ApiKeyService;
use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use Forge\Core\DI\Container;

#[Service]
final class ApiKeyMiddleware
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService,
    ) {}

    public function handle(Request $request, callable $next): Response
    {
        if (
            Container::getInstance()->has(
                \Forge\Core\Contracts\Database\DatabaseConnectionInterface::class,
            )
        ) {
            return $next($request);
        }

        $apiKey = $request->getHeader("X-API-KEY", null);

        if (!$apiKey) {
            return $this->createResponse(
                $request,
                "Unauthorized: API key missing",
                401,
            );
        }

        $keyRecord = $this->apiKeyService->validateApiKey($apiKey);
        
        if (!$keyRecord) {
            return $this->createResponse(
                $request,
                "Unauthorized: Invalid API key",
                401,
            );
        }
        
        $keyInfo = $this->apiKeyService->getApiKeyInfo($apiKey);
        if (!$keyInfo) {
            return $this->createResponse(
                $request,
                "Unauthorized: Invalid API key",
                401,
            );
        }

        $resolvedPermissions = [];
        $requiredPermissions = $request->getAttribute(
            "required_permissions",
            [],
        );

        if (!empty($requiredPermissions)) {
            $apiKeyPermissions = $keyInfo['permissions'] ?? [];
            $resolvedPermissions = array_intersect($requiredPermissions, $apiKeyPermissions);
        }

        $request->setAttribute("api_key_permissions", $resolvedPermissions);

        return $next($request);
    }

    private function createResponse(
        Request $request,
        string $message,
        int $statusCode,
    ): Response {
        $acceptsJson = str_contains(
            $request->getHeader("Accept", ""),
            "application/json",
        );

        if ($acceptsJson) {
            return new Response(
                json_encode([
                    "error" => $message,
                    "message" => $message,
                ]),
                $statusCode,
                ["Content-Type" => "application/json"],
            );
        }

        return new Response($message, $statusCode);
    }
}
