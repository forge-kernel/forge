<?php

declare(strict_types=1);

namespace App\Modules\ForgeDebugBar\Controllers\Hub;

use App\Modules\ForgeDebugBar\Services\DebugBarHubService;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;

#[Routable(prefix: '/hub')]
#[UseMiddleware(['web', 'auth'])]
final class DebugBarController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly DebugBarHubService $hubService
    ) {
    }

    #[Endpoint(path: "/debugbar")]
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

        return $this->view(view: "hub/debugbar", data: $data);
    }
}
