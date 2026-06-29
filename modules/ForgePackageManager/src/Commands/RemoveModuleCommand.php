<?php
declare(strict_types=1);

namespace Modules\ForgePackageManager\Commands;

use Modules\ForgePackageManager\Services\PackageManagerService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Services\TemplateGenerator;
use Forge\Traits\StringHelper;

use Forge\CLI\Attributes\CoreCommand;
use Throwable;

#[CoreCommand]
#[
    Cli(
        command: "package:remove-module",
        description: "Remove an installed module",
        usage: "package:remove-module [--module=<module-name>] [module-name ...] [--force]",
        examples: [
            "package:remove-module --module=my-module",
            "package:remove-module --module=my-module --force",
            "package:remove-module module-one module-two",
            "package:remove-module",
        ],
    ),
]
final class RemoveModuleCommand extends Command
{
    use Wizard;
    use StringHelper;

    #[
        Arg(
            name: "module",
            description: "Name of the module to remove",
            required: false,
        ),
    ]
    private ?string $moduleName = null;

    #[
        Arg(
            name: "force",
            description: "Skip the destructive-action confirmation",
            default: false,
            required: false,
        ),
    ]
    private bool $force = false;

    #[
        Arg(
            name: "debug",
            description: "Show debug information",
            default: false,
            required: false,
        ),
    ]
    private bool $debug = false;

    public function __construct(
        private readonly PackageManagerService $packageManager,
        private readonly TemplateGenerator $templateGenerator,
    ) {}

    public function execute(array $args): int
    {
        $this->wizard($args);

        $this->packageManager->setDebugMode($this->debug);

        $moduleNames = $this->parseModuleNames($args);

        if (empty($moduleNames)) {
            $this->runWizard();
            return 0;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($moduleNames as $moduleName) {
            if (
                !$this->force &&
                !$this->confirmDestructiveActionForModule($moduleName)
            ) {
                $this->warning("Module removal aborted for '{$moduleName}'.");
                $errorCount++;
                continue;
            }

            try {
                $this->packageManager->removeModule($moduleName);
                $this->success("Module '{$moduleName}' removed successfully.");
                $successCount++;
            } catch (Throwable $e) {
                $this->error(
                    "Error removing module '{$moduleName}': " .
                        $e->getMessage(),
                );
                $errorCount++;
            }
        }

        if ($errorCount > 0) {
            return 1;
        }

        return 0;
    }

    private function parseModuleNames(array $args): array
    {
        $moduleNames = [];

        if ($this->moduleName !== null) {
            $moduleNames[] = $this->moduleName;
        }

        foreach ($args as $arg) {
            if (!str_starts_with($arg, "--") && !str_starts_with($arg, "-")) {
                $moduleNames[] = $arg;
            }
        }

        return array_unique($moduleNames);
    }

    private function runWizard(): void
    {
        $installedModules = $this->getInstalledModules();

        if (empty($installedModules)) {
            $this->error("No modules are currently installed.");
            return;
        }

        $choice = $this->templateGenerator->selectFromList(
            "How would you like to proceed?",
            ["Select a single module", "Select multiple modules"],
            "Select a single module",
        );

        if ($choice === null) {
            $this->info("Removal cancelled.");
            return;
        }

        if ($choice === "Select multiple modules") {
            $this->handleMultiSelectRemoval($installedModules);
        } else {
            $this->handleSingleSelectRemoval($installedModules);
        }
    }

    private function handleSingleSelectRemoval(array $installedModules): void
    {
        $moduleOptions = [];
        foreach ($installedModules as $moduleName) {
            $description = $this->getModuleDescription($moduleName);
            $displayName = $description
                ? "{$moduleName} ({$description})"
                : $moduleName;
            $moduleOptions[] = $displayName;
        }

        $selectedModuleDisplay = $this->templateGenerator->selectFromList(
            "Select a module to remove",
            $moduleOptions,
            $moduleOptions[0] ?? null,
        );

        if ($selectedModuleDisplay === null) {
            $this->info("Removal cancelled.");
            return;
        }

        $selectedIndex = array_search(
            $selectedModuleDisplay,
            $moduleOptions,
            true,
        );
        $moduleName = $installedModules[$selectedIndex];

        if (
            !$this->force &&
            !$this->confirmDestructiveActionForModule($moduleName)
        ) {
            $this->warning("Module removal aborted.");
            return;
        }

        try {
            $this->packageManager->removeModule($moduleName);
            $this->success("Module '{$moduleName}' removed successfully.");
        } catch (Throwable $e) {
            $this->error("Error removing module: " . $e->getMessage());
        }
    }

    private function handleMultiSelectRemoval(array $installedModules): void
    {
        $moduleOptions = [];
        foreach ($installedModules as $moduleName) {
            $description = $this->getModuleDescription($moduleName);
            $displayName = $description
                ? "{$moduleName} ({$description})"
                : $moduleName;
            $moduleOptions[] = $displayName;
        }

        $selectedModulesDisplay = $this->templateGenerator->selectMultipleFromList(
            "Select modules to remove",
            $moduleOptions,
        );

        if (
            $selectedModulesDisplay === null ||
            empty($selectedModulesDisplay)
        ) {
            $this->info("Removal cancelled.");
            return;
        }

        $moduleNamesToRemove = [];
        foreach ($selectedModulesDisplay as $selectedDisplay) {
            $selectedIndex = array_search(
                $selectedDisplay,
                $moduleOptions,
                true,
            );
            if ($selectedIndex !== false) {
                $moduleNamesToRemove[] = $installedModules[$selectedIndex];
            }
        }

        if (empty($moduleNamesToRemove)) {
            $this->info("No modules selected.");
            return;
        }

        $this->line("");
        $this->info(
            "Removing " . count($moduleNamesToRemove) . " module(s)...",
        );
        $this->line("");

        $successCount = 0;
        $errorCount = 0;

        foreach ($moduleNamesToRemove as $moduleName) {
            if (
                !$this->force &&
                !$this->confirmDestructiveActionForModule($moduleName)
            ) {
                $this->warning("Skipping '{$moduleName}'.");
                $errorCount++;
                continue;
            }

            try {
                $this->packageManager->removeModule($moduleName);
                $this->success("Module '{$moduleName}' removed successfully.");
                $successCount++;
            } catch (Throwable $e) {
                $this->error(
                    "Error removing module '{$moduleName}': " .
                        $e->getMessage(),
                );
                $errorCount++;
            }
        }

        $this->line("");
        if ($successCount > 0) {
            $this->info("Successfully removed {$successCount} module(s).");
        }
        if ($errorCount > 0) {
            $this->warning("Failed to remove {$errorCount} module(s).");
        }
    }

    private function getInstalledModules(): array
    {
        $forgeJsonPath = BASE_PATH . "/forge.json";
        if (!file_exists($forgeJsonPath)) {
            return [];
        }

        $content = file_get_contents($forgeJsonPath);
        $config = json_decode($content, true);

        if (
            !is_array($config) ||
            !isset($config["modules"]) ||
            !is_array($config["modules"])
        ) {
            return [];
        }

        return array_keys($config["modules"]);
    }

    private function getModuleDescription(string $moduleName): ?string
    {
        $moduleFolderName = self::toPascalCase($moduleName);
        $moduleForgeJsonPath =
            BASE_PATH . "/modules/{$moduleFolderName}/forge.json";

        if (!file_exists($moduleForgeJsonPath)) {
            return null;
        }

        $content = file_get_contents($moduleForgeJsonPath);
        $moduleConfig = json_decode($content, true);

        if (!is_array($moduleConfig) || !isset($moduleConfig["description"])) {
            return null;
        }

        return $moduleConfig["description"];
    }

    private function confirmDestructiveActionForModule(string $moduleName): bool
    {
        $hasMigrations = $this->packageManager->moduleHasMigrations(
            $moduleName,
        );
        $hasSeeders = $this->packageManager->moduleHasSeeders($moduleName);
        $hasAssets = $this->packageManager->moduleHasAssets($moduleName);

        $messages = [];
        $messages[] = "Module: {$moduleName}";
        $messages[] =
            "Removing this module may BREAK functionality if your app uses it.";

        if ($hasMigrations) {
            $messages[] =
                "This will ROLLBACK all migrations provided by the module.";
        }
        if ($hasSeeders) {
            $messages[] = "All seeded data will be LOST.";
        }
        if ($hasAssets) {
            $messages[] = "Published assets will be UNLINKED.";
        }

        $this->showDangerBox(
            "DANGER ZONE",
            $messages,
            "There is NO UNDO. Are you absolutely sure?",
        );

        return $this->askYesNo("Type yes in UPPER-CASE to proceed", "YES");
    }
}
