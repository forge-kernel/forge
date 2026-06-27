<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\AppAuth\Services\UserContext;
use App\Modules\ForgeAuth\Enums\Role;
use App\Modules\ForgeHub\Services\ObservabilityServiceInterface;
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
#[Layout("ForgeComponents:admin-default")]
final class ObservabilityController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly ObservabilityServiceInterface $observabilityService,
        private readonly UserContext $userContext,
    ) {
    }

    #[Endpoint("/observability")]
    public function dashboard(Request $request): Response
    {
        $stats = $this->observabilityService->getDashboardStats(hours: 24);
        $recentSlowTraces = $this->observabilityService->getTraces(
            filters: ['status' => 'error'],
            page: 1,
            perPage: 5,
        );
        if (empty($recentSlowTraces['data'])) {
            $recentSlowTraces = $this->observabilityService->getTraces(
                filters: ['min_duration' => 200],
                page: 1,
                perPage: 5,
            );
        }

        $data = [
            'title' => 'Observability',
            'stats' => $stats,
            'recentTraces' => $recentSlowTraces['data'] ?? [],
            'activeItem' => 'overview',
            'breadcrumbs' => [
                ['label' => 'Hub', 'href' => '/hub'],
                ['label' => 'Observability', 'active' => true],
            ],
            'user' => $this->userContext->current(),
        ];

        return $this->view(view: "observability/index", data: $data);
    }

    #[Endpoint("/observability/traces")]
    public function traces(Request $request): Response
    {
        $page = (int) ($request->queryParams['page'] ?? 1);
        $filters = [
            'path' => $request->queryParams['path'] ?? '',
            'status' => $request->queryParams['status'] ?? '',
            'min_duration' => $request->queryParams['min_duration'] ?? '',
        ];
        $filters = array_filter($filters);

        $result = $this->observabilityService->getTraces(filters: $filters, page: $page, perPage: 25);

        $data = [
            'title' => 'Traces',
            'traces' => $result,
            'filters' => $filters,
            'activeItem' => 'traces',
            'breadcrumbs' => [
                ['label' => 'Hub', 'href' => '/hub'],
                ['label' => 'Observability', 'href' => '/hub/observability'],
                ['label' => 'Traces', 'active' => true],
            ],
            'user' => $this->userContext->current(),
        ];

        return $this->view(view: "observability/traces", data: $data);
    }

    #[Endpoint("/observability/traces/{id}")]
    public function traceDetail(Request $request, string $id): Response
    {
        $trace = $this->observabilityService->getTraceDetail($id);
        if ($trace === null) {
            return $this->view(view: "observability/trace-detail", data: [
                'title' => 'Trace Not Found',
                'trace' => null,
                'activeItem' => 'traces',
                'breadcrumbs' => [
                    ['label' => 'Hub', 'href' => '/hub'],
                    ['label' => 'Observability', 'href' => '/hub/observability'],
                    ['label' => 'Traces', 'href' => '/hub/observability/traces'],
                    ['label' => 'Not Found', 'active' => true],
                ],
                'user' => $this->userContext->current(),
            ]);
        }

        $data = [
            'title' => 'Trace Detail',
            'trace' => $trace,
            'activeItem' => 'traces',
            'breadcrumbs' => [
                ['label' => 'Hub', 'href' => '/hub'],
                ['label' => 'Observability', 'href' => '/hub/observability'],
                ['label' => 'Traces', 'href' => '/hub/observability/traces'],
                ['label' => $trace['id'], 'active' => true],
            ],
            'user' => $this->userContext->current(),
        ];

        return $this->view(view: "observability/trace-detail", data: $data);
    }

    #[Endpoint("/observability/slow-queries")]
    public function slowQueries(Request $request): Response
    {
        $minDuration = (float) ($request->queryParams['min_duration'] ?? 100);
        $queries = $this->observabilityService->getSlowQueries(limit: 50, minDurationMs: $minDuration);

        $data = [
            'title' => 'Slow Queries',
            'queries' => $queries,
            'minDuration' => $minDuration,
            'activeItem' => 'slow-queries',
            'breadcrumbs' => [
                ['label' => 'Hub', 'href' => '/hub'],
                ['label' => 'Observability', 'href' => '/hub/observability'],
                ['label' => 'Slow Queries', 'active' => true],
            ],
            'user' => $this->userContext->current(),
        ];

        return $this->view(view: "observability/slow-queries", data: $data);
    }

    #[Endpoint("/observability/api/stats", "GET")]
    public function stats(Request $request): Response
    {
        $hours = (int) ($request->queryParams['hours'] ?? 24);
        return $this->jsonResponse([
            'success' => true,
            'stats' => $this->observabilityService->getDashboardStats($hours),
        ]);
    }

}
