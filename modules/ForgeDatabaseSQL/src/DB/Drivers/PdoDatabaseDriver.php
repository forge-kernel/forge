<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Drivers;

use App\Modules\ForgeDatabaseSQL\DB\Contracts\DatabaseDriverInterface;
use PDO;
use PDOException;
use InvalidArgumentException;
use RuntimeException;

final class PdoDatabaseDriver implements DatabaseDriverInterface
{
    protected array $config;
    protected ?PDO $pdo = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connect(): PDO
    {
        if (!$this->pdo) {
            try {
                $dsn = $this->getDsn();
                $this->pdo = new PDO(
                    $dsn,
                    $this->config['username'] ?? null,
                    $this->config['password'] ?? null,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } catch (PDOException $e) {
                throw new RuntimeException("Database connection failed: " . $e->getMessage());
            }
        }
        return $this->pdo;
    }

    protected function getDsn(): string
    {
        return match ($this->config['driver']) {
            'sqlite' => "sqlite:" . $this->config['Database'],
            'mysql' => "mysql:host={$this->config['host']};dbname={$this->config['Database']};charset=utf8mb4",
            'pgsql' => "pgsql:host={$this->config['host']};dbname={$this->config['Database']}",
            default => throw new InvalidArgumentException("Unsupported Database driver: {$this->config['driver']}"),
        };
    }
}
