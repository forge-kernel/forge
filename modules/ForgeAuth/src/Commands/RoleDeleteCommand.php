<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Commands;

use Modules\ForgeAuth\Contracts\UserProviderInterface;
use Modules\ForgeAuth\Repositories\RoleRepository;
use Modules\ForgeAuth\Services\RoleService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Services\TemplateGenerator;
use Modules\ForgeLogger\Services\ForgeLoggerService;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Throwable;

#[
    Cli(
        command: "auth:role:delete",
        description: "Delete a role and remove it from all users with batch processing",
        usage: "auth:role:delete [--role=ROLE_NAME] [--force]",
        examples: [
            "auth:role:delete",
            "auth:role:delete --role=EDITOR",
            "auth:role:delete --role=TEMP_ROLE --force",
        ],
    ),
]
final class RoleDeleteCommand extends Command
{
    use Wizard;

    #[Arg(name: "role", description: "Role name to delete", required: false)]
    private ?string $role = null;

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
        private readonly UserProviderInterface $userProvider,
        private readonly RoleService $roleService,
        private readonly TemplateGenerator $templateGenerator,
        private readonly ForgeLoggerService $logger,
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    public function execute(array $args): int
    {
        $this->wizard($args);

        if (empty($args) && !$this->role) {
            $this->runWizard();
            return 0;
        }

        return $this->deleteRole();
    }

    private function runWizard(): void
    {
        $this->info("=== Delete Role ===");
        $this->line("");

        $allRoles = $this->roleRepository->getAllRoles();
        $roleNames = array_map(fn($role) => $role->name, $allRoles);

        if (empty($roleNames)) {
            $this->error("No roles found in the system");
            return;
        }

        if (!$this->role) {
            $this->role = $this->templateGenerator->selectFromList(
                "Select role to delete:",
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

        $this->showDestructiveWarning($selectedRole);
    }

    private function showDestructiveWarning($role): void
    {
        $this->line("");
        $this->info("=== DANGER ZONE ===");
        $this->line("");

        $userRoleCount = $this->queryBuilder
            ->table("user_roles")
            ->where("role_id", "=", $role->id)
            ->count();

        $messages = [];
        $messages[] = "Role to delete: {$role->name}";
        if ($role->description) {
            $messages[] = "Description: {$role->description}";
        }
        $messages[] = "This will PERMANENTLY delete the role '{$role->name}'.";
        $messages[] = "The role will be REMOVED from {$userRoleCount} user(s) that currently have it.";
        $messages[] = "This action CANNOT be undone.";

        foreach ($messages as $message) {
            $this->warning($message);
        }

        $this->line("");
        $this->error("There is NO UNDO. Are you absolutely sure?");
        $this->line("");

        if ($this->force) {
            $this->info("--force flag detected, proceeding with deletion");
            return;
        }

        $proceed = $this->askYesNo("Type YES in UPPER-CASE to proceed", "YES");
        if (!$proceed) {
            $this->info("Role deletion cancelled");
            return;
        }

        $this->deleteRole();
    }

    private function deleteRole(): int
    {
        try {
            if (empty($this->role)) {
                $this->error("--role parameter is required");
                return 1;
            }

            $role = $this->roleRepository->findByName($this->role);
            if (!$role) {
                $this->error("Role '{$this->role}' not found");
                return 1;
            }

            $this->info("Processing role deletion for '{$this->role}'...");

            $userRoleCount = $this->queryBuilder
                ->table("user_roles")
                ->where("role_id", "=", $role->id)
                ->count();

            if ($userRoleCount > 0) {
                $this->info(
                    "Found {$userRoleCount} user(s) with this role. Removing role assignments...",
                );

                $this->removeRoleFromUsers($role);
            }

            $this->roleService->deleteRole($role);

            $this->success("Role '{$this->role}' deleted successfully");
            if ($userRoleCount > 0) {
                $this->info("Removed role from {$userRoleCount} user(s)");
            }

            return 0;
        } catch (Throwable $e) {
            $this->error("Failed to delete role: " . $e->getMessage());
            $this->logger->debug("Role deletion failed", [
                "role" => $this->role,
                "error" => $e->getMessage(),
            ]);
            return 1;
        }
    }

    private function removeRoleFromUsers($role): void
    {
        $batchSize = 100;
        $offset = 0;
        $totalRemoved = 0;

        do {
            $userRoleRows = $this->queryBuilder
                ->table("user_roles")
                ->where("role_id", "=", $role->id)
                ->limit($batchSize)
                ->offset($offset)
                ->get();

            if (empty($userRoleRows)) {
                break;
            }

            $userIds = array_column($userRoleRows, "user_id");

            foreach ($userIds as $userId) {
                $user = $this->userProvider->findById($userId);
                if ($user) {
                    $this->roleService->removeRoleFromUser($role, $user);
                    $totalRemoved++;
                }
            }

            $offset += $batchSize;
            $this->info("Processed {$totalRemoved} user role assignments...");
        } while (count($userRoleRows) === $batchSize);

        $this->success(
            "Successfully removed role from {$totalRemoved} user(s)",
        );
    }
}
