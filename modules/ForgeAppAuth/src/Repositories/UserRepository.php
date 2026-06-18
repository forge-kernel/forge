<?php
declare(strict_types=1);

namespace App\Modules\ForgeAppAuth\Repositories;

use App\Modules\ForgeAppAuth\Dto\CreateUserData;
use App\Modules\ForgeAppAuth\Models\User;
use App\Modules\ForgeAuth\Contracts\AuthUserInterface;
use App\Modules\ForgeAuth\Contracts\UserProviderInterface;
use App\Modules\ForgeSqlOrm\ORM\Paginator;
use App\Modules\ForgeSqlOrm\Repositories\RecordRepository;
use Forge\Core\DI\Attributes\NoCache;

#[NoCache]
class UserRepository extends RecordRepository implements UserProviderInterface
{

    protected function getModelClass(): string
    {
        return User::class;
    }

    public function findById(int $id): ?AuthUserInterface
    {
        return parent::find($id);
    }

    public function findByIdentifier(string $identifier): ?AuthUserInterface
    {
        return User::query()->where('identifier', '=', $identifier)->first();
    }

    public function findByEmail(string $email): ?AuthUserInterface
    {
        return User::query()->where('email', '=', $email)->first();
    }

    public function verifyCredentials(string $identifier, string $password): ?AuthUserInterface
    {
        $user = User::query()->where('identifier', '=', $identifier)->first();
        if (!$user || !password_verify($password, $user->password)) {
            return null;
        }
        return $user;
    }

    public function createUser(array $credentials): AuthUserInterface
    {
        $data = new CreateUserData(
            identifier: $credentials['identifier'],
            email: $credentials['email'],
            password: password_hash($credentials['password'], PASSWORD_BCRYPT),
            status: $credentials['status'] ?? 'active',
            metadata: $credentials['metadata'] ?? null,
        );

        $user = new User();
        $user->identifier = $data->identifier;
        $user->email = $data->email;
        $user->password = $data->password;
        $user->status = $data->status;
        $user->metadata = $data->metadata;
        $user->save();
        $this->cache->invalidate($this->tableName);

        return $user;
    }

    public function updatePassword(int $userId, string $newPassword): bool
    {
        $user = User::query()->where('id', '=', $userId)->first();
        if (!$user) {
            return false;
        }

        $user->password = password_hash($newPassword, PASSWORD_BCRYPT);
        $user->save();
        $this->cache->invalidate($this->tableName);

        return true;
    }

    public function paginate(int $page = 1, int $perPage = 10, array $options = []): Paginator
    {
        return parent::paginate($page, $perPage, $options);
    }
}
