<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Commands;

use App\Modules\ForgeAuth\Repositories\RoleRepository;
use App\Modules\ForgeAuth\Repositories\PermissionRepository;
use App\Modules\ForgeAuth\Services\RoleService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Services\TemplateGenerator;
use App\Modules\ForgeLogger\Services\ForgeLoggerService;
use Throwable;

#[
    Cli(
        command: "auth:role:remove-permission",
        description: "Remove permissions from a role with batch processing",
        usage: "auth:role:remove-permission [--role=ROLE_NAME] [--permissions=PERM1,PERM2] [--force]",
        examples: [
            "auth:role:remove-permission",
            "auth:role:remove-permission --role=EDITOR --permissions=users.delete,content.delete",
            "auth:role:remove-permission --role=ADMIN --permissions=system.backup --force",
        ],
    ),
]
final class RoleRemovePermissionCommand extends Command
{
    use Wizard;

    #[Arg(name: "role", description: "Role name", required: false)]
    private ?string $role = null;

    #[
        Arg(
            name: "permissions",
            description: "Comma-separated list of permission names to remove",
            required: false,
        ),
    ]
    private ?string $permissions = null;

    #[
        Arg(
            name: "force",
            description: "Skip destructive action confirmation",
            default: false,
            required: false,
        ),
    ]
    private bool $force = false;

    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly RoleService $roleService,
        private readonly TemplateGenerator $templateGenerator,
        private readonly ForgeLoggerService $logger,
    ) {}

    public function execute(array $args): int
    {
        $this->wizard($args);

        if (empty($args) && (!$this->role || !$this->permissions)) {
            $this->runWizard();
            return 0;
        }

        return $this->removePermissions();
    }

    private function runWizard(): void
    {
        $this->info("=== Remove Permissions from Role ===");
        $this->line("");

        $allRoles = $this->roleRepository->getAllRoles();
        $roleNames = array_map(fn($role) => $role->name, $allRoles);

        if (empty($roleNames)) {
            $this->error("No roles found in the system");
            return;
        }

        if (!$this->role) {
            $this->role = $this->templateGenerator->selectFromList(
                "Select role:",
                $roleNames,
                $roleNames[0] ?? null,
            );

            if ($this->role === null) {
                $this->info("Operation cancelled");
                return;
            }
        }

        $selectedRole = null;
        foreach ($allRoles as $role) {
            if ($role->name === $this->role) {
                $selectedRole = $role;
                break;
            }
        }

        if (!$selectedRole) {
            $this->error("Role '{$this->role}' not found");
            return;
        }

        $rolePermissions = $this->roleService->getRolePermissions(
            $selectedRole,
        );
        if (empty($rolePermissions)) {
            $this->warning("Role '{$this->role}' has no permissions assigned");
            return;
        }

        if (!$this->permissions) {
            $permissionNames = array_map(
                fn($perm) => $perm->name,
                $rolePermissions,
            );
            $selectedPermissions = $this->templateGenerator->selectMultipleFromList(
                "Select permissions to remove:",
                $permissionNames,
            );

            if ($selectedPermissions === null || empty($selectedPermissions)) {
                $this->info("No permissions selected");
                return;
            }

            $this->permissions = implode(",", $selectedPermissions);
        }

        $this->showDestructiveWarning($selectedRole);
    }

    private function showDestructiveWarning($role): void
    {
        $this->line("");
        $this->info("=== DANGER ZONE ===");
        $this->line("");

        $messages = [];
        $messages[] = "Role: {$role->name}";
        $messages[] = "Permissions to remove: {$this->permissions}";
        $messages[] =
            "This will REMOVE the specified permissions from the role.";
        $messages[] =
            "Users with this role will lose access to these permissions.";

        if ($role->description) {
            $messages[] = "Role description: {$role->description}";
        }

        foreach ($messages as $message) {
            $this->warning($message);
        }

        $this->line("");
        $this->error("There is NO UNDO. Are you absolutely sure?");
        $this->line("");

        if ($this->force) {
            $this->info("--force flag detected, proceeding with removal");
            return;
        }

        $proceed = $this->askYesNo("Type YES in UPPER-CASE to proceed", "YES");
        if (!$proceed) {
            $this->info("Permission removal cancelled");
            return;
        }

        $this->removePermissions();
    }

    private function removePermissions(): int
    {
        try {
            if (empty($this->role) || empty($this->permissions)) {
                $this->error("Both --role and --permissions are required");
                return 1;
            }

            $role = $this->roleRepository->findByName($this->role);
            if (!$role) {
                $this->error("Role '{$this->role}' not found");
                return 1;
            }

            $permissionNames = array_map(
                "trim",
                explode(",", $this->permissions),
            );
            $removedCount = 0;
            $errorCount = 0;

            $this->info(
                "Processing " .
                    count($permissionNames) .
                    " permission(s) for removal...",
            );

            foreach ($permissionNames as $permissionName) {
                $permission = $this->permissionRepository->findByName(
                    $permissionName,
                );

                if (!$permission) {
                    $this->warning(
                        "Permission '{$permissionName}' not found, skipping",
                    );
                    $errorCount++;
                    continue;
                }

                try {
                    $this->roleService->removePermissionFromRole(
                        $role,
                        $permission,
                    );
                    $this->success(
                        "Removed '{$permissionName}' from role '{$this->role}'",
                    );
                    $removedCount++;
                } catch (Throwable $e) {
                    $this->error(
                        "Failed to remove '{$permissionName}': " .
                            $e->getMessage(),
                    );
                    $errorCount++;
                }
            }

            $this->line("");
            $this->info("Operation completed:");
            $this->success(
                "Successfully removed: {$removedCount} permission(s)",
            );
            if ($errorCount > 0) {
                $this->error("Failed to remove: {$errorCount} permission(s)");
            }

            return $errorCount > 0 ? 1 : 0;
        } catch (Throwable $e) {
            $this->error("Failed to remove permissions: " . $e->getMessage());
            $this->logger->debug("Permission removal failed", [
                "role" => $this->role,
                "permissions" => $this->permissions,
                "error" => $e->getMessage(),
            ]);
            return 1;
        }
    }
}
