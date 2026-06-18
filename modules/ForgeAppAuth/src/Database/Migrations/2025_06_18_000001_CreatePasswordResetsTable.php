<?php
declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Attributes\Column;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Table;
use App\Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[Table(name: "password_resets")]
#[Index(columns: ["email"], name: "idx_password_resets_email")]
#[Index(columns: ["token"], name: "idx_password_resets_token")]
class CreatePasswordResetsTable extends Migration
{
    #[Column(name: "id", type: ColumnType::INTEGER, primaryKey: true)]
    public readonly int $id;

    #[Column(name: "email", type: ColumnType::STRING, length: 255)]
    public readonly string $email;

    #[Column(name: "token", type: ColumnType::STRING, length: 255)]
    public readonly string $token;

    #[
        Column(
            name: "created_at",
            type: ColumnType::DATETIME,
        )
    ]
    public readonly string $created_at;
}
