<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Contracts;

/**
 * Resolves the application user behind an opening handshake. The HTTP request
 * headers (including the Cookie header, so the platform SessionDriver can be
 * used) and the requested path are provided; return a stable opaque user
 * identifier, or null to reject the connection (closed with 1008).
 */
interface AuthenticatorInterface
{
    public function authenticate(string $path, array $headers): ?string;
}