<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeAuth\Enums\Role;
use App\Modules\ForgeHub\Services\LogService;
use Forge\Core\Debug\Metrics;
use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Attributes\RequiresRole;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Service]
#[Middleware(['web', 'auth', 'role', 'hub-permissions'])]
#[RequiresRole(Role::ADMIN->value)]

final class StatsController
{
    use ControllerHelper;

    public function __construct(private LogService $logService)
    {
    }

    #[Route("/hub/stats")]
    #[Layout("ForgeHub:hub")]
    public function index(): Response
    {
        return $this->view(view: "pages/stats", data: []);
    }
}
