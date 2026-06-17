<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Commands;

use App\Modules\ForgeBilling\Events\GenerateInvoiceEvent;
use App\Modules\ForgeBilling\Services\BillingPlanService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Contracts\Database\CentralQueryBuilderInterface;

#[Cli(
    command: 'modules:billing:generate-invoices',
    description: 'Generate recurring invoices for subscriptions with expired billing periods',
    usage: 'modules:billing:generate-invoices [--dry-run] [--process]',
    examples: [
        'modules:billing:generate-invoices',
        'modules:billing:generate-invoices --dry-run',
        'modules:billing:generate-invoices --process',
    ]
)]
final class BillingGenerateInvoicesCommand extends Command
{
    use OutputHelper;

    public function __construct(
        private readonly CentralQueryBuilderInterface $centralQueryBuilder,
        private readonly BillingPlanService $planService,
        private readonly \App\Modules\ForgeEvents\Services\EventDispatcher $eventDispatcher,
    ) {
    }

    public function execute(array $args): int
    {
        $dryRun = in_array('--dry-run', $args, true);
        $process = in_array('--process', $args, true);

        $subscriptions = array_filter(
            $this->centralQueryBuilder->setTable('billing_subscriptions')
                ->where('status', '=', 'active')
                ->get(),
            fn(array $sub) => $sub['current_period_ends_at'] === null
                || $sub['current_period_ends_at'] <= date('Y-m-d H:i:s'),
        );

        if (empty($subscriptions)) {
            $this->info('No subscriptions need invoicing.');
            return 0;
        }

        $this->info(sprintf('Found %d subscription(s) needing invoicing.', count($subscriptions)));

        $dispatched = 0;
        foreach ($subscriptions as $sub) {
            $plan = $this->planService->getById($sub['plan_id']);
            if (!$plan) {
                $this->warning(sprintf('Plan %s not found for subscription %s, skipping.', $sub['plan_id'], $sub['id']));
                continue;
            }

            if ($dryRun) {
                $this->line(sprintf('[DRY-RUN] Would invoice: tenant=%s sub=%s plan=%s amount=%s %s',
                    $sub['tenant_id'], $sub['id'], $plan->name, $plan->amount, $plan->currency));
                $dispatched++;
                continue;
            }

            $this->eventDispatcher->dispatch(new GenerateInvoiceEvent(
                tenantId: $sub['tenant_id'],
                subscriptionId: $sub['id'],
                planId: $sub['plan_id'],
                planAmount: (float) $plan->amount,
                planCurrency: $plan->currency,
                planInterval: $plan->interval,
            ));
            $dispatched++;

            $this->line(sprintf('Dispatched invoice event for tenant=%s sub=%s', $sub['tenant_id'], $sub['id']));
        }

        if ($process && !$dryRun) {
            $this->info('Processing queued invoice events...');
            $processed = 0;
            while ($this->eventDispatcher->processNextEvent('billing')) {
                $processed++;
            }
            $this->success(sprintf('%d invoice(s) generated.', $processed));
        }

        $mode = $dryRun ? 'would have been generated' : 'dispatched';
        $this->success(sprintf('Done. %d invoice(s) %s.', $dispatched, $mode));
        return 0;
    }
}
