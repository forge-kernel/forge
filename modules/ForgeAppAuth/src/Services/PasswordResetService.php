<?php
declare(strict_types=1);

namespace Modules\ForgeAppAuth\Services;

use Modules\ForgeAppAuth\Repositories\UserRepository;
use Modules\ForgeAuth\Contracts\AuthUserInterface;
use Modules\ForgeNotification\Services\ForgeNotificationService;
use Forge\Core\Config\Config;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;

#[Service]
final class PasswordResetService
{
    private const string TABLE = 'password_resets';

    public function __construct(
        private readonly Config $config,
        private readonly UserRepository $users,
        private readonly ForgeNotificationService $notifications,
        private readonly Container $container,
    ) {
    }

    public function sendResetLink(string $email): bool
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return false;
        }

        $token = $this->generateToken();
        $this->storeToken($email, $token);

        $this->notifications->email()
            ->to($email)
            ->subject('Reset your password')
            ->html($this->buildResetEmail($user, $token))
            ->send();

        return true;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $record = $this->findToken($token);
        if (!$record) {
            return false;
        }

        if ($this->isTokenExpired($record['created_at'])) {
            $this->deleteToken($token);
            return false;
        }

        $user = $this->users->findByEmail($record['email']);
        if (!$user) {
            return false;
        }

        $this->users->updatePassword($user->getId(), $newPassword);
        $this->deleteToken($token);

        return true;
    }

    public function isTokenValid(string $token): bool
    {
        $record = $this->findToken($token);
        if (!$record) {
            return false;
        }

        if ($this->isTokenExpired($record['created_at'])) {
            $this->deleteToken($token);
            return false;
        }

        return true;
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function storeToken(string $email, string $token): void
    {
        $this->queryBuilder()
            ->table(self::TABLE)
            ->where('email', '=', $email)
            ->delete();

        $this->queryBuilder()
            ->table(self::TABLE)
            ->insert([
                'email' => $email,
                'token' => $token,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
    }

    private function findToken(string $token): ?array
    {
        return $this->queryBuilder()
            ->table(self::TABLE)
            ->where('token', '=', $token)
            ->first();
    }

    private function deleteToken(string $token): void
    {
        $this->queryBuilder()
            ->table(self::TABLE)
            ->where('token', '=', $token)
            ->delete();
    }

    private function isTokenExpired(string $createdAt): bool
    {
        $ttl = (int) ($this->config->get('forge_app_auth.password_reset.token_ttl') ?? 3600);
        $created = strtotime($createdAt);
        return $created === false || (time() - $created) > $ttl;
    }

    private function buildResetEmail(AuthUserInterface $user, string $token): string
    {
        $resetUrl = '/auth/reset-password?token=' . $token;

        return <<<HTML
        <div style="font-family: sans-serif; max-width: 480px; margin: 0 auto; padding: 24px;">
          <h2 style="color: #111827;">Reset your password</h2>
          <p style="color: #6B7280; line-height: 1.6;">
            Hi {$user->getIdentifier()},<br><br>
            We received a request to reset your password. Click the link below to choose a new one:
          </p>
          <p style="margin: 24px 0;">
            <a href="{$resetUrl}"
               style="display: inline-block; background: #FC7205; color: #fff; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: 500;">
              Reset password
            </a>
          </p>
          <p style="color: #9CA3AF; font-size: 14px;">
            If you didn't request this, you can safely ignore this email.
          </p>
        </div>
        HTML;
    }

    private function queryBuilder(): QueryBuilderInterface
    {
        return $this->container->get(QueryBuilderInterface::class);
    }
}
