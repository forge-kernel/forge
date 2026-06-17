<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Controllers\Hub\Traits;

use App\Modules\ForgeEvents\Services\QueueHubService;
use App\Modules\ForgeWire\Attributes\Action;

/**
 * Trait for job-related actions (retry, delete, trigger, view)
 */
trait QueueJobActions
{
    abstract protected function getQueueService(): QueueHubService;
    abstract protected function loadJobs(): void;
    abstract protected function loadStats(): void;
    abstract protected function flash(string $type, string $message): void;

    #[Action]
    public function retryJob(int $jobId): void
    {
        if ($this->getQueueService()->retryJob($jobId)) {
            $this->flash('success', 'Job queued for retry');
        } else {
            $this->flash('error', 'Failed to retry job');
        }
        $this->loadJobs();
        $this->loadStats();
    }

    #[Action]
    public function deleteJob(int $jobId): void
    {
        if ($this->getQueueService()->deleteJob($jobId)) {
            $this->flash('success', 'Job deleted successfully');
            $this->removeSelectedJob($jobId);
        } else {
            $this->flash('error', 'Failed to delete job');
        }
        $this->loadJobs();
        $this->loadStats();
    }

    #[Action]
    public function triggerJob(int $jobId): void
    {
        if ($this->getQueueService()->triggerJob($jobId)) {
            $this->flash('success', 'Job triggered successfully');
        } else {
            $this->flash('error', 'Failed to trigger job');
        }
        $this->loadJobs();
        $this->loadStats();
    }

    #[Action]
    public function viewJob(int $jobId): void
    {
        $details = $this->getQueueService()->getJobDetails($jobId);
        if ($details) {
            $this->setJobDetails($details);
            $this->setShowJobModal(true);
        } else {
            $this->flash('error', 'Job not found');
        }
    }

    #[Action]
    public function closeJobModal(): void
    {
        $this->setShowJobModal(false);
        $this->clearJobDetails();
    }

    abstract protected function setShowJobModal(bool $show): void;

    abstract protected function removeSelectedJob(int $jobId): void;
    abstract protected function setJobDetails(array $details): void;
    abstract protected function clearJobDetails(): void;
}
