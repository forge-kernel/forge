<?php

declare(strict_types=1);

use Modules\ForgeAuth\Contracts\AuthUserInterface;
use Modules\ForgeAuth\Services\RoleService;
use Modules\ForgeAuth\Services\PermissionService;
use Modules\ForgeAuth\Enums\Permission;
use Modules\ForgeAuth\Enums\Role;
use Forge\Core\DI\Container;

if (!function_exists("isOwner")) {
    function isOwner(mixed $resource): bool
    {
        $user = getCurrentUser();
        if (!$user || !$resource) {
            return false;
        }

        if (method_exists($resource, "getOwnerId") && $resource->getOwnerId() === $user->getId()) {
            return true;
        }
        if (method_exists($resource, "getUserId") && $resource->getUserId() === $user->getId()) {
            return true;
        }
        if (method_exists($resource, "getAuthorId") && $resource->getAuthorId() === $user->getId()) {
            return true;
        }

        return false;
    }
}

if (!function_exists("getAllUserPermissions")) {
    function getAllUserPermissions(AuthUserInterface $user): array
    {
        try {
            return Container::getInstance()->get(PermissionService::class)->getUserPermissions($user);
        } catch (\Exception $e) {
            return [];
        }
    }
}

if (!function_exists("hasRole")) {
    function hasRole(string|array|Role $roleNames): bool
    {
        $user = getCurrentUser();
        if (!$user) {
            return false;
        }

        $roleService = Container::getInstance()->get(RoleService::class);
        $userRoles = $roleService->getUserRoles($user);

        $rolesToCheck = is_array($roleNames) ? $roleNames : [$roleNames];
        $roleValues = array_map(fn($r) => $r instanceof Role ? $r->value : $r, $rolesToCheck);

        foreach ($userRoles as $userRole) {
            foreach ($roleValues as $rv) {
                if (strcasecmp($userRole->name, $rv) === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists("hasRoleEnum")) {
    function hasRoleEnum(Role ...$roles): bool
    {
        $user = getCurrentUser();
        if (!$user) {
            return false;
        }

        $roleService = Container::getInstance()->get(RoleService::class);
        $userRoles = $roleService->getUserRoles($user);

        foreach ($roles as $role) {
            $hasThisRole = false;
            foreach ($userRoles as $userRole) {
                if (strcasecmp($userRole->name, $role->value) === 0) {
                    $hasThisRole = true;
                    break;
                }
            }
            if (!$hasThisRole) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists("can")) {
    function can(
        string|array|Permission $permissions,
        mixed $resource = null,
    ): bool {
        if ($resource && isOwner($resource)) {
            return true;
        }

        $user = getCurrentUser();
        if (!$user) {
            return false;
        }

        $userPermissions = getAllUserPermissions($user);

        if (is_string($permissions) || $permissions instanceof Permission) {
            $permissionName = $permissions instanceof Permission ? $permissions->value : $permissions;
            return in_array($permissionName, $userPermissions, true);
        }

        foreach ($permissions as $permission) {
            $permissionName = $permission instanceof Permission ? $permission->value : $permission;
            if (!in_array($permissionName, $userPermissions, true)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists("canAny")) {
    function canAny(array $permissions, mixed $resource = null): bool
    {
        if ($resource && isOwner($resource)) {
            return true;
        }

        $user = getCurrentUser();
        if (!$user) {
            return false;
        }

        $userPermissions = getAllUserPermissions($user);

        foreach ($permissions as $permission) {
            $permissionName = $permission instanceof Permission ? $permission->value : $permission;
            if (in_array($permissionName, $userPermissions, true)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists("canAll")) {
    function canAll(array $permissions, mixed $resource = null): bool
    {
        if ($resource && isOwner($resource)) {
            return true;
        }

        $user = getCurrentUser();
        if (!$user) {
            return false;
        }

        $userPermissions = getAllUserPermissions($user);

        foreach ($permissions as $permission) {
            $permissionName = $permission instanceof Permission ? $permission->value : $permission;
            if (!in_array($permissionName, $userPermissions, true)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists("cannot")) {
    function cannot(
        string|array|Permission $permissions,
        mixed $resource = null,
    ): bool {
        return !can($permissions, $resource);
    }
}

if (!function_exists("canEnum")) {
    function canEnum(mixed $resource = null, Permission ...$permissions): bool
    {
        if ($resource && isOwner($resource)) {
            return true;
        }

        $user = getCurrentUser();
        if (!$user) {
            return false;
        }

        $userPermissions = getAllUserPermissions($user);

        foreach ($permissions as $permission) {
            if (!in_array($permission->value, $userPermissions, true)) {
                return false;
            }
        }

        return true;
    }
}
