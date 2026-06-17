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
        command: "auth:role:create",
        description: "Create a new role and assign permissions",
        usage: "auth:role:create [--name=ROLE_NAME] [--description=DESCRIPTION] [--permissions=PERM1,PERM2]",
        examples: [
            "auth:role:create",
            "auth:role:create --name=ADMIN --description=Administrator role",
            "auth:role:create --name=EDITOR --permissions=users.read,users.write,content.edit",
            "auth:role:create --name=VIEWER --permissions=users.read,content.read",
        ],
    ),
]
final class RoleCreateCommand extends Command
{
    use Wizard;

    #[
        Arg(
            name: "name",
            description: "Role name (must be unique)",
            required: false,
        ),
    ]
    private ?string $name = null;

    #[
        Arg(
            name: "description",
            description: "Role description",
            required: false,
        ),
    ]
    private ?string $description = null;

    #[
        Arg(
            name: "permissions",
            description: "Comma-separated list of permission names",
            required: false,
        ),
    ]
    private ?string $permissions = null;

    #[
        Arg(
            name: "create-permissions",
            description: "Auto-create permissions that dont exist",
            default: false,
            required: false,
        ),
    ]
    private bool $createPermissions = false;

    #[
        Arg(
            name: "interactive",
            description: "Force interactive mode even with all params provided",
            default: false,
            required: false,
        ),
    ]
    private bool $interactive = false;

    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly RoleService $roleService,
        private readonly EnumGeneratorService $enumGenerator,
        private readonly TemplateGenerator $templateGenerator,
        private readonly ForgeLoggerService $logger,
    ) {}

    public function execute(array $args): int
    {
        $this->wizard($args);

        // Parse additional args that might come from command line
        $this->parseAdditionalArgs($args);

        if (empty($args) && !$this->name) {
            $this->runWizard();
            return 0;
        }

        return $this->createRole();
    }

    private function parseAdditionalArgs(array $args): void
    {
        foreach ($args as $arg) {
            if (!str_starts_with($arg, "--") && !str_starts_with($arg, "-")) {
                // Handle positional arguments if needed
                continue;
            }
        }
    }

    private function runWizard(): void
    {
        $this->info("=== Role Creation Wizard ===");
        $this->line("");

        // Get role name
        if (!$this->name) {
            $this->name = $this->templateGenerator->askQuestion(
                "Enter role name:",
                "",
            );
        }
        if (empty($this->name)) {
            $this->error("Role name is required");
            return;
        }

        // Validate role name format
        if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $this->name)) {
            $this->warning(
                "Role name should be in UPPER_CASE format for enum compatibility (e.g., ADMIN, USER_MANAGER)",
            );
        }

        // Check if role already exists
        $existingRole = $this->roleRepository->findByName($this->name);
        if ($existingRole) {
            $this->error("Role '{$this->name}' already exists");
            return;
        }

        // Get description
        if (!$this->description) {
            $this->description = $this->templateGenerator->askQuestion(
                "Enter role description (optional):",
                "",
            );
        }

        // Handle permissions
        $this->handlePermissionSelection();

        $this->line("");
        $this->info("=== Role Summary ===");
        $this->line("Name: {$this->name}");
        if ($this->description) {
            $this->line("Description: {$this->description}");
        }
        if ($this->permissions) {
            $this->line("Permissions: {$this->permissions}");
        }
        if ($this->createPermissions) {
            $this->line("Auto-create permissions: Yes");
        }
        $this->line("");

        $proceed = $this->askYesNo("Create this role?", "yes");
        if (!$proceed) {
            $this->info("Role creation cancelled");
            return;
        }

        $this->createRole();
    }

    private function handlePermissionSelection(): void
    {
        $choice = $this->templateGenerator->selectFromList(
            "How would you like to handle permissions?",
            [
                "Skip permission assignment",
                "Select from existing permissions",
                "Enter permission names manually",
            ],
            "Select from existing permissions",
        );

        if ($choice === null) {
            return;
        }

        match ($choice) {
            "Skip permission assignment" => null,
            "Select from existing permissions"
                => $this->selectPermissionsFromList(),
            "Enter permission names manually"
                => $this->enterPermissionsManually(),
        };
    }

    private function selectPermissionsFromList(): void
    {
        $allPermissions = $this->permissionRepository->getAll();
        $permissionNames = array_map(fn($perm) => $perm->name, $allPermissions);

        if (empty($permissionNames)) {
            $this->warning(
                "No permissions found in the system. You may need to create permissions first.",
            );
            return;
        }

        $selectedPermissions = $this->templateGenerator->selectMultipleFromList(
            "Select permissions (use arrow keys and space to select, enter to finish):",
            $permissionNames,
        );

        if ($selectedPermissions && !empty($selectedPermissions)) {
            $this->permissions = implode(",", $selectedPermissions);
        }

        // Ask if non-existing permissions should be created
        $this->createPermissions = $this->askYesNo(
            "Create permissions that dont exist?",
            "no",
        );
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

        // Ask if non-existing permissions should be created
        $this->createPermissions = $this->askYesNo(
            "Create permissions that dont exist?",
            "yes",
        );
    }

    private function createRole(): int
    {
        try {
            // Validate input
            if (empty($this->name)) {
                $this->error("Role name is required");
                return 1;
            }

            // Check for existing role
            if ($this->roleRepository->findByName($this->name)) {
                $this->error("Role '{$this->name}' already exists");
                return 1;
            }

            // Create the role
            $role = $this->roleService->createRole(
                $this->name,
                $this->description,
            );
            $this->success(
                "Role '{$this->name}' created successfully with ID: {$role->id}",
            );

            // Handle permissions
            if ($this->permissions) {
                $permissionNames = array_map(
                    "trim",
                    explode(",", $this->permissions),
                );
                $assignedCount = 0;
                $createdCount = 0;

                foreach ($permissionNames as $permissionName) {
                    $permission = $this->permissionRepository->findByName(
                        $permissionName,
                    );

                    if (!$permission && $this->createPermissions) {
                        // Create the permission
                        $permission = $this->permissionRepository->createPermission(
                            $permissionName,
                            "Auto-created for role {$this->name}",
                        );
                        $createdCount++;
                        $this->info("Created permission: {$permissionName}");
                    }

                    if ($permission) {
                        $this->roleService->addPermissionToRole(
                            $role,
                            $permission,
                        );
                        $assignedCount++;
                    } else {
                        $this->warning(
                            "Permission '{$permissionName}' not found and auto-creation disabled",
                        );
                    }
                }

                $this->success(
                    "Assigned {$assignedCount} permission(s) to role",
                );
                if ($createdCount > 0) {
                    $this->info("Created {$createdCount} new permission(s)");
                }
            }

            // Generate enums
            try {
                $this->enumGenerator->generateRoleEnum();
                $this->enumGenerator->generatePermissionEnum();
                $this->success("Role and Permission enums updated");
            } catch (Throwable $e) {
                $this->warning("Failed to update enums: " . $e->getMessage());
            }

            return 0;
        } catch (Throwable $e) {
            $this->error("Failed to create role: " . $e->getMessage());
            $this->logger->debug("Role creation failed", [
                "name" => $this->name,
                "description" => $this->description,
                "permissions" => $this->permissions,
                "error" => $e->getMessage(),
            ]);
            return 1;
        }
    }
}
