<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Commands;

use App\Modules\ForgeAuth\Repositories\RoleRepository;
use App\Modules\ForgeAuth\Repositories\PermissionRepository;
use App\Modules\ForgeAuth\Services\RoleService;
use App\Modules\ForgeAuth\Services\EnumGeneratorService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Services\TemplateGenerator;
use App\Modules\ForgeLogger\Services\ForgeLoggerService;
use Throwable;

#[
    Cli(
        command: "auth:role:add-permission",
        description: "Add permissions to a role with wizard and param support",
        usage: "auth:role:add-permission [--role=ROLE_NAME] [--permissions=PERM1,PERM2]",
        examples: [
            "auth:role:add-permission",
            "auth:role:add-permission --role=EDITOR --permissions=content.create,content.edit",
            "auth:role:add-permission --role=VIEWER --permissions=users.read,content.read",
        ],
    ),
]
final class RoleAddPermissionCommand extends Command
{
    use Wizard;

    #[Arg(name: "role", description: "Role name", required: false)]
    private ?string $role = null;

    #[
        Arg(
            name: "permissions",
            description: "Comma-separated list of permission names to add",
            required: false,
        ),
    ]
    private ?string $permissions = null;

    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly RoleService $roleService,
        private readonly TemplateGenerator $templateGenerator,
        private readonly ForgeLoggerService $logger,
        private readonly EnumGeneratorService $enumGeneratorService,
    ) {}

    public function execute(array $args): int
    {
        $this->wizard($args);

        if (empty($args) && (!$this->role || !$this->permissions)) {
            $this->runWizard();
            return 0;
        }

        return $this->addPermissions();
    }

    private function runWizard(): void
    {
        $this->info("=== Add Permissions to Role ===");
        $this->line("");

        $allRoles = $this->roleRepository->getAllRoles();
        $roleNames = array_map(fn($role) => $role->name, $allRoles);

        if (empty($roleNames)) {
            $this->error("No roles found in system");
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

        $this->handlePermissionSelection($selectedRole);
    }

    private function handlePermissionSelection($role): void
    {
        $allPermissions = $this->permissionRepository->getAll();
        $permissionNames = array_map(fn($perm) => $perm->name, $allPermissions);

        $existingPermissions = $this->roleService->getRolePermissions($role);
        $existingPermissionNames = array_map(
            fn($perm) => $perm->name,
            $existingPermissions,
        );

        if (!empty($existingPermissionNames)) {
            $this->info("Role '{$role->name}' already has these permissions:");
            foreach ($existingPermissionNames as $permName) {
                $this->line("  - {$permName}");
            }
            $this->line("");
        }

        if (!$this->permissions) {
            $choice = $this->templateGenerator->selectFromList(
                "How would you like to add permissions?",
                [
                    "Select from existing permissions",
                    "Enter permission names manually",
                    "Create new permissions",
                ],
                "Select from existing permissions",
            );

            if ($choice === null) {
                $this->info("Operation cancelled");
                return;
            }

            match ($choice) {
                "Select from existing permissions"
                    => $this->selectPermissionsFromList(
                    $permissionNames,
                    $existingPermissionNames,
                ),
                "Enter permission names manually"
                    => $this->enterPermissionsManually(),
                "Create new permissions" => $this->createNewPermissions(),
            };
        }

        $this->showSummary($role);
    }

    private function selectPermissionsFromList(
        array $permissionNames,
        array $existingNames,
    ): void {
        $availablePermissions = array_diff($permissionNames, $existingNames);

        if (empty($availablePermissions)) {
            $this->warning("No additional permissions available to add");
            return;
        }

        $selectedPermissions = $this->templateGenerator->selectMultipleFromList(
            "Select permissions to add:",
            $availablePermissions,
        );

        if ($selectedPermissions && !empty($selectedPermissions)) {
            $this->permissions = implode(",", $selectedPermissions);
        }
    }

    private function enterPermissionsManually(): void
    {
        $permissionsInput = $this->templateGenerator->askQuestion(
            "Enter permission names (comma-separated, format: resource.action like users.read):",
            "",
        );

        if (!empty($permissionsInput)) {
            $this->permissions = $permissionsInput;
        }
    }

    private function createNewPermissions(): void
    {
        $this->info("Creating new permissions...");
        $permissionInput = $this->templateGenerator->askQuestion(
            "Enter permission names (comma-separated):",
            "",
        );

        if (!empty($permissionInput)) {
            $this->permissions = $permissionInput;
        }
    }

    private function showSummary($role): void
    {
        $this->line("");
        $this->info("=== Summary ===");
        $this->line("Role: {$role->name}");
        if ($this->permissions) {
            $this->line("Permissions to add: {$this->permissions}");
        }
        $this->line("");

        if (!$this->permissions) {
            $this->warning("No permissions selected to add");
            return;
        }

        $proceed = $this->askYesNo("Add these permissions?", "yes");
        if (!$proceed) {
            $this->info("Permission addition cancelled");
            return;
        }

        $this->addPermissions();
    }

    private function addPermissions(): int
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
            $addedCount = 0;
            $createdCount = 0;
            $errorCount = 0;

            $this->info(
                "Processing " . count($permissionNames) . " permission(s)...",
            );

            foreach ($permissionNames as $permissionName) {
                $permission = $this->permissionRepository->findByName(
                    $permissionName,
                );

                if (!$permission) {
                    $permission = $this->permissionRepository->createPermission(
                        $permissionName,
                        "Auto-created for role {$this->role}",
                    );
                    $createdCount++;
                    $this->info("Created permission: {$permissionName}");
                }

                try {
                    $this->roleService->addPermissionToRole($role, $permission);
                    $addedCount++;
                    $this->success(
                        "Added '{$permissionName}' to role '{$this->role}'",
                    );
                } catch (Throwable $e) {
                    if (
                        $e->getMessage() &&
                        str_contains($e->getMessage(), "already exists")
                    ) {
                        $this->warning(
                            "Permission '{$permissionName}' already assigned to role",
                        );
                    } else {
                        $this->error(
                            "Failed to add '{$permissionName}': " .
                                $e->getMessage(),
                        );
                        $errorCount++;
                    }
                }
            }

            $this->line("");
            $this->info("Operation completed:");
            $this->success("Successfully added: {$addedCount} permission(s)");
            if ($createdCount > 0) {
                $this->info("Updating Permission Enum...");
                $this->enumGeneratorService->generatePermissionEnum();
                $this->info("Created: {$createdCount} new permission(s)");
            }
            if ($errorCount > 0) {
                $this->error("Failed to add: {$errorCount} permission(s)");
            }

            return $errorCount > 0 ? 1 : 0;
        } catch (Throwable $e) {
            $this->error("Failed to add permissions: " . $e->getMessage());
            $this->logger->debug("Permission addition failed", [
                "role" => $this->role,
                "permissions" => $this->permissions,
                "error" => $e->getMessage(),
            ]);
            return 1;
        }
    }
}
