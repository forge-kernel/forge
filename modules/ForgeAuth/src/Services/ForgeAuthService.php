<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Services;

use Modules\ForgeAuth\Contracts\AuthUserInterface;
use Modules\ForgeAuth\Contracts\ForgeAuthInterface;
use Modules\ForgeAuth\Contracts\UserProviderInterface;
use Modules\ForgeAuth\Exceptions\LoginException;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use Forge\Core\Session\SessionInterface;

#[Service]
#[Provides(interface: ForgeAuthInterface::class, version: '0.1.7')]
#[Requires(SessionInterface::class, version: '>=0.1.0')]
#[Requires(Config::class, version: '>=0.1.0')]
final class ForgeAuthService implements ForgeAuthInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly SessionInterface $session,
        private readonly UserProviderInterface $userProvider,
        private readonly ?PermissionService $permissionService = null,
    ) {
    }

    public function register(array $credentials): bool
    {
        $this->userProvider->createUser($credentials);
        return true;
    }

    public function login(array $credentials): AuthUserInterface
    {
        $this->validateLoginAttempt();

        $user = $this->userProvider->verifyCredentials(
            $credentials['identifier'],
            $credentials['password']
        );

        if (!$user) {
            $this->handleFailedLogin();
            throw new LoginException();
        }

        $this->session->regenerate();
        $this->session->set('user_id', $user->getId());
        $this->session->set('user_identifier', $user->getIdentifier());
        $this->session->set('user_email', $user->getEmail());
        $this->resetLoginAttempts();

        if ($this->permissionService !== null) {
            $permissions = $this->permissionService->getUserPermissions($user);
            $this->session->set('user_permissions', $permissions);
        }

        return $user;
    }

    public function logout(): void
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        $this->session->clear();
    }

    private function validateLoginAttempt(): void
    {
        $attempts = (int) $this->session->get('login_attempts', 0);
        $lastAttempt = (int) $this->session->get('last_login_attempt', 0);

        $maxAttempts = (int) $this->config->get('forge_auth.password.max_login_attempts', 5);
        $lockoutTime = (int) $this->config->get('forge_auth.password.lockout_time', 300);

        if ($attempts >= $maxAttempts && time() - $lastAttempt < $lockoutTime) {
            throw new LoginException();
        }
    }

    private function handleFailedLogin(): void
    {
        $attempts = (int) $this->session->get('login_attempts', 0) + 1;
        $this->session->set('login_attempts', $attempts);
        $this->session->set('last_login_attempt', time());
    }

    private function resetLoginAttempts(): void
    {
        $this->session->remove('login_attempts');
        $this->session->remove('last_login_attempt');
    }
}
