<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Repositories;

use App\Modules\ForgeAuth\Models\Role;
use App\Modules\ForgeSqlOrm\ORM\Paginator;
use App\Modules\ForgeSqlOrm\ORM\RecordRepository;
use Forge\Core\Cache\Attributes\Cache;
use Forge\Core\Cache\Attributes\NoCache;
use Forge\Traits\CacheLifecycleHooks;

#[NoCache]
class RoleRepository extends RecordRepository
{
    use CacheLifecycleHooks;

    protected function getModelClass(): string
    {
        return Role::class;
    }

    #[Cache(key: "find_by_{id}", ttl: 3600)]
    public function findById(int $id): ?Role
    {
        return parent::find($id);
    }

    #[Cache(key: "role_name_{name}", ttl: 3600)]
    public function findByName(string $name): ?Role
    {
        return Role::query()->where("name", "=", $name)->first();
    }

    #[Cache(key: "pagination_{page}_{perPage}", ttl: 3600)]
    public function paginate(
        int $page = 1,
        int $perPage = 10,
        array $options = [],
    ): Paginator {
        return parent::paginate($page, $perPage, $options);
    }

    public function createRole(string $name, ?string $description = null): Role
    {
        $role = new Role();
        $role->name = $name;
        $role->description = $description;
        $role->save();

        $this->cache->invalidate($this->tableName);

        return $role;
    }

    public function deleteRole(Role $role): bool
    {
        $result = parent::delete($role->id);
        if ($result) {
            $this->cache->invalidate($this->tableName);
        }
        return $result;
    }

    public function getAllRoles(): array
    {
        return Role::query()->orderBy("name")->get();
    }
}
