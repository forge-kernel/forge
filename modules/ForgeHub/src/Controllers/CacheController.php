<?php

declare(strict_types=1);

namespace Modules\ForgeHub\Controllers;

use Modules\ForgeAuth\Enums\Permission;
use Modules\ForgeAuth\Enums\Role;
use Modules\ForgeHub\Services\CacheService;
use Modules\ForgeHub\Services\EnhancedCacheService;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Attributes\RequiresRole;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

#[Routable]
#[UseMiddleware(['web', 'auth', 'role', 'hub-permissions'])]
#[RequiresRole(Role::ADMIN->value)]

final class CacheController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly CacheService $cacheService,
        private readonly EnhancedCacheService $enhancedCacheService
    ) {
    }

    #[Endpoint(
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

        return $this->view(view: "cache", data: $data);
    }

    #[Endpoint(
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

    #[Endpoint(
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

    #[Endpoint(
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
