<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Services;

use Modules\ForgeBilling\Dto\BillingPlan;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\Helpers\UUID;

final class BillingPlanService
{
    public function __construct(
        private readonly QueryBuilderInterface $centralQueryBuilder,
    ) {
    }

    public function getAll(): array
    {
        $rows = $this->centralQueryBuilder->setTable('billing_plans')->get();
        return array_map(fn(array $row) => $this->toDto($row), $rows);
    }

    public function getById(string $id): ?BillingPlan
    {
        $row = $this->centralQueryBuilder->setTable('billing_plans')
            ->where('id', '=', $id)
            ->first();
        return $row ? $this->toDto($row) : null;
    }

    public function getBySlug(string $slug): ?BillingPlan
    {
        $row = $this->centralQueryBuilder->setTable('billing_plans')
            ->where('slug', '=', $slug)
            ->first();
        return $row ? $this->toDto($row) : null;
    }

    public function create(
        string $name,
        string $slug,
        float $amount,
        string $currency,
        string $interval,
        array $features = [],
    ): BillingPlan {
        $id = 'plan-' . UUID::generate();
        $data = [
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'amount' => $amount,
            'currency' => $currency,
            'interval' => $interval,
            'features' => json_encode($features),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->centralQueryBuilder->setTable('billing_plans')->insert($data);
        return $this->toDto($data);
    }

    public function disable(string $id): bool
    {
        return (bool) $this->centralQueryBuilder->setTable('billing_plans')
            ->where('id', '=', $id)
            ->update(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function delete(string $id): bool
    {
        return (bool) $this->centralQueryBuilder->setTable('billing_plans')
            ->where('id', '=', $id)
            ->delete();
    }

    private function toDto(array $row): BillingPlan
    {
        return new BillingPlan(
            id: $row['id'],
            name: $row['name'],
            slug: $row['slug'],
            amount: (float) $row['amount'],
            currency: $row['currency'],
            interval: $row['interval'],
            features: json_decode($row['features'] ?? '[]', true),
            isActive: (bool) ($row['is_active'] ?? true),
            createdAt: isset($row['created_at'])
            ? new \DateTimeImmutable($row['created_at'])
            : null,
        );
    }
}
