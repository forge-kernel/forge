<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Controllers\Hub\Traits;

use App\Modules\ForgeEvents\Services\QueueHubService;
use App\Modules\ForgeWire\Attributes\Action;

/**
 * Trait for bulk operations on selected jobs
 */
trait QueueBulkActions
{
    abstract protected function getQueueService(): QueueHubService;
    abstract protected function getSelectedJobs(): array;
    abstract protected function setSelectedJobs(array $jobs): void;
    abstract protected function loadJobs(): void;
    abstract protected function loadStats(): void;
    abstract protected function flash(string $type, string $message): void;

    #[Action]
    public function bulkRetry(): void
    {
        if (empty($this->getSelectedJobs())) {
            $this->flash('warning', 'No jobs selected');
            return;
        }

        $successCount = 0;
        foreach ($this->getSelectedJobs() as $jobId) {
            if ($this->getQueueService()->retryJob($jobId)) {
                $successCount++;
            }
        }

        if ($successCount > 0) {
            $this->flash('success', "{$successCount} job(s) queued for retry");
        } else {
            $this->flash('error', 'Failed to retry selected jobs');
        }

        $this->setSelectedJobs([]);
        $this->loadJobs();
        $this->loadStats();
    }

    #[Action]
    public function bulkDelete(): void
    {
        if (empty($this->getSelectedJobs())) {
            $this->flash('warning', 'No jobs selected');
            return;
        }

        $successCount = 0;
        foreach ($this->getSelectedJobs() as $jobId) {
            if ($this->getQueueService()->deleteJob($jobId)) {
                $successCount++;
            }
        }

        if ($successCount > 0) {
            $this->flash('success', "{$successCount} job(s) deleted successfully");
        } else {
            $this->flash('error', 'Failed to delete selected jobs');
        }

        $this->setSelectedJobs([]);
        $this->loadJobs();
        $this->loadStats();
    }
}
