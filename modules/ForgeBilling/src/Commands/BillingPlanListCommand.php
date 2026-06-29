<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Commands;

use Modules\ForgeBilling\Services\BillingPlanService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;

#[Cli(
    command: 'modules:billing:plan:list',
    description: 'List all billing plans',
    usage: 'modules:billing:plan:list',
    examples: ['modules:billing:plan:list']
)]
final class BillingPlanListCommand extends Command
{
    use OutputHelper;

    public function __construct(private readonly BillingPlanService $planService)
    {
    }

    public function execute(array $args): int
    {
        $plans = $this->planService->getAll();

        if (empty($plans)) {
            $this->info("No billing plans found.");
            return 0;
        }

        $this->table(
            ['ID', 'Name', 'Slug', 'Amount', 'Interval', 'Active'],
            array_map(fn($p) => [
                $p->id,
                $p->name,
                $p->slug,
                $p->amount . ' ' . $p->currency,
                $p->interval,
                $p->isActive ? 'Yes' : 'No',
            ], $plans)
        );

        return 0;
    }
}
