<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Migrations;

class MigrationException extends \RuntimeException
{
    public function __construct(
        string         $message,
        private string $failedSql
    )
    {
        parent::__construct($message);
    }

    public function getFailedSql(): string
    {
        return $this->failedSql;
    }
}
