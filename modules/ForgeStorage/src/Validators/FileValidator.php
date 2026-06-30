<?php

declare(strict_types=1);

namespace Modules\ForgeStorage\Validators;

use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Injectable;
use Modules\ForgeRouter\Http\UploadedFile;
use RuntimeException;

#[Injectable]
final class FileValidator
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function validate(UploadedFile $file, ?string $location = null): void
    {
        $config = $this->getLocationConfig($location);
        $maxSize = $config['max_size'] ?? $this->config->get('forge_storage.max_size', 10485760);
        $allowedTypes = $config['allowed_types'] ?? $this->config->get('forge_storage.allowed_types', '*');

        if ($file->getSize() > $maxSize) {
            throw new RuntimeException(
                "File size exceeds maximum allowed size of " . $this->formatBytes($maxSize)
            );
        }

        if ($allowedTypes !== '*' && !empty($allowedTypes)) {
            $mimeType = $file->getClientMediaType();
            $allowedArray = is_array($allowedTypes) ? $allowedTypes : explode(',', (string) $allowedTypes);
            $allowedArray = array_map('trim', $allowedArray);

            if (!in_array($mimeType, $allowedArray, true)) {
                throw new RuntimeException(
                    "File type '{$mimeType}' is not allowed. Allowed types: " . implode(', ', $allowedArray)
                );
            }
        }
    }

    private function getLocationConfig(?string $location): array
    {
        if ($location === null) {
            return [];
        }

        $locations = $this->config->get('forge_storage.locations', []);
        return $locations[$location] ?? [];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
