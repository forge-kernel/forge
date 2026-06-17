<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Services;

use App\Modules\ForgeAuth\Repositories\RoleRepository;
use App\Modules\ForgeAuth\Repositories\PermissionRepository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Services\TemplateGenerator;

#[Service]
final class EnumGeneratorService
{
    public const string ROLE_ENUM_PATH =
        BASE_PATH . "/modules/ForgeAuth/src/Enums/Role.php";
    public const string PERMISSION_ENUM_PATH =
        BASE_PATH . "/modules/ForgeAuth/src/Enums/Permission.php";

    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly TemplateGenerator $templateGenerator,
    ) {}

    public function generateRoleEnum(): void
    {
        $roles = $this->roleRepository->getAllRoles();
        $roleCases = [];

        foreach ($roles as $role) {
            $caseName = $this->toEnumCase($role->name);
            $roleCases[] = "    case {$caseName} = '{$role->name}';";
        }

        if (empty($roleCases)) {
            return;
        }

        $enumContent = $this->generateEnumContent("Role", $roleCases);

        if (file_exists(self::ROLE_ENUM_PATH)) {
            $existingContent = file_get_contents(self::ROLE_ENUM_PATH);
            if ($this->hasUserModifications($existingContent, $enumContent)) {
                $choice = $this->templateGenerator->selectFromList(
                    "Role enum has been modified. What would you like to do?",
                    [
                        "Replace with generated enum",
                        "Keep current enum",
                        "Show differences",
                    ],
                    "Keep current enum",
                );

                switch ($choice) {
                    case "Replace with generated enum":
                        break;
                    case "Keep current enum":
                        return;
                    case "Show differences":
                        $this->showDifferences($existingContent, $enumContent);
                        $newChoice = $this->templateGenerator->selectFromList(
                            "What would you like to do now?",
                            [
                                "Replace with generated enum",
                                "Keep current enum",
                            ],
                            "Keep current enum",
                        );
                        if ($newChoice === "Keep current enum") {
                            return;
                        }
                        break;
                    default:
                        return;
                }
            }
        }

        $this->writeEnumFile(self::ROLE_ENUM_PATH, $enumContent, "Role");
    }

    public function generatePermissionEnum(): void
    {
        $permissions = $this->permissionRepository->getAll();
        $permissionCases = [];

        foreach ($permissions as $permission) {
            $caseName = $this->toEnumCase($permission->name);
            $permissionCases[] = "    case {$caseName} = '{$permission->name}';";
        }

        if (empty($permissionCases)) {
            return;
        }

        $enumContent = $this->generateEnumContent(
            "Permission",
            $permissionCases,
        );

        if (file_exists(self::PERMISSION_ENUM_PATH)) {
            $existingContent = file_get_contents(self::PERMISSION_ENUM_PATH);
            if ($this->hasUserModifications($existingContent, $enumContent)) {
                $choice = $this->templateGenerator->selectFromList(
                    "Permission enum has been modified. What would you like to do?",
                    [
                        "Replace with generated enum",
                        "Keep current enum",
                        "Show differences",
                    ],
                    "Keep current enum",
                );

                switch ($choice) {
                    case "Replace with generated enum":
                        break;
                    case "Keep current enum":
                        return;
                    case "Show differences":
                        $this->showDifferences($existingContent, $enumContent);
                        $newChoice = $this->templateGenerator->selectFromList(
                            "What would you like to do now?",
                            [
                                "Replace with generated enum",
                                "Keep current enum",
                            ],
                            "Keep current enum",
                        );
                        if ($newChoice === "Keep current enum") {
                            return;
                        }
                        break;
                    default:
                        return;
                }
            }
        }

        $this->writeEnumFile(
            self::PERMISSION_ENUM_PATH,
            $enumContent,
            "Permission",
        );
    }

    private function generateEnumContent(string $enumName, array $cases): string
    {
        $casesString = implode("\n", $cases);

        return "<?php\n\n" .
            "declare(strict_types=1);\n\n" .
            "namespace App\\Modules\\ForgeAuth\\Enums;\n\n" .
            "enum {$enumName} : string\n" .
            "{\n" .
            $casesString .
            "\n" .
            "}\n";
    }

    private function toEnumCase(string $name): string
    {
        // Convert format like "users.read" to "USERS_READ"
        $caseName = strtoupper(str_replace(".", "_", $name));

        $caseName = preg_replace("/[^A-Z0-9_]/", "_", $caseName);
        $caseName = preg_replace("/_+/", "_", $caseName);
        $caseName = trim($caseName, "_");

        if (preg_match("/^[0-9]/", $caseName)) {
            $caseName = "_" . $caseName;
        }

        return $caseName;
    }

    private function hasUserModifications(
        string $existingContent,
        string $generatedContent,
    ): bool {
        $existingCases = $this->extractEnumCases($existingContent);
        $generatedCases = $this->extractEnumCases($generatedContent);

        return $existingCases !== $generatedCases;
    }

    private function extractEnumCases(string $content): array
    {
        preg_match_all(
            '/case\s+([A-Z_0-9]+)\s*=\s*[\'"]([^\'"]+)[\'"];/',
            $content,
            $matches,
        );

        if (empty($matches[2])) {
            return [];
        }

        return array_combine($matches[1], $matches[2]);
    }

    private function showDifferences(string $existing, string $generated): void
    {
        $existingCases = $this->extractEnumCases($existing);
        $generatedCases = $this->extractEnumCases($generated);

        echo "\n=== Differences ===\n";

        $removed = array_diff($existingCases, $generatedCases);
        $added = array_diff($generatedCases, $existingCases);

        if (!empty($removed)) {
            echo "Cases that would be removed:\n";
            foreach ($removed as $name => $value) {
                echo "  - {$name} = '{$value}'\n";
            }
        }

        if (!empty($added)) {
            echo "Cases that would be added:\n";
            foreach ($added as $name => $value) {
                echo "  + {$name} = '{$value}'\n";
            }
        }

        if (empty($removed) && empty($added)) {
            echo "No differences found in enum cases.\n";
        }

        echo "\n";
    }

    private function writeEnumFile(
        string $path,
        string $content,
        string $enumName,
    ): void {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_put_contents($path, $content) === false) {
            throw new \RuntimeException(
                "Failed to write {$enumName} enum to {$path}",
            );
        }
    }

    public function generateAllEnums(): void
    {
        $this->generateRoleEnum();
        $this->generatePermissionEnum();
    }
}
