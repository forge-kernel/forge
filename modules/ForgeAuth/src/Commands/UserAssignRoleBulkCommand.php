<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Commands;

use App\Modules\ForgeAuth\Contracts\AuthUserInterface;
use App\Modules\ForgeAuth\Contracts\UserProviderInterface;
use App\Modules\ForgeAuth\Repositories\RoleRepository;
use App\Modules\ForgeAuth\Services\RoleService;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Services\TemplateGenerator;

#[
    Cli(
        command: "auth:user:assign-role-bulk",
        description: "Assign a role to multiple users",
        usage: "auth:user:assign-role-bulk [--role=ROLE_NAME] [--users=ID1,ID2|EMAIL1,EMAIL2]",
        examples: [
            "auth:user:assign-role-bulk",
            "auth:user:assign-role-bulk --role=EDITOR --users=1,2,3",
            "auth:user:assign-role-bulk --role=VIEWER --users=user1@example.com,user2@example.com",
        ],
    ),
]
final class UserAssignRoleBulkCommand extends Command
{
    use Wizard;

    #[Arg(name: "role", description: "Role name", required: false)]
    private ?string $role = null;

    #[
        Arg(
            name: "users",
            description: "Comma-separated list of User IDs, Emails, or Identifiers",
            required: false,
        ),
    ]
    private ?string $users = null;

    public function __construct(
        private readonly UserProviderInterface $userProvider,
        private readonly RoleRepository $roleRepository,
        private readonly RoleService $roleService,
        private readonly TemplateGenerator $templateGenerator,
    ) {}

    public function execute(array $args): int
    {
        $this->wizard($args);

        if (empty($args) && (!$this->role || !$this->users)) {
            $this->runWizard();
            return 0;
        }

        return $this->assignRoleBulk();
    }

    private function runWizard(): void
    {
        $this->info("=== Bulk Assign Role to Users ===");
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

        // Select Users
        if (!$this->users) {
            $choice = $this->templateGenerator->selectFromList(
                "How would you like to select users?",
                ["Select from list", "Enter manually"],
                "Select from list",
            );

            if ($choice === "Select from list") {
                $this->handleUserListSelection();
            } else {
                $this->handleManualUserEntry();
            }
        }

        if (!$this->users) {
            $this->info("No users selected. Operation cancelled.");
            return;
        }

        $this->assignRoleBulk();
    }

    private function handleUserListSelection(): void
    {
        $page = 1;
        $perPage = 10;
        $selectedUserIds = [];

        while (true) {
            $paginator = $this->userProvider->paginate($page, $perPage);
            $users = $paginator->items();
            $total = $paginator->total();
            $lastPage = $paginator->lastPage();

            if (empty($users)) {
                $this->warning("No users found.");
                break;
            }

            $this->line("");
            $this->info(
                "Users (Page {$page}/{$lastPage}) - Total Selected: " .
                    count($selectedUserIds),
            );

            $options = [];
            $displayMap = [];
            $pageUserIds = [];

            foreach ($users as $user) {
                $id = is_array($user) ? $user["id"] : $user->getId();
                $identifier = is_array($user)
                    ? $user["identifier"]
                    : $user->getIdentifier();
                $email = is_array($user) ? $user["email"] : $user->getEmail();

                $display = "{$identifier} ({$email})";
                $options[] = $display;
                $displayMap[$display] = $id;
                $pageUserIds[] = $id;
            }

            $defaultSelected = [];
            foreach ($options as $display) {
                $id = $displayMap[$display];
                if (in_array($id, $selectedUserIds)) {
                    $defaultSelected[] = $display;
                }
            }

            $selectedDisplays = $this->templateGenerator->selectMultipleFromList(
                "Select users on this page (Space to toggle, Enter to confirm selection):",
                $options,
                $defaultSelected,
            );

            if ($selectedDisplays !== null) {
                $selectedUserIds = array_diff($selectedUserIds, $pageUserIds);

                foreach ($selectedDisplays as $display) {
                    if (isset($displayMap[$display])) {
                        $selectedUserIds[] = $displayMap[$display];
                    }
                }
                $selectedUserIds = array_unique($selectedUserIds);
            }

            $navOptions = [];
            if ($page < $lastPage) {
                $navOptions[] = "Next Page";
            }
            if ($page > 1) {
                $navOptions[] = "Previous Page";
            }
            $navOptions[] = "Finish Selection";

            $action = $this->templateGenerator->selectFromList(
                "Navigate:",
                $navOptions,
                "Finish Selection",
            );

            if ($action === "Finish Selection") {
                break;
            }
            if ($action === "Next Page") {
                $page++;
            }
            if ($action === "Previous Page") {
                $page--;
            }
        }

        if (!empty($selectedUserIds)) {
            $this->users = implode(",", $selectedUserIds);
        }
    }

    private function handleManualUserEntry(): void
    {
        $input = $this->templateGenerator->askQuestion(
            "Enter User IDs, Emails, or Identifiers (comma-separated):",
            "",
        );
        $this->users = $input;
    }

    private function assignRoleBulk(): int
    {
        if (!$this->users) {
            $this->error("No users specified");
            return 1;
        }

        $roleModel = $this->roleRepository->findByName($this->role);
        if (!$roleModel) {
            $this->error("Role '{$this->role}' not found");
            return 1;
        }

        $userInputs = explode(",", $this->users);
        $successCount = 0;
        $failCount = 0;

        foreach ($userInputs as $input) {
            $input = trim($input);
            if (empty($input)) {
                continue;
            }

            $userModel = $this->findUser($input);
            if (!$userModel) {
                $this->warning("User '{$input}' not found - Skipping");
                $failCount++;
                continue;
            }

            if ($this->roleService->userHasRole($userModel, $roleModel->name)) {
                $this->line(
                    "User '{$userModel->getIdentifier()}' already has role - Skipping",
                );
                continue;
            }

            $this->roleService->assignRoleToUser($roleModel, $userModel);
            $this->info(
                "Assigned role to: {$userModel->getIdentifier()} ({$userModel->getEmail()})",
            );
            $successCount++;
        }

        $this->line("");
        $this->info(
            "Bulk Assignment Complete. Success: {$successCount}, Failed: {$failCount}",
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
