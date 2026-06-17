<?php
declare(strict_types=1);

namespace App\Modules\ForgeTesting\Traits;

trait CacheTesting
{
    protected function flushCache(): void
    {
        $paths = [
            BASE_PATH . "/storage/framework/cache/data",
            BASE_PATH . "/bootstrap/cache/routes.php",
            BASE_PATH . "/bootstrap/cache/config.php",
        ];

        foreach ($paths as $path) {
            if (!is_dir($path) && !is_file($path)) {
                continue;
            }
            is_file($path) ? @unlink($path) : $this->recursiveDelete($path);
        }
    }

    protected function clearLogs(): void
    {
        $logPath = BASE_PATH . "/storage/logs";
        if (!is_dir($logPath)) {
            return;
        }
        foreach (glob($logPath . "/*.log") as $file) {
            @unlink($file);
        }
    }

    private function recursiveDelete(string $dir): void
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $dir,
                \FilesystemIterator::SKIP_DOTS,
            ),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $file) {
            $file->isDir()
                ? rmdir($file->getRealPath())
                : unlink($file->getRealPath());
        }
    }
}
