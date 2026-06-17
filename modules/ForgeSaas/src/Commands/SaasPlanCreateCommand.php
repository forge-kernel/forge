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
    command: 'modules:saas:plan:create',
    description: 'Create a new SaaS plan',
    usage: 'modules:saas:plan:create [--name=PlanName] [--slug=plan-slug]',
    examples: [
        'modules:saas:plan:create',
        'modules:saas:plan:create --name=Premium --slug=premium'
    ]
)]
final class SaasPlanCreateCommand extends Command
{
    use OutputHelper;
    use Wizard;

    #[Arg(name: 'name', description: 'The display name of the plan (e.g. Premium)', required: true)]
    private ?string $name = null;

    #[Arg(name: 'slug', description: 'The slug of the plan (e.g. premium)', required: true)]
    private ?string $slug = null;

    #[Arg(name: 'features', description: 'Comma separated features (e.g. api_access,custom_domain)', required: false)]
    private ?string $featuresStr = null;

    #[Arg(name: 'limits', description: 'JSON format limits (e.g. {"max_users": 10})', required: false)]
    private ?string $limitsStr = null;

    public function __construct(private readonly SubscriptionManagerInterface $manager)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        $features = [];
        if (!empty(trim((string) $this->featuresStr))) {
            $features = array_map('trim', explode(',', (string) $this->featuresStr));
        }

        $limits = [];
        if (!empty(trim((string) $this->limitsStr))) {
            $parsed = json_decode((string) $this->limitsStr, true);
            if (is_array($parsed)) {
                $limits = $parsed;
            } else {
                $this->warning("Invalid JSON for limits. Proceeding with empty limits.");
            }
        }

        try {
            $plan = $this->manager->createPlan($this->name, $this->slug, $features, $limits);
            $this->success("Plan '{$plan->name}' created successfully with ID '{$plan->id}'");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Failed to create plan: " . $e->getMessage());
            return 1;
        }
    }
}
