<?php

declare(strict_types=1);

use App\Modules\AppAuth\Models\User;
use App\Modules\AppAuth\Services\UserContext;
use Forge\Core\DI\Container;

if (!function_exists("getCurrentUser")) {
    function getCurrentUser(): ?User
    {
        try {
            return Container::getInstance()->get(UserContext::class)->current();
        } catch (\Exception $e) {
            return null;
        }
    }
}
