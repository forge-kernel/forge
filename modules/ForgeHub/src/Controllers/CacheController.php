<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeAuth\Enums\Permission;
use App\Modules\ForgeAuth\Enums\Role;
use App\Modules\ForgeHub\Services\CacheService;
use App\Modules\ForgeHub\Services\EnhancedCacheService;
use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Attributes\RequiresRole;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Service]
#[Middleware(['web', 'auth', 'role', 'hub-permissions'])]
#[RequiresRole(Role::ADMIN->value)]

final class CacheController
{
    use ControllerHelper;

    public function __construct(
        private readonly CacheService $cacheService,
        private readonly EnhancedCacheService $enhancedCacheService
    ) {
    }

    #[Route(
        path: "/hub/cache",
        permissions: [Permission::HUB_PERMISSIONS->value]
    )]
    #[Layout("ForgeHub:hub")]
    public function index(Request $request): Response
    {
        $stats = $this->cacheService->getStats();
        $details = $this->enhancedCacheService->getDetailedStats();
        $tags = $this->enhancedCacheService->getAvailableTags();

        $data = [
            'title' => 'Cache Management',
            'stats' => $stats,
            'details' => $details,
            'tags' => $tags,
        ];

        return $this->view(view: "pages/cache", data: $data);
    }

    #[Route(
        path: "/hub/cache/clear",
        method: "POST",
        permissions: [Permission::HUB_PERMISSIONS->value]
    )]
    public function clear(Request $request): Response
    {
        $this->cacheService->clearAll();

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Cache cleared successfully',
        ]);
    }

    #[Route(
        path: "/hub/cache/clear-expired",
        method: "POST",
        permissions: [Permission::HUB_PERMISSIONS->value]
    )]
    public function clearExpired(Request $request): Response
    {
        $hours = (int) ($request->postData['hours'] ?? 24);
        $this->cacheService->clearExpired($hours);

        return $this->jsonResponse([
            'success' => true,
            'message' => "Cleared cache entries older than {$hours} hours",
        ]);
    }

    #[Route(
        path: "/hub/cache/clear-tag",
        method: "POST",
        permissions: [Permission::HUB_PERMISSIONS->value]
    )]
    public function clearTag(Request $request): Response
    {
        $tag = $request->postData['tag'] ?? null;

        if (!$tag) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Tag is required',
            ], 400);
        }

        $this->enhancedCacheService->clearByTag($tag);

        return $this->jsonResponse([
            'success' => true,
            'message' => "Cache tag '{$tag}' cleared successfully",
        ]);
    }
}
