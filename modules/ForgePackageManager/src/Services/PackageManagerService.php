<?php

declare(strict_types=1);

namespace Modules\ForgePackageManager\Services;

use Modules\ForgePackageManager\Contracts\PackageManagerInterface;
use Modules\ForgePackageManager\Sources\SourceFactory;
use Modules\ForgePackageManager\Sources\SourceInterface;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Config\Config;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Helpers\Version;
use Forge\Core\Structure\StructureResolver;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use Forge\Traits\StringHelper;
use Forge\Traits\NamespaceHelper;
use ReflectionClass;
use ReflectionException;
use ZipArchive;

#[Provides(interface: PackageManagerInterface::class, version: "3.3.18")]
final class PackageManagerService implements PackageManagerInterface
{
    use OutputHelper;
    use StringHelper;
    use NamespaceHelper;

    private const string FRAMEWORK_MODULE_NAME = "forge-kernel/kernel";
    private const string PACKAGE_MANAGER_MODULE_NAME = "forge-package-manager";

    private array $registries = [];
    private int $cacheTtl;
    private string $modulesPath;
    private string $cachePath;
    private string $integrityHash;
    private string $trustedSourcesPath;
    private bool $debugEnabled = false;
    private array $resolvingDependencies = [];

    public function __construct(
        private readonly Config $config,
        private readonly ConfigGeneratorService $configGenerator,
        private readonly ?StructureResolver $structureResolver = null,
    ) {
        $this->registries = $this->config->get("source_list.registry", []);
        $cacheTtlValue = $this->config->get("source_list.cache_ttl", 3600);
        $this->cacheTtl = is_array($cacheTtlValue)
            ? 3600
            : (int) $cacheTtlValue;
        $this->modulesPath = BASE_PATH . "/modules/";
        $this->cachePath = BASE_PATH . "/storage/framework/cache/modules/";
        $this->trustedSourcesPath =
            BASE_PATH . "/storage/framework/trusted_sources.json";

        $this->ensureCacheDirectoryExists();
        $this->ensureModulesDirectoryExists();
        $this->ensureTrustedSourcesFileExists();
    }

    private function ensureCacheDirectoryExists(): void
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    private function ensureModulesDirectoryExists(): void
    {
        if (!is_dir($this->modulesPath)) {
            mkdir($this->modulesPath, 0755, true);
        }
    }

    private function cleanExpiredCache(): void
    {
        if (!is_dir($this->cachePath)) {
            return;
        }

        $files = glob($this->cachePath . "*.cache");
        if ($files === false) {
            return;
        }

        $now = time();
        $cleaned = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $age = $now - filemtime($file);
                if ($age >= $this->cacheTtl) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }

        if ($cleaned > 0) {
            $this->info("Cleaned {$cleaned} expired cache file(s).");
        }
    }

    private function ensureTrustedSourcesFileExists(): void
    {
        $dir = dirname($this->trustedSourcesPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!file_exists($this->trustedSourcesPath)) {
            file_put_contents(
                $this->trustedSourcesPath,
                json_encode(["sources" => []], JSON_PRETTY_PRINT),
            );
        }
    }

    private function readTrustedSources(): array
    {
        if (!file_exists($this->trustedSourcesPath)) {
            return ["sources" => []];
        }
        $content = file_get_contents($this->trustedSourcesPath);
        $data = json_decode($content, true);
        return is_array($data) ? $data : ["sources" => []];
    }

    private function writeTrustedSources(array $data): void
    {
        file_put_contents(
            $this->trustedSourcesPath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
    }

    private function isSourceTrusted(string $registryName): bool
    {
        $data = $this->readTrustedSources();
        return isset($data["sources"][$registryName]["trusted"]) &&
            $data["sources"][$registryName]["trusted"] === true;
    }

    private function trustSource(string $registryName): void
    {
        $data = $this->readTrustedSources();
        $data["sources"][$registryName] = [
            "trusted" => true,
            "trusted_at" => date("c"),
        ];
        $this->writeTrustedSources($data);
    }

    private function promptUserInput(
        string $prompt,
        string $default = "n",
    ): string {
        echo $prompt;
        $input = trim(fgets(STDIN) ?: "");
        return $input ?: $default;
    }

    private function showPostInstallWarning(
        string $moduleName,
        string $registryName,
        int $commandCount,
    ): void {
        $this->line("");
        $this->warning("⚠️  POST-INSTALL SCRIPT WARNING ⚠️");
        $this->line("");
        $this->error(
            "Module '{$moduleName}' from registry '{$registryName}' has {$commandCount} post-install command(s).",
        );
        $this->error(
            "These commands will execute with full system permissions.",
        );
        $this->line("");
    }

    private function confirmPostInstallCommand(
        string $command,
        string $moduleName,
        string $registryName,
        int $commandIndex,
        int $totalCommands,
    ): string {
        $messages = [];
        $messages[] = "Module '{$moduleName}' from registry '{$registryName}' wants to execute:";
        $messages[] = "Command: {$command}";
        $messages[] =
            "This command will run with the same permissions as this process.";
        $messages[] = "Only run commands from trusted sources.";
        $messages[] = "Options:";
        $messages[] = "[Y]es - Run this command";
        $messages[] = "[N]o - Skip this command";
        $messages[] = "[A]ll - Accept all remaining commands";
        $messages[] = "[R]eject All - Reject all remaining commands";

        $this->showWarningBox("SECURITY WARNING", $messages);

        $prompt = "Your choice [N]: ";
        $response = strtolower(trim($this->promptUserInput($prompt, "n")));

        if (in_array($response, ["yes", "y", "1", "true"], true)) {
            return "yes";
        } elseif (in_array($response, ["all", "a"], true)) {
            return "all";
        } elseif (in_array($response, ["reject", "reject-all", "r"], true)) {
            return "reject-all";
        }

        return "no";
    }

    private function promptTrustSource(string $registryName): bool
    {
        $messages = [];
        $messages[] = "Do you want to trust '{$registryName}' for future installations?";
        $messages[] =
            "This will skip confirmation prompts for PostInstall commands from this source.";

        $this->showInfoBox("TRUST SOURCE", $messages);

        $prompt = "[y]es/[n]o [n]: ";
        $response = strtolower(trim($this->promptUserInput($prompt, "n")));
        return in_array($response, ["yes", "y", "1", "true"], true);
    }

    public function getRegistries(): array
    {
        return $this->registries;
    }

    public function setDebugMode(bool $enabled): void
    {
        $this->debugEnabled = $enabled;
    }

    protected function debug(string $message, string $context = ""): void
    {
        if (!$this->debugEnabled) {
            return;
        }
        $prefix = $context ? "[{$context}] " : "";
        echo "\033[35m{$prefix}{$message}\033[0m\n";
    }

    public function installFromLock(): void
    {
        $lockData = $this->readForgeLockJson();

        if (!isset($lockData["modules"]) || !is_array($lockData["modules"])) {
            $this->error("Invalid or empty forge-lock.json module section.");
            return;
        }

        $modulesToInstall = $lockData["modules"];

        // If lock file has no modules, fall back to forge.json
        if (empty($modulesToInstall)) {
            $this->info("No modules in forge-lock.json, checking forge.json...");
            $this->installFromForgeJson();
            return;
        }
        $installErrors = false;
        $allPostInstallCommands = [];

        $this->info("Installing modules from forge-lock.json...");

        foreach ($modulesToInstall as $moduleName => $moduleLockInfo) {
            $versionToInstall = $moduleLockInfo["version"] ?? null;
            $expectedIntegrity = $moduleLockInfo["integrity"] ?? null;
            $registryName = $moduleLockInfo["registry"] ?? null;
            $sourceType = $moduleLockInfo["source_type"] ?? "git";
            $sourceConfig = $moduleLockInfo["source_config"] ?? [];
            $modulePath = $moduleLockInfo["module_path"] ?? null;

            if (!$versionToInstall || !$expectedIntegrity || !$modulePath) {
                $this->error(
                    "Incomplete lock information for module '{$moduleName}'. Skipping.",
                );
                $installErrors = true;
                continue;
            }

            $moduleInstallFolderName = $this->generateModuleInstallFolderName(
                $moduleName,
            );
            $moduleCacheFileName =
                $moduleInstallFolderName . "-" . $versionToInstall . ".zip";
            $moduleCachePath = $this->getCachePath() . $moduleCacheFileName;
            $moduleInstallPath =
                $this->getModulesPath() . $moduleInstallFolderName;

            $this->info(
                "Installing module {$moduleName} version {$versionToInstall} from lock file...",
            );

            if ($moduleName === self::FRAMEWORK_MODULE_NAME) {
                $this->installFrameworkModule($versionToInstall);
                continue;
            }

            $registryDetails = $registryName
                ? $this->getRegistryByName($registryName)
                : null;
            if (!$registryDetails) {
                $this->error(
                    "Registry '{$registryName}' not found in configured registries for module '{$moduleName}'. Skipping.",
                );
                $installErrors = true;
                continue;
            }
            $sourceConfig = array_merge($registryDetails, $sourceConfig);
            $sourceConfig["type"] = $sourceType;
            $sourceConfig["debug"] = $this->debugEnabled;
            $source = SourceFactory::create($sourceConfig);

            $this->info("Verifying integrity of {$moduleName}...");
            if (file_exists($moduleCachePath)) {
                $calculatedIntegrity = hash_file("sha256", $moduleCachePath);
                if ($calculatedIntegrity !== $expectedIntegrity) {
                    $this->warning(
                        "Integrity mismatch for cached module {$moduleName}. Re-downloading.",
                    );
                    unlink($moduleCachePath);
                } else {
                    $this->info(
                        "Integrity verified for cached module {$moduleName}.",
                    );
                }
            }

            if (!file_exists($moduleCachePath)) {
                $this->info("Downloading module {$moduleName}...");
                $integrityHash = $source->downloadModule(
                    $modulePath,
                    $moduleCachePath,
                    $versionToInstall,
                );
                if (!$integrityHash) {
                    $this->error(
                        "Failed to download module {$moduleName} from source.",
                    );
                    $installErrors = true;
                    continue;
                }

                if ($integrityHash !== $expectedIntegrity) {
                    $this->error(
                        "Integrity verification failed after download for module {$moduleName}!",
                    );
                    $this->error("Expected integrity: {$expectedIntegrity}");
                    $this->error("Calculated integrity: {$integrityHash}");
                    unlink($moduleCachePath);
                    $installErrors = true;
                    continue;
                }
                $this->info(
                    "Integrity verified after download for module {$moduleName}.",
                );
            }

            $this->info("Extracting module {$moduleName}...");
            $extractionSourcePath = "";
            if (
                !$this->extractModule(
                    $moduleCachePath,
                    $moduleInstallPath,
                    $extractionSourcePath,
                )
            ) {
                $this->error("Failed to extract module {$moduleName}.");
                $installErrors = true;
                continue;
            }

            $moduleSrcPath = $moduleInstallPath . '/src';
            if (is_dir($moduleSrcPath)) {
                \Forge\Core\Autoloader::addPath(
                    'Modules\\' . $moduleInstallFolderName . '\\',
                    $moduleSrcPath,
                );
            }

            $modulePascalName = $this->toPascalCase($moduleName);

            $depCommands = $this->resolveModuleDependencies(
                $moduleInstallPath,
                $moduleName,
                true,
            );
            $allPostInstallCommands = array_merge($allPostInstallCommands, $depCommands);

            $this->updateForgeJson($moduleName, $versionToInstall);

            $postInstallCommands = $this->detectPostInstallCommands(
                $moduleInstallPath,
                $modulePascalName,
            );

            if (!empty($postInstallCommands)) {
                foreach ($postInstallCommands as $cmd) {
                    $allPostInstallCommands[] = [
                        'command' => $cmd['command'],
                        'args' => $cmd['args'],
                        'moduleName' => $modulePascalName,
                        'registryName' => $registryName ?? 'unknown',
                        'autoTrustSource' => false,
                    ];
                }
            }

            $this->success(
                "Module {$moduleName} version {$versionToInstall} installed from lock file successfully.",
            );
        }

        if (!empty($allPostInstallCommands)) {
            $this->line();
            $this->info("── Running PostInstall commands ──");
            $this->executeBatchPostInstallCommands($allPostInstallCommands);
        }

        if ($installErrors) {
            $this->error(
                "Some modules failed to install from forge-lock.json. Check error messages above.",
            );
        } else {
            $this->success(
                "All modules from forge-lock.json installed successfully.",
            );
        }
    }

    private function installFromForgeJson(): void
    {
        $forgeJsonPath = BASE_PATH . "/forge.json";
        if (!file_exists($forgeJsonPath)) {
            $this->info("No forge.json found.");
            return;
        }

        $forgeData = json_decode(file_get_contents($forgeJsonPath), true);
        $modules = $forgeData["modules"] ?? [];

        if (empty($modules)) {
            $this->info("No modules defined in forge.json.");
            return;
        }

        $hadErrors = false;
        $allPostInstallCommands = [];

        foreach ($modules as $moduleName => $version) {
            if ($moduleName === "forge-package-manager") {
                continue;
            }

            $moduleDirName = $this->generateModuleInstallFolderName($moduleName);
            if (is_dir($this->getModulesPath() . $moduleDirName)) {
                $this->info("{$moduleName} already installed, skipping.");
                continue;
            }

            $this->info("Installing {$moduleName}" . ($version ? " v{$version}" : "") . " from forge.json...");

            try {
                $commands = $this->installModule($moduleName, $version, null, null, false, null, true);
                $allPostInstallCommands = array_merge($allPostInstallCommands, $commands);
            } catch (\Throwable $e) {
                $this->error(
                    "Failed to install {$moduleName}: " . $e->getMessage(),
                );
                $hadErrors = true;
                continue;
            }

            if (!is_dir($this->getModulesPath() . $moduleDirName)) {
                $hadErrors = true;
            }
        }

        if (!empty($allPostInstallCommands)) {
            $this->line();
            $this->info("── Running PostInstall commands ──");
            $this->executeBatchPostInstallCommands($allPostInstallCommands);
        }

        if ($hadErrors) {
            $this->error("Some modules from forge.json failed to install.");
        } else {
            $this->success("Modules from forge.json installed successfully.");
        }
    }

    private function readForgeLockJson(): array
    {
        $forgeLockJsonPath = BASE_PATH . "/forge-lock.json";
        if (!file_exists($forgeLockJsonPath)) {
            $defaultLockData = ["modules" => []];
            file_put_contents(
                $forgeLockJsonPath,
                json_encode(
                    $defaultLockData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                ),
            );
            return $defaultLockData;
        }
        $content = file_get_contents($forgeLockJsonPath);
        return json_decode($content, true) ?? ["modules" => []];
    }

    private function getRegistryByName(string $name): ?array
    {
        foreach ($this->registries as $registry) {
            if ($registry["name"] === $name) {
                return $registry;
            }
        }
        return null;
    }

    private function generateModuleInstallFolderName(string $fullName): string
    {
        return self::toPascalCase($fullName);
    }

    private function getCachePath(): string
    {
        return $this->cachePath;
    }

    private function getModulesPath(): string
    {
        return $this->modulesPath;
    }

    private function getStagingPath(string $moduleFolderName): string
    {
        return $this->cachePath . 'staging' . \DIRECTORY_SEPARATOR . $moduleFolderName;
    }

    private function installFrameworkModule(?string $version = null): void
    {
        $this->info("Installing Forge Kernel...");

        $installScriptPath = BASE_PATH . "/install.php";
        if (!file_exists($installScriptPath)) {
            $this->error(
                "Error: install.php script not found in project root.",
            );
            return;
        }

        $command = "php " . escapeshellarg($installScriptPath);
        if ($version) {
            $command .= " --version=" . escapeshellarg($version);
        }

        $this->info("Executing framework install script: {$command}");

        $process = proc_open(
            $command,
            [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"],
            ],
            $pipes,
        );

        if (is_resource($process)) {
            fclose($pipes[0]);

            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $returnCode = proc_close($process);

            echo $stdout;

            if ($returnCode !== 0) {
                $this->error(
                    "Framework install script failed with exit code: {$returnCode}",
                );
                if (!empty($stderr)) {
                    $this->error("Install script error output:\n" . $stderr);
                }
            } else {
                $this->success("Forge Framework installed successfully.");
                if (!empty($stderr)) {
                    $this->warning(
                        "Framework install script had warnings:\n" . $stderr,
                    );
                }
                $this->updateForgeJson(
                    self::FRAMEWORK_MODULE_NAME,
                    $version ?: "latest",
                );
            }
        } else {
            $this->error("Failed to execute framework install script.");
        }
    }

    private function updateForgeJson(string $moduleName, string $version): void
    {
        $forgeJsonPath = BASE_PATH . "/forge.json";
        $forgeConfig = $this->readForgeJson();

        $forgeConfig["modules"][$moduleName] = $version;
        $this->writeForgeJson($forgeConfig);
    }

    private function readForgeJson(): array
    {
        $forgeJsonPath = BASE_PATH . "/forge.json";
        if (!file_exists($forgeJsonPath)) {
            $defaultConfig = [
                "name" => "Forge Kernel",
                "kernel" => [
                    "name" => "forge-kernel",
                    "version" => "latest",
                ],
                "modules" => [],
            ];
            file_put_contents(
                $forgeJsonPath,
                json_encode(
                    $defaultConfig,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                ),
            );
            return $defaultConfig;
        }
        $content = file_get_contents($forgeJsonPath);
        return json_decode($content, true) ?? ["modules" => []];
    }

    private function writeForgeJson(array $data): void
    {
        $forgeJsonPath = BASE_PATH . "/forge.json";
        file_put_contents(
            $forgeJsonPath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
    }

    private function extractModule(
        string $zipPath,
        string $destinationPath,
        string $sourcePathInZip,
        ?string $preservedPath = null,
    ): bool {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $this->removeDirectory($destinationPath);
            if (
                !mkdir($destinationPath, 0755, true) &&
                !is_dir($destinationPath)
            ) {
                $this->error(
                    "Failed to create module directory: {$destinationPath}",
                );
                return false;
            }

            $zip->extractTo($destinationPath);
            $zip->close();

            if ($preservedPath !== null && is_dir($preservedPath)) {
                $this->applyPreservedFiles($preservedPath, $destinationPath);
            }

            return true;
        } else {
            return false;
        }
    }

    private function applyPreservedFiles(
        string $preservedPath,
        string $destinationPath,
    ): void {
        if (!is_dir($preservedPath)) {
            $this->warning("Preserved path does not exist: {$preservedPath}");
            return;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $preservedPath,
                    \RecursiveDirectoryIterator::SKIP_DOTS,
                ),
                \RecursiveIteratorIterator::SELF_FIRST,
            );

            $appliedCount = 0;
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = str_replace(
                        $preservedPath . "/",
                        "",
                        $file->getRealPath(),
                    );
                    $targetPath = $destinationPath . "/" . $relativePath;
                    $targetDir = dirname($targetPath);

                    if (!is_dir($targetDir)) {
                        if (!mkdir($targetDir, 0755, true)) {
                            $this->warning(
                                "Failed to create directory for preserved file: {$targetDir}",
                            );
                            continue;
                        }
                    }

                    if (file_exists($targetPath)) {
                        if (copy($file->getRealPath(), $targetPath)) {
                            $appliedCount++;
                        } else {
                            $this->warning(
                                "Failed to apply preserved file: {$relativePath}",
                            );
                        }
                    }
                }
            }

            if ($appliedCount > 0) {
                $this->info(
                    "Applied preserved modifications to {$appliedCount} file(s).",
                );
            }
        } catch (\Throwable $e) {
            $this->warning(
                "Error applying preserved files: " . $e->getMessage(),
            );
            $this->warning(
                "Proceeding with standard installation (modifications may be lost).",
            );
        }
    }

    private function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }
        $files = array_diff(scandir($dir), [".", ".."]);
        foreach ($files as $file) {
            is_dir("$dir/$file")
                ? $this->removeDirectory("$dir/$file")
                : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    private function clearAutoloaderCache(): void
    {
        $cacheFile = \defined('BASE_PATH')
            ? BASE_PATH . '/storage/framework/cache/class_file_map.php'
            : null;
        if ($cacheFile !== null && file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
    }

    /**
     * Find the module class ReflectionClass by scanning src/ for the #[Module] attribute.
     *
     * Uses the tokenizer (getClassNameFromFile) to extract FQCNs without executing PHP.
     * Only require_once's the single module class file if the class isn't already loaded.
     *
     * @return ReflectionClass|null
     */
    private function findModuleReflection(string $modulePath, string $moduleName): ?ReflectionClass
    {
        $srcPath = $modulePath . '/src';
        if (!is_dir($srcPath)) {
            return null;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcPath, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $filePath = $file->getRealPath();
            $content = file_get_contents($filePath);

            if (!str_contains($content, '#[Module') && !str_contains($content, '@Attributes\\Module')) {
                continue;
            }

            $className = $this->getClassNameFromFile($filePath);
            if ($className === null) {
                continue;
            }

            if (!class_exists($className, false)) {
                try {
                    require_once $filePath;
                } catch (\Throwable $e) {
                    continue;
                }
            }

            if (!class_exists($className)) {
                continue;
            }

            try {
                $ref = new ReflectionClass($className);
                $moduleAttr = $ref->getAttributes(Module::class);
                if (empty($moduleAttr)) {
                    continue;
                }

                $moduleInstance = $moduleAttr[0]->newInstance();
                if ($moduleInstance->name === $moduleName) {
                    return $ref;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Detect #[PostInstall] commands from the module class without executing them.
     *
     * @return array{command: string, args: array<string>}[] List of PostInstall command definitions.
     */
    private function detectPostInstallCommands(string $modulePath, string $moduleName): array
    {
        $ref = $this->findModuleReflection($modulePath, $moduleName);
        if ($ref === null) {
            $this->warning(
                "No #[Module] class found for '{$moduleName}', skipping PostInstall detection.",
            );
            return [];
        }

        $postInstallAttrs = $ref->getAttributes(PostInstall::class);
        if (empty($postInstallAttrs)) {
            $this->info(
                "Module {$moduleName} has no PostInstall attributes defined.",
            );
            return [];
        }

        $commands = [];
        foreach ($postInstallAttrs as $attr) {
            /** @var PostInstall $instance */
            $instance = $attr->newInstance();
            $commands[] = [
                'command' => $instance->command,
                'args' => $instance->args,
            ];
        }

        return $commands;
    }

    /**
     * Execute PostInstall commands with trust prompting.
     *
     * @param array{command: string, args: array<string>}[] $commands
     * @return array<string> List of executed command strings.
     */
    private function executePostInstallCommands(
        array $commands,
        string $moduleName,
        string $registryName,
        bool $autoTrustSource = false,
    ): array {
        $executedCommands = [];

        if (empty($commands)) {
            return $executedCommands;
        }

        $commandCount = count($commands);
        $isTrusted = $this->isSourceTrusted($registryName);

        if (!$isTrusted) {
            if ($autoTrustSource) {
                $this->trustSource($registryName);
                $this->success(
                    "Source '{$registryName}' has been automatically trusted.",
                );
                $isTrusted = true;
            } else {
                $this->showPostInstallWarning(
                    $moduleName,
                    $registryName,
                    $commandCount,
                );
            }
        } else {
            $this->info(
                "Source '{$registryName}' is trusted. Executing all PostInstall commands automatically.",
            );
        }

        $acceptAll = false;
        $rejectAll = false;
        $trustConfirmed = $isTrusted;

        foreach ($commands as $index => $cmd) {
            if ($rejectAll) {
                $this->info(
                    "Skipping command " .
                    ($index + 1) .
                    " of {$commandCount} (rejected all).",
                );
                continue;
            }

            $args = implode(" ", $cmd['args']);
            $command = "php forge.php {$cmd['command']} {$args}";

            if ($isTrusted || $acceptAll) {
                $shouldExecute = true;
            } else {
                $response = $this->confirmPostInstallCommand(
                    $command,
                    $moduleName,
                    $registryName,
                    $index + 1,
                    $commandCount,
                );

                if ($response === "reject-all") {
                    $rejectAll = true;
                    $shouldExecute = false;
                } elseif ($response === "all") {
                    $acceptAll = true;
                    $shouldExecute = true;
                } else {
                    $shouldExecute = $response === "yes";
                }
            }

            if ($shouldExecute) {
                $this->info("Running: {$command}");
                exec($command, $output, $code);
                $this->line();

                if ($code !== 0) {
                    $this->error(
                        "Command '{$command}' failed for module {$moduleName} (exit code {$code})",
                    );
                    if (!empty($output)) {
                        $this->error(
                            "Output:\n" . implode("\n", $output),
                        );
                    }
                } else {
                    $this->success(
                        "Command '{$command}' executed successfully.",
                    );
                    $executedCommands[] = $command;
                }
            } else {
                $this->info("Skipping command: {$command}");
            }
        }

        return $executedCommands;
    }

    /**
     * Execute a batch of PostInstall commands collected from multiple modules.
     * Handles trust prompting once per unique registry source.
     *
     * @param array<int, array{command: string, args: array<string>, moduleName: string, registryName: string, autoTrustSource: bool}> $allCommands
     */
    private function executeBatchPostInstallCommands(array $allCommands): void
    {
        if (empty($allCommands)) {
            return;
        }

        $trustedRegistries = [];
        $rejectedAll = false;

        foreach ($allCommands as $cmd) {
            if ($rejectedAll) {
                break;
            }

            $registryName = $cmd['registryName'];
            $moduleName = $cmd['moduleName'];
            $autoTrustSource = $cmd['autoTrustSource'];

            if (!isset($trustedRegistries[$registryName])) {
                $isTrusted = $this->isSourceTrusted($registryName);
                if (!$isTrusted && $autoTrustSource) {
                    $this->trustSource($registryName);
                    $this->success("Source '{$registryName}' has been automatically trusted.");
                    $isTrusted = true;
                }
                $trustedRegistries[$registryName] = $isTrusted;
            }

            $isTrusted = $trustedRegistries[$registryName];

            $args = implode(" ", $cmd['args']);
            $command = "php forge.php {$cmd['command']} {$args}";

            if ($isTrusted) {
                $this->info("Running: {$command}");
                exec($command, $output, $code);
                $this->line();

                if ($code !== 0) {
                    $this->error("Command '{$command}' failed for module {$moduleName} (exit code {$code})");
                    if (!empty($output)) {
                        $this->error("Output:\n" . implode("\n", $output));
                    }
                } else {
                    $this->success("Command '{$command}' executed successfully.");
                }
            } else {
                $response = $this->confirmPostInstallCommand(
                    $command,
                    $moduleName,
                    $registryName,
                    1,
                    1,
                );

                if ($response === "reject-all") {
                    $rejectedAll = true;
                    $this->info("Skipping command: {$command}");
                } elseif ($response === "all") {
                    $trustedRegistries[$registryName] = true;
                    $this->info("Running: {$command}");
                    exec($command, $output, $code);
                    $this->line();

                    if ($code !== 0) {
                        $this->error("Command '{$command}' failed for module {$moduleName} (exit code {$code})");
                        if (!empty($output)) {
                            $this->error("Output:\n" . implode("\n", $output));
                        }
                    } else {
                        $this->success("Command '{$command}' executed successfully.");
                    }
                } elseif ($response === "yes") {
                    $this->info("Running: {$command}");
                    exec($command, $output, $code);
                    $this->line();

                    if ($code !== 0) {
                        $this->error("Command '{$command}' failed for module {$moduleName} (exit code {$code})");
                        if (!empty($output)) {
                            $this->error("Output:\n" . implode("\n", $output));
                        }
                    } else {
                        $this->success("Command '{$command}' executed successfully.");
                    }
                } else {
                    $this->info("Skipping command: {$command}");
                }
            }
        }
    }

    /**
     * Prompt the user to trust the source after PostInstall commands have been shown.
     */
    private function promptTrustAfterPostInstall(
        string $moduleName,
        string $registryName,
        bool $autoTrustSource,
    ): bool {
        if ($this->isSourceTrusted($registryName)) {
            return true;
        }

        if ($autoTrustSource) {
            $this->trustSource($registryName);
            $this->success(
                "Source '{$registryName}' has been automatically trusted.",
            );
            return true;
        }

        $confirmed = $this->promptTrustSource($registryName);
        if ($confirmed) {
            $this->trustSource($registryName);
            $this->success(
                "Source '{$registryName}' has been trusted for future installations.",
            );
        }
        return $confirmed;
    }

    /**
     * @throws ReflectionException
     */
    private function isModuleInstalled(string $moduleName): bool
    {
        $moduleDirName = $this->generateModuleInstallFolderName($moduleName);
        return is_dir($this->getModulesPath() . $moduleDirName);
    }

    private function resolveVersionConstraint(array $moduleInfo, string $constraint): ?string
    {
        $bestVersion = null;
        foreach (array_keys($moduleInfo['versions'] ?? []) as $version) {
            if (Version::isVersionCompatible($version, $constraint)) {
                if ($bestVersion === null || version_compare($version, $bestVersion, '>')) {
                    $bestVersion = $version;
                }
            }
        }
        return $bestVersion;
    }

    private function resolveModuleDependencies(string $stagingPath, string $moduleName, bool $deferPostInstall = false): array
    {
        $collectedCommands = [];
        $modulePascalName = $this->toPascalCase($moduleName);
        $ref = $this->findModuleReflection($stagingPath, $modulePascalName);
        if ($ref === null) {
            return $collectedCommands;
        }

        $requiresAttrs = $ref->getAttributes(Requires::class);
        foreach ($requiresAttrs as $attr) {
            $instance = $attr->newInstance();
            if ($instance->module === null) {
                continue;
            }

            $requiredModule = $instance->module;

            if (in_array($requiredModule, $this->resolvingDependencies, true)) {
                throw new \RuntimeException(
                    "Circular dependency detected: module '{$moduleName}' requires '{$requiredModule}' which is already being resolved. Chain: "
                    . implode(' → ', $this->resolvingDependencies) . " → {$requiredModule}"
                );
            }

            if ($this->isModuleInstalled($requiredModule)) {
                $this->info("Dependency '{$requiredModule}' required by '{$moduleName}' is already installed.");
                continue;
            }

            $this->resolvingDependencies[] = $requiredModule;
            $this->info("Module '{$moduleName}' requires '{$requiredModule}'. Installing dependency...");

            try {
                $depCommands = $this->installModule($requiredModule, $instance->version, null, null, true, null, $deferPostInstall);
                $collectedCommands = array_merge($collectedCommands, $depCommands);
            } catch (\Throwable $e) {
                array_pop($this->resolvingDependencies);
                throw new \RuntimeException(
                    "Failed to install dependency '{$requiredModule}' required by '{$moduleName}': " . $e->getMessage(),
                    0,
                    $e
                );
            }

            array_pop($this->resolvingDependencies);
        }

        return $collectedCommands;
    }

    /**
     * Install a module and optionally defer PostInstall commands.
     *
     * @return array<int, array{command: string, args: array<string>, moduleName: string, registryName: string, autoTrustSource: bool}> Collected PostInstall commands when deferred, empty array otherwise.
     */
    public function installModule(
        string $moduleName,
        ?string $version = null,
        ?string $forceCache = null,
        ?string $preservedPath = null,
        bool $autoTrustSource = false,
        ?string $configMode = null,
        bool $deferPostInstall = false,
    ): array {
        $explicitLatest = $version === 'latest' || $version === '*';
        $resolveLatest = $version === null || $explicitLatest;

        $this->info(
            "Installing module: {$moduleName}" .
            ($resolveLatest ? " (latest)" : " constraint: {$version}"),
        );

        if ($moduleName === self::FRAMEWORK_MODULE_NAME) {
            $this->installFrameworkModule($resolveLatest ? null : $version);
            return [];
        }

        $moduleInfo = $this->getModuleInfo($moduleName, $version);
        if (!$moduleInfo) {
            $this->error("Module '{$moduleName}' not found in registries.");
            return [];
        }

        if (!$resolveLatest) {
            $versionToInstall = $this->resolveVersionConstraint($moduleInfo, $version);
            if ($versionToInstall === null) {
                $this->error(
                    "No available version satisfies constraint '{$version}' for module '{$moduleName}'.",
                );
                return [];
            }
        } else {
            $versionToInstall = $moduleInfo["latest"] ?? null;
        }

        $versionDetails = isset($moduleInfo["versions"][$versionToInstall])
            ? $moduleInfo["versions"][$versionToInstall]
            : null;

        if (!$versionDetails) {
            $this->error(
                "Version '{$versionToInstall}' for module '{$moduleName}' not found.",
            );
            return [];
        }

        $forgeJsonVersion = $explicitLatest ? 'latest' : $versionToInstall;

        $moduleDownloadPathInRepo = $versionDetails["url"];
        $registryDetails = $this->getRegistryDetailsForModule($moduleName);
        if (!$registryDetails) {
            $this->error(
                "No registry found for module '{$moduleName}'. Please ensure registries are configured in config/source_list.php",
            );
            return [];
        }
        $sourceType = $registryDetails["type"] ?? "git";
        $sourceConfig = $registryDetails;
        $sourceConfig["type"] = $sourceType;
        $sourceConfig["debug"] = $this->debugEnabled;
        $source = SourceFactory::create($sourceConfig);

        $moduleInstallFolderName = $this->generateModuleInstallFolderName(
            $moduleName,
        );
        $moduleCacheFileName =
            $moduleInstallFolderName . "-" . $versionToInstall . ".zip";
        $moduleCachePath = $this->getCachePath() . $moduleCacheFileName;
        $moduleInstallPath = $this->getModulesPath() . $moduleInstallFolderName;

        if ($forceCache === "force") {
            if (file_exists($moduleCachePath)) {
                unlink($moduleCachePath);
                $this->info(
                    "Cache bypassed, deleted cached module {$moduleName} version {$versionToInstall}.",
                );
            }
            $this->info(
                "Downloading module {$moduleName} version {$versionToInstall} from remote...",
            );
            $integrityHash = $source->downloadModule(
                $moduleDownloadPathInRepo,
                $moduleCachePath,
                $versionToInstall,
            );
            $this->integrityHash = $integrityHash;
            if (!$integrityHash) {
                $this->error("Failed to download module {$moduleName}.");
                return [];
            }
        } elseif (!file_exists($moduleCachePath)) {
            $this->info(
                "Downloading module {$moduleName} version {$versionToInstall}...",
            );
            $integrityHash = $source->downloadModule(
                $moduleDownloadPathInRepo,
                $moduleCachePath,
                $versionToInstall,
            );
            $this->integrityHash = $integrityHash;
            if (!$integrityHash) {
                $this->error("Failed to download module {$moduleName}.");
                return [];
            }
        } else {
            $this->info(
                "Using cached module {$moduleName} version {$versionToInstall}.",
            );
            $integrityHash = hash_file("sha256", $moduleCachePath);
            if (!$integrityHash) {
                $this->error(
                    "Failed to calculate integrity hash for cached module {$moduleName}.",
                );
                return [];
            }
        }

        $stagingPath = $this->getStagingPath($moduleInstallFolderName);

        if (is_dir($stagingPath)) {
            $this->removeDirectory($stagingPath);
        }

        $stagingParent = dirname($stagingPath);
        if (!is_dir($stagingParent)) {
            mkdir($stagingParent, 0755, true);
        }

        $extractionSourcePath = "";
        if (
            !$this->extractModule(
                $moduleCachePath,
                $stagingPath,
                $extractionSourcePath,
                null,
            )
        ) {
            $this->error("Failed to extract module {$moduleName}.");
            return [];
        }

        $registryName = $registryDetails["name"] ?? "unknown";
        $modulePascalName = $this->toPascalCase($moduleName);

        $moduleNamespacePrefix = 'Modules\\' . $moduleInstallFolderName . '\\';
        $stagingSrcPath = $stagingPath . '/src';
        if (is_dir($stagingSrcPath)) {
            \Forge\Core\Autoloader::addPath($moduleNamespacePrefix, $stagingSrcPath);
        }

        $collectedCommands = $this->resolveModuleDependencies($stagingPath, $moduleName, $deferPostInstall);

        $postInstallCommands = $this->detectPostInstallCommands(
            $stagingPath,
            $modulePascalName,
        );

        if (!$deferPostInstall && !empty($postInstallCommands) && !$this->isSourceTrusted($registryName)) {
            $confirmed = $this->promptTrustAfterPostInstall(
                $moduleName,
                $registryName,
                $autoTrustSource,
            );

            if (!$confirmed) {
                $this->removeDirectory($stagingPath);
                \Forge\Core\Autoloader::removePath($moduleNamespacePrefix);
                $this->warning("Installation of {$moduleName} cancelled by user.");
                return [];
            }
        }

        if (is_dir($moduleInstallPath)) {
            $this->removeDirectory($moduleInstallPath);
        }

        if (!rename($stagingPath, $moduleInstallPath)) {
            $this->error("Failed to finalize module {$moduleName} installation.");
            $this->removeDirectory($stagingPath);
            \Forge\Core\Autoloader::removePath($moduleNamespacePrefix);
            return [];
        }

        $installSrcPath = $moduleInstallPath . '/src';
        if (is_dir($installSrcPath)) {
            \Forge\Core\Autoloader::addPath($moduleNamespacePrefix, $installSrcPath);
        }

        $this->clearAutoloaderCache();

        if ($preservedPath !== null && is_dir($preservedPath)) {
            $this->removeDirectory($preservedPath);
        }

        $this->updateForgeJson($moduleName, $forgeJsonVersion);
        $this->createForgeLockJson(
            $moduleName,
            $versionToInstall,
            $registryDetails,
            $moduleDownloadPathInRepo,
            $integrityHash,
            $sourceType,
        );

        $this->configGenerator->generateConfigFromModule(
            $moduleInstallPath,
            $moduleName,
            $configMode,
        );

        if ($deferPostInstall) {
            foreach ($postInstallCommands as $cmd) {
                $collectedCommands[] = [
                    'command' => $cmd['command'],
                    'args' => $cmd['args'],
                    'moduleName' => $modulePascalName,
                    'registryName' => $registryName,
                    'autoTrustSource' => $autoTrustSource,
                ];
            }
        } elseif (!empty($postInstallCommands)) {
            $this->executePostInstallCommands(
                $postInstallCommands,
                $modulePascalName,
                $registryName,
                $autoTrustSource,
            );
        }

        $this->success(
            "Module {$moduleName} version {$versionToInstall} installed successfully.",
        );

        return $deferPostInstall ? $collectedCommands : [];
    }

    private function getModulesDataForRegistry(
        string $registryName,
        string $sourceType,
        array $registryConfig,
        SourceInterface $source,
    ): ?array {
        $debugConfig = $registryConfig;
        if (isset($debugConfig["personal_token"])) {
            $debugConfig["personal_token"] = "***hidden***";
        }
        if (isset($debugConfig["password"])) {
            $debugConfig["password"] = "***hidden***";
        }

        $this->debug("Checking registry: {$registryName}");
        $this->debug(
            "Registry config: " .
            json_encode($debugConfig, JSON_UNESCAPED_SLASHES),
        );
        $this->debug("Source type: {$sourceType}");

        $cacheKey = md5(
            $registryName . $sourceType . serialize($registryConfig),
        );
        $cacheFile = $this->getCachePath() . $cacheKey . ".cache";
        $this->debug("Cache file: {$cacheFile}");
        $modulesData = null;

        if (file_exists($cacheFile)) {
            $age = time() - filemtime($cacheFile);
            if ($age >= $this->cacheTtl) {
                unlink($cacheFile);
                $this->info(
                    "Cache expired, cleared cache file for {$registryName}.",
                );
            } else {
                $this->info("Using cached module list from {$registryName}.");
                $modulesData = json_decode(file_get_contents($cacheFile), true);
            }
        }

        if (!is_array($modulesData)) {
            $this->info("Fetching module list from {$registryName}...");
            $modulesData = $source->fetchModulesJson();

            if ($modulesData === null || !is_array($modulesData)) {
                $this->warning(
                    "Failed to fetch module list from registry: {$registryName}",
                );
                return null;
            }

            $written = @file_put_contents(
                $cacheFile,
                json_encode(
                    $modulesData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                ),
            );
            if ($written === false) {
                $this->warning(
                    "Failed to write cache file for registry: {$registryName}",
                );
            } else {
                $this->debug("Cache file written successfully: {$cacheFile}");
            }
        }

        return $modulesData;
    }

    public function getModuleInfo(
        ?string $moduleName = null,
        ?string $version = null,
    ): ?array {
        if (empty($this->registries)) {
            $this->error(
                "No package registries configured. Please configure registries in config/source_list.php",
            );
            return null;
        }

        if (!$moduleName) {
            return null;
        }

        $this->debug("Searching for module: {$moduleName}");
        $this->debug("Checking " . count($this->registries) . " registry(ies)");

        foreach ($this->registries as $index => $registryConfig) {
            $sourceType = $registryConfig["type"] ?? "git";
            $registryConfig["debug"] = $this->debugEnabled;
            $source = SourceFactory::create($registryConfig);
            $registryName = $registryConfig["name"] ?? "unknown";

            $this->debug(
                "Registry " .
                ($index + 1) .
                "/" .
                count($this->registries) .
                ": {$registryName}",
            );

            $modulesData = $this->getModulesDataForRegistry(
                $registryName,
                $sourceType,
                $registryConfig,
                $source,
            );

            if ($modulesData && isset($modulesData[$moduleName])) {
                $this->debug(
                    "Module '{$moduleName}' found in registry: {$registryName}",
                );
                return $modulesData[$moduleName];
            } else {
                $this->debug(
                    "Module '{$moduleName}' not found in registry: {$registryName}",
                );
            }
        }

        $this->error(
            "Module '{$moduleName}' not found in any configured registry.",
        );
        return null;
    }

    public function getAllModulesFromRegistry(array $registryConfig): ?array
    {
        $sourceType = $registryConfig["type"] ?? "git";
        $registryConfig["debug"] = $this->debugEnabled;
        $source = SourceFactory::create($registryConfig);
        $registryName = $registryConfig["name"] ?? "unknown";

        return $this->getModulesDataForRegistry(
            $registryName,
            $sourceType,
            $registryConfig,
            $source,
        );
    }

    private function getRegistryDetailsForModule(?string $moduleName): ?array
    {
        if (empty($this->registries)) {
            return null;
        }

        if ($moduleName) {
            foreach ($this->registries as $registry) {
                $sourceType = $registry["type"] ?? "git";
                $registry["debug"] = $this->debugEnabled;
                $source = SourceFactory::create($registry);
                $registryName = $registry["name"] ?? "unknown";

                $modulesData = $this->getModulesDataForRegistry(
                    $registryName,
                    $sourceType,
                    $registry,
                    $source,
                );

                if ($modulesData && isset($modulesData[$moduleName])) {
                    return $registry;
                }
            }
        }

        return $this->registries[0] ?? null;
    }

    private function createForgeLockJson(
        string $moduleName,
        string $version,
        array $registryDetails,
        string $modulePath,
        string $integrityHash,
        string $sourceType,
    ): void {
        $forgeLockJsonPath = BASE_PATH . "/forge-lock.json";
        $lockData = $this->readForgeLockJson();

        // Filter out sensitive credentials before storing in lock file
        $sanitizedConfig = $this->filterSensitiveDataFromConfig(
            $registryDetails,
        );

        $lockData["modules"][$moduleName] = [
            "version" => $version,
            "registry" => $registryDetails["name"] ?? "unknown",
            "module_path" => $modulePath,
            "integrity" => $integrityHash,
            "source_type" => $sourceType,
            "source_config" => $sanitizedConfig,
        ];

        $this->writeForgeLockJson($lockData);
    }

    /**
     * Filters out sensitive credentials and tokens from registry configuration
     * to prevent them from being stored in forge-lock.json.
     *
     * @param array $config The registry configuration array
     * @return array The sanitized configuration without sensitive fields
     */
    private function filterSensitiveDataFromConfig(array $config): array
    {
        // List of sensitive fields that should never be stored in lock file
        $sensitiveFields = [
            "personal_token",
            "token",
            "password",
            "key_passphrase",
            "api_key",
            "secret",
            "access_token",
            "refresh_token",
            "private_key",
            "ssh_key",
            "auth_token",
        ];

        $sanitized = $config;

        // Remove sensitive fields from the config
        foreach ($sensitiveFields as $field) {
            unset($sanitized[$field]);
        }

        // Also filter nested sensitive data if source_config exists
        if (
            isset($sanitized["source_config"]) &&
            is_array($sanitized["source_config"])
        ) {
            foreach ($sensitiveFields as $field) {
                unset($sanitized["source_config"][$field]);
            }
        }

        return $sanitized;
    }

    private function writeForgeLockJson(array $data): void
    {
        $forgeLockJsonPath = BASE_PATH . "/forge-lock.json";
        file_put_contents(
            $forgeLockJsonPath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
    }

    public function removeModule(string $moduleName): void
    {
        if ($moduleName === self::PACKAGE_MANAGER_MODULE_NAME) {
            $this->warning(
                "Uninstalling 'forge-package-manager' will disable automatic module management.",
            );
            $this->warning(
                "You will need to manually download and install modules until another package manager is installed.",
            );
            $this->warning(
                "Consider installing another package manager or reinstalling forge-package-manager afterwards.",
            );
        }

        $moduleInstallFolderName = $this->generateModuleInstallFolderName(
            $moduleName,
        );
        $moduleInstallPath = $this->getModulesPath() . $moduleInstallFolderName;

        if (!is_dir($moduleInstallPath)) {
            $this->info("Cleaning stale module entry: {$moduleName}");
            $this->updateForgeJsonOnModuleRemoval($moduleName);
            $this->updateForgeLockJsonOnModuleRemoval($moduleName);
            $this->success(
                "Stale module entry '{$moduleName}' cleaned successfully.",
            );
            return;
        }

        try {
            $this->runPostUninstallAttributes(
                $moduleInstallPath,
                $this->toPascalCase($moduleName),
            );
        } catch (\ReflectionException $e) {
            $this->warning(
                "Failed to execute PostUninstall for {$moduleName}: " .
                $e->getMessage(),
            );
        }

        $this->info("Removing module {$moduleName}...");

        $directoryRemoved = $this->removeDirectory($moduleInstallPath);
        if (!$directoryRemoved) {
            $this->error(
                "Failed to delete module directory: {$moduleInstallPath}",
            );
        } else {
            $this->info("Module directory removed successfully.");
        }

        $this->clearAutoloaderCache();

        // Always update JSON files regardless of directory removal status
        $jsonUpdateSuccess = true;
        try {
            $this->updateForgeJsonOnModuleRemoval($moduleName);
        } catch (\Throwable $e) {
            $this->error("Failed to update forge.json: " . $e->getMessage());
            $jsonUpdateSuccess = false;
        }

        try {
            $this->updateForgeLockJsonOnModuleRemoval($moduleName);
        } catch (\Throwable $e) {
            $this->error(
                "Failed to update forge-lock.json: " . $e->getMessage(),
            );
            $jsonUpdateSuccess = false;
        }

        // Report overall status
        if ($directoryRemoved && $jsonUpdateSuccess) {
            $this->success("Module {$moduleName} removed successfully.");
        } elseif ($directoryRemoved && !$jsonUpdateSuccess) {
            $this->warning(
                "Module directory removed, but some JSON file updates failed. Please check forge.json and forge-lock.json manually.",
            );
        } elseif (!$directoryRemoved && $jsonUpdateSuccess) {
            $this->warning(
                "Module directory removal failed, but JSON files were updated. You may need to manually remove the directory.",
            );
        } else {
            $this->error(
                "Module removal partially failed. Directory removal and JSON updates both encountered issues.",
            );
        }
    }

    /**
     * Executes #[PostUninstall] commands defined in the module class before removal.
     */
    private function runPostUninstallAttributes(
        string $moduleInstallPath,
        string $moduleName,
    ): void {
        $ref = $this->findModuleReflection($moduleInstallPath, $moduleName);
        if ($ref === null) {
            $this->warning(
                "No #[Module] class found for '{$moduleName}', skipping PostUninstall execution.",
            );
            return;
        }

        $postUninstallAttrs = $ref->getAttributes(PostUninstall::class);
        if (empty($postUninstallAttrs)) {
            $this->info(
                "Module {$moduleName} has no PostUninstall attributes defined.",
            );
            return;
        }

        $this->info(
            "Executing PostUninstall commands for module {$moduleName}...",
        );

        foreach ($postUninstallAttrs as $attr) {
            /** @var PostUninstall $instance */
            $instance = $attr->newInstance();
            $args = implode(" ", $instance->args);
            $command = "php forge.php {$instance->command} {$args}";
            $this->info("Running: {$command}");

            exec($command, $output, $code);
            $this->line();

            if ($code !== 0) {
                $this->error(
                    "Command '{$command}' failed for module {$moduleName} (exit code {$code})",
                );
                if (!empty($output)) {
                    $this->error("Output:\n" . implode("\n", $output));
                }
            } else {
                $this->success(
                    "Command '{$command}' executed successfully.",
                );
            }
        }
    }

    private function updateForgeJsonOnModuleRemoval(string $moduleName): void
    {
        $forgeJsonPath = BASE_PATH . "/forge.json";
        $forgeConfig = $this->readForgeJson();
        if (isset($forgeConfig["modules"][$moduleName])) {
            unset($forgeConfig["modules"][$moduleName]);
            $this->writeForgeJson($forgeConfig);
            $this->info("Removed '{$moduleName}' from forge.json.");
        } else {
            $this->warning(
                "Module '{$moduleName}' not found in forge.json modules section. Skipping forge.json update.",
            );
        }
    }

    private function updateForgeLockJsonOnModuleRemoval(
        string $moduleName,
    ): void {
        $forgeLockJsonPath = BASE_PATH . "/forge-lock.json";
        $lockData = $this->readForgeLockJson();
        if (isset($lockData["modules"][$moduleName])) {
            unset($lockData["modules"][$moduleName]);
            $this->writeForgeLockJson($lockData);
            $this->info("Removed '{$moduleName}' from forge-lock.json.");
        } else {
            $this->warning(
                "Module '{$moduleName}' not found in forge-lock.json modules section. Skipping forge-lock.json update.",
            );
        }
    }

    public function moduleHasMigrations(string $module): bool
    {
        $path = $this->resolveModuleStructurePath($module, 'migrations');
        return $path !== null && is_dir($path);
    }

    public function moduleHasSeeders(string $module): bool
    {
        $path = $this->resolveModuleStructurePath($module, 'seeders');
        return $path !== null && is_dir($path);
    }

    private function resolveModuleStructurePath(string $module, string $type): ?string
    {
        $moduleDir = $this->toPascalCase($module);

        if ($this->structureResolver) {
            try {
                $relativePath = $this->structureResolver->getModulePath($moduleDir, $type);
                $fullPath = BASE_PATH . "/modules/{$moduleDir}/{$relativePath}";
                return is_dir($fullPath) ? $fullPath : null;
            } catch (\InvalidArgumentException $e) {
                return $this->getDefaultModuleStructurePath($moduleDir, $type);
            }
        }

        return $this->getDefaultModuleStructurePath($moduleDir, $type);
    }

    private function getDefaultModuleStructurePath(string $moduleDir, string $type): ?string
    {
        $defaultPaths = [
            'migrations' => "/modules/{$moduleDir}/src/Database/Migrations",
            'seeders' => "/modules/{$moduleDir}/src/Database/Seeders",
        ];

        if (!isset($defaultPaths[$type])) {
            return null;
        }

        $fullPath = BASE_PATH . $defaultPaths[$type];
        return is_dir($fullPath) ? $fullPath : null;
    }

    public function moduleHasAssets(string $module): bool
    {
        return is_dir(BASE_PATH . "/modules/{$module}/src/UI/assets") ||
            is_dir(BASE_PATH . "/public/modules/{$module}");
    }

    /**
     * Scaffolds the app folder structure with empty directories.
     * Creates all necessary folders for organizing application code, resources, and tests.
     */
    public function scaffoldAppStructure(): void
    {
        $appPath = BASE_PATH . "/app";

        if (!is_dir($appPath)) {
            mkdir($appPath, 0755, true);
            $this->info("Created app directory.");
        }

        $this->info("Scaffolding app folder structure...");

        $directories = [
            "Commands",
            "Controllers",
            "Database/Migrations",
            "Database/Seeders",
            "Dto",
            "Events",
            "Models",
            "Services",
            "tests",
            "Components/Ui",
            "Components/Wire",
            "UI/assets/css",
            "UI/assets/js",
            "UI/views/components/shared",
            "UI/views/components/ui",
            "UI/views/components/wire",
            "UI/views/layouts",
            "UI/views/pages",
        ];

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($directories as $dir) {
            $fullPath = $appPath . "/" . $dir;

            if (!is_dir($fullPath)) {
                if (mkdir($fullPath, 0755, true)) {
                    $createdCount++;
                    $this->debug("Created: app/{$dir}", "scaffold");
                } else {
                    $this->warning("Failed to create: app/{$dir}");
                }
            } else {
                $skippedCount++;
                $this->debug(
                    "Skipped (already exists): app/{$dir}",
                    "scaffold",
                );
            }
        }

        if ($createdCount > 0) {
            $this->success(
                "App structure scaffolded successfully. Created {$createdCount} director" .
                ($createdCount === 1 ? "y" : "ies") .
                ".",
            );
        } else {
            $this->info(
                "App structure already exists. All directories are in place.",
            );
        }

        if ($skippedCount > 0 && $this->debugEnabled) {
            $this->info(
                "Skipped {$skippedCount} existing director" .
                ($skippedCount === 1 ? "y" : "ies") .
                ".",
            );
        }
    }
}
