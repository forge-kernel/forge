<?php

declare(strict_types=1);

namespace Modules\ForgePackageManager\Sources;

interface SourceInterface
{
    public function fetchManifest(string $path): ?array;
    
    public function fetchModulesJson(): ?array;
    
    public function downloadModule(string $sourcePath, string $destinationPath, ?string $version = null): bool|string;
    
    public function supportsVersioning(): bool;
    
    public function validateConnection(): bool;
}

