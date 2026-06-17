<?php


declare(strict_types=1);
use App\Modules\ForgeDatabaseSQL\DB\Seeders\Attributes\AutoRollback;
use App\Modules\ForgeDatabaseSQL\DB\Seeders\Attributes\SeederInfo;
use App\Modules\ForgeDatabaseSQL\DB\Seeders\Seeder;


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