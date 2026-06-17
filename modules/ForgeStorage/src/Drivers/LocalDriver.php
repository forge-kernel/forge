<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Drivers;

use App\Modules\ForgeStorage\Contracts\StorageDriverInterface;
use Forge\Core\Config\Config;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Traits\FileHelper;

class LocalDriver implements StorageDriverInterface
{
    use FileHelper;

    private string $root;
    private string $publicPath;

    public function __construct(
        private readonly Config $config
    )
    {
        $rootPath = $this->config->get('forge_storage.providers.local.root_path', 'storage/app');
        $publicPath = $this->config->get('forge_storage.providers.local.public_path', 'public/storage');

        $rootPath = is_string($rootPath) ? $rootPath : 'storage/app';
        $publicPath = is_string($publicPath) ? $publicPath : 'public/storage';

        $this->root = BASE_PATH . '/' . $rootPath;
        $this->publicPath = BASE_PATH . '/' . $publicPath;
    }

    public function get(string $path)
    {
        $fullPath = $this->root . '/' . $path;
        if (!FileExistenceCache::exists($fullPath)) {
            throw new \RuntimeException("File not found: {$path}");
        }
        return file_get_contents($fullPath);
    }

    public function exists(string $path): bool
    {
        return FileExistenceCache::exists($this->root . '/' . $path);
    }

    public function put(string $path, $contents, array $options = []): bool
    {
        $fullPath = $this->root . '/' . $path;
        $this->ensureDirectoryExists(dirname($fullPath));
        return file_put_contents($fullPath, $contents) !== false;
    }

    public function delete(string $path): bool
    {
        $fullPath = $this->root . '/' . $path;
        return FileExistenceCache::exists($fullPath) && unlink($fullPath);
    }

    public function getUrl(string $path): string
    {
        return "/storage/{$path}";
    }

    public function signedUrl(string $path, int $expires): string
    {
        $token = hash_hmac('sha256', "{$path}|{$expires}", env('APP_KEY', ''));
        return "/storage/signed/{$path}?expires={$expires}&signature={$token}";
    }

    public function getMetadata(string $path): array
    {
        $fullPath = $this->root . '/' . $path;

        if (!FileExistenceCache::exists($fullPath)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        $stat = stat($fullPath);
        $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
        $etag = md5_file($fullPath);

        return [
            'size' => $stat['size'],
            'mime_type' => $mimeType,
            'etag' => $etag,
            'last_modified' => date('Y-m-d H:i:s', $stat['mtime']),
        ];
    }

    public function copy(string $sourcePath, string $destPath): bool
    {
        $sourceFullPath = $this->root . '/' . $sourcePath;
        $destFullPath = $this->root . '/' . $destPath;

        if (!FileExistenceCache::exists($sourceFullPath)) {
            return false;
        }

        $this->ensureDirectoryExists(dirname($destFullPath));
        return copy($sourceFullPath, $destFullPath);
    }

    public function list(string $prefix = '', int $maxKeys = 1000): array
    {
        if (!is_dir($this->root)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $count = 0;
        foreach ($iterator as $file) {
            if ($count >= $maxKeys) {
                break;
            }

            if ($file->isFile()) {
                $relativePath = str_replace($this->root . '/', '', $file->getPathname());

                if ($prefix !== '' && !str_starts_with($relativePath, $prefix)) {
                    continue;
                }

                $stat = $file->getStat();
                $mimeType = mime_content_type($file->getPathname()) ?: 'application/octet-stream';

                $files[] = [
                    'path' => $relativePath,
                    'size' => $stat['size'],
                    'mime_type' => $mimeType,
                    'last_modified' => date('Y-m-d H:i:s', $stat['mtime']),
                ];
                $count++;
            }
        }

        return $files;
    }
}
