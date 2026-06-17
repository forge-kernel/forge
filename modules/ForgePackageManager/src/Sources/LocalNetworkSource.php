<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Sources;

final class LocalNetworkSource extends AbstractSource
{
    private string $basePath;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->basePath = $config['path'] ?? '';
    }

    public function fetchManifest(string $path): ?array
    {
        $manifestPath = $this->normalizePath($this->basePath . '/' . $this->sanitizePath($path) . '/modules.json');
        
        if (!file_exists($manifestPath) || !is_readable($manifestPath)) {
            return null;
        }

        $content = file_get_contents($manifestPath);
        if ($content === false) {
            return null;
        }

        $manifest = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !$this->validateManifest($manifest)) {
            return null;
        }

        return $manifest;
    }

    public function downloadModule(string $sourcePath, string $destinationPath, ?string $version = null): bool|string
    {
        $modulePath = $this->sanitizePath($sourcePath);
        
        if ($version === null) {
            $pathParts = explode('/', trim($sourcePath, '/'));
            $version = end($pathParts);
        }
        
        $zipName = $version . '.zip';
        $zipPath = $this->normalizePath($this->basePath . '/modules/' . $modulePath . '/' . $zipName);

        if (!file_exists($zipPath) || !is_readable($zipPath)) {
            return false;
        }

        if (!copy($zipPath, $destinationPath)) {
            return false;
        }

        return $this->calculateIntegrity($destinationPath);
    }

    public function fetchModulesJson(): ?array
    {
        $modulesJsonPath = $this->normalizePath($this->basePath . '/modules.json');
        
        if (!file_exists($modulesJsonPath) || !is_readable($modulesJsonPath)) {
            return null;
        }

        $content = file_get_contents($modulesJsonPath);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !$this->validateModulesJson($data)) {
            return null;
        }

        return $data;
    }

    public function supportsVersioning(): bool
    {
        return true;
    }

    public function validateConnection(): bool
    {
        return is_dir($this->basePath) && is_readable($this->basePath);
    }

    private function normalizePath(string $path): string
    {
        if (strpos($path, 'smb://') === 0) {
            $path = str_replace('smb://', '', $path);
            $path = '/' . str_replace('\\', '/', $path);
        }

        if (strpos($path, '\\\\') === 0) {
            $path = '/' . str_replace('\\', '/', $path);
        }

        return $path;
    }
}

