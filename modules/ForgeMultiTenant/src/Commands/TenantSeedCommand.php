<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Commands;

use App\Modules\ForgeMultiTenant\Services\TenantManager;
use App\Modules\ForgeMultiTenant\Services\TenantConnectionFactory;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Database\Seeders\SeederManager;

#[Cli(
    command: 'tenant:seed',
    description: 'Run seeders for one or all tenants (app/Database/Seeders/Tenants)',
    usage: 'tenant:seed [--tenant=TENANT_ID] [--preview]',
    examples: [
        'tenant:seed --tenant=123',
        'tenant:seed --preview',
        'tenant:seed  (runs for all tenants)'
    ]
)]
final class TenantSeedCommand extends Command
{
    use OutputHelper;

    #[Arg(name: 'tenant', description: 'Tenant ID to seed (default: all)', default: 'all', required: false)]
    private string $tenantId;

    #[Arg(name: 'preview', description: 'Preview seeders without executing', default: false, required: false)]
    private bool $preview;

    public function __construct(
        private readonly TenantManager           $tenants,
        private readonly TenantConnectionFactory $factory,
        private readonly SeederManager           $seeder
    )
    {
    }

    /**
     * @throws \Throwable
     */
    public function execute(array $args): int
    {
        $this->wizard($args);

        foreach ($this->resolveTenants($this->tenantId) as $tenant) {
            $this->info("Seeding tenant: {$tenant->id}");
            $conn = $this->factory->forTenant($tenant);
            $this->seeder->setConnection($conn);
            $this->seeder->createSeedsTable();

            if ($this->preview) {
                $this->dryRun($tenant);
            } else {
                $this->seeder->run('tenants');
            }
        }

        return 0;
    }

    private function resolveTenants(string $id): array
    {
        return $id === 'all' ? $this->tenants->all() : [$this->tenants->find($id)];
    }

    private function dryRun(object $tenant): void
    {
        $pending = $this->seeder->getPendingSeeders('tenants', null);
        if (empty($pending)) {
            $this->comment("  ✔ No pending seeders");
            return;
        }
        foreach ($pending as $file) {
            $this->line("  - " . basename($file));
        }
    }
}