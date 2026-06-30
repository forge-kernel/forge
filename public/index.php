<?php

declare(strict_types=1);

define("BASE_PATH", dirname(__DIR__));

require_once BASE_PATH . "/kernel/Core/Support/helpers.php";
require BASE_PATH . "/kernel/Core/Autoloader.php";

\Forge\Core\Autoloader::register();

$maintenanceFile = BASE_PATH . '/storage/framework/maintenance.html';
if (file_exists($maintenanceFile)) {
    readfile($maintenanceFile);
    exit;
}

\Forge\Core\Kernel::init();
