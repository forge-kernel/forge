<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeAuth\Enums\Role;
use App\Modules\ForgeHub\Services\MonitoringService;
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

final class MonitoringController
{
    use ControllerHelper;

    public function __construct(
        private readonly MonitoringService $monitoringService
    ) {
    }

    #[Route("/hub/monitoring")]
    #[Layout("ForgeHub:hub")]
    public function index(Request $request): Response
    {
        $metrics = $this->monitoringService->getAllMetrics();

        $data = [
            'title' => 'Monitoring',
            'metrics' => $metrics,
        ];

        return $this->view(view: "pages/monitoring", data: $data);
    }

    #[Route("/hub/monitoring/refresh", "POST")]
    public function refresh(Request $request): Response
    {
        $metrics = $this->monitoringService->getAllMetrics();

        return $this->jsonResponse([
            'success' => true,
            'metrics' => $metrics,
        ]);
    }
}
