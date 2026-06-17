<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Sources;

use Forge\CLI\Traits\OutputHelper;

abstract class AbstractSource implements SourceInterface
{
    use OutputHelper;

    protected array $config;
    protected ?string $token = null;
    protected bool $debugEnabled = false;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->token = $config['personal_token'] ?? $config['token'] ?? null;
        $this->debugEnabled = $config['debug'] ?? false;
    }

    protected function debug(string $message, string $context = ''): void
    {
        if (!$this->debugEnabled) {
            return;
        }
        $prefix = $context ? "[{$context}] " : '';
        echo "\033[35m{$prefix}{$message}\033[0m\n";
    }

    protected function calculateIntegrity(string $filePath): string
    {
        return hash_file('sha256', $filePath);
    }

    protected function ensureDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return mkdir($path, 0755, true);
        }
        return true;
    }

    protected function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        return rmdir($dir);
    }

    protected function validateManifest(array $manifest): bool
    {
        return isset($manifest['name']) && 
               isset($manifest['version']) && 
               isset($manifest['type']);
    }

    protected function validateModulesJson(array $data): bool
    {
        return is_array($data);
    }

    protected function sanitizePath(string $path): string
    {
        $path = str_replace(['../', '..\\', '//', '\\\\'], '', $path);
        return trim($path, '/\\');
    }
}

