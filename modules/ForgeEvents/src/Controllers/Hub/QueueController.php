<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Controllers\Hub;

use App\Modules\ForgeEvents\Controllers\Hub\Traits\QueueBulkActions;
use App\Modules\ForgeEvents\Controllers\Hub\Traits\QueueFilterActions;
use App\Modules\ForgeEvents\Controllers\Hub\Traits\QueueJobActions;
use App\Modules\ForgeEvents\Controllers\Hub\Traits\QueueSelectionActions;
use App\Modules\ForgeEvents\Controllers\Hub\Traits\QueueSortActions;
use App\Modules\ForgeEvents\Services\QueueHubService;
use App\Modules\ForgeSqlOrm\ORM\Paginator;
use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Traits\ReactiveControllerHelper;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Reactive]
#[Middleware(['web', 'auth', 'hub-permissions'])]
final class QueueController
{
    use ControllerHelper;
    use ReactiveControllerHelper;
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

    #[Route("/hub/queues")]
    #[Layout("ForgeHub:hub")]
    public function index(): Response
    {
        $this->loadJobs();
        $this->loadStats();

        $queues = $this->queueService->getQueues();

        return $this->view("pages/hub/queues", [
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
