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
    command: 'modules:billing:plan:disable',
    description: 'Disable a billing plan',
    usage: 'modules:billing:plan:disable --id=plan-xxx',
    examples: ['modules:billing:plan:disable --id=plan-premium-monthly']
)]
final class BillingPlanDisableCommand extends Command
{
    use OutputHelper;
    use Wizard;

    #[Arg(name: 'id', description: 'Plan ID to disable', required: true)]
    private ?string $id = null;

    public function __construct(private readonly BillingPlanService $planService)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        try {
            $this->planService->disable($this->id);
            $this->success("Plan '{$this->id}' disabled successfully.");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Failed to disable plan: " . $e->getMessage());
            return 1;
        }
    }
}
