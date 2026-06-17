<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Commands;

use App\Modules\ForgeMultiTenant\Services\TenantManager;
use App\Modules\ForgeMultiTenant\Services\TenantConnectionFactory;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Database\Migrator;

#[Cli(
    command: 'tenant:migrate',
    description: 'Run migrations for one or all tenants (app/Database/Migrations/Tenants)',
    usage: 'tenant:migrate [--tenant=TENANT_ID] [--preview]',
    examples: [
        'tenant:migrate --tenant=123',
        'tenant:migrate --preview',
        'tenant:migrate  (runs for all tenants)'
    ]
)]
final class TenantMigrateCommand extends Command
{
    use OutputHelper;
    use Wizard;

    #[Arg(name: 'tenant', description: 'Tenant ID to migrate (default: all)', default: 'all', required: false)]
    private string $tenantId;

    #[Arg(name: 'preview', description: 'Preview migrations without executing', default: false, required: false)]
    private bool $preview;

    public function __construct(
        private readonly TenantManager           $tenants,
        private readonly TenantConnectionFactory $factory,
        private readonly Migrator                $migrator
    )
    {
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function execute(array $args): int
    {
        $this->wizard($args);

        foreach ($this->resolveTenants($this->tenantId) as $tenant) {
            $this->info("Migrating tenant: {$tenant->id}");
            $this->migrator->setConnection($this->factory->forTenant($tenant));
            $this->migrator->createMigrationTable();

            if ($this->preview) {
                $this->dryRun($tenant);
            } else {
                $this->migrator->run('all', null, 'tenants');
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
        $pending = $this->migrator->previewRun('app', null, 'tenants');
        if (empty($pending)) {
            $this->comment("  ✔ No pending migrations");
            return;
        }
        foreach ($pending as $file) {
            $this->line("  - " . basename($file));
        }
    }
}