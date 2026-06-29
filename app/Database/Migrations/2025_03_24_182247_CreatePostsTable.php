<?php

declare(strict_types=1);

use Modules\AppAuth\Models\User;
use Modules\ForgeDatabaseSQL\DB\Attributes\Column;
use Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use Modules\ForgeDatabaseSQL\DB\Attributes\Relations\BelongsTo;
use Modules\ForgeDatabaseSQL\DB\Attributes\Table;
use Modules\ForgeDatabaseSQL\DB\Migrations\Migration;
use Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;

#[GroupMigration('tenants')]
#[Table(name: 'posts')]
#[BelongsTo(related: User::class)]
#[Index(columns: ['title'], name: 'idx_posts_title')]
class CreatePostsTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::INTEGER, primaryKey: true, autoIncrement: true)]
    public readonly int $id;

    #[Column(name: 'title', type: ColumnType::STRING)]
    public readonly string $title;

    #[Column(name: 'content', type: ColumnType::TEXT)]
    public readonly string $content;

    #[Column(name: 'metadata', type: ColumnType::JSON)]
    public readonly array $metadata;
}
