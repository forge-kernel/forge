<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\DB\Contracts;

use PDO;

interface DatabaseDriverInterface
{
    public function connect(): PDO;
}
