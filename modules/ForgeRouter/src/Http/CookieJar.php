<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http;

final class CookieJar
{
    private array $cookies = [];

    public function make(
        string $name,
        string $value,
        int $minutes = 0,
        array $options = []
    ): Cookie {
        $expires = $minutes > 0 ? time() + ($minutes * 60) : 0;
        return new Cookie(
            $name,
            $value,
            $expires,
            $options['path'] ?? '/',
            $options['domain'] ?? '',
            $options['secure'] ?? false,
            $options['httponly'] ?? false,
            $options['samesite'] ?? 'Lax'
        );
    }

    public function queue(Cookie $cookie): void
    {
        $this->cookies[] = $cookie;
    }

    public function getQueuedCookies(): array
    {
        return $this->cookies;
    }
}
