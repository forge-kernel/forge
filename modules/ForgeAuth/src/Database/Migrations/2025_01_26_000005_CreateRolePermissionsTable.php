<?php

declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: "security")]
class CreateRolePermissionsTable extends Migration
{
    public function up(): void
    {
        $sql = $this->createTable("role_permissions", [
            "id" => "INTEGER PRIMARY KEY AUTOINCREMENT",
            "role_id" => "INTEGER NOT NULL",
            "permission_id" => "INTEGER NOT NULL",
            "created_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP",
            "updated_at" => "DATETIME DEFAULT CURRENT_TIMESTAMP",
        ]);
        $this->execute($sql);

        $fk1Sql = $this->addForeignKey(
            "role_permissions",
            "role_id",
            "roles",
            "id",
            "CASCADE",
        );
        if ($fk1Sql !== null) {
            $this->execute($fk1Sql);
        }

        $fk2Sql = $this->addForeignKey(
            "role_permissions",
            "permission_id",
            "permissions",
            "id",
            "CASCADE",
        );
        if ($fk2Sql !== null) {
            $this->execute($fk2Sql);
        }

        $this->execute(
            "CREATE UNIQUE INDEX idx_role_permission_unique ON role_permissions (role_id, permission_id)",
        );
    }

    public function down(): void
    {
        $this->execute($this->dropTable("role_permissions"));
    }
}
