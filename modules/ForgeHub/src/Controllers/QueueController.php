<?php

declare(strict_types=1);

namespace Modules\ForgeHub\Controllers;

use Modules\ForgeHub\Controllers\Traits\QueueBulkActions;
use Modules\ForgeHub\Controllers\Traits\QueueFilterActions;
use Modules\ForgeHub\Controllers\Traits\QueueJobActions;
use Modules\ForgeHub\Controllers\Traits\QueueSelectionActions;
use Modules\ForgeHub\Controllers\Traits\QueueSortActions;
use Modules\ForgeEvents\Services\QueueHubService;
use Modules\ForgeSqlOrm\ORM\Paginator;
use Modules\ForgeWire\Attributes\Reactive;
use Modules\ForgeWire\Attributes\State;
use Modules\ForgeWire\Traits\WithWireResponse;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

#[Routable(prefix: '/hub')]
#[Reactive]
#[UseMiddleware(['web', 'auth', 'hub-permissions'])]
final class QueueController
{
    use ResponseHelper;
    use ViewHelper;
    use WithWireResponse;
    use QueueJobActions;
    use QueueBulkActions;
    use QueueFilterActions;
    use QueueSelectionActions;
    use QueueSortActions;

    #[State(shared: true)]
    public array $jobs = [];

    #[State]
    public string $statusFilter = '';

    #[State]
    public string $queueFilter = '';

    #[State]
    public string $search = '';

    #[State]
    public string $sortColumn = 'created_at';

    #[State]
    public string $sortDirection = 'desc';

    #[State]
    public int $currentPage = 1;

    #[State]
    public int $perPage = 10;

    #[State]
    public array $selectedJobs = [];

    #[State]
    public array $stats = [];

    #[State]
    public bool $showJobModal = false;

    #[State]
    public array $jobDetails = [];

    public ?Paginator $paginator = null;

    public function __construct(
        private readonly QueueHubService $queueService
    ) {
    }

    #[Endpoint("/queues")]
    #[Layout("ForgeHub:hub")]
    public function index(): Response
    {
        $this->loadJobs();
        $this->loadStats();

        $queues = $this->queueService->getQueues();

        return $this->view("queues", [
            'jobs' => $this->jobs,
            'stats' => $this->stats,
            'paginator' => $this->paginator,
            'queues' => $queues,
            'selectedJobs' => $this->selectedJobs,
            'sortColumn' => $this->sortColumn,
            'sortDirection' => $this->sortDirection,
            'search' => $this->search,
            'statusFilter' => $this->statusFilter,
            'queueFilter' => $this->queueFilter,
            'showJobModal' => $this->showJobModal,
            'jobDetails' => $this->jobDetails,
        ]);
    }

    protected function getQueueService(): QueueHubService
    {
        return $this->queueService;
    }

    protected function getSelectedJobs(): array
    {
        return $this->selectedJobs;
    }

    protected function setSelectedJobs(array $jobs): void
    {
        $this->selectedJobs = $jobs;
    }

    protected function getJobs(): array
    {
        return $this->jobs;
    }

    protected function getStatusFilter(): string
    {
        return $this->statusFilter;
    }

    protected function getQueueFilter(): string
    {
        return $this->queueFilter;
    }

    protected function getSearch(): string
    {
        return $this->search;
    }

    protected function setStatusFilter(string $filter): void
    {
        $this->statusFilter = $filter;
    }

    protected function setQueueFilter(string $filter): void
    {
        $this->queueFilter = $filter;
    }

    protected function setSearch(string $search): void
    {
        $this->search = $search;
    }

    protected function getSortColumn(): string
    {
        return $this->sortColumn;
    }

    protected function getSortDirection(): string
    {
        return $this->sortDirection;
    }

    protected function setSortColumn(string $column): void
    {
        $this->sortColumn = $column;
    }

    protected function setSortDirection(string $direction): void
    {
        $this->sortDirection = $direction;
    }

    protected function setCurrentPage(int $page): void
    {
        $this->currentPage = $page;
    }

    protected function removeSelectedJob(int $jobId): void
    {
        $this->selectedJobs = array_filter($this->selectedJobs, fn($id) => $id !== $jobId);
    }

    protected function setJobDetails(array $details): void
    {
        $this->jobDetails = $details;
    }

    protected function clearJobDetails(): void
    {
        $this->jobDetails = [];
    }

    protected function setShowJobModal(bool $show): void
    {
        $this->showJobModal = $show;
    }

    private function loadJobs(): void
    {
        $filters = [
            'status' => $this->statusFilter,
            'queue' => $this->queueFilter,
            'search' => $this->search,
        ];

        $this->paginator = $this->queueService->getJobs(
            $filters,
            $this->sortColumn,
            $this->sortDirection,
            $this->currentPage,
            $this->perPage
        );

        $this->jobs = $this->paginator->items();
    }

    private function loadStats(): void
    {
        $this->stats = $this->queueService->getStats();
    }
}
