<?php

declare(strict_types=1);

namespace App\Modules\ForgeSaas\Commands;

use App\Modules\ForgeSaas\Contracts\SubscriptionManagerInterface;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\CLI\Traits\OutputHelper;

#[Cli(
    command: 'modules:saas:plan:delete',
    description: 'Delete an existing SaaS plan',
    usage: 'modules:saas:plan:delete [--id=plan-id]',
    examples: [
        'modules:saas:plan:delete',
        'modules:saas:plan:delete --id=plan-premium'
    ]
)]
final class SaasPlanDeleteCommand extends Command
{
    use OutputHelper;
    use Wizard;

    #[Arg(name: 'id', description: 'The ID of the plan to delete (e.g. plan-premium)', required: true)]
    private ?string $id = null;

    public function __construct(private readonly SubscriptionManagerInterface $manager)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        if (!$this->id) {
            $this->error("Plan ID is required.");
            return 1;
        }

        try {
            $result = $this->manager->deletePlan($this->id);
            if ($result) {
                $this->success("Plan '{$this->id}' deleted successfully.");
            } else {
                $this->warning("Plan '{$this->id}' could not be deleted. It may not exist.");
            }
            return 0;
        } catch (\Throwable $e) {
            $this->error("Failed to delete plan: " . $e->getMessage());
            return 1;
        }
    }
}
