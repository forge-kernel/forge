<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Traits;

use Forge\CLI\Traits\OutputHelper;
use Modules\ForgeRouter\Services\ErrorPageRenderer;

trait ManagesMaintenanceMode
{
    use OutputHelper;

    private const string MAINTENANCE_DEST = BASE_PATH . '/storage/framework/maintenance.html';

    public function enableMaintenance(): int
    {
        $html = ErrorPageRenderer::renderStatic(503);

        $dir = dirname(self::MAINTENANCE_DEST);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        if (file_put_contents(self::MAINTENANCE_DEST, $html) !== false) {
            $this->success("Maintenance mode enabled. File written to " . self::MAINTENANCE_DEST);
            return 0;
        }

        $this->error("Failed to write maintenance file");
        return 1;
    }

    public function disableMaintenance(): int
    {
        if (!file_exists(self::MAINTENANCE_DEST)) {
            $this->error("Maintenance file not found at " . self::MAINTENANCE_DEST);
            return 1;
        }

        if (unlink(self::MAINTENANCE_DEST)) {
            $this->success("Maintenance mode disabled. File deleted from " . self::MAINTENANCE_DEST);
            return 0;
        }

        $this->error("Failed to delete maintenance file");
        return 1;
    }

    public function isMaintenanceEnabled(): bool
    {
        return file_exists(self::MAINTENANCE_DEST);
    }
}
