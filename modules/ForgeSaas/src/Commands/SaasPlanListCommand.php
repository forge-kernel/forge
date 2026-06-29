<?php

declare(strict_types=1);

namespace Modules\ForgeSaas\Commands;

use Modules\ForgeSaas\Contracts\SubscriptionManagerInterface;
use Modules\ForgeSaas\Dto\SaasPlan;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;

#[Cli(
    command: 'modules:saas:plan:list',
    description: 'List all SaaS plans',
    usage: 'modules:saas:plan:list',
    examples: [
        'modules:saas:plan:list'
    ]
)]
final class SaasPlanListCommand extends Command
{
    use OutputHelper;

    public function __construct(private readonly SubscriptionManagerInterface $manager)
    {
    }

    public function execute(array $args): int
    {
        $plans = $this->manager->getAllPlans();

        if (empty($plans)) {
            $this->warning('No plans found.');
            return 0;
        }

        $rows = array_map(fn(SaasPlan $p) => [
            'ID' => $p->id,
            'Name' => $p->name,
            'Slug' => $p->slug,
            'Status' => $p->isActive ? "\033[1;32mActive\033[0m" : "\033[0;31mInactive\033[0m",
            'Features' => count($p->features),
            'Limits' => count($p->limits),
        ], $plans);

        $this->table(['ID', 'Name', 'Slug', 'Status', 'Features', 'Limits'], $rows);
        return 0;
    }
}
