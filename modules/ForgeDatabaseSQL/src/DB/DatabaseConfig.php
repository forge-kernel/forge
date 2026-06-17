<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB;

use Forge\Core\Contracts\Database\DatabaseConfigInterface;
use InvalidArgumentException;
use PDO;

final class DatabaseConfig implements DatabaseConfigInterface
{
    private array $driverOptions = [
        "sqlite" => [
            "dsn" => "sqlite:%Database%",
            "options" => [],
        ],
        "mysql" => [
            "dsn" =>
                "mysql:host=%host%;port=%port%;dbname=%Database%;charset=%charset%",
            "options" => [
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '%charset%'",
            ],
        ],
        "pgsql" => [
            "dsn" => "pgsql:host=%host%;port=%port%;dbname=%Database%",
            "options" => [
                PDO::ATTR_PERSISTENT => true,
            ],
        ],
    ];

    public function __construct(
        private readonly string $driver,
        private readonly string $database,
        private readonly string $host = "localhost",
        private readonly string $username = "",
        private readonly string $password = "",
        private readonly int $port = 3306,
        private readonly string $charset = "utf8mb4"
    ) {
        $this->validateDriver();
    }

    private function validateDriver(): void
    {
        if (!array_key_exists($this->driver, $this->driverOptions)) {
            throw new InvalidArgumentException(
                "Unsupported Database driver: {$this->driver}"
            );
        }
    }

    public function getDsn(): string
    {
        return str_replace(
            ["%host%", "%port%", "%Database%", "%charset%"],
            [$this->host, $this->port, $this->database, $this->charset],
            $this->driverOptions[$this->driver]["dsn"]
        );
    }

    public function getOptions(): array
    {
        return $this->driverOptions[$this->driver]["options"];
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }
}
