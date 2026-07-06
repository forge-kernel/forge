<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Services;

use Forge\Core\Config\Config;
use Forge\Core\Session\SessionInterface;

final class TokenManager
{
    private const SESSION_KEY = '_csrf.tokens';
    private const CLOCK_SKEW = 600;
    private string $appKey;

    public function __construct(
        private SessionInterface $session,
        private Config $config
    ) {
        $this->appKey = $config->get('security.app_key', env('APP_KEY', 'your-secure-app-key'));
    }

    public function getToken(string $intent = 'default'): string
    {
        $bag = $this->session->get(self::SESSION_KEY, []);
        if (!isset($bag[$intent])) {
            $raw = bin2hex(random_bytes(32));
            $bag[$intent] = [
                'raw' => $raw,
                'issued_at' => time(),
                'sid' => $this->session->getId(),
            ];
            $this->session->set(self::SESSION_KEY, $bag);
        }
        return $this->sign($bag[$intent]);
    }

    public function isValid(?string $provided, string $intent = 'default'): bool
    {
        if (!$provided) {
            return false;
        }

        $bag = $this->session->get(self::SESSION_KEY, []);
        if (!isset($bag[$intent])) {
            return false;
        }

        $expected = $this->sign($bag[$intent]);
        if (!hash_equals($expected, $provided)) {
            return false;
        }

        $iat = (int) ($bag[$intent]['issued_at'] ?? 0);
        if ($iat > 0 && $iat < (time() - 86400)) {
            unset($bag[$intent]);
            $this->session->set(self::SESSION_KEY, $bag);
        }

        return true;
    }

    private function sign(array $record): string
    {
        $payload = json_encode([
            'raw' => $record['raw'] ?? '',
            'sid' => $record['sid'] ?? '',
        ], JSON_UNESCAPED_SLASHES);

        $mac = hash_hmac('sha256', $payload, $this->appKey);
        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=') . '.' . $mac;
    }
}
