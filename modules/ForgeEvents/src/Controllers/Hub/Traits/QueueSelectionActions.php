<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Controllers\Hub\Traits;

use App\Modules\ForgeWire\Attributes\Action;

/**
 * Trait for job selection actions
 */
trait QueueSelectionActions
{
    abstract protected function getSelectedJobs(): array;
    abstract protected function setSelectedJobs(array $jobs): void;
    abstract protected function getJobs(): array;
    abstract protected function loadJobs(): void;

    #[Action]
    public function toggleJobSelection(int $jobId): void
    {
        $selected = $this->getSelectedJobs();
        $index = array_search($jobId, $selected);

        if ($index !== false) {
            unset($selected[$index]);
            $this->setSelectedJobs(array_values($selected));
        } else {
            $selected[] = $jobId;
            $this->setSelectedJobs($selected);
        }
    }

    #[Action]
    public function selectAll(): void
    {
        $jobs = $this->getJobs();
        if (empty($jobs)) {
            $this->loadJobs();
            $jobs = $this->getJobs();
        }
        $this->setSelectedJobs(array_column($jobs, 'id'));
    }

    #[Action]
    public function deselectAll(): void
    {
        $this->setSelectedJobs([]);
    }
}
