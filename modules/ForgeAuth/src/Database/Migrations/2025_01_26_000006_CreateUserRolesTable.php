<?php

declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: "security")]
class CreateUserRolesTable extends Migration
{
    public function up(): void
    {
        $sql = $this->createTable("user_roles", [
            "id" => "INTEGER PRIMARY KEY AUTOINCREMENT",
            "user_id" => "INTEGER NOT NULL",
            "role_id" => "INTEGER NOT NULL",
            "created_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP",
            "updated_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP",
        ]);
        $this->execute($sql);

        $fk1Sql = $this->addForeignKey(
            "user_roles",
            "user_id",
            "users",
            "id",
            "CASCADE",
        );
        if ($fk1Sql !== null) {
            $this->execute($fk1Sql);
        }

        $fk2Sql = $this->addForeignKey(
            "user_roles",
            "role_id",
            "roles",
            "id",
            "CASCADE",
        );
        if ($fk2Sql !== null) {
            $this->execute($fk2Sql);
        }

        $this->execute(
            "CREATE UNIQUE INDEX idx_user_role_unique ON user_roles (user_id, role_id)",
        );
    }

    public function down(): void
    {
        $this->execute($this->dropTable("user_roles"));
    }
}
