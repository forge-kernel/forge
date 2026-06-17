<?php

namespace App\Modules\ForgeAuth\Contracts;

interface ForgeAuthInterface
{
    public function register(array $credentials): bool;
    public function login(array $credentials): AuthUserInterface;
    public function logout(): void;
}
