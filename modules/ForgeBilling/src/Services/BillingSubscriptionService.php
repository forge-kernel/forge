<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Services;

use App\Modules\ForgeBilling\Dto\BillingPlan;
use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeBilling\Dto\BillingSubscription;
use App\Modules\ForgeBilling\Enums\SubscriptionStatus;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\Helpers\UUID;

final class BillingSubscriptionService
{
    private ?BillingSubscription $current = null;
    private bool $loaded = false;

    public function __construct(
        private readonly QueryBuilderInterface $centralQueryBuilder,
        private readonly BillingPlanService $planService,
    ) {
    }

    public function forTenant(string $tenantId): static
    {
        if ($this->loaded) {
            return $this;
        }

        $this->loaded = true;
        $row = $this->centralQueryBuilder->setTable('billing_subscriptions')
            ->where('tenant_id', '=', $tenantId)
            ->first();

        if (!$row) {
            return $this;
        }

        $plan = $this->planService->getById($row['plan_id']);
        if (!$plan) {
            return $this;
        }

        $this->current = new BillingSubscription(
            id: $row['id'],
            tenantId: $row['tenant_id'],
            plan: $plan,
            status: SubscriptionStatus::from($row['status']),
            trialEndsAt: $row['trial_ends_at'] ? new \DateTimeImmutable($row['trial_ends_at']) : null,
            currentPeriodEndsAt: $row['current_period_ends_at'] ? new \DateTimeImmutable($row['current_period_ends_at']) : null,
            cancelledAt: $row['cancelled_at'] ? new \DateTimeImmutable($row['cancelled_at']) : null,
        );

        return $this;
    }

    public function current(): ?BillingSubscription
    {
        return $this->current;
    }

    public function isActive(): bool
    {
        if ($this->current === null) {
            return false;
        }
        return in_array($this->current->status, [
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::TRIAL,
        ], true);
    }

    public function onTrial(): bool
    {
        return $this->current?->status === SubscriptionStatus::TRIAL;
    }

    public function assign(string $tenantId, string $planId, SubscriptionStatus $status = SubscriptionStatus::ACTIVE, ?\DateTimeImmutable $currentPeriodEndsAt = null): BillingSubscription
    {
        $plan = $this->planService->getById($planId);
        if (!$plan) {
            throw new \RuntimeException("Billing plan {$planId} not found.");
        }

        $periodEnd = $currentPeriodEndsAt?->format('Y-m-d H:i:s');

        $existing = $this->centralQueryBuilder->setTable('billing_subscriptions')
            ->where('tenant_id', '=', $tenantId)
            ->first();

        if ($existing) {
            $updateData = [
                'plan_id' => $planId,
                'status' => $status->value,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($periodEnd !== null) {
                $updateData['current_period_ends_at'] = $periodEnd;
            }
            $this->centralQueryBuilder->setTable('billing_subscriptions')
                ->where('id', '=', $existing['id'])
                ->update($updateData);
            $subId = $existing['id'];
        } else {
            $insertData = [
                'id' => $subId = UUID::generate(),
                'tenant_id' => $tenantId,
                'plan_id' => $planId,
                'status' => $status->value,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($periodEnd !== null) {
                $insertData['current_period_ends_at'] = $periodEnd;
            }
            $this->centralQueryBuilder->setTable('billing_subscriptions')->insert($insertData);
        }

        return new BillingSubscription(
            id: $subId,
            tenantId: $tenantId,
            plan: $plan,
            status: $status,
            currentPeriodEndsAt: $currentPeriodEndsAt,
        );
    }

    public function cancel(string $tenantId): bool
    {
        return (bool) $this->centralQueryBuilder->setTable('billing_subscriptions')
            ->where('tenant_id', '=', $tenantId)
            ->update([
                'status' => SubscriptionStatus::CANCELED->value,
                'cancelled_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
