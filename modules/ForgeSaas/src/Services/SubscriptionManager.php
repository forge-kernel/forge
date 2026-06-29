<?php

declare(strict_types=1);

namespace Modules\ForgeSaas\Services;

use Modules\ForgeSaas\Contracts\SubscriptionManagerInterface;
use Modules\ForgeSaas\Dto\SaasPlan;
use Modules\ForgeSaas\Dto\SaasSubscription;
use Modules\ForgeSaas\Enums\SubscriptionStatus;
use Modules\ForgeMultiTenant\DTO\Tenant;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\Helpers\UUID;

final class SubscriptionManager implements SubscriptionManagerInterface
{
    private ?SaasSubscription $subscription = null;
    private bool $loaded = false;

    public function __construct(
        private readonly QueryBuilderInterface $centralQueryBuilder,
    ) {
    }

    public function forTenant(Tenant $tenant): static
    {
        if ($this->loaded) {
            return $this;
        }

        $this->loaded = true;
        $this->subscription = $this->fetchSubscription($tenant->id);
        return $this;
    }

    public function hasFeature(string $feature): bool
    {
        return $this->currentPlan()?->hasFeature($feature) ?? false;
    }

    public function withinLimit(string $resource, int $currentCount): bool
    {
        $limit = $this->limitFor($resource);
        if ($limit === -1) {
            return true;
        }
        return $currentCount < $limit;
    }

    public function limitFor(string $resource): int
    {
        $limit = $this->currentPlan()?->limitFor($resource) ?? PHP_INT_MAX;
        return $limit === PHP_INT_MAX ? PHP_INT_MAX : (int) $limit;
    }

    public function onPlan(string $planSlug): bool
    {
        return $this->currentPlan()?->slug === $planSlug;
    }

    public function isActive(): bool
    {
        if ($this->subscription === null) {
            return false;
        }
        return in_array($this->subscription->status, [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL], true);
    }

    public function currentPlan(): ?SaasPlan
    {
        return $this->subscription?->plan;
    }

    public function currentSubscription(): ?SaasSubscription
    {
        return $this->subscription;
    }

    private function fetchSubscription(string $tenantId): ?SaasSubscription
    {
        try {
            $sub = $this->centralQueryBuilder->setTable('tenant_subscriptions')
                ->where('tenant_id', '=', $tenantId)
                ->first();

            if (!$sub) {
                return null;
            }

            $plan = $this->centralQueryBuilder->setTable('saas_plans')
                ->where('id', '=', $sub['plan_id'])
                ->first();

            if (!$plan) {
                return null;
            }

            $planDto = new SaasPlan(
                id: $plan['id'],
                name: $plan['name'],
                slug: $plan['slug'],
                features: json_decode($plan['features'], true) ?? [],
                limits: json_decode($plan['limits'], true) ?? [],
                isActive: (bool) $plan['is_active'],
            );

            return new SaasSubscription(
                id: $sub['id'],
                tenantId: $sub['tenant_id'],
                plan: $planDto,
                status: SubscriptionStatus::from($sub['status']),
                trialEndsAt: $sub['trial_ends_at'] ? new \DateTimeImmutable($sub['trial_ends_at']) : null,
                currentPeriodEndsAt: $sub['current_period_ends_at'] ? new \DateTimeImmutable($sub['current_period_ends_at']) : null,
            );
        } catch (\Throwable) {
            return null;
        }
    }

    public function getAllPlans(): array
    {
        $rows = $this->centralQueryBuilder->setTable('saas_plans')->get();

        $plans = [];
        foreach ($rows as $row) {
            $plans[] = new SaasPlan(
                id: $row['id'],
                name: $row['name'],
                slug: $row['slug'],
                features: json_decode($row['features'], true) ?? [],
                limits: json_decode($row['limits'], true) ?? [],
                isActive: (bool) $row['is_active'],
            );
        }

        return $plans;
    }

    public function createPlan(string $name, string $slug, array $features, array $limits): SaasPlan
    {
        $id = 'plan-' . $slug;

        $data = [
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'features' => json_encode($features),
            'limits' => json_encode($limits),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->centralQueryBuilder->setTable('saas_plans')->insert($data);

        return new SaasPlan(
            id: $id,
            name: $name,
            slug: $slug,
            features: $features,
            limits: $limits,
            isActive: true
        );
    }

    public function deletePlan(string $id): bool
    {
        $count = $this->centralQueryBuilder->setTable('tenant_subscriptions')
            ->where('plan_id', '=', $id)
            ->count();

        if ($count > 0) {
            throw new \RuntimeException("Cannot delete plan {$id} because it has active subscriptions.");
        }

        return $this->centralQueryBuilder->setTable('saas_plans')->where('id', '=', $id)->delete();
    }

    public function disablePlan(string $id): bool
    {
        return $this->centralQueryBuilder->setTable('saas_plans')
            ->where('id', '=', $id)
            ->update([
                'is_active' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function assignPlanToTenant(string $tenantId, string $planId, SubscriptionStatus $status = SubscriptionStatus::ACTIVE): SaasSubscription
    {
        $planRow = $this->centralQueryBuilder->setTable('saas_plans')->where('id', '=', $planId)->first();
        if (!$planRow) {
            throw new \RuntimeException("Plan {$planId} not found.");
        }

        $existing = $this->centralQueryBuilder->setTable('tenant_subscriptions')->where('tenant_id', '=', $tenantId)->first();

        if ($existing) {
            $this->centralQueryBuilder->setTable('tenant_subscriptions')
                ->where('id', '=', $existing['id'])
                ->update([
                    'plan_id' => $planId,
                    'status' => $status->value,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            $subId = $existing['id'];
        } else {
            $subId = UUID::generate();
            $this->centralQueryBuilder->setTable('tenant_subscriptions')->insert([
                'id' => $subId,
                'tenant_id' => $tenantId,
                'plan_id' => $planId,
                'status' => $status->value,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $planDto = new SaasPlan(
            id: $planRow['id'],
            name: $planRow['name'],
            slug: $planRow['slug'],
            features: json_decode($planRow['features'], true) ?? [],
            limits: json_decode($planRow['limits'], true) ?? [],
            isActive: (bool) $planRow['is_active'],
        );

        return new SaasSubscription(
            id: $subId,
            tenantId: $tenantId,
            plan: $planDto,
            status: $status,
            trialEndsAt: null,
            currentPeriodEndsAt: null,
        );
    }
}
