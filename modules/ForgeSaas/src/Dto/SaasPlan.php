<?php

declare(strict_types=1);

namespace Modules\ForgeSaas\Dto;

final readonly class SaasPlan
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public array $features,
        public array $limits,
        public bool $isActive,
    ) {}

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features, true);
    }

    public function limitFor(string $resource): int
    {
        return $this->limits[$resource] ?? PHP_INT_MAX;
    }
}
