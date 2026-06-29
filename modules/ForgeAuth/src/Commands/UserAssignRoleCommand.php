<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Commands;

use Modules\ForgeAuth\Contracts\AuthUserInterface;
use Modules\ForgeAuth\Contracts\UserProviderInterface;
use Modules\ForgeAuth\Repositories\RoleRepository;
use Modules\ForgeAuth\Services\RoleService;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Services\TemplateGenerator;

#[
    Cli(
        command: "auth:user:assign-role",
        description: "Assign a role to a user",
        usage: "auth:user:assign-role [--user=ID|EMAIL|IDENTIFIER] [--role=ROLE_NAME]",
        examples: [
            "auth:user:assign-role",
            "auth:user:assign-role --user=admin@example.com --role=ADMIN",
            "auth:user:assign-role --user=1 --role=EDITOR",
        ],
    ),
]
final class UserAssignRoleCommand extends Command
{
    use Wizard;

    #[
        Arg(
            name: "user",
            description: "User ID, Email, or Identifier",
            required: false,
        ),
    ]
    private ?string $user = null;

    #[Arg(name: "role", description: "Role name", required: false)]
    private ?string $role = null;

    public function __construct(
        private readonly UserProviderInterface $userProvider,
        private readonly RoleRepository $roleRepository,
        private readonly RoleService $roleService,
        private readonly TemplateGenerator $templateGenerator,
    ) {}

    public function execute(array $args): int
    {
        $this->wizard($args);

        if (empty($args) && (!$this->user || !$this->role)) {
            $this->runWizard();
            return 0;
        }

        return $this->assignRole();
    }

    private function runWizard(): void
    {
        $this->info("=== Assign Role to User ===");
        $this->line("");

        if (!$this->role) {
            $allRoles = $this->roleRepository->getAllRoles();
            $roleNames = array_map(fn($role) => $role->name, $allRoles);

            if (empty($roleNames)) {
                $this->error("No roles found in system");
                return;
            }

            $this->role = $this->templateGenerator->selectFromList(
                "Select role to assign:",
                $roleNames,
                $roleNames[0] ?? null,
            );

            if ($this->role === null) {
                $this->info("Operation cancelled");
                return;
            }
        }

        if (!$this->user) {
            $choice = $this->templateGenerator->selectFromList(
                "How would you like to select the user?",
                ["Select from list", "Enter manually"],
                "Select from list",
            );

            if ($choice === "Select from list") {
                $this->handleUserListSelection();
            } else {
                $this->handleManualUserEntry();
            }

            if (!$this->user) {
                $this->info("Operation cancelled");
                return;
            }
        }

        $userModel = $this->findUser($this->user);
        if (!$userModel) {
            $this->error("User '{$this->user}' not found");
            return;
        }
        $this->info(
            "Selected User: {$userModel->getIdentifier()} ({$userModel->getEmail()})",
        );

        $this->assignRole();
    }

    private function handleUserListSelection(): void
    {
        $page = 1;
        $perPage = 10;

        while (true) {
            $paginator = $this->userProvider->paginate($page, $perPage);
            $users = $paginator->items();
            $total = $paginator->total();
            $lastPage = $paginator->lastPage();

            if (empty($users)) {
                $this->warning("No users found.");
                return;
            }

            $this->line("");
            $this->info("Users (Page {$page}/{$lastPage})");

            $options = [];
            $displayMap = [];
            foreach ($users as $user) {
                $id = is_array($user) ? $user["id"] : $user->getId();
                $identifier = is_array($user) ? $user["identifier"] : $user->getIdentifier();
                $email = is_array($user) ? $user["email"] : $user->getEmail();

                $display = "{$identifier} ({$email})";
                $options[] = $display;
                $displayMap[$display] = $id;
            }

            if ($page < $lastPage) {
                $options[] = "--> Next Page";
            }
            if ($page > 1) {
                $options[] = "<-- Previous Page";
            }
            $options[] = "[ Cancel Operation ]";

            $selection = $this->templateGenerator->selectFromList(
                "Select a user:",
                $options
            );

            if ($selection === "--> Next Page") {
                $page++;
                continue;
            }
            if ($selection === "<-- Previous Page") {
                $page--;
                continue;
            }
            if ($selection === "[ Cancel Operation ]" || $selection === null) {
                return;
            }

            $this->user = (string) $displayMap[$selection];
            return;
        }
    }

    private function handleManualUserEntry(): void
    {
        $this->user = $this->templateGenerator->askQuestion(
            "Enter User (ID, Email, or Identifier):",
            "",
        );
    }

    private function assignRole(): int
    {
        $userModel = $this->findUser($this->user);
        if (!$userModel) {
            $this->error("User '{$this->user}' not found");
            return 1;
        }

        $roleModel = $this->roleRepository->findByName($this->role);
        if (!$roleModel) {
            $this->error("Role '{$this->role}' not found");
            return 1;
        }

        if ($this->roleService->userHasRole($userModel, $roleModel->name)) {
            $this->warning(
                "User '{$userModel->getIdentifier()}' already has role '{$roleModel->name}'",
            );
            return 0;
        }

        $this->roleService->assignRoleToUser($roleModel, $userModel);
        $this->info(
            "Role '{$roleModel->name}' assigned to user '{$userModel->getIdentifier()}'",
        );

        return 0;
    }

    private function findUser(string $input): ?AuthUserInterface
    {
        if (is_numeric($input)) {
            $user = $this->userProvider->findById((int) $input);
            if ($user) {
                return $user;
            }
        }

        if (str_contains($input, "@")) {
            $user = $this->userProvider->findByEmail($input);
            if ($user) {
                return $user;
            }
        }

        return $this->userProvider->findByIdentifier($input);
    }
}
