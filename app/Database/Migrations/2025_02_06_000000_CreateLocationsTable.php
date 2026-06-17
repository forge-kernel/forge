<?php

declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Attributes\Column;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Relations\HasMany;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Table;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Timestamps;
use App\Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: 'locations')]
#[Table(name: 'locations')]
#[Timestamps]
#[HasMany(related: LocationPhoto::class)]
#[Index(columns: ['coordinates'], name: 'idx_locations_coords')]
class CreateLocationsTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::INTEGER, primaryKey: true, autoIncrement: true, unsigned: true)]
    public readonly int $id;

    #[Column(name: 'name', type: ColumnType::STRING, length: 255, comment: 'Location name')]
    public readonly string $name;

    #[Column(name: 'latitude', type: ColumnType::DECIMAL, precision: 10, scale: 8, comment: 'GPS latitude coordinate')]
    public readonly float $latitude;

    #[Column(name: 'longitude', type: ColumnType::DECIMAL, precision: 11, scale: 8, comment: 'GPS longitude coordinate')]
    public readonly float $longitude;

    #[Column(name: 'altitude', type: ColumnType::DECIMAL, precision: 8, scale: 2, nullable: true, default: 0.00)]
    public readonly ?float $altitude;

    #[Column(name: 'accuracy', type: ColumnType::DECIMAL, precision: 5, scale: 2, nullable: true)]
    public readonly ?float $accuracy;

    #[Column(name: 'priority', type: ColumnType::INTEGER, unsigned: true, default: 0, check: 'priority >= 0 AND priority <= 100')]
    public readonly int $priority;
}
