<?php
declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Attributes\Column;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Table;
use App\Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[Table(name: "profiles")]
#[Index(columns: ["user_id"], name: "idx_profiles_user_id")]
class CreateProfilesTable extends Migration
{
    #[Column(name: "id", type: ColumnType::INTEGER, primaryKey: true)]
    public readonly int $id;

    #[Column(name: "user_id", type: ColumnType::INTEGER)]
    public readonly int $user_id;

    #[Column(name: "first_name", type: ColumnType::STRING, length: 255)]
    public readonly string $first_name;

    #[Column(name: "last_name", type: ColumnType::STRING, length: 255, nullable: true)]
    public readonly ?string $last_name;

    #[Column(name: "avatar", type: ColumnType::STRING, length: 255, nullable: true)]
    public readonly ?string $avatar;

    #[Column(name: "email", type: ColumnType::STRING, length: 255, nullable: true)]
    public readonly ?string $email;

    #[Column(name: "phone", type: ColumnType::STRING, length: 50, nullable: true)]
    public readonly ?string $phone;
}
