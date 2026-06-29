<?php

declare(strict_types=1);

namespace Modules\ForgeHub\Controllers;

use Modules\ForgeAuth\Enums\Role;
use Modules\ForgeHub\Services\MonitoringService;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Attributes\RequiresRole;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

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
