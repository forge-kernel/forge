<?php

/**
 * Middleware Configuration
 *
 * This file controls which middlewares are applied to your routes.
 *
 * HOW IT WORKS:
 * - This config is the source of truth for middleware ordering
 * - Modules register their middlewares via ForgeRouterModule::registerMiddleware() in their register() method
 * - Module-registered middlewares not listed here are merged as defaults (prepended to their group)
 * - To override a module middleware's position, list it explicitly in the desired order
 * - To remove a module middleware, don't include it in this config
 *
 * ESSENTIAL MIDDLEWARES (registered by ForgeRouter):
 *
 * Global Group (applies to all routes):
 * - \Modules\ForgeRouter\Http\Middlewares\ObservabilityMiddleware::class (order: -1) - Request tracing and observability
 * - \Modules\ForgeRouter\Http\Middlewares\RateLimitMiddleware::class (order: 0) - Rate limiting
 * - \Modules\ForgeRouter\Http\Middlewares\CircuitBreakerMiddleware::class (order: 1) - Circuit breaker
 * - \Modules\ForgeRouter\Http\Middlewares\SanitizeInputMiddleware::class (order: 3, disabled by default) - Input sanitization
 *
 * Web Group (applies to web routes):
 * - \Modules\ForgeRouter\Http\Middlewares\SessionMiddleware::class (order: 0) - Session management
 * - \Modules\ForgeRouter\Http\Middlewares\CsrfMiddleware::class (order: 1) - CSRF protection
 *
 * AVAILABLE FOR CONFIG (add to groups as needed):
 *
 * Global Group:
 * - \Modules\ForgeRouter\Http\Middlewares\CorsMiddleware::class - CORS headers
 * - \Modules\ForgeRouter\Http\Middlewares\CompressionMiddleware::class - Response compression
 *
 * Web Group:
 * - \Modules\ForgeRouter\Http\Middlewares\RelaxSecurityHeadersMiddleware::class - Security headers
 *
 * API Group:
 * - \Modules\ForgeRouter\Http\Middlewares\IpWhiteListMiddleware::class - IP whitelist
 * - \Modules\ForgeRouter\Http\Middlewares\ApiKeyMiddleware::class - API key auth
 * - \Modules\ForgeRouter\Http\Middlewares\CookieMiddleware::class - Cookie handling
 * - \Modules\ForgeRouter\Http\Middlewares\ApiMiddleware::class - API response formatting
 *
 * EXAMPLE: Explicitly control middleware order
 * 'global' => [
 *     \Modules\ForgeRouter\Http\Middlewares\RateLimitMiddleware::class,
 *     \Modules\ForgeRouter\Http\Middlewares\CircuitBreakerMiddleware::class,
 *     \Modules\ForgeRouter\Http\Middlewares\CorsMiddleware::class,
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
