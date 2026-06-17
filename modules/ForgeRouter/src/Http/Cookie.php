<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Http;

final class Cookie
{
    public function __construct(
        public readonly string $name,
        public readonly string $value,
        public readonly int $expires = 0,
        public readonly string $path = '/',
        public readonly string $domain = '',
        public readonly bool $secure = false,
        public readonly bool $httponly = false,
        public readonly string $samesite = 'Lax'
    ) {
    }
}
