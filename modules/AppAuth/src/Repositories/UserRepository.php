<?php

declare(strict_types=1);

namespace App\Modules\AppAuth\Repositories;

use App\Modules\AppAuth\Dto\CreateUserData;
use App\Modules\AppAuth\Dto\UserMetadataDto;
use App\Modules\AppAuth\Models\User;
use App\Modules\ForgeAuth\Contracts\AuthUserInterface;
use App\Modules\ForgeAuth\Contracts\UserProviderInterface;
use App\Modules\ForgeSqlOrm\ORM\Paginator;
use App\Modules\ForgeSqlOrm\ORM\RecordRepository;
use Forge\Core\Cache\Attributes\NoCache;
use Forge\Core\DI\Container;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Traits\CacheLifecycleHooks;

#[NoCache]
class UserRepository extends RecordRepository implements UserProviderInterface
{
    use CacheLifecycleHooks;

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
            identifier: $credentials["identifier"],
            email: $credentials["email"],
            password: password_hash($credentials["password"], PASSWORD_BCRYPT),
            status: $credentials["status"] ?? 'active',
            metadata: $credentials["metadata"] ?? null
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

    public function paginate(int $page = 1, int $perPage = 10, array $options = []): Paginator
    {
        return parent::paginate($page, $perPage, $options);
    }

    public function createUserWithRoles(
        string $identifier,
        string $email,
        string $password,
        string $status,
        ?UserMetadataDto $metadata = null,
        array $roleIds = []
    ): User {
        $user = $this->createUser([
            'identifier' => $identifier,
            'email' => $email,
            'password' => $password,
            'status' => $status,
            'metadata' => $metadata,
        ]);

        if (!empty($roleIds) && $user instanceof User) {
            $queryBuilder = Container::getInstance()->get(QueryBuilderInterface::class);
            foreach ($roleIds as $roleId) {
                $queryBuilder
                    ->table('user_roles')
                    ->insert([
                        'user_id' => $user->id,
                        'role_id' => $roleId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
            }
            $this->cache->invalidate($this->tableName);
        }

        return $user;
    }
}
