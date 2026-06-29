<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Traits;

use Modules\ForgeAuth\Contracts\AuthUserInterface;
use Modules\ForgeAuth\Services\RoleService;
use Forge\Core\DI\Container;
use Modules\ForgeRouter\Http\Response;

trait HasRoles
{
    private function getRoleService(): RoleService
    {
        return Container::getInstance()->get(RoleService::class);
    }

    public function authorize(string|\BackedEnum $permission): void
    {
        $permissionName = $permission instanceof \BackedEnum ? (string) $permission->value : $permission;

        if (!method_exists($this, 'getCurrentUser')) {
            throw new \RuntimeException('The class using HasRoles must also use HasCurrentUser or implement getCurrentUser() to use authorize().');
        }

        $user = $this->getCurrentUser();

        if (!$user) {
            ob_start();
            $errorCode = 401;
            require BASE_PATH . "/kernel/Templates/Views/error_page.php";
            $content = ob_get_clean();
            (new Response($content, 401))->send();
            exit;
        }

        if (!$this->hasPermission($user, $permissionName)) {
            ob_start();
            $errorCode = 403;
            require BASE_PATH . "/kernel/Templates/Views/error_page.php";
            $content = ob_get_clean();
            (new Response($content, 403))->send();
            exit;
        }
    }

    public function authorizeAny(array $permissions): void
    {
        $permissionNames = array_map(
            fn($p) => $p instanceof \BackedEnum ? (string) $p->value : $p,
            $permissions
        );

        if (!method_exists($this, 'getCurrentUser')) {
            throw new \RuntimeException('The class using HasRoles must also use HasCurrentUser or implement getCurrentUser() to use authorizeAny().');
        }

        $user = $this->getCurrentUser();

        if (!$user) {
            ob_start();
            $errorCode = 401;
            require BASE_PATH . "/kernel/Templates/Views/error_page.php";
            $content = ob_get_clean();
            (new Response($content, 401))->send();
            exit;
        }

        if (!$this->hasAnyPermission($user, $permissionNames)) {
            ob_start();
            $errorCode = 403;
            require BASE_PATH . "/kernel/Templates/Views/error_page.php";
            $content = ob_get_clean();
            (new Response($content, 403))->send();
            exit;
        }
    }

    public function hasRole(AuthUserInterface $user, string $roleName): bool
    {
        return $this->getRoleService()->userHasRole($user, $roleName);
    }

    public function hasAnyRole(AuthUserInterface $user, array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if ($this->hasRole($user, $roleName)) {
                return true;
            }
        }
        return false;
    }

    public function hasAllRoles(AuthUserInterface $user, array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if (!$this->hasRole($user, $roleName)) {
                return false;
            }
        }
        return true;
    }

    public function hasPermission(AuthUserInterface $user, string $permission): bool
    {
        return $this->getRoleService()->userHasPermission($user, $permission);
    }

    public function hasAnyPermission(AuthUserInterface $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }
        return false;
    }

    public function hasAllPermissions(AuthUserInterface $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }
        return true;
    }

    public function can(
        AuthUserInterface $user,
        string $permission,
        mixed $resource = null,
    ): bool {
        if ($resource) {
            if (
                method_exists($resource, "getOwnerId") &&
                $resource->getOwnerId() === $user->getId()
            ) {
                return true;
            }

            if (
                method_exists($resource, "getUserId") &&
                $resource->getUserId() === $user->getId()
            ) {
                return true;
            }

            if (
                method_exists($resource, "getAuthorId") &&
                $resource->getAuthorId() === $user->getId()
            ) {
                return true;
            }
        }

        return $this->hasPermission($user, $permission);
    }

    public function canAny(
        AuthUserInterface $user,
        array $permissions,
        mixed $resource = null,
    ): bool {
        foreach ($permissions as $permission) {
            if ($this->can($user, $permission, $resource)) {
                return true;
            }
        }
        return false;
    }

    public function canAll(
        AuthUserInterface $user,
        array $permissions,
        mixed $resource = null,
    ): bool {
        foreach ($permissions as $permission) {
            if (!$this->can($user, $permission, $resource)) {
                return false;
            }
        }
        return true;
    }

    public function cannot(
        AuthUserInterface $user,
        string $permission,
        mixed $resource = null,
    ): bool {
        return !$this->can($user, $permission, $resource);
    }

    public function isOwner(AuthUserInterface $user, mixed $resource): bool
    {
        if (!$resource) {
            return false;
        }

        if (
            method_exists($resource, "getOwnerId") &&
            $resource->getOwnerId() === $user->getId()
        ) {
            return true;
        }

        if (
            method_exists($resource, "getUserId") &&
            $resource->getUserId() === $user->getId()
        ) {
            return true;
        }

        if (
            method_exists($resource, "getAuthorId") &&
            $resource->getAuthorId() === $user->getId()
        ) {
            return true;
        }

        return false;
    }
}
