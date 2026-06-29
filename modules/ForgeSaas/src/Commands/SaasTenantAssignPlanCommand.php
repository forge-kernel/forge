<?php

declare(strict_types=1);

namespace Modules\ForgeSaas\Commands;

use Modules\ForgeSaas\Contracts\SubscriptionManagerInterface;
use Modules\ForgeSaas\Enums\SubscriptionStatus;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\CLI\Traits\OutputHelper;

#[Cli(
    command: 'modules:saas:tenant:assign',
    description: 'Assign a SaaS plan to a tenant',
    usage: 'modules:saas:tenant:assign [--tenant=tenant-id] [--plan=plan-id]',
    examples: [
        'modules:saas:tenant:assign',
        'modules:saas:tenant:assign --tenant=upper --plan=plan-pro'
    ]
)]
final class SaasTenantAssignPlanCommand extends Command
{
    use OutputHelper;
    use Wizard;

    #[Arg(name: 'tenant', description: 'The ID of the tenant (e.g. upper)', required: true)]
    private ?string $tenant = null;

    #[Arg(name: 'plan', description: 'The ID of the plan to assign (e.g. plan-pro)', required: true)]
    private ?string $plan = null;

    public function __construct(private readonly SubscriptionManagerInterface $manager)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        if (!$this->tenant || !$this->plan) {
            $this->error("Both Tenant ID and Plan ID are required.");
            return 1;
        }

        try {
            $subscription = $this->manager->assignPlanToTenant(
                $this->tenant,
                $this->plan,
                SubscriptionStatus::ACTIVE
            );
            $this->success("Successfully assigned plan '{$this->plan}' to tenant '{$this->tenant}'. Subscription ID: {$subscription->id}");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Failed to assign plan: " . $e->getMessage());
            return 1;
        }
    }
}
