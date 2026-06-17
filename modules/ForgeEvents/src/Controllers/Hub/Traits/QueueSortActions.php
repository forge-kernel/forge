<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Controllers\Hub\Traits;

use App\Modules\ForgeWire\Attributes\Action;

/**
 * Trait for sorting and pagination actions
 */
trait QueueSortActions
{
  abstract protected function getSortColumn(): string;
  abstract protected function getSortDirection(): string;
  abstract protected function setSortColumn(string $column): void;
  abstract protected function setSortDirection(string $direction): void;
  abstract protected function setCurrentPage(int $page): void;
  abstract protected function loadJobs(): void;

  #[Action]
  public function sort(string $column): void
  {
    if ($this->getSortColumn() === $column) {
      $this->setSortDirection($this->getSortDirection() === 'asc' ? 'desc' : 'asc');
    } else {
      $this->setSortColumn($column);
      $this->setSortDirection('asc');
    }
    $this->loadJobs();
  }

  #[Action]
  public function changePage(int $page): void
  {
    $this->setCurrentPage(max(1, $page));
    $this->loadJobs();
  }
}
