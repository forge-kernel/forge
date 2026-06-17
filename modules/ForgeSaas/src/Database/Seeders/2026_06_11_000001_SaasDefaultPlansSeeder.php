<?php

declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Seeders\Attributes\SeederInfo;
use App\Modules\ForgeDatabaseSQL\DB\Seeders\Seeder;

#[SeederInfo(description: 'Seeds the default SaaS plans', author: 'Forge Team')]
class SaasDefaultPlansSeeder extends Seeder
{
    public function up(): void
    {
        $this->insertBatch('saas_plans', [
            [
                'id' => 'plan-free',
                'name' => 'Free',
                'slug' => 'free',
                'features' => json_encode([]),
                'limits' => json_encode(['max_users' => 3, 'max_storage_gb' => 1]),
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'plan-pro',
                'name' => 'Pro',
                'slug' => 'pro',
                'features' => json_encode(['advanced_reports', 'api_access', 'custom_domain']),
                'limits' => json_encode(['max_users' => 25, 'max_storage_gb' => 20]),
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'plan-enterprise',
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'features' => json_encode(['advanced_reports', 'api_access', 'custom_domain', 'white_label', 'priority_support', 'sso']),
                'limits' => json_encode(['max_users' => -1, 'max_storage_gb' => -1]),
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
