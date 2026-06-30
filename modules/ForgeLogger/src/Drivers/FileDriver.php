<?php

declare(strict_types=1);

namespace Modules\ForgeLogger\Drivers;

use Modules\ForgeLogger\Contracts\LogDriverInterface;
use Forge\Core\Helpers\FileExistenceCache;

final class FileDriver implements LogDriverInterface
{
    public function __construct(
        private string $logPath,
        private int $maxFileSize = 0,
    ) {
    }

    public function write(string $message): void
    {
        $directory = dirname($this->logPath);

        if (!FileExistenceCache::isDir($directory)) {
            mkdir($directory, 0755, true);
        }

        if ($this->maxFileSize > 0 && file_exists($this->logPath)) {
            $this->rotateIfNeeded();
        }

        file_put_contents($this->logPath, $message.PHP_EOL, FILE_APPEND);
    }

    private function rotateIfNeeded(): void
    {
        if (filesize($this->logPath) < $this->maxFileSize) {
            return;
        }

        $rotatedPath = $this->logPath . '.1';

        if (file_exists($rotatedPath)) {
            unlink($rotatedPath);
        }

        rename($this->logPath, $rotatedPath);
    }
}
