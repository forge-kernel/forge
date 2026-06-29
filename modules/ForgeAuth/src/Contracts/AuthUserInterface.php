<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Contracts;

interface AuthUserInterface
{
    public function getId(): int;
    public function getIdentifier(): string;
    public function getEmail(): string;
}
