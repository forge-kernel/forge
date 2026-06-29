<?php

declare(strict_types=1);

namespace Modules\ForgeDebugBar\Controllers\Hub;

use Modules\ForgeDebugBar\Services\DebugBarHubService;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

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
