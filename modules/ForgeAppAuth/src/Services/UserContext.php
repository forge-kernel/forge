<?php
declare(strict_types=1);

namespace App\Modules\ForgeAppAuth\Services;

use App\Modules\ForgeAppAuth\Repositories\UserRepository;
use App\Modules\ForgeAuth\Contracts\AuthUserInterface;
use App\Modules\ForgeAuth\Contracts\UserContextInterface;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Requires;
use Forge\Core\Session\SessionInterface;

#[Service]
#[Requires(SessionInterface::class, version: '>=0.1.0')]
#[Requires(Config::class, version: '>=0.1.0')]
final class UserContext implements UserContextInterface
{
    private ?AuthUserInterface $cachedUser = null;

    public function __construct(
        private readonly Config $config,
        private readonly SessionInterface $session,
        private readonly UserRepository $users,
    ) {
    }

    public function current(): ?AuthUserInterface
    {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }

        $userId = $this->session->get('user_id');
        if (!$userId) {
            return null;
        }

        return $this->cachedUser = $this->users->findById((int) $userId);
    }

    public function isAuthenticated(): bool
    {
        return $this->current() !== null;
    }

    public function setCurrentUser(AuthUserInterface $user): void
    {
        $this->cachedUser = $user;
    }
}
