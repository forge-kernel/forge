<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeAuth\Enums\Permission;
use App\Modules\ForgeAuth\Enums\Role;
use App\Modules\ForgeHub\Services\HubItemRegistry;
use App\Modules\ForgeHub\Services\LogService;
use App\Modules\ForgeHub\Services\CacheService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Helpers\Framework;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Attributes\RequiresRole;
use App\Modules\ForgeRouter\Http\Response;
use Forge\Core\Module\ModuleLoader\Loader;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Service]
#[Middleware(['web', 'auth', 'role', 'hub-permissions'])]
#[RequiresRole(Role::ADMIN->value)]

final class DashboardController
{
    use ControllerHelper;

    public function __construct(
        private readonly HubItemRegistry $registry,
        private readonly Loader $loader,
        private readonly LogService $logService,
        private readonly Container $container
    ) {
    }

    #[Route(
        path: "/hub",
        permissions: [Permission::HUB_PERMISSIONS->value]
    )]
    #[Layout('ForgeHub:hub')]
    public function index(): Response
    {

        $modules = $this->loader->getSortedModuleRegistry();
        $hubItems = $this->registry->getHubItems();
        $logFiles = $this->logService->getLogFiles();

        $cacheStats = null;
        if ($this->container->has(CacheService::class)) {
            try {
                $cacheService = $this->container->get(CacheService::class);
                $cacheStats = $cacheService->getStats();
            } catch (\Throwable) {
            }
        }

        $queueStats = null;
        if ($this->container->has(\App\Modules\ForgeEvents\Services\QueueHubService::class)) {
            try {
                $queueService = $this->container->get(\App\Modules\ForgeEvents\Services\QueueHubService::class);
                $queueStats = $queueService->getStats();
            } catch (\Throwable) {
            }
        }

        $data = [
            'title' => 'Dashboard',
            'phpVersion' => phpversion(),
            'frameworkVersion' => Framework::version(),
            'moduleCount' => count($modules),
            'hubItemCount' => count($hubItems),
            'logFileCount' => count($logFiles),
            'cacheStats' => $cacheStats,
            'queueStats' => $queueStats,
            'hubItems' => $hubItems,
        ];

        return $this->view(view: "pages/dashboard", data: $data);
    }
}
