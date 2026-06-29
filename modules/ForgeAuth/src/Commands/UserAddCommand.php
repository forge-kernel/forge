<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Commands;

use Modules\AppAuth\Dto\UserMetadataDto;
use Modules\ForgeAuth\Contracts\AuthUserInterface;
use Modules\ForgeAuth\Contracts\UserProviderInterface;
use Modules\ForgeAuth\Repositories\RoleRepository;
use Modules\ForgeAuth\Services\RoleService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Services\TemplateGenerator;
use Modules\ForgeLogger\Services\ForgeLoggerService;
use Forge\Core\DI\Attributes\Service;
use Throwable;

#[
    Cli(
        command: "auth:user:add",
        description: "Add a new user to the database and assign roles/permissions",
        usage: "auth:user:add [--identifier=ID] [--email=EMAIL] [--password=PASSWORD] [--roles=ROLE1,ROLE2] [--permissions=PERM1,PERM2]",
        examples: [
            "auth:user:add",
            "auth:user:add --identifier=john --email=john@example.com --password=secret",
            "auth:user:add --identifier=admin --email=admin@example.com --roles=ADMIN --permissions=users.read,users.write",
            "auth:user:add --identifier=user1 --email=user1@example.com --roles=USER,EDITOR",
        ],
    ),
]
final class UserAddCommand extends Command
{
    use Wizard;

    #[
        Arg(
            name: "identifier",
            description: "User identifier/username",
            required: false,
        ),
    ]
    private ?string $identifier = null;

    #[Arg(name: "email", description: "User email address", required: false)]
    private ?string $email = null;

    #[
        Arg(
            name: "password",
            description: "User password (will prompt if not provided)",
            required: false,
        ),
    ]
    private ?string $password = null;

    #[
        Arg(
            name: "roles",
            description: "Comma-separated list of role names",
            required: false,
        ),
    ]
    private ?string $roles = null;

    #[
        Arg(
            name: "permissions",
            description: "Comma-separated list of permission names (legacy support)",
            required: false,
        ),
    ]
    private ?string $permissions = null;

    #[
        Arg(
            name: "status",
            description: "User status: active, inactive, suspended",
            validate: "active|inactive|suspended",
            default: "active",
            required: false,
        ),
    ]
    private string $status = "active";

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
        private readonly UserProviderInterface $userProvider,
        private readonly RoleRepository $roleRepository,
        private readonly RoleService $roleService,
        private readonly TemplateGenerator $templateGenerator,
        private readonly ForgeLoggerService $logger,
    ) {}

    public function execute(array $args): int
    {
        $this->wizard($args);

        // Parse additional args that might come from command line
        $this->parseAdditionalArgs($args);

        if (empty($args) && !$this->identifier && !$this->email) {
            $this->runWizard();
            return 0;
        }

        return $this->createUser();
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
        $this->info("=== User Creation Wizard ===");
        $this->line("");

        // Get user identifier
        if (!$this->identifier) {
            $this->identifier = $this->templateGenerator->askQuestion(
                "Enter user identifier/username:",
                "",
            );
        }
        if (empty($this->identifier)) {
            $this->error("User identifier is required");
            return;
        }

        // Check if user already exists
        $existingUser = $this->userProvider->findByIdentifier(
            $this->identifier,
        );
        if ($existingUser) {
            $this->error(
                "User with identifier '{$this->identifier}' already exists",
            );
            return;
        }

        // Get email
        if (!$this->email) {
            $this->email = $this->templateGenerator->askQuestion(
                "Enter user email:",
                "",
            );
        }
        if (
            empty($this->email) ||
            !filter_var($this->email, FILTER_VALIDATE_EMAIL)
        ) {
            $this->error("Valid email address is required");
            return;
        }

        // Check if email already exists
        $existingEmail = $this->userProvider->findByEmail($this->email);
        if ($existingEmail) {
            $this->error("User with email '{$this->email}' already exists");
            return;
        }

        // Get password
        if (!$this->password) {
            $this->password = $this->templateGenerator->askQuestion(
                "Enter user password:",
                "",
                true,
            );
        }
        if (empty($this->password)) {
            $this->error("Password is required");
            return;
        }

        // Get status
        $statusOptions = ["active", "inactive", "suspended"];
        $this->status = $this->templateGenerator->selectFromList(
            "Select user status:",
            $statusOptions,
            "active",
        );
        if ($this->status === null) {
            $this->info("User creation cancelled");
            return;
        }

        // Get roles
        $this->handleRoleSelection();

        $this->line("");
        $this->info("=== User Summary ===");
        $this->line("Identifier: {$this->identifier}");
        $this->line("Email: {$this->email}");
        $this->line("Status: {$this->status}");
        if ($this->roles) {
            $this->line("Roles: {$this->roles}");
        }
        if ($this->permissions) {
            $this->line("Legacy Permissions: {$this->permissions}");
        }
        $this->line("");

        $proceed = $this->askYesNo("Create this user?", "yes");
        if (!$proceed) {
            $this->info("User creation cancelled");
            return;
        }

        $this->createUser();
    }

    private function handleRoleSelection(): void
    {
        $allRoles = $this->roleRepository->getAllRoles();
        $roleNames = array_map(fn($role) => $role->name, $allRoles);

        if (empty($roleNames)) {
            $this->warning(
                "No roles found in the system. You may create roles using role:create command.",
            );
            return;
        }

        $choice = $this->templateGenerator->selectFromList(
            "How would you like to assign roles?",
            [
                "Skip role assignment",
                "Select from existing roles",
                "Enter role names manually",
            ],
            "Select from existing roles",
        );

        if ($choice === null) {
            return;
        }

        match ($choice) {
            "Skip role assignment" => null,
            "Select from existing roles" => $this->selectRolesFromList(
                $roleNames,
            ),
            "Enter role names manually" => $this->enterRolesManually(),
        };
    }

    private function selectRolesFromList(array $roleNames): void
    {
        $selectedRoles = $this->templateGenerator->selectMultipleFromList(
            "Select roles (use arrow keys and space to select, enter to finish):",
            $roleNames,
        );

        if ($selectedRoles && !empty($selectedRoles)) {
            $this->roles = implode(",", $selectedRoles);
        }
    }

    private function enterRolesManually(): void
    {
        $rolesInput = $this->templateGenerator->askQuestion(
            "Enter role names (comma-separated):",
            "",
        );

        if (!empty($rolesInput)) {
            $this->roles = $rolesInput;
        }
    }

    private function createUser(): int
    {
        try {
            // Validate input
            if (
                empty($this->identifier) ||
                empty($this->email) ||
                empty($this->password)
            ) {
                $this->error(
                    "Missing required parameters: identifier, email, or password",
                );
                return 1;
            }

            // Check for existing user
            if ($this->userProvider->findByIdentifier($this->identifier)) {
                $this->error(
                    "User with identifier '{$this->identifier}' already exists",
                );
                return 1;
            }

            if ($this->userProvider->findByEmail($this->email)) {
                $this->error("User with email '{$this->email}' already exists");
                return 1;
            }

            // Hash password
            $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);

            // Parse roles
            $roleIds = [];
            if ($this->roles) {
                $roleNames = array_map("trim", explode(",", $this->roles));
                foreach ($roleNames as $roleName) {
                    $role = $this->roleRepository->findByName($roleName);
                    if ($role) {
                        $roleIds[] = $role->id;
                    } else {
                        $this->warning(
                            "Role '{$roleName}' not found, skipping",
                        );
                    }
                }
            }

            // Create user with roles
            $user = $this->userProvider->createUser([
                'identifier' => $this->identifier,
                'email' => $this->email,
                'password' => $hashedPassword,
                'status' => $this->status,
                'metadata' => $this->permissions
                    ? new UserMetadataDto()
                    : null,
            ]);

            foreach ($roleIds as $roleId) {
                $role = $this->roleRepository->findById($roleId);
                if ($role && $user instanceof AuthUserInterface) {
                    $this->roleService->assignRoleToUser($role, $user);
                }
            }

            $this->success(
                "User '{$this->identifier}' created successfully with ID: {$user->getId()}",
            );

            if (!empty($roleIds)) {
                $this->info("Assigned " . count($roleIds) . " role(s) to user");
            }

            if ($this->permissions) {
                $this->warning(
                    "Legacy permissions assigned to metadata. Consider using roles instead.",
                );
            }

            return 0;
        } catch (Throwable $e) {
            $this->error("Failed to create user: " . $e->getMessage());
            $this->logger->debug("User creation failed", [
                "identifier" => $this->identifier,
                "email" => $this->email,
                "error" => $e->getMessage(),
            ]);
            return 1;
        }
    }
}
