<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Commands;

use App\Modules\ForgeBilling\Services\BillingPlanService;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\CLI\Traits\OutputHelper;

#[Cli(
    command: 'modules:billing:plan:create',
    description: 'Create a new billing plan',
    usage: 'modules:billing:plan:create [--name=PlanName] [--slug=plan-slug] [--amount=9.99]',
    examples: [
        'modules:billing:plan:create',
        'modules:billing:plan:create --name=Premium --slug=premium --amount=19.99',
    ]
)]
final class BillingPlanCreateCommand extends Command
{
    use OutputHelper;
    use Wizard;

    #[Arg(name: 'name', description: 'Display name of the plan', required: true)]
    private ?string $name = null;

    #[Arg(name: 'slug', description: 'Unique slug (e.g. premium-monthly)', required: true)]
    private ?string $slug = null;

    #[Arg(name: 'amount', description: 'Price amount (e.g. 9.99)', required: true)]
    private ?string $amount = null;

    #[Arg(name: 'currency', description: 'Currency code (USD, EUR)', required: false)]
    private ?string $currency = 'USD';

    #[Arg(name: 'interval', description: 'Billing interval (monthly, yearly, weekly, one_time)', required: false)]
    private ?string $interval = 'monthly';

    #[Arg(name: 'features', description: 'Comma-separated feature slugs', required: false)]
    private ?string $featuresStr = null;

    public function __construct(private readonly BillingPlanService $planService)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        $features = [];
        if (!empty(trim((string) $this->featuresStr))) {
            $features = array_map('trim', explode(',', (string) $this->featuresStr));
        }

        try {
            $plan = $this->planService->create(
                name: $this->name,
                slug: $this->slug,
                amount: (float) $this->amount,
                currency: $this->currency ?? 'USD',
                interval: $this->interval ?? 'monthly',
                features: $features,
            );
            $this->success("Plan '{$plan->name}' created successfully with ID '{$plan->id}'");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Failed to create plan: " . $e->getMessage());
            return 1;
        }
    }
}
