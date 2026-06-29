<?php


declare(strict_types=1);

namespace App\Database\Seeders\Tenants;

use Modules\ForgeDatabaseSQL\DB\Seeders\Attributes\AutoRollback;
use Modules\ForgeDatabaseSQL\DB\Seeders\Attributes\SeederInfo;
use Modules\ForgeDatabaseSQL\DB\Seeders\Attributes\Seedable;
use Modules\ForgeDatabaseSQL\DB\Seeders\Seeder;


#[Seedable]
#[SeederInfo(description: 'Seed default admin user', author: 'Jeremias')]
#[AutoRollback('users', ['email' => 'admin@example.com'])]
class CreateAdminUserSeeder extends Seeder
{
    public function up(): void
    {
        $this->insertBatch(
            'users',
            [
                [
                    'status' => 'active',
                    'identifier' => 'admin',
                    'email' => 'admin@example.com',
                    'password' => password_hash('secret', PASSWORD_BCRYPT),
                ]
            ]
        );
    }
}