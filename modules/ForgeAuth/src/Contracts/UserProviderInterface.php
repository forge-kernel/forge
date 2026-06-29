<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Contracts;

use Modules\ForgeSqlOrm\ORM\Paginator;

interface UserProviderInterface
{
    public function findById(int $id): ?AuthUserInterface;

    public function findByIdentifier(string $identifier): ?AuthUserInterface;

    public function findByEmail(string $email): ?AuthUserInterface;

    public function verifyCredentials(string $identifier, string $password): ?AuthUserInterface;

    public function createUser(array $credentials): AuthUserInterface;

    public function paginate(int $page = 1, int $perPage = 10, array $options = []): Paginator;
}
