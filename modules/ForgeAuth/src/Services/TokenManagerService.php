<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Services;

use Modules\ForgeAuth\Contracts\AuthUserInterface;
use Modules\ForgeAuth\Contracts\UserProviderInterface;
use Modules\ForgeAuth\Exceptions\JwtTokenExpiredException;
use Modules\ForgeAuth\Exceptions\JwtTokenInvalidException;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Requires;

#[Service]
#[Requires(Config::class, version: '>=0.1.0')]
final class TokenManagerService
{
    private static array $customClaimsCallbacks = [];
    private ?bool $jwtEnabled = null;
    private ?int $jwtTtl = null;
    private ?int $jwtRefreshTtl = null;

    public function __construct(
        private readonly Config $config,
        private readonly JwtService $jwtService,
        private readonly UserProviderInterface $users
    ) {
    }

    public static function addCustomClaimsCallback(callable $callback): void
    {
        self::$customClaimsCallbacks[] = $callback;
    }

    public static function resetCustomClaimsCallbacks(): void
    {
        self::$customClaimsCallbacks = [];
    }

    public function issueToken(AuthUserInterface $user): array
    {
        if (!$this->isJwtEnabled()) {
            throw new \RuntimeException('JWT is not enabled');
        }

        $now = time();
        $ttl = $this->getJwtTtl();
        $refreshTtl = $this->getJwtRefreshTtl();

        $accessPayload = [
            'user_id' => $user->getId(),
            'exp' => $now + $ttl,
            'iat' => $now,
            'jti' => bin2hex(random_bytes(16)),
            'type' => 'access',
        ];

        $refreshPayload = [
            'user_id' => $user->getId(),
            'exp' => $now + $refreshTtl,
            'type' => 'refresh',
            'jti' => bin2hex(random_bytes(16)),
        ];

        $accessPayload = $this->applyCustomClaims($user, $accessPayload);
        $refreshPayload = $this->applyCustomClaims($user, $refreshPayload);

        $accessToken = $this->jwtService->encode($accessPayload);
        $refreshToken = $this->jwtService->encode($refreshPayload);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $ttl,
        ];
    }

    public function refreshToken(string $refreshToken): ?array
    {
        try {
            $payload = $this->jwtService->decode($refreshToken);
        } catch (JwtTokenInvalidException | JwtTokenExpiredException) {
            return null;
        }

        if (($payload['type'] ?? '') !== 'refresh') {
            return null;
        }

        $userId = $payload['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        $user = $this->users->findById((int) $userId);
        if (!$user) {
            return null;
        }

        return $this->issueToken($user);
    }

    public function resolveUserFromToken(string $token): ?AuthUserInterface
    {
        try {
            $payload = $this->jwtService->decode($token);
        } catch (JwtTokenInvalidException | JwtTokenExpiredException $e) {
            throw $e;
        }

        $userId = $payload['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        return $this->users->findById((int) $userId);
    }

    private function applyCustomClaims(AuthUserInterface $user, array $basePayload): array
    {
        if (empty(self::$customClaimsCallbacks)) {
            return $basePayload;
        }

        $protected = ['user_id', 'exp', 'iat', 'jti', 'type'];

        foreach (self::$customClaimsCallbacks as $callback) {
            $customClaims = $callback($user, $basePayload);
            if (is_array($customClaims) && !empty($customClaims)) {
                foreach ($customClaims as $key => $value) {
                    if (!in_array($key, $protected, true)) {
                        $basePayload[$key] = $value;
                    }
                }
            }
        }

        return $basePayload;
    }

    private function isJwtEnabled(): bool
    {
        if ($this->jwtEnabled === null) {
            $this->jwtEnabled = (bool) $this->config->get('forge_auth.jwt.enabled', false);
        }

        return $this->jwtEnabled;
    }

    private function getJwtTtl(): int
    {
        if ($this->jwtTtl === null) {
            $this->jwtTtl = (int) $this->config->get('forge_auth.jwt.ttl', 900);
        }

        return $this->jwtTtl;
    }

    private function getJwtRefreshTtl(): int
    {
        if ($this->jwtRefreshTtl === null) {
            $this->jwtRefreshTtl = (int) $this->config->get('forge_auth.jwt.refresh_ttl', 604800);
        }

        return $this->jwtRefreshTtl;
    }
}
