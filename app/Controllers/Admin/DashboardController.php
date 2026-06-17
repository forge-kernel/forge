<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Modules\AppAuth\Services\UserContext;
use App\Modules\ForgeMultiTenant\Attributes\TenantScope;
use App\Modules\ForgeSaas\Attributes\RequiresFeature;
use App\Modules\ForgeSaas\Attributes\RequiresPlan;
use App\Modules\ForgeSaas\Attributes\WithinLimit;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Traits\ControllerHelper;
use Forge\Traits\SecurityHelper;

#[TenantScope("tenant")]
#[Middleware("web")]
#[Layout("ForgeComponents:admin")]
final class DashboardController
{
    use ControllerHelper;
    use SecurityHelper;

    public function __construct(private readonly UserContext $userContext)
    {
    }

    #[Route("/admin/dashboard")]
    public function index(): Response
    {

        $user = $this->userContext->current();

        $sidebarItems = [
            ['url' => '/admin', 'text' => 'Dashboard', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>', 'active' => true],
            ['url' => '/admin/users', 'text' => 'Users', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>'],
            ['url' => '/admin/settings', 'text' => 'Settings', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>'],
        ];

        $stats = [
            ['label' => 'Total Users', 'value' => '1,234', 'trend' => 12, 'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>'],
            ['label' => 'Revenue', 'value' => '$45,231', 'trend' => 8, 'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'],
            ['label' => 'Orders', 'value' => '892', 'trend' => -3, 'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>'],
            ['label' => 'Active Sessions', 'value' => '234', 'trend' => 5, 'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>'],
        ];

        $data = [
            'title' => 'Admin Dashboard',
            'sidebarItems' => $sidebarItems,
            'user' => $user ? [
                'name' => $user['name'] ?? 'User',
                'avatar' => $user['avatar'] ?? null,
                'initials' => strtoupper(substr($user['name'] ?? 'U', 0, 2))
            ] : null,
            'stats' => $stats,
            'breadcrumb' => [
                ['url' => '/admin', 'text' => 'Dashboard']
            ]
        ];

        return $this->view(view: "pages/admin/dashboard", data: $data);
    }

    #[Route("/admin/reports")]
    #[RequiresFeature("advanced_reports")]
    public function reports(): Response
    {
        return $this->view(view: "pages/admin/reports");
    }

    #[Route("/admin/white-label")]
    #[RequiresPlan("enterprise")]
    public function whiteLabel(): Response
    {
        return $this->view(view: "pages/admin/whitelabel");
    }

    #[WithinLimit(resource: "max_users", table: "users")]
    public function createUser(Request $request): Response
    {
        return $this->jsonResponse(['message' => 'User created successfully']);
    }
}
