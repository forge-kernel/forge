<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Controllers\Hub\Traits;

use App\Modules\ForgeWire\Attributes\Action;

/**
 * Trait for filter and search actions
 */
trait QueueFilterActions
{
    abstract protected function getStatusFilter(): string;
    abstract protected function getQueueFilter(): string;
    abstract protected function getSearch(): string;
    abstract protected function setStatusFilter(string $filter): void;
    abstract protected function setQueueFilter(string $filter): void;
    abstract protected function setSearch(string $search): void;
    abstract protected function setCurrentPage(int $page): void;
    abstract protected function loadJobs(): void;

    #[Action]
    public function refresh(): void
    {
        $this->loadJobs();
        $this->loadStats();
    }

    #[Action]
    public function clearFilters(): void
    {
        $this->setStatusFilter('');
        $this->setQueueFilter('');
        $this->setSearch('');
        $this->setCurrentPage(1);
        $this->loadJobs();
    }

    #[Action]
    public function applyFilters(): void
    {
        $this->setCurrentPage(1);
        $this->loadJobs();
    }

    #[Action]
    public function input(...$keys): void
    {
        if (in_array('statusFilter', $keys) || in_array('queueFilter', $keys) || in_array('search', $keys)) {
            $this->setCurrentPage(1);
            $this->loadJobs();
        }
    }

    abstract protected function loadStats(): void;
}
