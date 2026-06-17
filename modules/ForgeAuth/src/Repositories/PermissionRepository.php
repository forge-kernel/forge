<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Repositories;

use App\Modules\ForgeAuth\Models\Permission;
use App\Modules\ForgeSqlOrm\ORM\Paginator;
use App\Modules\ForgeSqlOrm\ORM\RecordRepository;
use Forge\Core\Cache\Attributes\Cache;
use Forge\Core\Cache\Attributes\NoCache;
use Forge\Traits\CacheLifecycleHooks;

#[NoCache]
class PermissionRepository extends RecordRepository
{
    use CacheLifecycleHooks;

    protected function getModelClass(): string
    {
        return Permission::class;
    }

    #[Cache(key: 'find_by_{id}', ttl: 3600)]
    public function findById(int $id): ?Permission
    {
        return parent::find($id);
    }

    #[Cache(key: 'permission_name_{name}', ttl: 3600)]
    public function findByName(string $name): ?Permission
    {
        return Permission::query()->where('name', '=', $name)->first();
    }

    #[Cache(key: 'pagination_{page}_{perPage}', ttl: 3600)]
    public function paginate(int $page = 1, int $perPage = 10, array $options = []): Paginator
    {
        return parent::paginate($page, $perPage, $options);
    }

    public function createPermission(string $name, ?string $description = null): Permission
    {
        $queryBuilder = \Forge\Core\DI\Container::getInstance()->get(\Forge\Core\Contracts\Database\QueryBuilderInterface::class);
        
        $data = [
            'name' => $name,
            'description' => $description ?? null
        ];
        
        $id = $queryBuilder->table('permissions')->insertGetId($data);
        
        $permission = new Permission();
        $permission->id = $id;
        $permission->name = $name;
        $permission->description = $description;

        $this->cache->invalidate($this->tableName);

        return $permission;
    }

    public function deletePermission(Permission $permission): bool
    {
        $result = parent::delete($permission->id);
        if ($result) {
            $this->cache->invalidate($this->tableName);
        }
        return $result;
    }

    public function getAll(): array
    {
        return Permission::query()->orderBy('name')->get();
    }
}