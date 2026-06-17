<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Sources;

final class LocalSource extends AbstractSource
{
    private string $basePath;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->basePath = rtrim($config['path'] ?? '', '/\\');
    }

    public function fetchManifest(string $path): ?array
    {
        $manifestPath = $this->basePath . '/' . $this->sanitizePath($path) . '/modules.json';
        
        if (!file_exists($manifestPath) || !is_readable($manifestPath)) {
            return null;
        }

        $realPath = realpath($manifestPath);
        $realBase = realpath($this->basePath);
        
        if (!$realPath || !$realBase || strpos($realPath, $realBase) !== 0) {
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
        $zipPath = $this->basePath . '/modules/' . $modulePath . '/' . $zipName;

        $realPath = realpath($zipPath);
        $realBase = realpath($this->basePath);
        
        if (!$realPath || !$realBase || strpos($realPath, $realBase) !== 0) {
            return false;
        }

        if (!file_exists($realPath) || !is_readable($realPath)) {
            return false;
        }

        if (!copy($realPath, $destinationPath)) {
            return false;
        }

        return $this->calculateIntegrity($destinationPath);
    }

    public function fetchModulesJson(): ?array
    {
        $modulesJsonPath = $this->basePath . '/modules.json';
        
        if (!file_exists($modulesJsonPath) || !is_readable($modulesJsonPath)) {
            return null;
        }

        $realPath = realpath($modulesJsonPath);
        $realBase = realpath($this->basePath);
        
        if (!$realPath || !$realBase || strpos($realPath, $realBase) !== 0) {
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
}

