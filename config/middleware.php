<?php

/**
 * Middleware Configuration
 *
 * This file controls which middlewares are applied to your routes.
 *
 * HOW IT WORKS:
 * - If you explicitly list kernel middlewares below, their order is respected
 * - If you omit kernel middlewares, they are auto-discovered and merged (backward compatible)
 * - To remove an kernel middleware, simply don't include it in the list
 * - To reorder middlewares, change their position in the array
 *
 * AVAILABLE KERNEL MIDDLEWARES:
 *
 * Global Group (applies to all routes):
 * - \Modules\ForgeRouter\Http\Middlewares\ObservabilityMiddleware::class (order: -1) - Request tracing and observability
 * - \Modules\ForgeRouter\Http\Middlewares\RateLimitMiddleware::class (order: 0) - Rate limiting
 * - \Modules\ForgeRouter\Http\Middlewares\CircuitBreakerMiddleware::class (order: 1) - Circuit breaker
 * - \Modules\ForgeRouter\Http\Middlewares\CorsMiddleware::class (order: 2) - CORS headers
 * - \Modules\ForgeRouter\Http\Middlewares\SanitizeInputMiddleware::class (order: 3, disabled by default) - Input sanitization
 * - \Modules\ForgeRouter\Http\Middlewares\CompressionMiddleware::class (order: 4) - Response compression
 *
 * Web Group (applies to web routes):
 * - \Modules\ForgeRouter\Http\Middlewares\SessionMiddleware::class (order: 0) - Session management
 * - \Modules\ForgeRouter\Http\Middlewares\CsrfMiddleware::class (order: 1) - CSRF protection
 * - \Modules\ForgeRouter\Http\Middlewares\RelaxSecurityHeadersMiddleware::class (order: 3) - Security headers
 *
 * API Group (applies to API routes):
 * - \Modules\ForgeRouter\Http\Middlewares\IpWhiteListMiddleware::class (order: 0) - IP whitelist
 * - \Modules\ForgeRouter\Http\Middlewares\ApiKeyMiddleware::class (order: 1) - API key auth
 * - \Modules\ForgeRouter\Http\Middlewares\CookieMiddleware::class (order: 2) - Cookie handling
 * - \Modules\ForgeRouter\Http\Middlewares\ApiMiddleware::class (order: 2) - API response formatting
 *
 * EXAMPLE: Explicitly control kernel middleware order
 * 'global' => [
 *     \Modules\ForgeRouter\Http\Middlewares\RateLimitMiddleware::class,
 *     \Modules\ForgeRouter\Http\Middlewares\CircuitBreakerMiddleware::class,
 *     // Omit SanitizeInputMiddleware to remove it
 *     \Modules\ForgeRouter\Http\Middlewares\CompressionMiddleware::class,
 *     // Your custom middleware
 *     \App\Middlewares\CustomMiddleware::class,
 * ],
 */

return [
    "global" => [
        \Modules\ForgeRouter\Http\Middlewares\ObservabilityMiddleware::class,
        \Modules\ForgeRouter\Http\Middlewares\SanitizeInputMiddleware::class,
    ],
    "web" => [
        \Modules\ForgeRouter\Http\Middlewares\SessionMiddleware::class,
        \Modules\ForgeRouter\Http\Middlewares\CsrfMiddleware::class,
        \Modules\ForgeHtmx\Middlewares\ForgeHtmxMiddleware::class,
        \Modules\ForgeWire\Middlewares\ForgeWireMiddleware::class,
    ],
    "api" => [
        \Modules\ForgeRouter\Http\Middlewares\IpWhiteListMiddleware::class,
        \Modules\ForgeRouter\Http\Middlewares\CookieMiddleware::class,
        \Modules\ForgeRouter\Http\Middlewares\ApiMiddleware::class,
    ],
    "api-auth" => [],
    "auth" => [\Modules\AppAuth\Middlewares\AuthMiddleware::class],
    "role" => [
        \Modules\ForgeAuth\Middlewares\RoleMiddleware::class,
    ],
    "permission" => [
        \Modules\ForgeAuth\Middlewares\PermissionMiddleware::class,
    ],
    "hub-permissions" => [
        \Modules\ForgeHub\Middlewares\HubPermissionMiddleware::class,
    ],
];
