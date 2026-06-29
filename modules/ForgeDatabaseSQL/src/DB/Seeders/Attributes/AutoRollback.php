<?php
declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\DB\Seeders\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AutoRollback
{
    public function __construct(
        public string $table,
        public array  $where
    )
    {
    }
}