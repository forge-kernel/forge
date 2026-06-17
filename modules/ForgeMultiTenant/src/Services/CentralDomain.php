<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class CentralDomain
{
    private static ?string $host = null;

    public static function get(): string
    {
        return self::$host ??= env('CENTRAL_DOMAIN', 'forge-v3.test');
    }

    public static function stripPort(string $host): string
    {
        return explode(':', $host, 2)[0];
    }

    public static function isLocal(string $host): bool
    {
        $host = self::stripPort($host);
        return in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true);
    }
}