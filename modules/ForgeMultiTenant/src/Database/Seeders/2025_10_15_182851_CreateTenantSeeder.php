<?php

declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Database\Seeders;

use App\Modules\ForgeDatabaseSQL\DB\Seeders\Attributes\AutoRollback;
use App\Modules\ForgeDatabaseSQL\DB\Seeders\Attributes\SeederInfo;
use App\Modules\ForgeDatabaseSQL\DB\Seeders\Attributes\Seedable;
use App\Modules\ForgeDatabaseSQL\DB\Seeders\Seeder;


#[Seedable]
#[SeederInfo(description: 'Seed for TenantSeeder', author: 'Forge Team')]
#[AutoRollback('tenants', ['domain' => 'central'])]
class CreateTenantSeeder extends Seeder
{
    public function up(): void
    {
        $this->insertBatch('tenants', [
            [
                'id' => 'central',
                'domain' => env('CENTRAL_DOMAIN', 'forge-v3.test'),
                'subdomain' => null,
                'strategy' => 'column',
                'db_name' => null,
                'connection' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        ]);
        $this->insertBatch('tenants', [
            [
                'id' => 'upper',
                'domain' => env('CENTRAL_DOMAIN', 'forge-v3.test'),
                'subdomain' => 'upper',
                'strategy' => 'database',
                'db_name' => 'tenant_upper',
                'connection' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        ]);
    }
}