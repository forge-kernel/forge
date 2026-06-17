<?php

declare(strict_types=1);

namespace App\Modules\ForgeDebugBar\Controllers\Hub;

use App\Modules\ForgeDebugBar\Services\DebugBarHubService;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Middleware('web')]
#[Middleware('auth')]
final class DebugBarController
{
    use ControllerHelper;

    public function __construct(
        private readonly DebugBarHubService $hubService
    ) {
    }

    #[Route(path: "/hub/debugbar")]
    #[Layout("ForgeHub:hub")]
    public function index(Request $request): Response
    {
        $latestData = $this->hubService->getLatestData();
        $formattedData = $this->hubService->formatDataForDisplay($latestData);

        $data = [
            'title' => 'Debug Bar',
            'debugData' => $formattedData,
            'hasData' => $latestData !== null,
        ];

        return $this->view(view: "pages/hub/debugbar", data: $data);
    }
}
