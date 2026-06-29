<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Contracts;

interface UserContextInterface
{
    public function current(): ?AuthUserInterface;

    public function isAuthenticated(): bool;

    public function setCurrentUser(AuthUserInterface $user): void;
}
