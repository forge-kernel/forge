<?php
declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Services;

use Forge\Core\Config\Config;

final class CentralDomain
{
    private static ?string $host = null;

    public function __construct(protected readonly Config $config)
    {
        self::$host = $this->config->get('forge_multi_tenant.central_domain', env('FORGE_MULTI_TENANT_CENTRAL_DOMAIN', 'forge-v3.test'));
    }

    public static function get(): string
    {
        return self::$host ??= env('FORGE_MULTI_TENANT_CENTRAL_DOMAIN', 'forge-v3.test');
    }

    public static function stripPort(string $host): string
    {
        if (str_starts_with($host, '[')) {
            $close = strpos($host, ']');
            if ($close !== false) {
                return substr($host, 0, $close + 1);
            }
        }

        if (substr_count($host, ':') > 1) {
            return $host;
        }

        return explode(':', $host, 2)[0];
    }

    public static function isLocal(string $host): bool
    {
        $host = self::stripPort($host);
        return in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true);
    }
}
