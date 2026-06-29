<?php
declare(strict_types=1);

namespace Modules\ForgeAuth\Traits;

use Modules\ForgeAuth\Contracts\AuthUserInterface;
use Modules\ForgeAuth\Contracts\UserProviderInterface;

trait HasCurrentUser
{
    private function getCurrentUser(): ?AuthUserInterface
    {
        try {
            $session = \Forge\Core\DI\Container::getInstance()->get(
                \Forge\Core\Session\SessionInterface::class,
            );
            $userId = $session->get("user_id");

            if (!$userId) {
                return null;
            }

            $userProvider = \Forge\Core\DI\Container::getInstance()->get(
                UserProviderInterface::class,
            );
            return $userProvider->findById($userId);
        } catch (\Exception $e) {
            return null;
        }
    }
}
