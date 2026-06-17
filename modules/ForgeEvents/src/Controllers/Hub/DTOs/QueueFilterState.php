<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Controllers\Hub\DTOs;

/**
 * DTO for queue filter state
 * Groups related filter properties together
 */
final class QueueFilterState
{
    public function __construct(
        public string $statusFilter = '',
        public string $queueFilter = '',
        public string $search = '',
    ) {
    }

    public function toArray(): array
    {
        return [
            'status' => $this->statusFilter,
            'queue' => $this->queueFilter,
            'search' => $this->search,
        ];
    }

    public function isEmpty(): bool
    {
        return empty($this->statusFilter) && empty($this->queueFilter) && empty($this->search);
    }

    public function clear(): void
    {
        $this->statusFilter = '';
        $this->queueFilter = '';
        $this->search = '';
    }
}
