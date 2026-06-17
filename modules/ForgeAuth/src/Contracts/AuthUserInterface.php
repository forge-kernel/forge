<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Contracts;

interface AuthUserInterface
{
    public function getId(): int;
    public function getIdentifier(): string;
    public function getEmail(): string;
}
