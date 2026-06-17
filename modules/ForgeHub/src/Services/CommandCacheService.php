<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\FileExistenceCache;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

#[Service]
final class CommandCacheService
{
    public const COMMANDS_CACHE_FILE = BASE_PATH . '/storage/framework/cache/forgehub_commands.php';
    public const ARGUMENTS_CACHE_FILE = BASE_PATH . '/storage/framework/cache/forgehub_command_args.php';
    public const PHP_EXECUTABLE_CACHE_FILE = BASE_PATH . '/storage/framework/cache/php_executable.php';

    private array $commandsCache = [];
    private array $argumentsCache = [];
    private ?string $phpExecutable = null;
    private bool $cacheLoaded = false;

    public function loadCache(): void
    {
        if ($this->cacheLoaded) {
            return;
        }

        $this->loadCommandsCache();
        $this->loadArgumentsCache();
        $this->loadPhpExecutableCache();
        $this->cacheLoaded = true;
    }

    private function loadCommandsCache(): void
    {
        if (!FileExistenceCache::exists(self::COMMANDS_CACHE_FILE)) {
            return;
        }

        $cache = include self::COMMANDS_CACHE_FILE;
        if (is_array($cache) && $this->isCacheValid($cache['timestamps'] ?? [])) {
            $this->commandsCache = $cache['commands'] ?? [];
        }
    }

    private function loadArgumentsCache(): void
    {
        if (!FileExistenceCache::exists(self::ARGUMENTS_CACHE_FILE)) {
            return;
        }

        $cache = include self::ARGUMENTS_CACHE_FILE;
        if (is_array($cache) && $this->isCacheValid($cache['timestamps'] ?? [])) {
            $this->argumentsCache = $cache['arguments'] ?? [];
        }
    }

    private function loadPhpExecutableCache(): void
    {
        if (!FileExistenceCache::exists(self::PHP_EXECUTABLE_CACHE_FILE)) {
            return;
        }

        $cache = include self::PHP_EXECUTABLE_CACHE_FILE;
        if (is_array($cache) && !empty($cache['executable']) && is_executable($cache['executable'])) {
            $this->phpExecutable = $cache['executable'];
        }
    }

    public function getCachedCommands(): ?array
    {
        $this->loadCache();
        return $this->commandsCache ?: null;
    }

    public function setCachedCommands(array $commands, array $fileTimestamps): void
    {
        $this->commandsCache = $commands;
        $this->saveCache(
            self::COMMANDS_CACHE_FILE,
            ['commands' => $commands, 'timestamps' => $fileTimestamps]
        );
    }

    public function getCachedArguments(string $commandName): ?array
    {
        $this->loadCache();
        return $this->argumentsCache[$commandName] ?? null;
    }

    public function setCachedArguments(string $commandName, array $arguments, array $fileTimestamps): void
    {
        $this->argumentsCache[$commandName] = $arguments;
        $this->saveCache(
            self::ARGUMENTS_CACHE_FILE,
            ['arguments' => $this->argumentsCache, 'timestamps' => $fileTimestamps]
        );
    }

    public function getCachedPhpExecutable(): ?string
    {
        $this->loadCache();
        return $this->phpExecutable;
    }

    public function setCachedPhpExecutable(string $executable): void
    {
        $this->phpExecutable = $executable;
        $this->saveCache(
            self::PHP_EXECUTABLE_CACHE_FILE,
            ['executable' => $executable]
        );
    }

    public function clearCache(): void
    {
        $this->commandsCache = [];
        $this->argumentsCache = [];
        $this->phpExecutable = null;

        foreach ([self::COMMANDS_CACHE_FILE, self::ARGUMENTS_CACHE_FILE, self::PHP_EXECUTABLE_CACHE_FILE] as $file) {
            if (FileExistenceCache::exists($file)) {
                @unlink($file);
            }
        }
    }

    private function isCacheValid(array $timestamps): bool
    {
        foreach ($timestamps as $file => $cachedTime) {
            if (!FileExistenceCache::exists($file) || filemtime($file) > $cachedTime) {
                return false;
            }
        }
        return true;
    }

    private function getFileTimestamps(array $files): array
    {
        $timestamps = [];
        foreach ($files as $file) {
            if (FileExistenceCache::exists($file)) {
                $timestamps[$file] = filemtime($file);
            }
        }
        return $timestamps;
    }

    public function getCommandFilesTimestamps(): array
    {
        $commandFiles = $this->getAllCommandFiles();
        return $this->getFileTimestamps($commandFiles);
    }

    private function getAllCommandFiles(): array
    {
        $files = [];
        
        // App commands directory
        $appCommandsPath = BASE_PATH . '/app/Commands';
        if (is_dir($appCommandsPath)) {
            $files = array_merge($files, $this->scanDirectoryForCommands($appCommandsPath));
        }

        // Module command directories
        $modulesPath = BASE_PATH . '/modules';
        if (is_dir($modulesPath)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($modulesPath)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && 
                    $file->getExtension() === 'php' && 
                    str_ends_with($file->getFilename(), 'Command.php')) {
                    $files[] = $file->getRealPath();
                }
            }
        }

        return array_unique($files);
    }

    private function scanDirectoryForCommands(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && 
                $file->getExtension() === 'php' && 
                str_ends_with($file->getFilename(), 'Command.php')) {
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }

    private function saveCache(string $file, array $data): void
    {
        $directory = dirname($file);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $fp = fopen($file, 'c+');
        if ($fp && flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, '<?php return ' . var_export($data, true) . ';');
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        } else {
            file_put_contents($file, '<?php return ' . var_export($data, true) . ';');
        }
    }
}