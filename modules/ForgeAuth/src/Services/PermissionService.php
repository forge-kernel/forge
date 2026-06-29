<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Services;

use Modules\ForgeAuth\Contracts\AuthUserInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Contracts\Database\QueryBuilderInterface;

#[Service]
final class PermissionService
{
  private array $permissionsCache = [];

  public function __construct(
    private readonly QueryBuilderInterface $queryBuilder
  ) {
  }

  /**
   * Get all permissions for a user based on their roles.
   *
   * @return array<string> Array of permission strings
   */
  public function getUserPermissions(AuthUserInterface $user): array
  {
    if (isset($this->permissionsCache[$user->getId()])) {
      return $this->permissionsCache[$user->getId()];
    }

    $rows = $this->queryBuilder
        ->table('permissions')
        ->select('permissions.name')
        ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
        ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
        ->where('user_roles.user_id', '=', $user->getId())
        ->get();

    $permissions = array_values(array_unique(array_column($rows, 'name')));
    $this->permissionsCache[$user->getId()] = $permissions;
    return $permissions;
  }

  /**
   * Check if a user has a specific permission.
   */
  public function hasPermission(AuthUserInterface $user, string $permission): bool
  {
    $permissions = $this->getUserPermissions($user);
    return in_array($permission, $permissions, true);
  }

  /**
   * Check if a user has any of the specified permissions.
   */
  public function hasAnyPermission(AuthUserInterface $user, array $permissions): bool
  {
    if (empty($permissions)) {
      return true;
    }

    $userPermissions = $this->getUserPermissions($user);

    foreach ($permissions as $permission) {
      if (in_array($permission, $userPermissions, true)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Check if a user has all of the specified permissions.
   */
  public function hasAllPermissions(AuthUserInterface $user, array $permissions): bool
  {
    if (empty($permissions)) {
      return true;
    }

    $userPermissions = $this->getUserPermissions($user);

    foreach ($permissions as $permission) {
      if (!in_array($permission, $userPermissions, true)) {
        return false;
      }
    }

    return true;
  }
}
