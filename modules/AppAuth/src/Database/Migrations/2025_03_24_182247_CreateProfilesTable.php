<?php

declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Attributes\Column;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\MetaData;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\SoftDelete;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Status;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Table;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Timestamps;
use App\Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use App\Modules\ForgeDatabaseSQL\DB\Enums\ConfirmedStatus;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: "user")]
#[Table(name: "profiles")]
#[Index(columns: ["user_id"], name: "idx_profiles_user_id")]
#[Status(column: "email_confirmed", enum: ConfirmedStatus::class)]
#[Status(column: "phone_confirmed", enum: ConfirmedStatus::class)]
#[MetaData]
#[Timestamps]
#[SoftDelete]
class CreateProfilesTable extends Migration
{
    #[Column(name: "id", type: ColumnType::INTEGER, primaryKey: true)]
    public readonly int $id;

    #[Column(name: "user_id", type: ColumnType::INTEGER)]
    public readonly int $userId;

    #[Column(name: "first_name", type: ColumnType::STRING, length: 255)]
    public readonly string $firstName;

    #[
        Column(
            name: "last_name",
            type: ColumnType::STRING,
            length: 255,
            nullable: true,
        ),
    ]
    public readonly string $lastName;

    #[
        Column(
            name: "avatar",
            type: ColumnType::STRING,
            length: 255,
            nullable: true,
        ),
    ]
    public readonly string $avatar;

    #[
        Column(
            name: "email",
            type: ColumnType::STRING,
            length: 255,
            unique: true,
            nullable: true,
        ),
    ]
    public readonly string $email;

    #[
        Column(
            name: "phone",
            type: ColumnType::STRING,
            length: 255,
            unique: true,
            nullable: true,
        ),
    ]
    public readonly string $phone;

    #[
        Column(
            name: "pending_email",
            type: ColumnType::STRING,
            length: 255,
            nullable: true,
        ),
    ]
    public readonly string $pendingEmail;

    #[
        Column(
            name: "pending_phone",
            type: ColumnType::STRING,
            length: 255,
            nullable: true,
        ),
    ]
    public readonly string $pendingPhone;
}
