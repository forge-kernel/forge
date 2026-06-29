<?php

declare(strict_types=1);

use Modules\ForgeDatabaseSQL\DB\Attributes\Column;
use Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use Modules\ForgeDatabaseSQL\DB\Attributes\MetaData;
use Modules\ForgeDatabaseSQL\DB\Attributes\SoftDelete;
use Modules\ForgeDatabaseSQL\DB\Attributes\Table;
use Modules\ForgeDatabaseSQL\DB\Attributes\Timestamps;
use Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: "user")]
#[Table(name: "users")]
#[Index(columns: ["id"], name: "idx_users_id")]
#[Index(columns: ["email"], name: "idx_users_email")]
#[Index(columns: ["deleted_at"], name: "idx_users_deleted_at")]
#[MetaData]
#[Timestamps]
#[SoftDelete]
class CreateUsersTable extends Migration
{
    #[Column(name: "id", type: ColumnType::INTEGER, primaryKey: true)]
    public readonly int $id;

    #[
        Column(
            name: "status",
            type: ColumnType::ENUM,
            enum: ["active", "inactive", "pending"],
        ),
    ]
    public readonly string $status;

    #[
        Column(
            name: "identifier",
            type: ColumnType::STRING,
            unique: true,
            length: 255,
        ),
    ]
    public readonly string $identifier;

    #[
        Column(
            name: "email",
            type: ColumnType::STRING,
            unique: true,
            length: 255,
        ),
    ]
    public readonly string $email;

    #[Column(name: "password", type: ColumnType::STRING, length: 255)]
    public readonly string $password;
}
