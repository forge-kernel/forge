<?php

declare(strict_types=1);

namespace App\Modules\ForgeLogger\Drivers;

use App\Modules\ForgeLogger\Contracts\LogDriverInterface;
use Forge\Core\Helpers\FileExistenceCache;

final class FileDriver implements LogDriverInterface
{
    public function __construct(private string $logPath)
    {
    }
    public function write(string $message): void
    {
        $directory = dirname($this->logPath);

        if (!FileExistenceCache::isDir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($this->logPath, $message.PHP_EOL, FILE_APPEND);
    }
}
