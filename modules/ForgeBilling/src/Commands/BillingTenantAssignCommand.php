<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Commands;

use App\Modules\ForgeBilling\Services\BillingSubscriptionService;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\CLI\Traits\OutputHelper;

#[Cli(
    command: 'modules:billing:tenant:assign',
    description: 'Assign a billing plan to a tenant',
    usage: 'modules:billing:tenant:assign --tenant=xxx --plan=plan-xxx',
    examples: [
        'modules:billing:tenant:assign --tenant=tenant_upper --plan=plan-premium-monthly',
    ]
)]
final class BillingTenantAssignCommand extends Command
{
    use OutputHelper;
    use Wizard;

    #[Arg(name: 'tenant', description: 'Tenant ID to assign the plan to', required: true)]
    private ?string $tenantId = null;

    #[Arg(name: 'plan', description: 'Plan ID to assign', required: true)]
    private ?string $planId = null;

    public function __construct(private readonly BillingSubscriptionService $subscriptionService)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        try {
            $sub = $this->subscriptionService->assign(
                tenantId: $this->tenantId,
                planId: $this->planId,
            );
            $this->success("Plan '{$sub->plan->name}' assigned to tenant '{$this->tenantId}'.");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Failed to assign plan: " . $e->getMessage());
            return 1;
        }
    }
}
