<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Commands;

use App\Modules\ForgeAuth\Repositories\RoleRepository;
use App\Modules\ForgeAuth\Repositories\PermissionRepository;
use App\Modules\ForgeAuth\Services\RoleService;
use App\Modules\ForgeAuth\Services\EnumGeneratorService;
use App\Modules\ForgeAuth\Services\RolePermissionCacheService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Services\TemplateGenerator;
use App\Modules\ForgeLogger\Services\ForgeLoggerService;
use App\Modules\ForgeDatabaseSQL\DB\Migrator;
use Throwable;

#[
    Cli(
        command: "auth:role:sync",
        description: "Sync database and enum states, run migrations if needed",
        usage: "auth:role:sync [--dry-run] [--direction=database-to-enum|enum-to-database|both]",
        examples: [
            "auth:role:sync",
            "auth:role:sync --direction=database-to-enum",
            "auth:role:sync --direction=enum-to-database",
            "auth:role:sync --direction=both",
            "auth:role:sync --dry-run",
        ],
    ),
]
final class RoleSyncCommand extends Command
{
    use Wizard;

    #[
        Arg(
            name: "dry-run",
            description: "Show what would be synced without making changes",
            default: false,
            required: false,
        ),
    ]
    private bool $dryRun = false;

    #[
        Arg(
            name: "direction",
            description: "Sync direction: database-to-enum, enum-to-database, or both",
            validate: "database-to-enum|enum-to-database|both",
            default: "both",
            required: false,
        ),
    ]
    private string $direction = "both";

    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly RoleService $roleService,
        private readonly EnumGeneratorService $enumGenerator,
        private readonly RolePermissionCacheService $cacheService,
        private readonly TemplateGenerator $templateGenerator,
        private readonly ForgeLoggerService $logger,
        private readonly Migrator $migrator,
    ) {}

    public function execute(array $args): int
    {
        $this->wizard($args);

        $this->info("=== Role & Permission Sync ===");
        $this->line("");

        if ($this->dryRun) {
            $this->warning("DRY RUN MODE - No changes will be made");
        }

        $this->syncMigrations();
        $this->syncCache();

        match ($this->direction) {
            "database-to-enum" => $this->syncDatabaseToEnums(),
            "enum-to-database" => $this->syncEnumToDatabase(),
            "both" => $this->syncBothWays(),
            default => $this->syncBothWays(),
        };

        $this->success("Sync completed successfully");
        return 0;
    }

    private function syncMigrations(): void
    {
        $this->info("Checking for required migrations...");

        if (!$this->dryRun) {
            try {
                $this->migrator->run("all", null, "security");
                $this->success("Security migrations completed");
            } catch (Throwable $e) {
                $this->error("Migration failed: " . $e->getMessage());
                throw $e;
            }
        } else {
            $this->info("Would run security migrations (dry run)");
        }
    }

    private function syncDatabaseToEnums(): void
    {
        $this->info("Syncing database to enums...");

        if (!$this->dryRun) {
            try {
                $this->enumGenerator->generateAllEnums();
                $this->success("Enums updated from database");
            } catch (Throwable $e) {
                $this->error("Enum generation failed: " . $e->getMessage());
                throw $e;
            }
        } else {
            $this->info("Would generate enums from database (dry run)");
        }
    }

    private function syncEnumToDatabase(): void
    {
        $this->info("Syncing enums to database...");

        $enumRoles = $this->getCurrentEnumRoles();
        $enumPermissions = $this->getCurrentEnumPermissions();

        $this->info("Found " . count($enumRoles) . " role(s) in Role enum");
        $this->info(
            "Found " .
                count($enumPermissions) .
                " permission(s) in Permission enum",
        );

        if (!$this->dryRun) {
            $this->info("Adding roles from enum to database...");
            foreach ($enumRoles as $enumRole) {
                try {
                    $existingRole = $this->roleRepository->findByName(
                        $enumRole["name"],
                    );
                    if (!$existingRole) {
                        $this->roleRepository->createRole(
                            $enumRole["name"],
                            $enumRole["description"] ?? null,
                        );
                        $this->info(
                            "Added role from enum to database: {$enumRole["name"]}",
                        );
                    }
                } catch (Throwable $e) {
                    $this->warning(
                        "Failed to add role '{$enumRole["name"]}': " .
                            $e->getMessage(),
                    );
                }
            }

            $this->info("Adding permissions from enum to database...");
            foreach ($enumPermissions as $enumPermission) {
                try {
                    $existingPermission = $this->permissionRepository->findByName(
                        $enumPermission["name"],
                    );
                    if (!$existingPermission) {
                        $this->permissionRepository->createPermission(
                            $enumPermission["name"],
                            $enumPermission["description"] ?? null,
                        );
                        $this->info(
                            "Added permission from enum to database: {$enumPermission["name"]}",
                        );
                    }
                } catch (Throwable $e) {
                    $this->warning(
                        "Failed to add permission '{$enumPermission["name"]}': " .
                            $e->getMessage(),
                    );
                }
            }

            $this->info("Creating role-permission relationships...");
            $this->createRolePermissionRelationships(
                $enumRoles,
                $enumPermissions,
            );
        } else {
            $this->info(
                "Would add roles/permissions from enum to database (dry run)",
            );
        }
    }

    private function syncBothWays(): void
    {
        $this->syncDatabaseToEnums();
        $this->syncEnumToDatabase();
    }

    private function getCurrentEnumRoles(): array
    {
        $roleEnumPath =
            \App\Modules\ForgeAuth\Services\EnumGeneratorService::ROLE_ENUM_PATH;
        if (!file_exists($roleEnumPath)) {
            return [];
        }

        try {
            $content = file_get_contents($roleEnumPath);
            preg_match_all(
                '/case\s+([A-Z_0-9]+)\s*=\s*[\'"]([^\'"]+)[\'"]/',
                $content,
                $matches,
            );

            $roles = [];
            foreach ($matches[1] as $index => $caseName) {
                $roles[] = [
                    "name" => $matches[2][$index],
                    "caseName" => $caseName,
                ];
            }

            return $roles;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getCurrentEnumPermissions(): array
    {
        $permissionEnumPath =
            \App\Modules\ForgeAuth\Services\EnumGeneratorService::PERMISSION_ENUM_PATH;
        if (!file_exists($permissionEnumPath)) {
            return [];
        }

        try {
            $content = file_get_contents($permissionEnumPath);
            preg_match_all(
                '/case\s+([A-Z_0-9]+)\s*=\s*[\'"]([^\'"]+)[\'"]/',
                $content,
                $matches,
            );

            $permissions = [];
            foreach ($matches[1] as $index => $caseName) {
                $permissions[] = [
                    "name" => $matches[2][$index],
                    "caseName" => $caseName,
                ];
            }

            return $permissions;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function createRolePermissionRelationships(
        array $enumRoles,
        array $enumPermissions,
    ): void {
        foreach ($enumRoles as $enumRole) {
            $role = $this->roleRepository->findByName($enumRole["name"]);
            if (!$role) {
                continue;
            }

            foreach ($enumPermissions as $enumPermission) {
                $permission = $this->permissionRepository->findByName(
                    $enumPermission["name"],
                );
                if (!$permission) {
                    continue;
                }

                try {
                    $this->roleService->addPermissionToRole($role, $permission);
                    $this->info(
                        "Added permission '{$enumPermission["name"]}' to role '{$enumRole["name"]}'",
                    );
                } catch (Throwable $e) {
                    $this->warning(
                        "Failed to add permission '{$enumPermission["name"]}' to role '{$enumRole["name"]}': " .
                            $e->getMessage(),
                    );
                }
            }
        }
    }

    private function syncCache(): void
    {
        $this->info("Warming role/permission cache...");

        if (!$this->dryRun) {
            try {
                $this->cacheService->warmCache();
                $this->success("Cache warmed successfully");
            } catch (Throwable $e) {
                $this->error("Cache warming failed: " . $e->getMessage());
                throw $e;
            }
        } else {
            $this->info("Would warm cache (dry run)");
        }
    }
}
