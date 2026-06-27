<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeAuth\Enums\Role;
use App\Modules\ForgeHub\Services\MonitoringService;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Attributes\RequiresRole;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;

#[Routable(prefix: '/hub')]
#[UseMiddleware(['web', 'auth', 'role', 'hub-permissions'])]
#[RequiresRole(Role::ADMIN->value)]

final class MonitoringController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly MonitoringService $monitoringService
    ) {
    }

    #[Endpoint("/monitoring")]
    #[Layout("ForgeHub:hub")]
    public function index(Request $request): Response
    {
        $metrics = $this->monitoringService->getAllMetrics();

        $data = [
            'title' => 'Monitoring',
            'metrics' => $metrics,
        ];

        return $this->view(view: "monitoring", data: $data);
    }

    #[Endpoint("/monitoring/refresh", "POST")]
    public function refresh(Request $request): Response
    {
        $metrics = $this->monitoringService->getAllMetrics();

        return $this->jsonResponse([
            'success' => true,
            'metrics' => $metrics,
        ]);
    }
}
