<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Services;

use Modules\ForgeAuth\Contracts\AuthUserInterface;
use Modules\ForgeAuth\Models\Role;
use Modules\ForgeAuth\Models\Permission;
use Modules\ForgeAuth\Repositories\RoleRepository;
use Forge\Core\Contracts\Database\QueryBuilderInterface;

final class RoleService
{
    private array $userRolesCache = [];

    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
    }

    public function createRole(string $name, ?string $description = null): Role
    {
        $existingRole = $this->roleRepository->findByName($name);
        if ($existingRole) {
            throw new \InvalidArgumentException(
                "Role with name '{$name}' already exists",
            );
        }

        return $this->roleRepository->createRole($name, $description);
    }

    public function deleteRole(Role $role): void
    {
        $this->queryBuilder
            ->table("user_roles")
            ->where("role_id", "=", $role->id)
            ->delete();

        $this->queryBuilder
            ->table("role_permissions")
            ->where("role_id", "=", $role->id)
            ->delete();

        $this->roleRepository->deleteRole($role);
    }

    public function addPermissionToRole(
        Role $role,
        Permission $permission,
    ): void {
        $exists = $this->queryBuilder
            ->table("role_permissions")
            ->where("role_id", "=", $role->id)
            ->where("permission_id", "=", $permission->id)
            ->first();

        if (!$exists) {
            $this->queryBuilder->table("role_permissions")->insert([
                "role_id" => $role->id,
                "permission_id" => $permission->id,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s"),
            ]);
        }
    }

    public function removePermissionFromRole(
        Role $role,
        Permission $permission,
    ): void {
        $this->queryBuilder
            ->table("role_permissions")
            ->where("role_id", "=", $role->id)
            ->where("permission_id", "=", $permission->id)
            ->delete();
    }

    public function assignRoleToUser(Role $role, AuthUserInterface $user): void
    {
        $exists = $this->queryBuilder
            ->table("user_roles")
            ->where("user_id", "=", $user->getId())
            ->where("role_id", "=", $role->id)
            ->first();

        if (!$exists) {
            $this->queryBuilder->table("user_roles")->insert([
                "user_id" => $user->getId(),
                "role_id" => $role->id,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s"),
            ]);
        }
    }

    public function removeRoleFromUser(Role $role, AuthUserInterface $user): void
    {
        $this->queryBuilder
            ->table("user_roles")
            ->where("user_id", "=", $user->getId())
            ->where("role_id", "=", $role->id)
            ->delete();
    }

    public function getUserRoles(AuthUserInterface $user): array
    {
        if (isset($this->userRolesCache[$user->getId()])) {
            return $this->userRolesCache[$user->getId()];
        }

        $userRoleRows = $this->queryBuilder
            ->table('user_roles')
            ->select('role_id')
            ->where('user_id', '=', $user->getId())
            ->get();

        if (empty($userRoleRows)) {
            $this->userRolesCache[$user->getId()] = [];
            return [];
        }

        $roleIds = array_column($userRoleRows, 'role_id');
        $roles = Role::query()->whereIn('id', $roleIds)->get();

        $this->userRolesCache[$user->getId()] = $roles;
        return $roles;
    }

    public function getRolePermissions(Role $role): array
    {
        $rolePermissionRows = $this->queryBuilder
            ->table("role_permissions")
            ->where("role_id", "=", $role->id)
            ->get();

        if (empty($rolePermissionRows)) {
            return [];
        }

        $permissionIds = array_column($rolePermissionRows, "permission_id");
        return Permission::query()->whereIn("id", $permissionIds)->get();
    }

    public function getAllRoles(): array
    {
        return $this->roleRepository->getAllRoles();
    }

    public function findRoleById(int $id): ?Role
    {
        return $this->roleRepository->findById($id);
    }

    public function findRoleByName(string $name): ?Role
    {
        return $this->roleRepository->findByName($name);
    }

    public function userHasRole(AuthUserInterface $user, string $roleName): bool
    {
        $roles = $this->getUserRoles($user);
        foreach ($roles as $role) {
            if (strcasecmp($role->name, $roleName) === 0) {
                return true;
            }
        }
        return false;
    }

    public function userHasPermission(AuthUserInterface $user, string $permission): bool
    {
        $roles = $this->getUserRoles($user);
        foreach ($roles as $role) {
            $permissions = $this->getRolePermissions($role);
            foreach ($permissions as $perm) {
                if (strcasecmp($perm->name, $permission) === 0) {
                    return true;
                }
            }
        }
        return false;
    }
}
