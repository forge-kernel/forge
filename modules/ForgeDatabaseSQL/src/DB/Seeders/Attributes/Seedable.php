<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Seeders\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Seedable
{
    public function __construct()
    {
    }
}
